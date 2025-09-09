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
            this.hasUserConsent = false;
            this.consentPopupShown = false;
            this.autoReactivationSetup = false;
            this.pendingAudioActivation = false;
            this.audioInitializationAttempts = 0;
            this.maxInitializationAttempts = 5;
            this.silentAudioBuffer = null;
            this.isAudioContextUnlocked = false;
            
            // Verificar si los sonidos deben estar habilitados
            if (!this.shouldEnableSounds()) {
                return;
            }
            
            // Configurar detección de focus/blur para reactivación automática
            this.setupPageVisibilityDetection();
            
            // Verificar consentimiento de audio
            this.checkAudioConsent();
        }
        
        /**
         * Configurar detección de visibilidad de página para reactivación automática
         */
        setupPageVisibilityDetection() {
            // Detectar cuando el usuario vuelve a la página (cambio de tab, minimizar/maximizar ventana)
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden && this.hasUserConsent && !this.isAudioContextUnlocked) {
                    console.log('Nova Sound FX: Página visible - intentando reactivación automática');
                    this.attemptAutoReactivationOnVisibility();
                }
            });
            
            // Detectar focus de la ventana
            window.addEventListener('focus', () => {
                if (this.hasUserConsent && !this.isAudioContextUnlocked) {
                    console.log('Nova Sound FX: Ventana enfocada - intentando reactivación automática');
                    this.attemptAutoReactivationOnVisibility();
                }
            });
            
            // Detectar interacción con la página después de carga
            const interactionEvents = ['mousemove', 'scroll', 'keypress'];
            let interactionDetected = false;
            
            const detectInteraction = () => {
                if (!interactionDetected && this.hasUserConsent && !this.isAudioContextUnlocked) {
                    interactionDetected = true;
                    console.log('Nova Sound FX: Interacción detectada - intentando activación');
                    setTimeout(() => {
                        this.attemptAutoReactivationOnVisibility();
                    }, 100);
                }
            };
            
            interactionEvents.forEach(event => {
                document.addEventListener(event, detectInteraction, { once: true, passive: true });
            });
        }
        
        /**
         * Intentar reactivación automática cuando la página vuelve a ser visible
         */
        async attemptAutoReactivationOnVisibility() {
            try {
                // Método más agresivo: intentar múltiples técnicas
                const techniques = [
                    () => this.tryDirectAudioInitialization(),
                    () => this.tryResumeExistingContext(),
                    () => this.setupSingleEventCapture()
                ];
                
                for (const technique of techniques) {
                    try {
                        const success = await technique();
                        if (success) {
                            console.log('Nova Sound FX: Reactivación automática exitosa');
                            this.isAudioContextUnlocked = true;
                            this.finishAudioSetup();
                            this.hideAudioPendingNotification();
                            
                            // Mostrar confirmación sutil
                            this.showNotificationToast('Audio reactivado automáticamente ✓', 'success');
                            return;
                        }
                    } catch (error) {
                        continue;
                    }
                }
                
                // Si ninguna técnica funcionó, setup listeners menos invasivos
                this.setupReducedActivationListeners();
                
            } catch (error) {
                console.log('Nova Sound FX: Error en reactivación automática:', error);
                this.setupReducedActivationListeners();
            }
        }
        
        /**
         * Intentar reanudar contexto de audio existente
         */
        async tryResumeExistingContext() {
            if (this.audioContext && this.audioContext.state === 'suspended') {
                try {
                    await this.audioContext.resume();
                    
                    if (this.audioContext.state === 'running') {
                        this.updateLastActivation();
                        return true;
                    }
                } catch (error) {
                    console.log('Nova Sound FX: Error al reanudar contexto:', error);
                }
            }
            return false;
        }
        
        /**
         * Setup de captura de evento único (menos invasivo)
         */
        setupSingleEventCapture() {
            return new Promise((resolve) => {
                const quickEvents = ['click', 'touchstart', 'keydown'];
                let resolved = false;
                
                const quickHandler = async (event) => {
                    if (resolved) return;
                    resolved = true;
                    
                    // Remover todos los listeners
                    quickEvents.forEach(eventName => {
                        document.removeEventListener(eventName, quickHandler, true);
                    });
                    
                    try {
                        const success = await this.tryUnlockFromEvent();
                        resolve(success);
                    } catch (error) {
                        resolve(false);
                    }
                };
                
                // Agregar listeners temporales
                quickEvents.forEach(eventName => {
                    document.addEventListener(eventName, quickHandler, { once: true, capture: true });
                });
                
                // Timeout después de 5 segundos
                setTimeout(() => {
                    if (!resolved) {
                        resolved = true;
                        quickEvents.forEach(eventName => {
                            document.removeEventListener(eventName, quickHandler, true);
                        });
                        resolve(false);
                    }
                }, 5000);
            });
        }
        
        /**
         * Setup de listeners reducidos (menos invasivos que el método completo)
         */
        setupReducedActivationListeners() {
            console.log('Nova Sound FX: Configurando listeners reducidos...');
            
            // Solo eventos esenciales
            const essentialEvents = ['click', 'touchstart'];
            
            const reducedHandler = async (event) => {
                console.log('Nova Sound FX: Activación con evento reducido:', event.type);
                
                const success = await this.tryUnlockFromEvent();
                
                if (success) {
                    essentialEvents.forEach(eventName => {
                        document.removeEventListener(eventName, reducedHandler, true);
                    });
                    
                    this.finishAudioSetup();
                    this.hideAudioPendingNotification();
                    
                    // Mostrar confirmación
                    const i18n = novaSoundFX.i18n || {};
                    this.showNotificationToast(i18n.soundsActivated || '¡Audio activado! 🎵', 'success');
                    
                    setTimeout(() => {
                        this.playWelcomeSound();
                    }, 300);
                }
            };
            
            essentialEvents.forEach(eventName => {
                document.addEventListener(eventName, reducedHandler, { capture: true, passive: false });
            });
            
            // Mostrar notificación más sutil
            this.showAudioPendingNotification();
        }
        
        /**
         * Verificar consentimiento de audio
         */
        checkAudioConsent() {
            // Verificar si ya tenemos consentimiento guardado
            const consentData = this.getConsentData();
            const savedConsent = consentData ? consentData.status : null;
            
            if (savedConsent === 'granted') {
                this.hasUserConsent = true;
                
                // Intentar inicialización inmediata agresiva (técnica AJAX-style)
                this.attemptImmediateAudioInitialization();
                
                this.createFloatingController();
                
            } else if (savedConsent === 'denied') {
                this.hasUserConsent = false;
                this.createFloatingController(); // Mostrar controlador pero deshabilitado
            } else {
                // No hay consentimiento previo, mostrar popup después de un pequeño delay
                setTimeout(() => {
                    this.showConsentPopup();
                }, 1000);
            }
        }
        
        /**
         * Intentar inicialización inmediata de audio (técnica avanzada)
         */
        async attemptImmediateAudioInitialization() {
            console.log('Nova Sound FX: Intentando inicialización inmediata de audio...');
            
            try {
                // Método 1: Intentar inicialización directa con audio silencioso
                const success = await this.tryDirectAudioInitialization();
                
                if (success) {
                    console.log('Nova Sound FX: Audio inicializado exitosamente de forma directa');
                    this.isAudioContextUnlocked = true;
                    this.finishAudioSetup();
                    return;
                }
                
                // Método 2: Setup de múltiples listeners para captura temprana
                this.setupMultipleActivationListeners();
                
            } catch (error) {
                console.warn('Nova Sound FX: Error en inicialización inmediata:', error);
                this.setupMultipleActivationListeners();
            }
        }
        
        /**
         * Intentar inicialización directa con audio silencioso
         */
        async tryDirectAudioInitialization() {
            try {
                // Crear contexto de audio
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                this.audioContext = new AudioContext();
                
                // Crear buffer de audio silencioso (técnica de reproductores AJAX)
                await this.createSilentAudioBuffer();
                
                // Intentar reproducir audio silencioso para "desbloquear" el contexto
                const unlocked = await this.unlockAudioContext();
                
                if (unlocked && this.audioContext.state === 'running') {
                    // Configurar nodo de ganancia maestro
                    this.masterGain = this.audioContext.createGain();
                    this.masterGain.connect(this.audioContext.destination);
                    this.setMasterVolume(this.userPreferences.volume);
                    
                    this.isInitialized = true;
                    this.updateLastActivation();
                    
                    return true;
                }
                
            } catch (error) {
                console.log('Nova Sound FX: Inicialización directa falló (esperado):', error.message);
            }
            
            return false;
        }
        
        /**
         * Crear buffer de audio silencioso
         */
        async createSilentAudioBuffer() {
            if (this.silentAudioBuffer) return;
            
            // Crear un buffer de audio silencioso muy corto (técnica común)
            const sampleRate = this.audioContext.sampleRate;
            const bufferLength = sampleRate * 0.01; // 10ms
            
            this.silentAudioBuffer = this.audioContext.createBuffer(1, bufferLength, sampleRate);
            // El buffer se queda silencioso (valores por defecto son 0)
        }
        
        /**
         * Intentar desbloquear contexto de audio
         */
        async unlockAudioContext() {
            try {
                if (!this.silentAudioBuffer) return false;
                
                // Crear fuente y reproducir audio silencioso
                const source = this.audioContext.createBufferSource();
                source.buffer = this.silentAudioBuffer;
                source.connect(this.audioContext.destination);
                source.start(0);
                
                // Esperar un poco para ver si el contexto se desbloquea
                await new Promise(resolve => setTimeout(resolve, 100));
                
                return this.audioContext.state === 'running';
                
            } catch (error) {
                return false;
            }
        }
        
        /**
         * Configurar múltiples listeners de activación (método fallback)
         */
        setupMultipleActivationListeners() {
            console.log('Nova Sound FX: Configurando listeners múltiples para activación...');
            
            // Lista de eventos que pueden desbloquear audio
            const unlockEvents = [
                'click', 'contextmenu', 'auxclick', 'dblclick',
                'mousedown', 'mouseup', 'pointerup', 'touchend',
                'keydown', 'keyup', 'scroll', 'wheel'
            ];
            
            const unlockHandler = async (event) => {
                // Ignorar eventos en elementos del plugin
                if (event.target.closest('.nova-consent-backdrop, .nova-floating-controller, .nova-toast')) {
                    return;
                }
                
                console.log('Nova Sound FX: Intentando desbloqueo con evento:', event.type);
                
                const success = await this.tryUnlockFromEvent();
                
                if (success) {
                    // Remover todos los listeners
                    unlockEvents.forEach(eventName => {
                        document.removeEventListener(eventName, unlockHandler, true);
                    });
                    
                    this.finishAudioSetup();
                    this.hideAudioPendingNotification();
                    
                    // Mostrar confirmación
                    const i18n = novaSoundFX.i18n || {};
                    this.showNotificationToast(i18n.soundsActivated || '¡Audio activado! 🎵', 'success');
                    
                    // Reproducir sonido de bienvenida
                    setTimeout(() => {
                        this.playWelcomeSound();
                    }, 300);
                }
            };
            
            // Agregar listeners para todos los eventos
            unlockEvents.forEach(eventName => {
                document.addEventListener(eventName, unlockHandler, true);
            });
            
            // Mostrar notificación de audio pendiente
            this.showAudioPendingNotification();
            
            // Auto-cleanup después de 30 segundos si no se desbloquea
            setTimeout(() => {
                if (!this.isAudioContextUnlocked) {
                    unlockEvents.forEach(eventName => {
                        document.removeEventListener(eventName, unlockHandler, true);
                    });
                    console.log('Nova Sound FX: Timeout - listeners de desbloqueo removidos');
                }
            }, 30000);
        }
        
        /**
         * Intentar desbloquear desde evento
         */
        async tryUnlockFromEvent() {
            try {
                if (!this.audioContext) {
                    await this.initAudioContextImmediate();
                }
                
                if (this.audioContext.state === 'suspended') {
                    await this.audioContext.resume();
                }
                
                // Intentar reproducir audio silencioso
                const unlocked = await this.unlockAudioContext();
                
                if (unlocked) {
                    this.isAudioContextUnlocked = true;
                    this.updateLastActivation();
                    return true;
                }
                
            } catch (error) {
                console.log('Nova Sound FX: Error en desbloqueo desde evento:', error);
            }
            
            return false;
        }
        
        /**
         * Finalizar configuración de audio después de desbloqueo exitoso
         */
        finishAudioSetup() {
            console.log('Nova Sound FX: Finalizando configuración de audio...');
            
            // Cargar mapeos de sonido
            this.loadSoundMappings();
            
            // Configurar transiciones de página
            this.setupPageTransitions();
            
            // Inicializar controles existentes
            this.initializeUserControls();
            
            // Precargar sonidos
            this.preloadSounds();
            
            // Actualizar controlador flotante
            const controller = document.querySelector('.nova-floating-controller');
            if (controller) {
                this.updateFloatingControllerState(controller);
            }
            
            this.isInitialized = true;
            console.log('Nova Sound FX: Audio completamente configurado y listo');
        }
        
        /**
         * Determinar si necesitamos reactivación de audio
         */
        needsAudioReactivation(consentData) {
            if (!consentData || !consentData.lastActivation) {
                return true; // Primera vez o sin datos de activación
            }
            
            // Si la última activación fue hace más de 30 segundos, asumir que hubo reload
            const timeSinceActivation = Date.now() - consentData.lastActivation;
            return timeSinceActivation > 30000; // 30 segundos
        }
        
        /**
         * Obtener estado de consentimiento guardado
         */
        getAudioConsent() {
            const consentData = this.getConsentData();
            return consentData ? consentData.status : null;
        }
        
        /**
         * Guardar consentimiento de audio
         */
        saveAudioConsent(consent) {
            const consentData = {
                status: consent,
                timestamp: Date.now(),
                lastActivation: consent === 'granted' ? Date.now() : null
            };
            
            try {
                localStorage.setItem('nova_sound_fx_audio_consent', JSON.stringify(consentData));
            } catch (e) {
                this.setCookie('nova_sound_fx_audio_consent', JSON.stringify(consentData), 365);
            }
        }
        
        /**
         * Obtener datos completos de consentimiento
         */
        getConsentData() {
            try {
                const data = localStorage.getItem('nova_sound_fx_audio_consent');
                if (data) {
                    const parsed = JSON.parse(data);
                    // Compatibilidad con versión anterior (string simple)
                    if (typeof parsed === 'string') {
                        return { status: parsed, timestamp: null, lastActivation: null };
                    }
                    return parsed;
                }
            } catch (e) {
                const data = this.getCookie('nova_sound_fx_audio_consent');
                if (data) {
                    try {
                        const parsed = JSON.parse(decodeURIComponent(data));
                        if (typeof parsed === 'string') {
                            return { status: parsed, timestamp: null, lastActivation: null };
                        }
                        return parsed;
                    } catch (e2) {
                        // Formato antiguo
                        return { status: data, timestamp: null, lastActivation: null };
                    }
                }
            }
            return null;
        }
        
        /**
         * Actualizar timestamp de última activación
         */
        updateLastActivation() {
            const consentData = this.getConsentData();
            if (consentData) {
                consentData.lastActivation = Date.now();
                try {
                    localStorage.setItem('nova_sound_fx_audio_consent', JSON.stringify(consentData));
                } catch (e) {
                    this.setCookie('nova_sound_fx_audio_consent', JSON.stringify(consentData), 365);
                }
            }
        }
        
        /**
         * Configurar auto-reactivación en primer click
         */
        setupAutoReactivation() {
            if (this.autoReactivationSetup) {
                return; // Ya está configurado
            }
            
            this.autoReactivationSetup = true;
            this.pendingAudioActivation = true;
            
            // Event listener para capturar CUALQUIER click en la página
            const autoActivateHandler = (event) => {
                // Ignorar clicks en elementos del plugin para evitar doble activación
                if (event.target.closest('.nova-consent-backdrop, .nova-floating-controller, .nova-toast, .nova-settings-modal')) {
                    return;
                }
                
                console.log('Nova Sound FX: Auto-activando audio por click del usuario');
                
                // Activar audio inmediatamente
                this.activateAudioFromUserInteraction();
                
                // Remover el listener
                document.removeEventListener('click', autoActivateHandler, true);
                document.removeEventListener('touchstart', autoActivateHandler, true);
                
                this.autoReactivationSetup = false;
                this.pendingAudioActivation = false;
                
                // Ocultar notificación de audio pendiente
                this.hideAudioPendingNotification();
            };
            
            // Usar capture = true para capturar antes que otros handlers
            document.addEventListener('click', autoActivateHandler, true);
            document.addEventListener('touchstart', autoActivateHandler, true);
            
            console.log('Nova Sound FX: Auto-reactivación configurada - esperando click del usuario');
        }
        
        /**
         * Activar audio desde interacción del usuario
         */
        activateAudioFromUserInteraction() {
            // Inicializar contexto de audio inmediatamente
            this.initAudioContextImmediate();
            
            // Cargar mapeos de sonido
            this.loadSoundMappings();
            
            // Configurar transiciones de página
            this.setupPageTransitions();
            
            // Inicializar controles existentes
            this.initializeUserControls();
            
            // Actualizar timestamp de activación
            this.updateLastActivation();
            
            // Mostrar confirmación
            const i18n = novaSoundFX.i18n || {};
            this.showNotificationToast(i18n.soundsActivated || '¡Audio activado! 🎵', 'success');
            
            // Actualizar controlador flotante
            const controller = document.querySelector('.nova-floating-controller');
            if (controller) {
                this.updateFloatingControllerState(controller);
            }
            
            // Reproducir sonido de bienvenida
            setTimeout(() => {
                this.playWelcomeSound();
            }, 300);
        }
        
        /**
         * Mostrar notificación de audio pendiente
         */
        showAudioPendingNotification() {
            // Crear notificación persistente
            const notification = document.createElement('div');
            notification.className = 'nova-audio-pending-notification';
            notification.innerHTML = `
                <div class="nova-pending-content">
                    <div class="nova-pending-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 18V5l12-2v13"></path>
                            <circle cx="6" cy="18" r="3"></circle>
                            <circle cx="18" cy="16" r="3"></circle>
                        </svg>
                    </div>
                    <div class="nova-pending-text">
                        <span class="nova-pending-title">Audio listo para activar</span>
                        <span class="nova-pending-subtitle">Haz click en cualquier lugar para comenzar</span>
                    </div>
                    <button class="nova-pending-activate" id="nova-activate-now">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <polygon points="5 3 19 12 5 21 5 3"></polygon>
                        </svg>
                        Activar Ahora
                    </button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Mostrar con animación
            setTimeout(() => {
                notification.classList.add('nova-pending-show');
            }, 100);
            
            // Event handler para botón "Activar Ahora"
            document.getElementById('nova-activate-now').addEventListener('click', (e) => {
                e.stopPropagation();
                this.activateAudioFromUserInteraction();
                this.hideAudioPendingNotification();
            });
            
            // Agregar clase al body para otros elementos puedan reaccionar
            document.body.classList.add('nova-audio-pending');
        }
        
        /**
         * Ocultar notificación de audio pendiente
         */
        hideAudioPendingNotification() {
            const notification = document.querySelector('.nova-audio-pending-notification');
            if (notification) {
                notification.classList.remove('nova-pending-show');
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }
            
            document.body.classList.remove('nova-audio-pending');
        }
        
        /**
         * Mostrar popup de consentimiento
         */
        showConsentPopup() {
            if (this.consentPopupShown) return;
            this.consentPopupShown = true;
            
            // Crear elementos del popup
            const backdrop = document.createElement('div');
            backdrop.className = 'nova-consent-backdrop';
            backdrop.style.cssText = 'display: none;';
            
            const popup = document.createElement('div');
            popup.className = 'nova-consent-popup';
            const i18n = novaSoundFX.i18n || {};
            popup.innerHTML = `
                <div class="nova-consent-content">
                    <div class="nova-consent-icon">
                        <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 18V5l12-2v13"></path>
                            <circle cx="6" cy="18" r="3"></circle>
                            <circle cx="18" cy="16" r="3"></circle>
                        </svg>
                    </div>
                    <h2 class="nova-consent-title">${i18n.consentTitle || '🎵 Experiencia de Audio Mejorada'}</h2>
                    <p class="nova-consent-description">
                        ${i18n.consentDescription || 'Este sitio utiliza efectos de sonido interactivos para mejorar tu experiencia de navegación. Los sonidos se reproducirán cuando interactúes con elementos específicos de la página.'}
                    </p>
                    <div class="nova-consent-benefits">
                        <div class="nova-consent-benefit">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            <span>${i18n.consentBenefit1 || 'Feedback inmediato en interacciones'}</span>
                        </div>
                        <div class="nova-consent-benefit">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            <span>${i18n.consentBenefit2 || 'Control total del volumen'}</span>
                        </div>
                        <div class="nova-consent-benefit">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            <span>${i18n.consentBenefit3 || 'Puedes desactivarlo en cualquier momento'}</span>
                        </div>
                    </div>
                    <div class="nova-consent-actions">
                        <button class="nova-consent-accept" id="nova-consent-accept">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M3 12a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"></path>
                                <path d="M9 12l2 2l4 -4"></path>
                            </svg>
                            ${i18n.consentAccept || 'Activar Sonidos'}
                        </button>
                        <button class="nova-consent-reject" id="nova-consent-reject">
                            ${i18n.consentReject || 'Continuar en Silencio'}
                        </button>
                    </div>
                    <div class="nova-consent-remember">
                        <label>
                            <input type="checkbox" id="nova-consent-remember" checked>
                            <span>${i18n.consentRemember || 'Recordar mi preferencia'}</span>
                        </label>
                    </div>
                </div>
            `;
            
            backdrop.appendChild(popup);
            document.body.appendChild(backdrop);
            
            // Mostrar con animación
            setTimeout(() => {
                backdrop.style.display = 'flex';
                setTimeout(() => {
                    backdrop.classList.add('nova-consent-show');
                }, 10);
            }, 500);
            
            // Event handlers
            document.getElementById('nova-consent-accept').addEventListener('click', () => {
                this.handleConsentResponse(true);
            });
            
            document.getElementById('nova-consent-reject').addEventListener('click', () => {
                this.handleConsentResponse(false);
            });
            
            // Cerrar con ESC
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && backdrop.classList.contains('nova-consent-show')) {
                    this.handleConsentResponse(false);
                }
            });
        }
        
        /**
         * Manejar respuesta de consentimiento
         */
        handleConsentResponse(accepted) {
            const backdrop = document.querySelector('.nova-consent-backdrop');
            const rememberChoice = document.getElementById('nova-consent-remember').checked;
            
            if (accepted) {
                this.hasUserConsent = true;
                if (rememberChoice) {
                    this.saveAudioConsent('granted');
                }
                
                // IMPORTANTE: Usar el método completo de activación para asegurar
                // que todo se inicialice correctamente desde esta interacción
                this.activateAudioFromUserInteraction();
                
                this.createFloatingController();
                
            } else {
                this.hasUserConsent = false;
                if (rememberChoice) {
                    this.saveAudioConsent('denied');
                }
                this.createFloatingController();
                
                // Mostrar notificación
                const i18n = novaSoundFX.i18n || {};
                this.showNotificationToast(i18n.soundsDeactivated || 'Sonidos desactivados. Puedes activarlos desde el control flotante.', 'info');
            }
            
            // Ocultar popup con animación
            backdrop.classList.remove('nova-consent-show');
            setTimeout(() => {
                backdrop.remove();
            }, 300);
        }
        
        /**
         * Inicializar contexto de audio inmediatamente (llamado desde interacción del usuario)
         */
        initAudioContextImmediate() {
            try {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                this.audioContext = new AudioContext();
                
                // Crear nodo de ganancia maestro para control de volumen
                this.masterGain = this.audioContext.createGain();
                this.masterGain.connect(this.audioContext.destination);
                this.setMasterVolume(this.userPreferences.volume);
                
                // Marcar como inicializado
                this.isInitialized = true;
                
                // Precargar sonidos frecuentemente usados
                this.preloadSounds();
                
                console.log('Nova Sound FX: Contexto de audio inicializado exitosamente');
            } catch (e) {
                console.error('Nova Sound FX: Error al inicializar el contexto de audio', e);
            }
        }
        
        /**
         * Reproducir sonido de bienvenida
         */
        playWelcomeSound() {
            // Buscar el primer sonido disponible para reproducir como confirmación
            if (this.sounds && Object.keys(this.sounds).length > 0) {
                const firstSoundUrl = this.sounds[Object.keys(this.sounds)[0]];
                this.playSound(firstSoundUrl, { 
                    volume: 0.6,
                    isWelcome: true 
                });
            }
        }
        
        /**
         * Crear controlador flotante
         */
        createFloatingController() {
            // Verificar si ya existe
            if (document.querySelector('.nova-floating-controller')) {
                return;
            }
            
            const controller = document.createElement('div');
            controller.className = 'nova-floating-controller';
            const i18n = novaSoundFX.i18n || {};
            controller.innerHTML = `
                <div class="nova-controller-main">
                    <button class="nova-controller-toggle" id="nova-controller-toggle" aria-label="Toggle audio" title="${i18n.toggleAudio || 'Control de Audio'}">
                        <svg class="nova-icon-volume-on" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                            <path class="nova-sound-wave-1" d="M19.07 4.93a10 10 0 0 1 0 14.14"></path>
                            <path class="nova-sound-wave-2" d="M15.54 8.46a5 5 0 0 1 0 7.07"></path>
                        </svg>
                        <svg class="nova-icon-volume-off" style="display: none;" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                            <line x1="23" y1="9" x2="17" y2="15"></line>
                            <line x1="17" y1="9" x2="23" y2="15"></line>
                        </svg>
                    </button>
                </div>
                <div class="nova-controller-expanded">
                    <div class="nova-volume-control">
                        <input type="range" 
                               class="nova-volume-slider" 
                               id="nova-floating-volume" 
                               min="0" 
                               max="100" 
                               value="${this.userPreferences.volume || 50}"
                               aria-label="${i18n.volumeLabel || 'Volumen'}">
                        <span class="nova-volume-value">${this.userPreferences.volume || 50}%</span>
                    </div>
                    <div class="nova-controller-actions">
                        <button class="nova-controller-settings" id="nova-controller-settings" title="${i18n.settings || 'Configuración'}">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <circle cx="12" cy="12" r="3"></circle>
                                <path d="M12 1v6m0 6v6m4.22-13.22l4.24 4.24M1.54 1.54l4.24 4.24M20.46 20.46l-4.24-4.24M1.54 20.46l4.24-4.24"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(controller);
            
            // Setup event handlers
            this.setupFloatingControllerEvents(controller);
            
            // Animación de entrada
            setTimeout(() => {
                controller.classList.add('nova-controller-show');
            }, 100);
            
            // Actualizar estado inicial
            this.updateFloatingControllerState(controller);
        }
        
        /**
         * Configurar eventos del controlador flotante
         */
        setupFloatingControllerEvents(controller) {
            const toggleBtn = controller.querySelector('#nova-controller-toggle');
            const volumeSlider = controller.querySelector('#nova-floating-volume');
            const volumeValue = controller.querySelector('.nova-volume-value');
            const settingsBtn = controller.querySelector('#nova-controller-settings');
            
            // Toggle mute/unmute
            toggleBtn.addEventListener('click', () => {
                if (!this.hasUserConsent) {
                    // Si no hay consentimiento, inicializar audio inmediatamente y habilitar
                    this.hasUserConsent = true;
                    this.saveAudioConsent('granted');
                    
                    // Inicializar contexto de audio inmediatamente
                    this.initAudioContextImmediate();
                    this.loadSoundMappings();
                    
                    // Mostrar notificación
                    const i18n = novaSoundFX.i18n || {};
                    this.showNotificationToast(i18n.soundsActivated || '¡Sonidos activados! 🎵', 'success');
                    
                    // Reproducir sonido de bienvenida
                    setTimeout(() => {
                        this.playWelcomeSound();
                    }, 500);
                    
                    this.updateFloatingControllerState(controller);
                    return;
                }
                
                if (this.userPreferences.muted) {
                    this.unmute();
                } else {
                    this.mute();
                }
                this.updateFloatingControllerState(controller);
                this.saveUserPreferences();
                
                // Feedback sonoro
                if (!this.userPreferences.muted && Object.keys(this.sounds).length > 0) {
                    this.playSound(this.sounds[Object.keys(this.sounds)[0]], { volume: 0.3 });
                }
            });
            
            // Expandir/contraer al hover
            controller.addEventListener('mouseenter', () => {
                controller.classList.add('nova-controller-expanded-show');
            });
            
            controller.addEventListener('mouseleave', () => {
                controller.classList.remove('nova-controller-expanded-show');
            });
            
            // Control de volumen
            if (volumeSlider) {
                volumeSlider.addEventListener('input', (e) => {
                    const volume = parseInt(e.target.value);
                    this.setMasterVolume(volume);
                    volumeValue.textContent = volume + '%';
                });
                
                volumeSlider.addEventListener('change', () => {
                    this.saveUserPreferences();
                    // Sonido de feedback
                    if (!this.userPreferences.muted && Object.keys(this.sounds).length > 0) {
                        this.playSound(this.sounds[Object.keys(this.sounds)[0]], { volume: 0.5 });
                    }
                });
            }
            
            // Botón de configuración
            if (settingsBtn) {
                settingsBtn.addEventListener('click', () => {
                    this.showSettingsModal();
                });
            }
        }
        
        /**
         * Actualizar estado del controlador flotante
         */
        updateFloatingControllerState(controller) {
            const volumeOn = controller.querySelector('.nova-icon-volume-on');
            const volumeOff = controller.querySelector('.nova-icon-volume-off');
            const toggleBtn = controller.querySelector('#nova-controller-toggle');
            
            if (!this.hasUserConsent || this.userPreferences.muted) {
                volumeOn.style.display = 'none';
                volumeOff.style.display = 'block';
                controller.classList.add('nova-controller-muted');
            } else {
                volumeOn.style.display = 'block';
                volumeOff.style.display = 'none';
                controller.classList.remove('nova-controller-muted');
            }
        }
        
        /**
         * Mostrar modal de configuración
         */
        showSettingsModal() {
            // Modal de configuración avanzada
            const modal = document.createElement('div');
            modal.className = 'nova-settings-modal';
            const i18n = novaSoundFX.i18n || {};
            modal.innerHTML = `
                <div class="nova-settings-content">
                    <h3>${i18n.settingsTitle || 'Configuración de Audio'}</h3>
                    <div class="nova-settings-option">
                        <label>
                            <input type="checkbox" id="nova-reset-consent">
                            <span>${i18n.resetConsent || 'Restablecer preferencias de consentimiento'}</span>
                        </label>
                    </div>
                    <div class="nova-settings-actions">
                        <button id="nova-settings-close">${i18n.close || 'Cerrar'}</button>
                        <button id="nova-settings-reset" class="nova-danger">${i18n.resetAll || 'Restablecer Todo'}</button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            setTimeout(() => {
                modal.classList.add('nova-modal-show');
            }, 10);
            
            // Event handlers
            document.getElementById('nova-settings-close').addEventListener('click', () => {
                modal.classList.remove('nova-modal-show');
                setTimeout(() => modal.remove(), 300);
            });
            
            document.getElementById('nova-settings-reset').addEventListener('click', () => {
                const i18n = novaSoundFX.i18n || {};
                if (confirm(i18n.resetConfirm || '¿Estás seguro de que quieres restablecer todas las preferencias?')) {
                    localStorage.removeItem('nova_sound_fx_preferences');
                    localStorage.removeItem('nova_sound_fx_audio_consent');
                    this.showNotificationToast(i18n.preferencesReset || 'Preferencias restablecidas', 'success');
                    setTimeout(() => location.reload(), 1000);
                }
            });
        }
        
        /**
         * Mostrar notificación toast
         */
        showNotificationToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `nova-toast nova-toast-${type}`;
            toast.innerHTML = `
                <div class="nova-toast-content">
                    <span>${message}</span>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.add('nova-toast-show');
            }, 10);
            
            setTimeout(() => {
                toast.classList.remove('nova-toast-show');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
        
        /**
         * Inicializar el sistema de sonido
         */
        init() {
            // Solo configurar listeners si el contexto de audio no ha sido inicializado aún
            if (!this.isInitialized) {
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
            }
            
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
                disabled: false,
                audioConsent: null
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
            const i18n = novaSoundFX.i18n || {};
            this.showNotification(i18n.preferencesSaved || '¡Preferencias guardadas!');
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
                
                // Agregar clases condicionales según opciones
                if (mapping.show_visual_effect) {
                    element.classList.add('nova-visual-enabled');
                }
                if (mapping.show_speaker_icon) {
                    element.classList.add('nova-speaker-enabled');
                }
                
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
                
                // No agregar estilos CSS automáticamente para evitar conflictos
                // Los desarrolladores pueden agregar sus propios estilos si lo desean
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
                
                // Agregar retroalimentación visual solo si está habilitada
                if (options.element && options.mapping && options.mapping.show_visual_effect) {
                    options.element.classList.add('nova-sound-playing');
                    
                    // Agregar clase específica del evento
                    if (options.mapping.event_type) {
                        options.element.classList.add(`nova-sound-playing-${options.mapping.event_type}`);
                    }
                    
                    source.onended = () => {
                        options.element.classList.remove('nova-sound-playing');
                        if (options.mapping.event_type) {
                            options.element.classList.remove(`nova-sound-playing-${options.mapping.event_type}`);
                        }
                    };
                }
                
                // Reproducir sonido
                source.start(0);
                
                // Mostrar indicador visual solo si está habilitado globalmente y en el mapeo
                if (this.settings.show_visual_feedback !== false && 
                    options.mapping && options.mapping.show_visual_effect) {
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
