/**
 * Nova Sound FX Public JavaScript - Versión Mejorada
 * Con soporte completo para todos los tipos de eventos
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
            this.activeElements = new Set();
            
            // Verificar si los sonidos deben estar habilitados
            if (!this.shouldEnableSounds()) {
                return;
            }
            
            this.init();
        }
        
        /**
         * Inicializar el sistema de sonido
         */
        init() {
            // Esperar interacción del usuario para inicializar el contexto de audio (requisito del navegador)
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
            
            // Cargar mapeos de sonido
            this.loadSoundMappings();
            
            // Configurar manejadores de transición de página
            this.setupPageTransitions();
            
            // Inicializar controles de usuario
            this.initializeUserControls();
            
            // Exponer API global
            window.NovaSoundFX = {
                play: this.playSound.bind(this),
                setVolume: this.setMasterVolume.bind(this),
                mute: this.mute.bind(this),
                unmute: this.unmute.bind(this),
                isMuted: () => this.userPreferences.muted,
                getVolume: () => this.userPreferences.volume,
                savePreferences: this.saveUserPreferences.bind(this),
                reload: this.reloadMappings.bind(this)
            };
        }
        
        /**
         * Verificar si los sonidos deben estar habilitados
         */
        shouldEnableSounds() {
            // Verificar si los sonidos están habilitados en la configuración
            if (!this.settings.enable_sounds) {
                return false;
            }
            
            // Verificar configuración móvil
            if (novaSoundFX.isMobile && !this.settings.mobile_enabled) {
                return false;
            }
            
            // Verificar preferencias de accesibilidad
            if (this.settings.respect_prefers_reduced_motion && 
                window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                return false;
            }
            
            // Verificar preferencias del usuario
            if (this.userPreferences.disabled) {
                return false;
            }
            
            return true;
        }
        
        /**
         * Inicializar contexto de Web Audio API
         */
        initAudioContext() {
            try {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                this.audioContext = new AudioContext();
                
                // Crear nodo de ganancia maestro para control de volumen
                this.masterGain = this.audioContext.createGain();
                this.masterGain.connect(this.audioContext.destination);
                this.setMasterVolume(this.userPreferences.volume);
                
                // Precargar sonidos frecuentemente usados
                this.preloadSounds();
            } catch (e) {
                console.error('Nova Sound FX: Error al inicializar el contexto de audio', e);
            }
        }
        
        /**
         * Cargar preferencias del usuario desde localStorage
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
                // Fallback a cookies si localStorage falla
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
         * Guardar preferencias del usuario
         */
        saveUserPreferences() {
            try {
                localStorage.setItem('nova_sound_fx_preferences', JSON.stringify(this.userPreferences));
            } catch (e) {
                // Fallback a cookies
                this.setCookie('nova_sound_fx_preferences', JSON.stringify(this.userPreferences), 365);
            }
            
            // Disparar evento para actualizaciones de UI
            $(document).trigger('nova-sound-fx:preferences-saved', this.userPreferences);
            
            // Mostrar notificación si existen controles
            this.showNotification('¡Preferencias guardadas!');
        }
        
        /**
         * Cargar mapeos de sonido y configurar event listeners
         */
        loadSoundMappings() {
            if (!window.NovaSoundFXData || !window.NovaSoundFXData.cssMappings) {
                return;
            }
            
            // Limpiar listeners existentes
            this.clearEventListeners();
            
            // Agregar nuevos mapeos
            window.NovaSoundFXData.cssMappings.forEach(mapping => {
                this.addSoundMapping(mapping);
            });
        }
        
        /**
         * Limpiar event listeners existentes
         */
        clearEventListeners() {
            this.eventListeners.forEach((events, element) => {
                events.forEach((handler, eventName) => {
                    element.removeEventListener(eventName, handler);
                });
            });
            this.eventListeners.clear();
        }
        
        /**
         * Recargar mapeos (útil después de cambios dinámicos)
         */
        reloadMappings() {
            this.loadSoundMappings();
        }
        
        /**
         * Agregar mapeo de sonido para selector CSS
         */
        addSoundMapping(mapping) {
            // Solo permitir selectores de clase e ID
            if (!/^[#.][\w-]+(\s*,\s*[#.][\w-]+)*$/.test(mapping.css_selector)) {
                console.warn('Nova Sound FX: Selector inválido:', mapping.css_selector);
                return;
            }
            
            const elements = document.querySelectorAll(mapping.css_selector);
            
            elements.forEach(element => {
                // Marcar elemento como que tiene sonido
                element.classList.add('nova-sound-fx-active');
                element.setAttribute('data-nova-sound-event', mapping.event_type);
                
                // Crear manejador de eventos
                const handler = (e) => {
                    this.handleSoundEvent(e, mapping);
                };
                
                // Mapear tipos de evento a eventos reales
                let eventNames = [];
                switch (mapping.event_type) {
                    case 'hover':
                        eventNames = ['mouseenter'];
                        break;
                    case 'active':
                        eventNames = ['mousedown', 'touchstart'];
                        // También necesitamos manejar mouseup/touchend para el estado active
                        const activeEndHandler = (e) => {
                            this.activeElements.delete(element);
                        };
                        element.addEventListener('mouseup', activeEndHandler);
                        element.addEventListener('touchend', activeEndHandler);
                        element.addEventListener('mouseleave', activeEndHandler);
                        break;
                    case 'click':
                        eventNames = ['click'];
                        break;
                    case 'focus':
                        eventNames = ['focus'];
                        break;
                    case 'blur':
                        eventNames = ['blur'];
                        break;
                    case 'mouseenter':
                        eventNames = ['mouseenter'];
                        break;
                    case 'mouseleave':
                        eventNames = ['mouseleave'];
                        break;
                    case 'mousedown':
                        eventNames = ['mousedown'];
                        break;
                    case 'mouseup':
                        eventNames = ['mouseup'];
                        break;
                    default:
                        eventNames = [mapping.event_type];
                }
                
                // Almacenar referencia de handler para limpieza
                if (!this.eventListeners.has(element)) {
                    this.eventListeners.set(element, new Map());
                }
                
                // Agregar event listeners
                eventNames.forEach(eventName => {
                    this.eventListeners.get(element).set(eventName, handler);
                    element.addEventListener(eventName, handler);
                });
                
                // Agregar indicador visual opcional
                if (mapping.event_type === 'hover' || mapping.event_type === 'active') {
                    element.style.cursor = 'pointer';
                }
            });
        }
        
        /**
         * Manejar evento de sonido
         */
        handleSoundEvent(event, mapping) {
            if (this.userPreferences.muted) {
                return;
            }
            
            // Para eventos active, verificar si ya está activo
            if (mapping.event_type === 'active') {
                const element = event.currentTarget;
                if (this.activeElements.has(element)) {
                    return; // Ya está activo, no reproducir de nuevo
                }
                this.activeElements.add(element);
            }
            
            // Aplicar retraso si está especificado
            if (mapping.delay > 0) {
                setTimeout(() => {
                    this.playMappingSound(mapping, event.currentTarget);
                }, mapping.delay);
            } else {
                this.playMappingSound(mapping, event.currentTarget);
            }
        }
        
        /**
         * Reproducir sonido para mapeo
         */
        playMappingSound(mapping, element) {
            const soundUrl = mapping.sound_url;
            const volume = (mapping.volume / 100) * (this.userPreferences.volume / 100);
            
            this.playSound(soundUrl, {
                volume: volume,
                element: element,
                mapping: mapping
            });
        }
        
        /**
         * Reproducir sonido con Web Audio API
         */
        async playSound(url, options = {}) {
            if (!url || !this.audioContext || this.userPreferences.muted) {
                return;
            }
            
            try {
                // Reanudar contexto de audio si está suspendido
                if (this.audioContext.state === 'suspended') {
                    await this.audioContext.resume();
                }
                
                // Obtener o cargar buffer de audio
                let buffer = this.audioBuffers[url];
                if (!buffer) {
                    buffer = await this.loadAudioBuffer(url);
                    if (!buffer) {
                        throw new Error('No se pudo cargar el buffer de audio');
                    }
                    this.audioBuffers[url] = buffer;
                }
                
                // Crear fuente
                const source = this.audioContext.createBufferSource();
                source.buffer = buffer;
                
                // Crear nodo de ganancia para este sonido
                const gainNode = this.audioContext.createGain();
                gainNode.gain.value = options.volume || 1;
                
                // Conectar nodos
                source.connect(gainNode);
                gainNode.connect(this.masterGain);
                
                // Agregar retroalimentación visual
                if (options.element) {
                    options.element.classList.add('nova-sound-playing');
                    
                    // Agregar clase específica del evento
                    if (options.mapping && options.mapping.event_type) {
                        options.element.classList.add(`nova-sound-playing-${options.mapping.event_type}`);
                    }
                    
                    source.onended = () => {
                        options.element.classList.remove('nova-sound-playing');
                        if (options.mapping && options.mapping.event_type) {
                            options.element.classList.remove(`nova-sound-playing-${options.mapping.event_type}`);
                        }
                    };
                }
                
                // Reproducir sonido
                source.start(0);
                
                // Mostrar indicador visual
                if (this.settings.show_visual_feedback !== false) {
                    this.showSoundWave(options.element);
                }
                
            } catch (error) {
                console.error('Nova Sound FX: Error al reproducir sonido', error);
                
                // Fallback a HTML5 Audio
                this.playFallbackAudio(url, options);
            }
        }
        
        /**
         * Cargar buffer de audio
         */
        async loadAudioBuffer(url) {
            try {
                const response = await fetch(url);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const arrayBuffer = await response.arrayBuffer();
                return await this.audioContext.decodeAudioData(arrayBuffer);
            } catch (error) {
                console.error('Nova Sound FX: Error al cargar audio', url, error);
                return null;
            }
        }
        
        /**
         * Reproducción de audio de respaldo
         */
        playFallbackAudio(url, options = {}) {
            try {
                const audio = new Audio(url);
                audio.volume = (options.volume || 1) * (this.userPreferences.volume / 100);
                audio.play().catch(e => {
                    console.error('Nova Sound FX: Fallo el audio de respaldo', e);
                });
            } catch (e) {
                console.error('Nova Sound FX: Error crítico en audio', e);
            }
        }
        
        /**
         * Precargar sonidos frecuentemente usados
         */
        preloadSounds() {
            // Precargar primeros 5 sonidos
            const soundUrls = Object.values(this.sounds).slice(0, 5);
            
            soundUrls.forEach(url => {
                this.loadAudioBuffer(url).catch(e => {
                    console.error('Nova Sound FX: Error al precargar sonido', url, e);
                });
            });
        }
        
        /**
         * Configurar manejadores de transición de página
         */
        setupPageTransitions() {
            if (!window.NovaSoundFXData || !window.NovaSoundFXData.transitions) {
                return;
            }
            
            // Reproducir sonido de entrada para la página actual
            setTimeout(() => {
                this.playPageEntrySound();
            }, 100);
            
            // Interceptar clics en enlaces para sonidos de salida
            document.addEventListener('click', (e) => {
                const link = e.target.closest('a');
                if (link && link.href && !link.getAttribute('data-nova-no-sound')) {
                    // Verificar si es un enlace interno
                    const currentHost = window.location.host;
                    const linkHost = new URL(link.href, window.location.href).host;
                    
                    if (currentHost === linkHost) {
                        this.handlePageExit(e, link.href);
                    }
                }
            });
            
            // Manejar navegación del navegador hacia atrás/adelante
            window.addEventListener('beforeunload', () => {
                this.playPageExitSound(false);
            });
        }
        
        /**
         * Reproducir sonido de entrada de página
         */
        playPageEntrySound() {
            const transition = this.findMatchingTransition(window.location.href, 'enter');
            if (transition) {
                const volume = (transition.volume / 100) * (this.userPreferences.volume / 100);
                this.playSound(transition.sound_url, { 
                    volume,
                    isTransition: true 
                });
            }
        }
        
        /**
         * Reproducir sonido de salida de página
         */
        playPageExitSound(wait = true) {
            const transition = this.findMatchingTransition(window.location.href, 'exit');
            if (transition) {
                const volume = (transition.volume / 100) * (this.userPreferences.volume / 100);
                
                if (wait) {
                    // Mostrar overlay de transición
                    this.showTransitionOverlay();
                }
                
                this.playSound(transition.sound_url, { 
                    volume,
                    isTransition: true 
                });
                
                return wait ? 300 : 0; // Retornar duración del retraso
            }
            return 0;
        }
        
        /**
         * Manejar salida de página
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
         * Encontrar transición coincidente para URL
         */
        findMatchingTransition(url, type) {
            const transitions = window.NovaSoundFXData.transitions
                .filter(t => t.transition_type === type || t.transition_type === 'both')
                .sort((a, b) => b.priority - a.priority);
            
            // Obtener solo la parte de la ruta de la URL
            const urlPath = new URL(url).pathname;
            
            for (const transition of transitions) {
                if (this.matchesUrlPattern(urlPath, transition.url_pattern)) {
                    return transition;
                }
            }
            
            return null;
        }
        
        /**
         * Verificar si la URL coincide con el patrón
         */
        matchesUrlPattern(url, pattern) {
            // Manejar patrones regex
            if (pattern.startsWith('regex:')) {
                try {
                    const regex = new RegExp(pattern.substring(6));
                    return regex.test(url);
                } catch (e) {
                    console.error('Nova Sound FX: Patrón regex inválido', pattern);
                    return false;
                }
            }
            
            // Convertir patrón comodín a regex
            const regexPattern = pattern
                .replace(/[.+?^${}()|[\]\\]/g, '\\$&')
                .replace(/\*/g, '.*');
            
            try {
                const regex = new RegExp('^' + regexPattern + '$');
                return regex.test(url);
            } catch (e) {
                console.error('Nova Sound FX: Patrón inválido', pattern);
                return false;
            }
        }
        
        /**
         * Inicializar controles de usuario
         */
        initializeUserControls() {
            const controls = document.querySelectorAll('.nova-sound-fx-controls');
            
            controls.forEach(control => {
                this.setupControlWidget(control);
            });
            
            // Escuchar controles agregados dinámicamente
            const observer = new MutationObserver((mutations) => {
                mutations.forEach(mutation => {
                    mutation.addedNodes.forEach(node => {
                        if (node.nodeType === 1 && node.classList && node.classList.contains('nova-sound-fx-controls')) {
                            this.setupControlWidget(node);
                        }
                    });
                });
            });
            
            observer.observe(document.body, { childList: true, subtree: true });
        }
        
        /**
         * Configurar widget de control
         */
        setupControlWidget(widget) {
            const muteBtn = widget.querySelector('.nova-mute-btn');
            const volumeSlider = widget.querySelector('.nova-volume-slider');
            const saveBtn = widget.querySelector('.nova-save-btn');
            const expandBtn = widget.querySelector('.nova-expand-btn');
            
            // Establecer estados iniciales
            this.updateMuteButton(muteBtn);
            if (volumeSlider) {
                volumeSlider.value = this.userPreferences.volume;
                const valueDisplay = widget.querySelector('.nova-volume-value');
                if (valueDisplay) {
                    valueDisplay.textContent = this.userPreferences.volume + '%';
                }
            }
            
            // Botón de silenciar/desilenciar
            if (muteBtn) {
                muteBtn.addEventListener('click', () => {
                    if (this.userPreferences.muted) {
                        this.unmute();
                    } else {
                        this.mute();
                    }
                    this.updateMuteButton(muteBtn);
                    
                    // Reproducir sonido de feedback
                    if (!this.userPreferences.muted) {
                        this.playSound(novaSoundFX.sounds[Object.keys(novaSoundFX.sounds)[0]], {
                            volume: 0.3
                        });
                    }
                });
            }
            
            // Slider de volumen
            if (volumeSlider) {
                volumeSlider.addEventListener('input', (e) => {
                    const volume = parseInt(e.target.value);
                    this.setMasterVolume(volume);
                    
                    const valueDisplay = widget.querySelector('.nova-volume-value');
                    if (valueDisplay) {
                        valueDisplay.textContent = volume + '%';
                    }
                });
                
                // Reproducir sonido al soltar el slider
                volumeSlider.addEventListener('change', (e) => {
                    if (!this.userPreferences.muted && Object.keys(novaSoundFX.sounds).length > 0) {
                        this.playSound(novaSoundFX.sounds[Object.keys(novaSoundFX.sounds)[0]], {
                            volume: 0.5
                        });
                    }
                });
            }
            
            // Botón de guardar
            if (saveBtn) {
                saveBtn.addEventListener('click', () => {
                    this.saveUserPreferences();
                });
            }
            
            // Botón de expandir/contraer
            if (expandBtn) {
                expandBtn.addEventListener('click', () => {
                    widget.classList.toggle('nova-collapsed');
                    const isCollapsed = widget.classList.contains('nova-collapsed');
                    expandBtn.setAttribute('aria-expanded', !isCollapsed);
                });
                
                // Comenzar contraído
                widget.classList.add('nova-collapsed');
                expandBtn.setAttribute('aria-expanded', 'false');
            }
            
            // Agregar animación de entrada
            widget.classList.add('nova-controls-enter');
            
            // Escuchar cambios de preferencias
            $(document).on('nova-sound-fx:preferences-saved', (e, prefs) => {
                this.updateMuteButton(muteBtn);
                if (volumeSlider) {
                    volumeSlider.value = prefs.volume;
                    const valueDisplay = widget.querySelector('.nova-volume-value');
                    if (valueDisplay) {
                        valueDisplay.textContent = prefs.volume + '%';
                    }
                }
            });
        }
        
        /**
         * Actualizar estado del botón de silencio
         */
        updateMuteButton(button) {
            if (!button) return;
            
            const volumeOn = button.querySelector('.nova-icon-volume-on');
            const volumeOff = button.querySelector('.nova-icon-volume-off');
            
            if (this.userPreferences.muted) {
                if (volumeOn) volumeOn.style.display = 'none';
                if (volumeOff) volumeOff.style.display = 'block';
                button.setAttribute('aria-label', 'Activar sonido');
            } else {
                if (volumeOn) volumeOn.style.display = 'block';
                if (volumeOff) volumeOff.style.display = 'none';
                button.setAttribute('aria-label', 'Silenciar');
            }
        }
        
        /**
         * Establecer volumen maestro
         */
        setMasterVolume(volume) {
            this.userPreferences.volume = Math.max(0, Math.min(100, volume));
            
            if (this.masterGain) {
                this.masterGain.gain.value = this.userPreferences.volume / 100;
            }
        }
        
        /**
         * Silenciar sonidos
         */
        mute() {
            this.userPreferences.muted = true;
            if (this.masterGain) {
                this.masterGain.gain.value = 0;
            }
        }
        
        /**
         * Desilenciar sonidos
         */
        unmute() {
            this.userPreferences.muted = false;
            if (this.masterGain) {
                this.masterGain.gain.value = this.userPreferences.volume / 100;
            }
        }
        
        /**
         * Mostrar indicador de onda de sonido
         */
        showSoundWave(element) {
            let wave = document.querySelector('.nova-sound-wave');
            
            if (!wave) {
                wave = document.createElement('div');
                wave.className = 'nova-sound-wave';
                wave.innerHTML = '<span></span><span></span><span></span><span></span><span></span>';
                document.body.appendChild(wave);
            }
            
            // Posicionar cerca del elemento si existe
            if (element) {
                const rect = element.getBoundingClientRect();
                wave.style.position = 'fixed';
                wave.style.left = rect.left + rect.width / 2 + 'px';
                wave.style.top = rect.top + rect.height / 2 + 'px';
                wave.style.transform = 'translate(-50%, -50%)';
            }
            
            wave.classList.add('active');
            
            setTimeout(() => {
                wave.classList.remove('active');
            }, 1000);
        }
        
        /**
         * Mostrar overlay de transición
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
         * Mostrar notificación
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
                        notification.classList.remove('nova-show');
                        setTimeout(() => {
                            notification.style.display = 'none';
                        }, 300);
                    }, 2000);
                }
            });
        }
        
        /**
         * Helpers de cookies
         */
        setCookie(name, value, days) {
            const expires = new Date();
            expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
            document.cookie = name + '=' + value + ';expires=' + expires.toUTCString() + ';path=/;SameSite=Lax';
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
         * Método de limpieza
         */
        destroy() {
            // Remover event listeners
            this.clearEventListeners();
            
            // Cerrar contexto de audio
            if (this.audioContext) {
                this.audioContext.close();
            }
            
            // Limpiar elementos activos
            this.activeElements.clear();
        }
    }
    
    // Inicializar en DOM ready
    $(document).ready(function() {
        // Solo inicializar si las configuraciones están disponibles
        if (typeof novaSoundFX !== 'undefined') {
            window.novaSoundFXInstance = new NovaSoundFX();
        }
    });
    
})(jQuery);
