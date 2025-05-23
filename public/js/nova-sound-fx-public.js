/**
 * Nova Sound FX Public JavaScript
 */
(function($) {
    'use strict';

    // Nova Sound FX Class
    class NovaSoundFX {
        constructor() {
            this.settings = novaSoundFX.settings || {};
            this.sounds = novaSoundFX.sounds || {};
            this.audioContext = null;
            this.audioBuffers = {};
            this.isInitialized = false;
            this.userPreferences = this.loadUserPreferences();
            this.currentPageSounds = [];
            this.eventListeners = new Map();
            
            // Check if sounds should be enabled
            if (!this.shouldEnableSounds()) {
                return;
            }
            
            this.init();
        }
        
        /**
         * Initialize the sound system
         */
        init() {
            // Wait for user interaction to initialize audio context (browser requirement)
            const initAudio = () => {
                if (!this.isInitialized) {
                    this.initAudioContext();
                    this.isInitialized = true;
                    document.removeEventListener('click', initAudio);
                    document.removeEventListener('touchstart', initAudio);
                }
            };
            
            document.addEventListener('click', initAudio);
            document.addEventListener('touchstart', initAudio);
            
            // Load sound mappings
            this.loadSoundMappings();
            
            // Set up page transition handlers
            this.setupPageTransitions();
            
            // Initialize user controls
            this.initializeUserControls();
            
            // Expose global API
            window.NovaSoundFX = {
                play: this.playSound.bind(this),
                setVolume: this.setMasterVolume.bind(this),
                mute: this.mute.bind(this),
                unmute: this.unmute.bind(this),
                isMuted: () => this.userPreferences.muted,
                getVolume: () => this.userPreferences.volume,
                savePreferences: this.saveUserPreferences.bind(this)
            };
        }
        
        /**
         * Check if sounds should be enabled
         */
        shouldEnableSounds() {
            // Check if sounds are enabled in settings
            if (!this.settings.enable_sounds) {
                return false;
            }
            
            // Check mobile settings
            if (novaSoundFX.isMobile && !this.settings.mobile_enabled) {
                return false;
            }
            
            // Check accessibility preferences
            if (this.settings.respect_prefers_reduced_motion && 
                window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                return false;
            }
            
            // Check user preferences
            if (this.userPreferences.disabled) {
                return false;
            }
            
            return true;
        }
        
        /**
         * Initialize Web Audio API context
         */
        initAudioContext() {
            try {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                this.audioContext = new AudioContext();
                
                // Create master gain node for volume control
                this.masterGain = this.audioContext.createGain();
                this.masterGain.connect(this.audioContext.destination);
                this.setMasterVolume(this.userPreferences.volume);
                
                // Preload frequently used sounds
                this.preloadSounds();
            } catch (e) {
                console.error('Nova Sound FX: Failed to initialize audio context', e);
            }
        }
        
        /**
         * Load user preferences from localStorage
         */
        loadUserPreferences() {
            const defaults = {
                volume: this.settings.default_volume || 50,
                muted: false,
                disabled: false
            };
            
            try {
                const saved = localStorage.getItem('nova_sound_fx_preferences');
                if (saved) {
                    return Object.assign(defaults, JSON.parse(saved));
                }
            } catch (e) {
                // Fallback to cookies if localStorage fails
                const cookieValue = this.getCookie('nova_sound_fx_preferences');
                if (cookieValue) {
                    try {
                        return Object.assign(defaults, JSON.parse(decodeURIComponent(cookieValue)));
                    } catch (e) {}
                }
            }
            
            return defaults;
        }
        
        /**
         * Save user preferences
         */
        saveUserPreferences() {
            try {
                localStorage.setItem('nova_sound_fx_preferences', JSON.stringify(this.userPreferences));
            } catch (e) {
                // Fallback to cookies
                this.setCookie('nova_sound_fx_preferences', JSON.stringify(this.userPreferences), 365);
            }
            
            // Trigger event for UI updates
            $(document).trigger('nova-sound-fx:preferences-saved', this.userPreferences);
            
            // Show notification if controls exist
            this.showNotification('Preferences saved!');
        }
        
        /**
         * Load sound mappings and set up event listeners
         */
        loadSoundMappings() {
            if (!window.NovaSoundFXData || !window.NovaSoundFXData.cssMappings) {
                return;
            }
            
            window.NovaSoundFXData.cssMappings.forEach(mapping => {
                this.addSoundMapping(mapping);
            });
        }
        
        /**
         * Add sound mapping for CSS selector
         */
        addSoundMapping(mapping) {
            const elements = document.querySelectorAll(mapping.css_selector);
            
            elements.forEach(element => {
                // Mark element as having sound
                element.classList.add('nova-sound-fx-active');
                element.setAttribute('data-nova-sound-event', mapping.event_type);
                
                // Create event handler
                const handler = (e) => {
                    this.handleSoundEvent(e, mapping);
                };
                
                // Map event types to actual events
                let eventName = mapping.event_type;
                if (eventName === 'hover') eventName = 'mouseenter';
                
                // Store handler reference for cleanup
                if (!this.eventListeners.has(element)) {
                    this.eventListeners.set(element, new Map());
                }
                this.eventListeners.get(element).set(eventName, handler);
                
                // Add event listener
                element.addEventListener(eventName, handler);
            });
        }
        
        /**
         * Handle sound event
         */
        handleSoundEvent(event, mapping) {
            if (this.userPreferences.muted) {
                return;
            }
            
            // Apply delay if specified
            if (mapping.delay > 0) {
                setTimeout(() => {
                    this.playMappingSound(mapping, event.target);
                }, mapping.delay);
            } else {
                this.playMappingSound(mapping, event.target);
            }
        }
        
        /**
         * Play sound for mapping
         */
        playMappingSound(mapping, element) {
            const soundUrl = mapping.sound_url;
            const volume = (mapping.volume / 100) * (this.userPreferences.volume / 100);
            
            this.playSound(soundUrl, {
                volume: volume,
                element: element
            });
        }
        
        /**
         * Play sound with Web Audio API
         */
        async playSound(url, options = {}) {
            if (!this.audioContext || this.userPreferences.muted) {
                return;
            }
            
            try {
                // Resume audio context if suspended
                if (this.audioContext.state === 'suspended') {
                    await this.audioContext.resume();
                }
                
                // Get or load audio buffer
                let buffer = this.audioBuffers[url];
                if (!buffer) {
                    buffer = await this.loadAudioBuffer(url);
                    this.audioBuffers[url] = buffer;
                }
                
                // Create source
                const source = this.audioContext.createBufferSource();
                source.buffer = buffer;
                
                // Create gain node for this sound
                const gainNode = this.audioContext.createGain();
                gainNode.gain.value = options.volume || 1;
                
                // Connect nodes
                source.connect(gainNode);
                gainNode.connect(this.masterGain);
                
                // Add visual feedback
                if (options.element) {
                    options.element.classList.add('nova-sound-playing');
                    setTimeout(() => {
                        options.element.classList.remove('nova-sound-playing');
                    }, 500);
                }
                
                // Play sound
                source.start(0);
                
                // Show visual indicator
                this.showSoundWave();
                
            } catch (error) {
                console.error('Nova Sound FX: Error playing sound', error);
                
                // Fallback to HTML5 Audio
                this.playFallbackAudio(url, options);
            }
        }
        
        /**
         * Load audio buffer
         */
        async loadAudioBuffer(url) {
            const response = await fetch(url);
            const arrayBuffer = await response.arrayBuffer();
            return await this.audioContext.decodeAudioData(arrayBuffer);
        }
        
        /**
         * Fallback audio playback
         */
        playFallbackAudio(url, options = {}) {
            const audio = new Audio(url);
            audio.volume = (options.volume || 1) * (this.userPreferences.volume / 100);
            audio.play().catch(e => {
                console.error('Nova Sound FX: Fallback audio failed', e);
            });
        }
        
        /**
         * Preload frequently used sounds
         */
        preloadSounds() {
            // Preload first 5 sounds
            const soundUrls = Object.values(this.sounds).slice(0, 5);
            
            soundUrls.forEach(url => {
                this.loadAudioBuffer(url).catch(e => {
                    console.error('Nova Sound FX: Failed to preload sound', url, e);
                });
            });
        }
        
        /**
         * Set up page transition handlers
         */
        setupPageTransitions() {
            if (!window.NovaSoundFXData || !window.NovaSoundFXData.transitions) {
                return;
            }
            
            // Play entry sound for current page
            this.playPageEntrySound();
            
            // Intercept link clicks for exit sounds
            document.addEventListener('click', (e) => {
                const link = e.target.closest('a');
                if (link && link.href && !link.getAttribute('data-nova-no-sound')) {
                    this.handlePageExit(e, link.href);
                }
            });
            
            // Handle browser back/forward
            window.addEventListener('beforeunload', () => {
                this.playPageExitSound(false);
            });
        }
        
        /**
         * Play page entry sound
         */
        playPageEntrySound() {
            const transition = this.findMatchingTransition(window.location.href, 'enter');
            if (transition) {
                const volume = (transition.volume / 100) * (this.userPreferences.volume / 100);
                this.playSound(transition.sound_url, { volume });
            }
        }
        
        /**
         * Play page exit sound
         */
        playPageExitSound(wait = true) {
            const transition = this.findMatchingTransition(window.location.href, 'exit');
            if (transition) {
                const volume = (transition.volume / 100) * (this.userPreferences.volume / 100);
                
                if (wait) {
                    // Show transition overlay
                    this.showTransitionOverlay();
                }
                
                this.playSound(transition.sound_url, { volume });
                
                return wait ? 300 : 0; // Return delay duration
            }
            return 0;
        }
        
        /**
         * Handle page exit
         */
        handlePageExit(event, targetUrl) {
            const delay = this.playPageExitSound();
            
            if (delay > 0) {
                event.preventDefault();
                
                setTimeout(() => {
                    window.location.href = targetUrl;
                }, delay);
            }
        }
        
        /**
         * Find matching transition for URL
         */
        findMatchingTransition(url, type) {
            const transitions = window.NovaSoundFXData.transitions
                .filter(t => t.transition_type === type || t.transition_type === 'both')
                .sort((a, b) => b.priority - a.priority);
            
            for (const transition of transitions) {
                if (this.matchesUrlPattern(url, transition.url_pattern)) {
                    return transition;
                }
            }
            
            return null;
        }
        
        /**
         * Check if URL matches pattern
         */
        matchesUrlPattern(url, pattern) {
            // Handle regex patterns
            if (pattern.startsWith('regex:')) {
                const regex = new RegExp(pattern.substring(6));
                return regex.test(url);
            }
            
            // Convert wildcard pattern to regex
            const regexPattern = pattern
                .replace(/[.+?^${}()|[\]\\]/g, '\\$&')
                .replace(/\*/g, '.*');
            
            const regex = new RegExp('^' + regexPattern + '$');
            return regex.test(url);
        }
        
        /**
         * Initialize user controls
         */
        initializeUserControls() {
            const controls = document.querySelectorAll('.nova-sound-fx-controls');
            
            controls.forEach(control => {
                this.setupControlWidget(control);
            });
            
            // Listen for dynamically added controls
            const observer = new MutationObserver((mutations) => {
                mutations.forEach(mutation => {
                    mutation.addedNodes.forEach(node => {
                        if (node.classList && node.classList.contains('nova-sound-fx-controls')) {
                            this.setupControlWidget(node);
                        }
                    });
                });
            });
            
            observer.observe(document.body, { childList: true, subtree: true });
        }
        
        /**
         * Set up control widget
         */
        setupControlWidget(widget) {
            const muteBtn = widget.querySelector('.nova-mute-btn');
            const volumeSlider = widget.querySelector('.nova-volume-slider');
            const saveBtn = widget.querySelector('.nova-save-btn');
            const expandBtn = widget.querySelector('.nova-expand-btn');
            
            // Set initial states
            this.updateMuteButton(muteBtn);
            if (volumeSlider) {
                volumeSlider.value = this.userPreferences.volume;
                const valueDisplay = widget.querySelector('.nova-volume-value');
                if (valueDisplay) {
                    valueDisplay.textContent = this.userPreferences.volume + '%';
                }
            }
            
            // Mute/unmute button
            if (muteBtn) {
                muteBtn.addEventListener('click', () => {
                    if (this.userPreferences.muted) {
                        this.unmute();
                    } else {
                        this.mute();
                    }
                    this.updateMuteButton(muteBtn);
                });
            }
            
            // Volume slider
            if (volumeSlider) {
                volumeSlider.addEventListener('input', (e) => {
                    const volume = parseInt(e.target.value);
                    this.setMasterVolume(volume);
                    
                    const valueDisplay = widget.querySelector('.nova-volume-value');
                    if (valueDisplay) {
                        valueDisplay.textContent = volume + '%';
                    }
                });
            }
            
            // Save button
            if (saveBtn) {
                saveBtn.addEventListener('click', () => {
                    this.saveUserPreferences();
                });
            }
            
            // Expand/collapse button
            if (expandBtn) {
                expandBtn.addEventListener('click', () => {
                    widget.classList.toggle('nova-collapsed');
                });
                
                // Start collapsed
                widget.classList.add('nova-collapsed');
            }
            
            // Add entrance animation
            widget.classList.add('nova-controls-enter');
        }
        
        /**
         * Update mute button state
         */
        updateMuteButton(button) {
            if (!button) return;
            
            const volumeOn = button.querySelector('.nova-icon-volume-on');
            const volumeOff = button.querySelector('.nova-icon-volume-off');
            
            if (this.userPreferences.muted) {
                volumeOn.style.display = 'none';
                volumeOff.style.display = 'block';
            } else {
                volumeOn.style.display = 'block';
                volumeOff.style.display = 'none';
            }
        }
        
        /**
         * Set master volume
         */
        setMasterVolume(volume) {
            this.userPreferences.volume = Math.max(0, Math.min(100, volume));
            
            if (this.masterGain) {
                this.masterGain.gain.value = this.userPreferences.volume / 100;
            }
        }
        
        /**
         * Mute sounds
         */
        mute() {
            this.userPreferences.muted = true;
            if (this.masterGain) {
                this.masterGain.gain.value = 0;
            }
        }
        
        /**
         * Unmute sounds
         */
        unmute() {
            this.userPreferences.muted = false;
            if (this.masterGain) {
                this.masterGain.gain.value = this.userPreferences.volume / 100;
            }
        }
        
        /**
         * Show sound wave indicator
         */
        showSoundWave() {
            let wave = document.querySelector('.nova-sound-wave');
            
            if (!wave) {
                wave = document.createElement('div');
                wave.className = 'nova-sound-wave';
                wave.innerHTML = '<span></span><span></span><span></span><span></span><span></span>';
                document.body.appendChild(wave);
            }
            
            wave.classList.add('active');
            
            setTimeout(() => {
                wave.classList.remove('active');
            }, 1000);
        }
        
        /**
         * Show transition overlay
         */
        showTransitionOverlay() {
            let overlay = document.querySelector('.nova-page-transition-overlay');
            
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.className = 'nova-page-transition-overlay';
                document.body.appendChild(overlay);
            }
            
            overlay.classList.add('active');
        }
        
        /**
         * Show notification
         */
        showNotification(message) {
            const notifications = document.querySelectorAll('.nova-notification');
            
            notifications.forEach(notification => {
                const textElement = notification.querySelector('.nova-notification-text');
                if (textElement) {
                    textElement.textContent = message;
                    notification.style.display = 'block';
                    notification.classList.add('nova-show');
                    
                    setTimeout(() => {
                        notification.style.display = 'none';
                        notification.classList.remove('nova-show');
                    }, 2000);
                }
            });
        }
        
        /**
         * Cookie helpers
         */
        setCookie(name, value, days) {
            const expires = new Date();
            expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
            document.cookie = name + '=' + value + ';expires=' + expires.toUTCString() + ';path=/';
        }
        
        getCookie(name) {
            const nameEQ = name + '=';
            const ca = document.cookie.split(';');
            
            for (let i = 0; i < ca.length; i++) {
                let c = ca[i];
                while (c.charAt(0) === ' ') c = c.substring(1, c.length);
                if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
            }
            
            return null;
        }
        
        /**
         * Cleanup method
         */
        destroy() {
            // Remove event listeners
            this.eventListeners.forEach((events, element) => {
                events.forEach((handler, eventName) => {
                    element.removeEventListener(eventName, handler);
                });
            });
            
            this.eventListeners.clear();
            
            // Close audio context
            if (this.audioContext) {
                this.audioContext.close();
            }
        }
    }
    
    // Initialize on DOM ready
    $(document).ready(function() {
        // Only initialize if settings are available
        if (typeof novaSoundFX !== 'undefined') {
            window.novaSoundFXInstance = new NovaSoundFX();
        }
    });
    
})(jQuery);
