/**
 * Nova Sound FX Admin JavaScript - Versión Reparada
 * Desarrollado por un experto WordPress con 12+ años de experiencia
 */
(function($) {
    'use strict';

    // Variables globales
    let mediaUploader;
    let currentAudioElement = null;
    let soundLibraryCache = [];

    // Inicializar cuando el documento esté listo
    $(document).ready(function() {
        initializeTabs();
        loadSoundLibrary();
        loadCSSMappings();
        loadTransitions();
        bindEvents();
        initializeVolumeSliders();
        initializeSettings();
    });

    /**
     * Inicializar navegación por pestañas
     */
    function initializeTabs() {
        $('.nav-tab').on('click', function(e) {
            e.preventDefault();
            
            const tabId = $(this).data('tab');
            
            // Actualizar pestaña activa
            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            
            // Mostrar contenido correspondiente
            $('.tab-pane').removeClass('active');
            $('#' + tabId).addClass('active');
            
            // Actualizar URL sin recargar
            const url = new URL(window.location);
            url.searchParams.set('tab', tabId);
            window.history.pushState({}, '', url);
            
            // Recargar datos cuando se cambia a la pestaña de mapeos CSS
            if (tabId === 'css-mapping') {
                loadCSSMappings();
            } else if (tabId === 'page-transitions') {
                loadTransitions();
            } else if (tabId === 'sound-library') {
                loadSoundLibrary();
            }
        });

        // Cargar pestaña desde URL
        const urlParams = new URLSearchParams(window.location.search);
        const activeTab = urlParams.get('tab');
        if (activeTab) {
            $('.nav-tab[data-tab="' + activeTab + '"]').click();
        }
    }

    /**
     * Cargar biblioteca de sonidos con actualización forzada
     */
    function loadSoundLibrary(forceRefresh = false) {
        $.ajax({
            url: nova_sound_fx_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'nova_sound_fx_get_sound_library',
                nonce: nova_sound_fx_admin.nonce,
                refresh: forceRefresh ? 1 : 0
            },
            success: function(response) {
                if (response.success) {
                    soundLibraryCache = response.data;
                    renderSoundLibrary(response.data);
                    populateSoundSelects(response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading sound library:', error);
                showToast('Error loading sound library', 'error');
            }
        });
    }

    /**
     * Renderizar grilla de biblioteca de sonidos
     */
    function renderSoundLibrary(sounds) {
        const $grid = $('#sound-library-grid');
        $grid.empty();

        if (sounds.length === 0) {
            $grid.html(`
                <div class="nova-empty-state">
                    <h3>No hay sonidos cargados aún</h3>
                    <p>Haz clic en "Subir Nuevo Sonido" para agregar tu primer efecto de sonido.</p>
                </div>
            `);
            return;
        }

        sounds.forEach(function(sound) {
            const $item = $(`
                <div class="sound-item" data-sound-id="${sound.id}">
                    <div class="sound-item-icon">
                        <span class="dashicons dashicons-format-audio"></span>
                    </div>
                    <div class="sound-item-info">
                        <div class="sound-item-title" title="${sound.title}">${sound.title}</div>
                        <div class="sound-item-meta">
                            <span class="sound-format">${sound.mime_type.split('/')[1].toUpperCase()}</span>
                            <span class="sound-date">${sound.date}</span>
                        </div>
                    </div>
                    <div class="sound-item-controls">
                        <button class="button button-small play-sound" data-url="${sound.url}" title="Reproducir">
                            <span class="dashicons dashicons-controls-play"></span>
                        </button>
                        <button class="button button-small copy-url" data-url="${sound.url}" title="Copiar URL">
                            <span class="dashicons dashicons-admin-links"></span>
                        </button>
                        <button class="button button-small delete-sound" data-id="${sound.id}" title="Eliminar">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                </div>
            `);
            $grid.append($item);
        });
    }

    /**
     * Poblar selectores de sonido
     */
    function populateSoundSelects(sounds) {
        $('.sound-select').each(function() {
            const $select = $(this);
            const currentValue = $select.val();
            
            $select.empty();
            $select.append('<option value="">— Seleccionar sonido —</option>');
            
            // Agrupar por tipo
            const audioGroups = {
                'audio/mpeg': 'MP3',
                'audio/wav': 'WAV',
                'audio/x-wav': 'WAV',
                'audio/wave': 'WAV'
            };

            const grouped = {};
            sounds.forEach(function(sound) {
                const group = audioGroups[sound.mime_type] || 'Otros';
                if (!grouped[group]) grouped[group] = [];
                grouped[group].push(sound);
            });

            // Agregar opciones agrupadas
            Object.keys(grouped).forEach(function(group) {
                if (grouped[group].length > 0) {
                    $select.append(`<optgroup label="${group}">`);
                    grouped[group].forEach(function(sound) {
                        $select.append(`<option value="${sound.id}" data-url="${sound.url}">${sound.title}</option>`);
                    });
                    $select.append('</optgroup>');
                }
            });
            
            // Restaurar valor seleccionado
            if (currentValue) {
                $select.val(currentValue);
            }
        });
    }

    /**
     * Cargar mapeos CSS desde la base de datos
     */
    function loadCSSMappings() {
        console.log('Cargando mapeos CSS...');
        $.ajax({
            url: nova_sound_fx_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'nova_sound_fx_get_css_mappings',
                nonce: nova_sound_fx_admin.nonce
            },
            success: function(response) {
                console.log('Mapeos CSS recibidos:', response);
                if (response.success) {
                    renderCSSMappings(response.data);
                } else {
                    console.error('Error al cargar mapeos:', response);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error AJAX al cargar mapeos:', error);
            }
        });
    }

    /**
     * Renderizar tabla de mapeos CSS
     */
    function renderCSSMappings(mappings) {
        const $tbody = $('#css-mappings-list');
        $tbody.empty();

        if (!mappings || mappings.length === 0) {
            $tbody.html(`
                <tr>
                    <td colspan="6" style="text-align: center; padding: 40px;">
                        <p>No hay mapeos CSS configurados aún.</p>
                        <p>Haz clic en "Agregar Nuevo Mapeo" para comenzar.</p>
                    </td>
                </tr>
            `);
            return;
        }

        mappings.forEach(function(mapping) {
            const eventLabels = {
                'hover': 'Hover',
                'click': 'Click',
                'focus': 'Focus',
                'blur': 'Blur',
                'mouseenter': 'Mouse Enter',
                'mouseleave': 'Mouse Leave',
                'mousedown': 'Mouse Down',
                'mouseup': 'Mouse Up',
                'active': 'Active (Click sostenido)'
            };

            const $row = $(`
                <tr data-mapping-id="${mapping.id}">
                    <td>
                        <code class="css-selector-display">${mapping.css_selector}</code>
                        <span class="selector-type">${getSelectorType(mapping.css_selector)}</span>
                    </td>
                    <td>
                        <span class="event-badge event-${mapping.event_type}">${eventLabels[mapping.event_type] || mapping.event_type}</span>
                    </td>
                    <td>
                        <span class="sound-name">${mapping.sound_title || 'Sonido no encontrado'}</span>
                        ${mapping.sound_url ? '<button class="button-link preview-sound" data-url="' + mapping.sound_url + '">Previsualizar</button>' : ''}
                    </td>
                    <td>
                        <div class="volume-display">
                            <span class="dashicons dashicons-megaphone"></span>
                            <span>${mapping.volume}%</span>
                        </div>
                    </td>
                    <td>${mapping.delay}ms</td>
                    <td class="column-actions">
                        <div class="nova-action-buttons">
                            <button class="button button-small edit-mapping" data-mapping='${JSON.stringify(mapping)}'>Editar</button>
                            <button class="button button-small button-link-delete delete-mapping">Eliminar</button>
                        </div>
                    </td>
                </tr>
            `);
            $tbody.append($row);
        });
    }

    /**
     * Detectar tipo de selector (clase, ID, elemento)
     */
    function getSelectorType(selector) {
        if (selector.startsWith('#')) return 'ID';
        if (selector.startsWith('.')) return 'Clase';
        if (selector.includes('[')) return 'Atributo';
        return 'Elemento';
    }

    /**
     * Cargar transiciones
     */
    function loadTransitions() {
        $.ajax({
            url: nova_sound_fx_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'nova_sound_fx_get_transitions',
                nonce: nova_sound_fx_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderTransitions(response.data);
                }
            }
        });
    }

    /**
     * Renderizar tabla de transiciones
     */
    function renderTransitions(transitions) {
        const $tbody = $('#url-transitions-list');
        $tbody.empty();

        if (!transitions || transitions.length === 0) {
            $tbody.html(`
                <tr>
                    <td colspan="6" style="text-align: center; padding: 40px;">
                        No hay transiciones URL específicas configuradas aún.
                    </td>
                </tr>
            `);
            return;
        }

        transitions.forEach(function(transition) {
            let priorityClass = 'low';
            if (transition.priority >= 70) priorityClass = 'high';
            else if (transition.priority >= 40) priorityClass = 'medium';

            const transitionTypes = {
                'enter': 'Entrada de página',
                'exit': 'Salida de página',
                'both': 'Ambas'
            };

            const $row = $(`
                <tr data-transition-id="${transition.id}">
                    <td><code>${transition.url_pattern}</code></td>
                    <td>${transitionTypes[transition.transition_type] || transition.transition_type}</td>
                    <td>${transition.sound_title || 'Sonido no encontrado'}</td>
                    <td>${transition.volume}%</td>
                    <td><span class="priority-badge ${priorityClass}">${transition.priority}</span></td>
                    <td class="column-actions">
                        <div class="nova-action-buttons">
                            <button class="button button-small edit-transition" data-transition='${JSON.stringify(transition)}'>Editar</button>
                            <button class="button button-small button-link-delete delete-transition">Eliminar</button>
                        </div>
                    </td>
                </tr>
            `);
            $tbody.append($row);
        });
    }

    /**
     * Inicializar configuraciones
     */
    function initializeSettings() {
        // Cargar configuraciones actuales
        $.ajax({
            url: nova_sound_fx_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'nova_sound_fx_get_settings',
                nonce: nova_sound_fx_admin.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    const settings = response.data;
                    $('#enable-sounds').prop('checked', settings.enable_sounds);
                    $('#default-volume').val(settings.default_volume);
                    $('#mobile-enabled').prop('checked', settings.mobile_enabled);
                    $('#respect-prefers-reduced-motion').prop('checked', settings.respect_prefers_reduced_motion);
                    $('#preview-mode').prop('checked', settings.preview_mode);
                    
                    // Actualizar visualización de volumen
                    $('#default-volume').siblings('.volume-value').text(settings.default_volume + '%');
                }
            }
        });
    }

    /**
     * Vincular eventos
     */
    function bindEvents() {
        // Botón de subir sonido
        $('#nova-upload-sound').on('click', function(e) {
            e.preventDefault();
            openMediaUploader();
        });

        // Botones de reproducir sonido
        $(document).on('click', '.play-sound, .preview-sound', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const url = $(this).data('url');
            const $button = $(this);
            
            if (currentAudioElement && !currentAudioElement.paused) {
                currentAudioElement.pause();
                $('.play-sound, .preview-sound').find('.dashicons').removeClass('dashicons-controls-pause').addClass('dashicons-controls-play');
            }
            
            if ($button.find('.dashicons').hasClass('dashicons-controls-play')) {
                playSound(url);
                $button.find('.dashicons').removeClass('dashicons-controls-play').addClass('dashicons-controls-pause');
            }
        });

        // Copiar URL
        $(document).on('click', '.copy-url', function(e) {
            e.preventDefault();
            const url = $(this).data('url');
            copyToClipboard(url);
            showToast('URL copiada al portapapeles', 'success');
        });

        // Eliminar sonido
        $(document).on('click', '.delete-sound', function(e) {
            e.preventDefault();
            if (confirm('¿Estás seguro de que deseas eliminar este sonido? Los mapeos asociados quedarán sin sonido.')) {
                const soundId = $(this).data('id');
                // Aquí iría la lógica para eliminar el sonido de la biblioteca de medios
                showToast('Función en desarrollo', 'info');
            }
        });

        // Agregar mapeo CSS
        $('#add-css-mapping').on('click', function(e) {
            e.preventDefault();
            openCSSMappingModal();
        });

        // Editar mapeo CSS
        $(document).on('click', '.edit-mapping', function(e) {
            e.preventDefault();
            const mapping = $(this).data('mapping');
            openCSSMappingModal(mapping);
        });

        // Agregar transición URL
        $('#add-url-transition').on('click', function(e) {
            e.preventDefault();
            openURLTransitionModal();
        });

        // Editar transición
        $(document).on('click', '.edit-transition', function(e) {
            e.preventDefault();
            const transition = $(this).data('transition');
            openURLTransitionModal(transition);
        });

        // Cerrar modales
        $('.nova-modal-close, .nova-modal-cancel').on('click', function() {
            $(this).closest('.nova-modal').fadeOut();
        });

        // Click en fondo del modal
        $('.nova-modal').on('click', function(e) {
            if (e.target === this) {
                $(this).fadeOut();
            }
        });

        // Envío de formulario de mapeo CSS
        $('#css-mapping-form').on('submit', function(e) {
            e.preventDefault();
            saveCSSMapping();
        });

        // Envío de formulario de transición URL
        $('#url-transition-form').on('submit', function(e) {
            e.preventDefault();
            saveURLTransition();
        });

        // Eliminar mapeo CSS
        $(document).on('click', '.delete-mapping', function(e) {
            e.preventDefault();
            if (confirm(nova_sound_fx_admin.strings.confirm_delete)) {
                const id = $(this).closest('tr').data('mapping-id');
                deleteCSSMapping(id);
            }
        });

        // Eliminar transición
        $(document).on('click', '.delete-transition', function(e) {
            e.preventDefault();
            if (confirm(nova_sound_fx_admin.strings.confirm_delete)) {
                const id = $(this).closest('tr').data('transition-id');
                deleteTransition(id);
            }
        });

        // Vista previa de sonidos en modales
        $('#preview-mapping-sound, #preview-transition-sound').on('click', function(e) {
            e.preventDefault();
            const $select = $(this).siblings('select');
            const url = $select.find('option:selected').data('url');
            if (url) {
                playSound(url);
            }
        });

        // Validación de selector CSS
        $('#css-selector').on('input', function() {
            const selector = $(this).val();
            validateCSSSelector(selector);
            updateSelectorPreview(selector);
        });

        // Tipo de selector (clase o ID)
        $('input[name="selector-type"]').on('change', function() {
            updateSelectorHelper();
        });

        // Envío de formulario de configuración
        $('#nova-sound-fx-settings').on('submit', function(e) {
            e.preventDefault();
            saveSettings();
        });

        // Filtro de categoría de sonido
        $('#sound-category-filter').on('change', function() {
            filterSoundLibrary($(this).val());
        });

        // Botón de actualizar biblioteca
        $('#refresh-sound-library').on('click', function(e) {
            e.preventDefault();
            loadSoundLibrary(true);
        });

        // Evento de audio terminado
        if (currentAudioElement) {
            currentAudioElement.addEventListener('ended', function() {
                $('.play-sound, .preview-sound').find('.dashicons').removeClass('dashicons-controls-pause').addClass('dashicons-controls-play');
            });
        }
    }

    /**
     * Inicializar sliders de volumen
     */
    function initializeVolumeSliders() {
        $('input[type="range"]').each(function() {
            const $slider = $(this);
            const $value = $slider.siblings('.volume-value');
            
            $slider.on('input', function() {
                $value.text($(this).val() + '%');
            });
        });
    }

    /**
     * Abrir cargador de medios
     */
    function openMediaUploader() {
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        mediaUploader = wp.media({
            title: 'Seleccionar o Subir Efecto de Sonido',
            button: {
                text: 'Usar este sonido'
            },
            library: {
                type: ['audio']
            },
            multiple: false
        });

        mediaUploader.on('select', function() {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            
            // Recargar biblioteca de sonidos después de subir
            setTimeout(function() {
                loadSoundLibrary(true);
                showToast('¡Sonido subido exitosamente!', 'success');
            }, 500);
        });

        mediaUploader.open();
    }

    /**
     * Reproducir sonido
     */
    function playSound(url) {
        if (currentAudioElement) {
            currentAudioElement.pause();
            currentAudioElement.currentTime = 0;
        }

        currentAudioElement = new Audio(url);
        currentAudioElement.volume = 0.5;
        
        currentAudioElement.addEventListener('ended', function() {
            $('.play-sound, .preview-sound').find('.dashicons').removeClass('dashicons-controls-pause').addClass('dashicons-controls-play');
        });
        
        currentAudioElement.play().catch(function(error) {
            console.error('Error al reproducir sonido:', error);
            showToast('Error al reproducir el sonido', 'error');
        });
    }

    /**
     * Abrir modal de mapeo CSS
     */
    function openCSSMappingModal(mapping = null) {
        const $modal = $('#css-mapping-modal');
        
        // Resetear formulario
        $('#css-mapping-form')[0].reset();
        
        if (mapping) {
            // Modo edición
            $('#mapping-id').val(mapping.id);
            $('#css-selector').val(mapping.css_selector);
            $('#event-type').val(mapping.event_type);
            $('#mapping-sound').val(mapping.sound_id);
            $('#mapping-volume').val(mapping.volume);
            $('#mapping-delay').val(mapping.delay);
            $('#show-visual-effect').prop('checked', mapping.show_visual_effect == 1);
            $('#show-speaker-icon').prop('checked', mapping.show_speaker_icon == 1);
            
            // Actualizar visualizaciones
            $('#mapping-volume').siblings('.volume-value').text(mapping.volume + '%');
            validateCSSSelector(mapping.css_selector);
            updateSelectorPreview(mapping.css_selector);
        } else {
            // Modo creación
            $('#mapping-id').val('');
            $('#mapping-volume').val(100);
            $('#mapping-volume').siblings('.volume-value').text('100%');
            $('#show-visual-effect').prop('checked', true);
            $('#show-speaker-icon').prop('checked', true);
        }
        
        $modal.fadeIn();
    }

    /**
     * Abrir modal de transición URL
     */
    function openURLTransitionModal(transition = null) {
        const $modal = $('#url-transition-modal');
        
        // Resetear formulario
        $('#url-transition-form')[0].reset();
        
        if (transition) {
            // Modo edición
            $('#transition-id').val(transition.id);
            $('#url-pattern').val(transition.url_pattern);
            $('#transition-type').val(transition.transition_type);
            $('#transition-sound').val(transition.sound_id);
            $('#transition-volume').val(transition.volume);
            $('#transition-priority').val(transition.priority);
            
            // Actualizar visualizaciones
            $('#transition-volume').siblings('.volume-value').text(transition.volume + '%');
        } else {
            // Modo creación
            $('#transition-id').val('');
            $('#transition-volume').val(100);
            $('#transition-priority').val(50);
            $('#transition-volume').siblings('.volume-value').text('100%');
        }
        
        $modal.fadeIn();
    }

    /**
     * Validar selector CSS
     */
    function validateCSSSelector(selector) {
        const $input = $('#css-selector');
        const $feedback = $('#css-selector-feedback');
        
        if (!selector) {
            $feedback.html('<span class="nova-form-info">Ingresa una clase CSS (.ejemplo) o ID (#ejemplo)</span>');
            $input.removeClass('valid invalid');
            return false;
        }

        // Validar que sea clase o ID
        const isValidFormat = /^[#.][\w-]+(\s*,\s*[#.][\w-]+)*$/.test(selector);
        
        if (!isValidFormat) {
            $feedback.html('<span class="nova-form-error">Usa solo clases (.clase) o IDs (#id)</span>');
            $input.removeClass('valid').addClass('invalid');
            return false;
        }

        try {
            // Intentar usar el selector
            $(selector);
            $feedback.html('<span class="nova-form-success">Selector CSS válido</span>');
            $input.removeClass('invalid').addClass('valid');
            return true;
        } catch (e) {
            $feedback.html('<span class="nova-form-error">Selector CSS inválido</span>');
            $input.removeClass('valid').addClass('invalid');
            return false;
        }
    }

    /**
     * Actualizar vista previa del selector
     */
    function updateSelectorPreview(selector) {
        const $preview = $('#selector-preview');
        
        if (!selector) {
            $preview.hide();
            return;
        }
        
        const elements = $(selector).length;
        let message = '';
        
        if (elements > 0) {
            message = `Se encontraron ${elements} elemento(s) con este selector en la página actual.`;
        } else {
            message = 'No se encontraron elementos con este selector en la página actual.';
        }
        
        $preview.html(`<div class="nova-preview-info">${message}</div>`).show();
    }

    /**
     * Actualizar ayuda del selector
     */
    function updateSelectorHelper() {
        const type = $('input[name="selector-type"]:checked').val();
        const $helper = $('#selector-helper');
        
        if (type === 'class') {
            $helper.text('Ejemplo: .mi-boton, .header-link');
        } else {
            $helper.text('Ejemplo: #mi-id, #formulario-contacto');
        }
    }

    /**
     * Guardar mapeo CSS
     */
    function saveCSSMapping() {
        const selector = $('#css-selector').val();
        
        // Validar selector
        if (!validateCSSSelector(selector)) {
            showToast('Por favor ingresa un selector CSS válido', 'error');
            return;
        }
        
        const data = {
            action: 'nova_sound_fx_save_css_mapping',
            nonce: nova_sound_fx_admin.nonce,
            id: $('#mapping-id').val(),
            css_selector: selector,
            event_type: $('#event-type').val(),
            sound_id: $('#mapping-sound').val(),
            volume: $('#mapping-volume').val(),
            delay: $('#mapping-delay').val(),
            show_visual_effect: $('#show-visual-effect').is(':checked') ? 1 : 0,
            show_speaker_icon: $('#show-speaker-icon').is(':checked') ? 1 : 0
        };

        // Validar campos requeridos
        if (!data.sound_id) {
            showToast('Por favor selecciona un sonido', 'error');
            return;
        }

        $.ajax({
            url: nova_sound_fx_admin.ajax_url,
            type: 'POST',
            data: data,
            beforeSend: function() {
                $('#css-mapping-form button[type="submit"]').prop('disabled', true).text('Guardando...');
            },
            success: function(response) {
                if (response.success) {
                    $('#css-mapping-modal').fadeOut();
                    loadCSSMappings();
                    showToast(response.data.message, 'success');
                } else {
                    showToast(response.data.message || nova_sound_fx_admin.strings.error, 'error');
                }
            },
            error: function() {
                showToast(nova_sound_fx_admin.strings.error, 'error');
            },
            complete: function() {
                $('#css-mapping-form button[type="submit"]').prop('disabled', false).text('Guardar Mapeo');
            }
        });
    }

    /**
     * Guardar transición URL
     */
    function saveURLTransition() {
        const data = {
            action: 'nova_sound_fx_save_transition',
            nonce: nova_sound_fx_admin.nonce,
            id: $('#transition-id').val(),
            url_pattern: $('#url-pattern').val(),
            transition_type: $('#transition-type').val(),
            sound_id: $('#transition-sound').val(),
            volume: $('#transition-volume').val(),
            priority: $('#transition-priority').val()
        };

        // Validar campos requeridos
        if (!data.url_pattern) {
            showToast('Por favor ingresa un patrón de URL', 'error');
            return;
        }
        
        if (!data.sound_id) {
            showToast('Por favor selecciona un sonido', 'error');
            return;
        }

        $.ajax({
            url: nova_sound_fx_admin.ajax_url,
            type: 'POST',
            data: data,
            beforeSend: function() {
                $('#url-transition-form button[type="submit"]').prop('disabled', true).text('Guardando...');
            },
            success: function(response) {
                if (response.success) {
                    $('#url-transition-modal').fadeOut();
                    loadTransitions();
                    showToast(response.data.message, 'success');
                } else {
                    showToast(response.data.message || nova_sound_fx_admin.strings.error, 'error');
                }
            },
            error: function() {
                showToast(nova_sound_fx_admin.strings.error, 'error');
            },
            complete: function() {
                $('#url-transition-form button[type="submit"]').prop('disabled', false).text('Guardar Transición');
            }
        });
    }

    /**
     * Eliminar mapeo CSS
     */
    function deleteCSSMapping(id) {
        $.ajax({
            url: nova_sound_fx_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'nova_sound_fx_delete_css_mapping',
                nonce: nova_sound_fx_admin.nonce,
                id: id
            },
            beforeSend: function() {
                showToast('Eliminando...', 'info');
            },
            success: function(response) {
                if (response.success) {
                    loadCSSMappings();
                    showToast(response.data.message, 'success');
                } else {
                    showToast(response.data.message || nova_sound_fx_admin.strings.error, 'error');
                }
            }
        });
    }

    /**
     * Eliminar transición
     */
    function deleteTransition(id) {
        $.ajax({
            url: nova_sound_fx_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'nova_sound_fx_delete_transition',
                nonce: nova_sound_fx_admin.nonce,
                id: id
            },
            beforeSend: function() {
                showToast('Eliminando...', 'info');
            },
            success: function(response) {
                if (response.success) {
                    loadTransitions();
                    showToast(response.data.message, 'success');
                } else {
                    showToast(response.data.message || nova_sound_fx_admin.strings.error, 'error');
                }
            }
        });
    }

    /**
     * Guardar configuraciones
     */
    function saveSettings() {
        const formData = $('#nova-sound-fx-settings').serialize();
        
        $.ajax({
            url: nova_sound_fx_admin.ajax_url,
            type: 'POST',
            data: formData + '&action=nova_sound_fx_save_settings&nonce=' + nova_sound_fx_admin.nonce,
            beforeSend: function() {
                $('#nova-sound-fx-settings button[type="submit"]').prop('disabled', true).text('Guardando...');
            },
            success: function(response) {
                if (response.success) {
                    showToast(response.data.message || nova_sound_fx_admin.strings.saved, 'success');
                } else {
                    showToast(response.data.message || nova_sound_fx_admin.strings.error, 'error');
                }
            },
            complete: function() {
                $('#nova-sound-fx-settings button[type="submit"]').prop('disabled', false).text('Guardar Configuración');
            }
        });
    }

    /**
     * Filtrar biblioteca de sonidos
     */
    function filterSoundLibrary(category) {
        // Implementación futura para filtrar por categorías
        console.log('Filtrando por categoría:', category);
    }

    /**
     * Copiar al portapapeles
     */
    function copyToClipboard(text) {
        const temp = $('<input>');
        $('body').append(temp);
        temp.val(text).select();
        document.execCommand('copy');
        temp.remove();
    }

    /**
     * Mostrar notificación toast
     */
    function showToast(message, type = 'info') {
        const $toast = $(`<div class="nova-toast nova-toast-${type}">
            <span class="dashicons dashicons-${getToastIcon(type)}"></span>
            <span>${message}</span>
        </div>`);
        
        $('body').append($toast);
        
        // Animar entrada
        setTimeout(function() {
            $toast.addClass('nova-toast-show');
        }, 10);
        
        // Remover después de 3 segundos
        setTimeout(function() {
            $toast.removeClass('nova-toast-show');
            setTimeout(function() {
                $toast.remove();
            }, 300);
        }, 3000);
    }

    /**
     * Obtener icono para toast
     */
    function getToastIcon(type) {
        const icons = {
            'success': 'yes-alt',
            'error': 'dismiss',
            'warning': 'warning',
            'info': 'info-outline'
        };
        return icons[type] || icons.info;
    }

})(jQuery);
