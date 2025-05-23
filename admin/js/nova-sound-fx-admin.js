/**
 * Nova Sound FX Admin JavaScript
 */
(function($) {
    'use strict';

    // Global variables
    let mediaUploader;
    let currentAudioElement = null;

    // Initialize when document is ready
    $(document).ready(function() {
        initializeTabs();
        loadSoundLibrary();
        loadCSSMappings();
        loadTransitions();
        bindEvents();
        initializeVolumeSliders();
    });

    /**
     * Initialize tab navigation
     */
    function initializeTabs() {
        $('.nav-tab').on('click', function(e) {
            e.preventDefault();
            
            const tabId = $(this).data('tab');
            
            // Update active tab
            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            
            // Show corresponding content
            $('.tab-pane').removeClass('active');
            $('#' + tabId).addClass('active');
            
            // Update URL without reload
            const url = new URL(window.location);
            url.searchParams.set('tab', tabId);
            window.history.pushState({}, '', url);
        });

        // Load tab from URL
        const urlParams = new URLSearchParams(window.location.search);
        const activeTab = urlParams.get('tab');
        if (activeTab) {
            $('.nav-tab[data-tab="' + activeTab + '"]').click();
        }
    }

    /**
     * Load sound library
     */
    function loadSoundLibrary() {
        $.ajax({
            url: nova_sound_fx_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'nova_sound_fx_get_sound_library',
                nonce: nova_sound_fx_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderSoundLibrary(response.data);
                    populateSoundSelects(response.data);
                }
            }
        });
    }

    /**
     * Render sound library grid
     */
    function renderSoundLibrary(sounds) {
        const $grid = $('#sound-library-grid');
        $grid.empty();

        if (sounds.length === 0) {
            $grid.html(`
                <div class="nova-empty-state">
                    <h3>No sounds uploaded yet</h3>
                    <p>Click "Upload New Sound" to add your first sound effect.</p>
                </div>
            `);
            return;
        }

        sounds.forEach(function(sound) {
            const $item = $(`
                <div class="sound-item" data-sound-id="${sound.id}">
                    <div class="sound-item-icon">🎵</div>
                    <div class="sound-item-title" title="${sound.title}">${sound.title}</div>
                    <div class="sound-item-controls">
                        <button class="button button-small play-sound" data-url="${sound.url}">Play</button>
                        <button class="button button-small edit-sound">Edit</button>
                    </div>
                    <button class="sound-item-delete" title="Delete">×</button>
                </div>
            `);
            $grid.append($item);
        });
    }

    /**
     * Populate sound select dropdowns
     */
    function populateSoundSelects(sounds) {
        const $selects = $('.sound-select');
        
        $selects.each(function() {
            const $select = $(this);
            const currentValue = $select.val();
            
            $select.empty();
            $select.append('<option value="">None</option>');
            
            sounds.forEach(function(sound) {
                $select.append(`<option value="${sound.id}" data-url="${sound.url}">${sound.title}</option>`);
            });
            
            if (currentValue) {
                $select.val(currentValue);
            }
        });
    }

    /**
     * Load CSS mappings
     */
    function loadCSSMappings() {
        // This would normally load from database via AJAX
        // For now, we'll use the data embedded in the page
        if (window.NovaSoundFXData && window.NovaSoundFXData.cssMappings) {
            renderCSSMappings(window.NovaSoundFXData.cssMappings);
        }
    }

    /**
     * Render CSS mappings table
     */
    function renderCSSMappings(mappings) {
        const $tbody = $('#css-mappings-list');
        $tbody.empty();

        if (!mappings || mappings.length === 0) {
            $tbody.html(`
                <tr>
                    <td colspan="6" style="text-align: center; padding: 40px;">
                        No CSS mappings configured yet. Click "Add New Mapping" to get started.
                    </td>
                </tr>
            `);
            return;
        }

        mappings.forEach(function(mapping) {
            const $row = $(`
                <tr data-mapping-id="${mapping.id}">
                    <td><code>${mapping.css_selector}</code></td>
                    <td>${mapping.event_type}</td>
                    <td>${mapping.sound_title || 'Unknown'}</td>
                    <td>${mapping.volume}%</td>
                    <td>${mapping.delay}ms</td>
                    <td class="column-actions">
                        <div class="nova-action-buttons">
                            <button class="button button-small edit-mapping">Edit</button>
                            <button class="button button-small button-link-delete delete-mapping">Delete</button>
                        </div>
                    </td>
                </tr>
            `);
            $tbody.append($row);
        });
    }

    /**
     * Load transitions
     */
    function loadTransitions() {
        // This would normally load from database via AJAX
        // For now, we'll use the data embedded in the page
        if (window.NovaSoundFXData && window.NovaSoundFXData.transitions) {
            renderTransitions(window.NovaSoundFXData.transitions);
        }
    }

    /**
     * Render transitions table
     */
    function renderTransitions(transitions) {
        const $tbody = $('#url-transitions-list');
        $tbody.empty();

        if (!transitions || transitions.length === 0) {
            $tbody.html(`
                <tr>
                    <td colspan="6" style="text-align: center; padding: 40px;">
                        No URL-specific transitions configured yet.
                    </td>
                </tr>
            `);
            return;
        }

        transitions.forEach(function(transition) {
            let priorityClass = 'low';
            if (transition.priority >= 70) priorityClass = 'high';
            else if (transition.priority >= 40) priorityClass = 'medium';

            const $row = $(`
                <tr data-transition-id="${transition.id}">
                    <td><code>${transition.url_pattern}</code></td>
                    <td>${transition.transition_type}</td>
                    <td>${transition.sound_title || 'Unknown'}</td>
                    <td>${transition.volume}%</td>
                    <td><span class="priority-badge ${priorityClass}">${transition.priority}</span></td>
                    <td class="column-actions">
                        <div class="nova-action-buttons">
                            <button class="button button-small edit-transition">Edit</button>
                            <button class="button button-small button-link-delete delete-transition">Delete</button>
                        </div>
                    </td>
                </tr>
            `);
            $tbody.append($row);
        });
    }

    /**
     * Bind events
     */
    function bindEvents() {
        // Upload sound button
        $('#nova-upload-sound').on('click', function(e) {
            e.preventDefault();
            openMediaUploader();
        });

        // Play sound buttons
        $(document).on('click', '.play-sound', function(e) {
            e.preventDefault();
            const url = $(this).data('url');
            playSound(url);
        });

        // Add CSS mapping
        $('#add-css-mapping').on('click', function(e) {
            e.preventDefault();
            openCSSMappingModal();
        });

        // Add URL transition
        $('#add-url-transition').on('click', function(e) {
            e.preventDefault();
            openURLTransitionModal();
        });

        // Modal close buttons
        $('.nova-modal-close, .nova-modal-cancel').on('click', function() {
            $(this).closest('.nova-modal').fadeOut();
        });

        // Modal background click
        $('.nova-modal').on('click', function(e) {
            if (e.target === this) {
                $(this).fadeOut();
            }
        });

        // CSS mapping form submit
        $('#css-mapping-form').on('submit', function(e) {
            e.preventDefault();
            saveCSSMapping();
        });

        // URL transition form submit
        $('#url-transition-form').on('submit', function(e) {
            e.preventDefault();
            saveURLTransition();
        });

        // Delete CSS mapping
        $(document).on('click', '.delete-mapping', function(e) {
            e.preventDefault();
            if (confirm(nova_sound_fx_admin.strings.confirm_delete)) {
                const id = $(this).closest('tr').data('mapping-id');
                deleteCSSMapping(id);
            }
        });

        // Delete transition
        $(document).on('click', '.delete-transition', function(e) {
            e.preventDefault();
            if (confirm(nova_sound_fx_admin.strings.confirm_delete)) {
                const id = $(this).closest('tr').data('transition-id');
                deleteTransition(id);
            }
        });

        // Preview sounds in modals
        $('#preview-mapping-sound, #preview-transition-sound').on('click', function(e) {
            e.preventDefault();
            const $select = $(this).siblings('select');
            const url = $select.find('option:selected').data('url');
            if (url) {
                playSound(url);
            }
        });

        // CSS selector validation
        $('#css-selector').on('input', function() {
            validateCSSSelector($(this).val());
        });

        // Settings form submit
        $('#nova-sound-fx-settings').on('submit', function(e) {
            e.preventDefault();
            saveSettings();
        });

        // Sound category filter
        $('#sound-category-filter').on('change', function() {
            filterSoundLibrary($(this).val());
        });
    }

    /**
     * Initialize volume sliders
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
     * Open media uploader
     */
    function openMediaUploader() {
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        mediaUploader = wp.media({
            title: 'Select or Upload Sound Effect',
            button: {
                text: 'Use this sound'
            },
            library: {
                type: ['audio']
            },
            multiple: false
        });

        mediaUploader.on('select', function() {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            // Reload sound library after upload
            loadSoundLibrary();
            showToast('Sound uploaded successfully!', 'success');
        });

        mediaUploader.open();
    }

    /**
     * Play sound
     */
    function playSound(url) {
        if (currentAudioElement) {
            currentAudioElement.pause();
        }

        currentAudioElement = new Audio(url);
        currentAudioElement.volume = 0.5;
        currentAudioElement.play();
    }

    /**
     * Open CSS mapping modal
     */
    function openCSSMappingModal(mappingId = null) {
        const $modal = $('#css-mapping-modal');
        
        // Reset form
        $('#css-mapping-form')[0].reset();
        $('#mapping-id').val(mappingId || '');
        
        // Load sound options
        loadSoundLibrary();
        
        $modal.fadeIn();
    }

    /**
     * Open URL transition modal
     */
    function openURLTransitionModal(transitionId = null) {
        const $modal = $('#url-transition-modal');
        
        // Reset form
        $('#url-transition-form')[0].reset();
        $('#transition-id').val(transitionId || '');
        
        // Load sound options
        loadSoundLibrary();
        
        $modal.fadeIn();
    }

    /**
     * Validate CSS selector
     */
    function validateCSSSelector(selector) {
        const $feedback = $('.css-selector-feedback');
        
        if (!selector) {
            $feedback.remove();
            return;
        }

        try {
            document.querySelector(selector);
            if ($feedback.length === 0) {
                $('#css-selector').after('<div class="css-selector-feedback valid">Valid CSS selector</div>');
            } else {
                $feedback.removeClass('invalid').addClass('valid').text('Valid CSS selector');
            }
        } catch (e) {
            if ($feedback.length === 0) {
                $('#css-selector').after('<div class="css-selector-feedback invalid">Invalid CSS selector</div>');
            } else {
                $feedback.removeClass('valid').addClass('invalid').text('Invalid CSS selector');
            }
        }
    }

    /**
     * Save CSS mapping
     */
    function saveCSSMapping() {
        const data = {
            action: 'nova_sound_fx_save_css_mapping',
            nonce: nova_sound_fx_admin.nonce,
            css_selector: $('#css-selector').val(),
            event_type: $('#event-type').val(),
            sound_id: $('#mapping-sound').val(),
            volume: $('#mapping-volume').val(),
            delay: $('#mapping-delay').val()
        };

        $.ajax({
            url: nova_sound_fx_admin.ajax_url,
            type: 'POST',
            data: data,
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
            }
        });
    }

    /**
     * Save URL transition
     */
    function saveURLTransition() {
        const data = {
            action: 'nova_sound_fx_save_transition',
            nonce: nova_sound_fx_admin.nonce,
            url_pattern: $('#url-pattern').val(),
            transition_type: $('#transition-type').val(),
            sound_id: $('#transition-sound').val(),
            volume: $('#transition-volume').val(),
            priority: $('#transition-priority').val()
        };

        $.ajax({
            url: nova_sound_fx_admin.ajax_url,
            type: 'POST',
            data: data,
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
            }
        });
    }

    /**
     * Delete CSS mapping
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
     * Delete transition
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
     * Save settings
     */
    function saveSettings() {
        const formData = $('#nova-sound-fx-settings').serialize();
        
        $.ajax({
            url: nova_sound_fx_admin.ajax_url,
            type: 'POST',
            data: formData + '&action=nova_sound_fx_save_settings&nonce=' + nova_sound_fx_admin.nonce,
            success: function(response) {
                if (response.success) {
                    showToast(response.data.message || nova_sound_fx_admin.strings.saved, 'success');
                } else {
                    showToast(response.data.message || nova_sound_fx_admin.strings.error, 'error');
                }
            }
        });
    }

    /**
     * Filter sound library
     */
    function filterSoundLibrary(category) {
        // This would be implemented with actual filtering logic
        console.log('Filtering by category:', category);
    }

    /**
     * Show toast notification
     */
    function showToast(message, type = 'info') {
        const $toast = $(`<div class="nova-toast ${type}">${message}</div>`);
        $('body').append($toast);
        
        setTimeout(function() {
            $toast.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }

})(jQuery);
