<?php
/**
 * Admin functionality - Versión Mejorada
 * Con correcciones para mostrar audios y evitar conflictos de URL
 */
class Nova_Sound_FX_Admin {
    
    private $version;
    
    public function __construct($version) {
        $this->version = $version;
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Nova Sound FX', 'nova-sound-fx'),
            __('Nova Sound FX', 'nova-sound-fx'),
            'manage_options',
            'nova-sound-fx',
            array($this, 'render_admin_page'),
            'dashicons-format-audio',
            30
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        ?>
        <div class="wrap nova-sound-fx-admin">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="nav-tab-wrapper">
                <a href="#sound-library" class="nav-tab nav-tab-active" data-tab="sound-library">
                    <?php _e('Biblioteca de Sonidos', 'nova-sound-fx'); ?>
                </a>
                <a href="#css-mapping" class="nav-tab" data-tab="css-mapping">
                    <?php _e('Mapeo de Sonidos CSS', 'nova-sound-fx'); ?>
                </a>
                <a href="#page-transitions" class="nav-tab" data-tab="page-transitions">
                    <?php _e('Transiciones de Página', 'nova-sound-fx'); ?>
                </a>
                <a href="#settings" class="nav-tab" data-tab="settings">
                    <?php _e('Configuración', 'nova-sound-fx'); ?>
                </a>
            </div>
            
            <div class="tab-content">
                <!-- Sound Library Tab -->
                <div id="sound-library" class="tab-pane active">
                    <h2><?php _e('Biblioteca de Sonidos', 'nova-sound-fx'); ?></h2>
                    <p><?php _e('Sube y administra tus efectos de sonido. Formatos soportados: MP3, WAV', 'nova-sound-fx'); ?></p>
                    
                    <div class="sound-upload-section">
                        <button id="nova-upload-sound" class="button button-primary">
                            <span class="dashicons dashicons-upload"></span>
                            <?php _e('Subir Nuevo Sonido', 'nova-sound-fx'); ?>
                        </button>
                        
                        <button id="refresh-sound-library" class="button">
                            <span class="dashicons dashicons-update"></span>
                            <?php _e('Actualizar Biblioteca', 'nova-sound-fx'); ?>
                        </button>
                        
                        <div class="sound-categories">
                            <label><?php _e('Filtrar por categoría:', 'nova-sound-fx'); ?></label>
                            <select id="sound-category-filter">
                                <option value=""><?php _e('Todas las Categorías', 'nova-sound-fx'); ?></option>
                                <option value="hover"><?php _e('Efectos Hover', 'nova-sound-fx'); ?></option>
                                <option value="click"><?php _e('Efectos Click', 'nova-sound-fx'); ?></option>
                                <option value="transition"><?php _e('Transiciones de Página', 'nova-sound-fx'); ?></option>
                                <option value="notification"><?php _e('Notificaciones', 'nova-sound-fx'); ?></option>
                                <option value="ambient"><?php _e('Ambiente', 'nova-sound-fx'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <div id="sound-library-grid" class="sound-grid">
                        <!-- Los elementos de sonido se cargarán aquí vía AJAX -->
                    </div>
                </div>
                
                <!-- CSS Mapping Tab -->
                <div id="css-mapping" class="tab-pane">
                    <h2><?php _e('Mapeo de Sonidos CSS', 'nova-sound-fx'); ?></h2>
                    <p><?php _e('Asigna efectos de sonido a clases CSS e IDs HTML con diferentes eventos', 'nova-sound-fx'); ?></p>
                    
                    <div class="nova-info-box">
                        <span class="dashicons dashicons-info"></span>
                        <p><?php _e('Solo puedes usar selectores de clase (.ejemplo) o ID (#ejemplo). No se permiten selectores de elementos o atributos.', 'nova-sound-fx'); ?></p>
                    </div>
                    
                    <button id="add-css-mapping" class="button button-primary">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php _e('Agregar Nuevo Mapeo', 'nova-sound-fx'); ?>
                    </button>
                    
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 25%"><?php _e('Selector CSS', 'nova-sound-fx'); ?></th>
                                <th style="width: 15%"><?php _e('Evento', 'nova-sound-fx'); ?></th>
                                <th style="width: 25%"><?php _e('Sonido', 'nova-sound-fx'); ?></th>
                                <th style="width: 10%"><?php _e('Volumen', 'nova-sound-fx'); ?></th>
                                <th style="width: 10%"><?php _e('Retraso (ms)', 'nova-sound-fx'); ?></th>
                                <th style="width: 15%"><?php _e('Acciones', 'nova-sound-fx'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="css-mappings-list">
                            <!-- Los mapeos se cargarán aquí -->
                        </tbody>
                    </table>
                </div>
                
                <!-- Page Transitions Tab -->
                <div id="page-transitions" class="tab-pane">
                    <h2><?php _e('Transiciones de Página', 'nova-sound-fx'); ?></h2>
                    <p><?php _e('Configura sonidos para entradas y salidas de páginas', 'nova-sound-fx'); ?></p>
                    
                    <div class="transition-settings">
                        <h3><?php _e('Transiciones Globales', 'nova-sound-fx'); ?></h3>
                        <div class="global-transitions">
                            <div class="transition-row">
                                <label><?php _e('Sonido de Entrada de Página por Defecto:', 'nova-sound-fx'); ?></label>
                                <select id="global-entry-sound" class="sound-select">
                                    <option value=""><?php _e('Ninguno', 'nova-sound-fx'); ?></option>
                                </select>
                                <input type="range" id="global-entry-volume" min="0" max="100" value="50">
                                <span class="volume-value">50%</span>
                            </div>
                            <div class="transition-row">
                                <label><?php _e('Sonido de Salida de Página por Defecto:', 'nova-sound-fx'); ?></label>
                                <select id="global-exit-sound" class="sound-select">
                                    <option value=""><?php _e('Ninguno', 'nova-sound-fx'); ?></option>
                                </select>
                                <input type="range" id="global-exit-volume" min="0" max="100" value="50">
                                <span class="volume-value">50%</span>
                            </div>
                        </div>
                        
                        <h3><?php _e('Transiciones Específicas por URL', 'nova-sound-fx'); ?></h3>
                        <button id="add-url-transition" class="button button-primary">
                            <span class="dashicons dashicons-plus-alt"></span>
                            <?php _e('Agregar Patrón de URL', 'nova-sound-fx'); ?>
                        </button>
                        
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Patrón de URL', 'nova-sound-fx'); ?></th>
                                    <th><?php _e('Tipo', 'nova-sound-fx'); ?></th>
                                    <th><?php _e('Sonido', 'nova-sound-fx'); ?></th>
                                    <th><?php _e('Volumen', 'nova-sound-fx'); ?></th>
                                    <th><?php _e('Prioridad', 'nova-sound-fx'); ?></th>
                                    <th><?php _e('Acciones', 'nova-sound-fx'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="url-transitions-list">
                                <!-- Las transiciones URL se cargarán aquí -->
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Settings Tab -->
                <div id="settings" class="tab-pane">
                    <h2><?php _e('Configuración', 'nova-sound-fx'); ?></h2>
                    
                    <form id="nova-sound-fx-settings">
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="enable-sounds"><?php _e('Habilitar Sonidos', 'nova-sound-fx'); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" id="enable-sounds" name="enable_sounds" value="1" checked>
                                    <p class="description"><?php _e('Interruptor maestro para habilitar/deshabilitar todos los efectos de sonido', 'nova-sound-fx'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="default-volume"><?php _e('Volumen por Defecto', 'nova-sound-fx'); ?></label>
                                </th>
                                <td>
                                    <input type="range" id="default-volume" name="default_volume" min="0" max="100" value="50">
                                    <span class="volume-value">50%</span>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="mobile-enabled"><?php _e('Habilitar en Móviles', 'nova-sound-fx'); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" id="mobile-enabled" name="mobile_enabled" value="1">
                                    <p class="description"><?php _e('Habilitar efectos de sonido en dispositivos móviles', 'nova-sound-fx'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="respect-prefers-reduced-motion"><?php _e('Respetar Configuración de Accesibilidad', 'nova-sound-fx'); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" id="respect-prefers-reduced-motion" name="respect_prefers_reduced_motion" value="1" checked>
                                    <p class="description"><?php _e('Deshabilitar sonidos cuando el usuario tiene activado prefers-reduced-motion', 'nova-sound-fx'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="preview-mode"><?php _e('Modo Vista Previa', 'nova-sound-fx'); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" id="preview-mode" name="preview_mode" value="1">
                                    <p class="description"><?php _e('Habilitar sonidos solo para administradores (para pruebas)', 'nova-sound-fx'); ?></p>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" class="button button-primary"><?php _e('Guardar Configuración', 'nova-sound-fx'); ?></button>
                        </p>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Modal for CSS Mapping -->
        <div id="css-mapping-modal" class="nova-modal" style="display:none;">
            <div class="nova-modal-content">
                <span class="nova-modal-close">&times;</span>
                <h2><?php _e('Agregar Mapeo de Sonido CSS', 'nova-sound-fx'); ?></h2>
                <form id="css-mapping-form">
                    <input type="hidden" id="mapping-id" value="">
                    <table class="form-table">
                        <tr>
                            <th><label for="css-selector"><?php _e('Selector CSS', 'nova-sound-fx'); ?></label></th>
                            <td>
                                <input type="text" id="css-selector" class="regular-text" placeholder=".mi-clase, #mi-id">
                                <div id="css-selector-feedback" class="nova-form-feedback"></div>
                                <p class="description"><?php _e('Ingresa selectores de clase (.clase) o ID (#id). Puedes usar múltiples selectores separados por comas.', 'nova-sound-fx'); ?></p>
                                <div id="selector-preview" style="display:none;"></div>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="event-type"><?php _e('Tipo de Evento', 'nova-sound-fx'); ?></label></th>
                            <td>
                                <select id="event-type" class="regular-text">
                                    <option value="hover"><?php _e('Hover (Pasar el mouse)', 'nova-sound-fx'); ?></option>
                                    <option value="click"><?php _e('Click', 'nova-sound-fx'); ?></option>
                                    <option value="active"><?php _e('Active (Click sostenido)', 'nova-sound-fx'); ?></option>
                                    <option value="focus"><?php _e('Focus (Enfocar elemento)', 'nova-sound-fx'); ?></option>
                                    <option value="blur"><?php _e('Blur (Desenfocar elemento)', 'nova-sound-fx'); ?></option>
                                    <option value="mouseenter"><?php _e('Mouse Enter (Entrar con mouse)', 'nova-sound-fx'); ?></option>
                                    <option value="mouseleave"><?php _e('Mouse Leave (Salir con mouse)', 'nova-sound-fx'); ?></option>
                                    <option value="mousedown"><?php _e('Mouse Down (Presionar botón del mouse)', 'nova-sound-fx'); ?></option>
                                    <option value="mouseup"><?php _e('Mouse Up (Soltar botón del mouse)', 'nova-sound-fx'); ?></option>
                                </select>
                                <p class="description"><?php _e('Selecciona cuándo se debe reproducir el sonido', 'nova-sound-fx'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="mapping-sound"><?php _e('Efecto de Sonido', 'nova-sound-fx'); ?></label></th>
                            <td>
                                <select id="mapping-sound" class="sound-select regular-text">
                                    <option value=""><?php _e('Selecciona un sonido', 'nova-sound-fx'); ?></option>
                                </select>
                                <button type="button" id="preview-mapping-sound" class="button"><?php _e('Vista Previa', 'nova-sound-fx'); ?></button>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="mapping-volume"><?php _e('Volumen', 'nova-sound-fx'); ?></label></th>
                            <td>
                                <input type="range" id="mapping-volume" min="0" max="100" value="100">
                                <span class="volume-value">100%</span>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="mapping-delay"><?php _e('Retraso (ms)', 'nova-sound-fx'); ?></label></th>
                            <td>
                                <input type="number" id="mapping-delay" min="0" max="5000" value="0" class="small-text">
                                <p class="description"><?php _e('Retraso antes de reproducir el sonido (en milisegundos)', 'nova-sound-fx'); ?></p>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php _e('Guardar Mapeo', 'nova-sound-fx'); ?></button>
                        <button type="button" class="button nova-modal-cancel"><?php _e('Cancelar', 'nova-sound-fx'); ?></button>
                    </p>
                </form>
            </div>
        </div>
        
        <!-- Modal for URL Transitions -->
        <div id="url-transition-modal" class="nova-modal" style="display:none;">
            <div class="nova-modal-content">
                <span class="nova-modal-close">&times;</span>
                <h2><?php _e('Agregar Transición de URL', 'nova-sound-fx'); ?></h2>
                <form id="url-transition-form">
                    <input type="hidden" id="transition-id" value="">
                    <table class="form-table">
                        <tr>
                            <th><label for="url-pattern"><?php _e('Patrón de URL', 'nova-sound-fx'); ?></label></th>
                            <td>
                                <input type="text" id="url-pattern" class="regular-text" placeholder="/404, */contacto/*, regex:.*\.pdf$">
                                <p class="description"><?php _e('Usa * para comodines, prefijo con regex: para patrones regex', 'nova-sound-fx'); ?></p>
                                <p class="description"><?php _e('Ejemplos: /404 (página 404), */shop/* (todas las páginas de tienda), regex:.*\.pdf$ (enlaces PDF)', 'nova-sound-fx'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="transition-type"><?php _e('Tipo de Transición', 'nova-sound-fx'); ?></label></th>
                            <td>
                                <select id="transition-type" class="regular-text">
                                    <option value="enter"><?php _e('Entrada de Página', 'nova-sound-fx'); ?></option>
                                    <option value="exit"><?php _e('Salida de Página', 'nova-sound-fx'); ?></option>
                                    <option value="both"><?php _e('Ambas', 'nova-sound-fx'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="transition-sound"><?php _e('Efecto de Sonido', 'nova-sound-fx'); ?></label></th>
                            <td>
                                <select id="transition-sound" class="sound-select regular-text">
                                    <option value=""><?php _e('Selecciona un sonido', 'nova-sound-fx'); ?></option>
                                </select>
                                <button type="button" id="preview-transition-sound" class="button"><?php _e('Vista Previa', 'nova-sound-fx'); ?></button>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="transition-volume"><?php _e('Volumen', 'nova-sound-fx'); ?></label></th>
                            <td>
                                <input type="range" id="transition-volume" min="0" max="100" value="100">
                                <span class="volume-value">100%</span>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="transition-priority"><?php _e('Prioridad', 'nova-sound-fx'); ?></label></th>
                            <td>
                                <input type="number" id="transition-priority" min="0" max="100" value="0" class="small-text">
                                <p class="description"><?php _e('Los patrones con mayor prioridad tienen precedencia sobre los de menor prioridad', 'nova-sound-fx'); ?></p>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php _e('Guardar Transición', 'nova-sound-fx'); ?></button>
                        <button type="button" class="button nova-modal-cancel"><?php _e('Cancelar', 'nova-sound-fx'); ?></button>
                    </p>
                </form>
            </div>
        </div>
        <?php
    }
    
    /**
     * Enqueue admin styles
     */
    public function enqueue_styles($hook) {
        if ($hook !== 'toplevel_page_nova-sound-fx') {
            return;
        }
        
        wp_enqueue_style(
            'nova-sound-fx-admin',
            NOVA_SOUND_FX_PLUGIN_URL . 'admin/css/nova-sound-fx-admin.css',
            array(),
            $this->version
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'toplevel_page_nova-sound-fx') {
            return;
        }
        
        wp_enqueue_media();
        
        wp_enqueue_script(
            'nova-sound-fx-admin',
            NOVA_SOUND_FX_PLUGIN_URL . 'admin/js/nova-sound-fx-admin.js',
            array('jquery', 'wp-mediaelement'),
            $this->version,
            true
        );
        
        wp_localize_script('nova-sound-fx-admin', 'nova_sound_fx_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('nova_sound_fx_admin'),
            'strings' => array(
                'confirm_delete' => __('¿Estás seguro de que deseas eliminar esto?', 'nova-sound-fx'),
                'error' => __('Ocurrió un error. Por favor intenta de nuevo.', 'nova-sound-fx'),
                'saved' => __('¡Configuración guardada exitosamente!', 'nova-sound-fx')
            )
        ));
    }
    
    /**
     * AJAX: Get CSS mappings
     */
    public function ajax_get_css_mappings() {
        check_ajax_referer('nova_sound_fx_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        $mappings = Nova_Sound_FX::get_css_mappings();
        wp_send_json_success($mappings);
    }
    
    /**
     * AJAX: Get transitions
     */
    public function ajax_get_transitions() {
        check_ajax_referer('nova_sound_fx_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        $transitions = Nova_Sound_FX::get_transitions();
        wp_send_json_success($transitions);
    }
    
    /**
     * AJAX: Save CSS mapping
     */
    public function ajax_save_css_mapping() {
        check_ajax_referer('nova_sound_fx_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        $data = array(
            'css_selector' => sanitize_text_field($_POST['css_selector']),
            'event_type' => sanitize_text_field($_POST['event_type']),
            'sound_id' => intval($_POST['sound_id']),
            'volume' => intval($_POST['volume']),
            'delay' => intval($_POST['delay'])
        );
        
        // Validar que el selector sea una clase o ID
        if (!preg_match('/^[#.][\w-]+(\s*,\s*[#.][\w-]+)*$/', $data['css_selector'])) {
            wp_send_json_error(array('message' => __('El selector CSS debe ser una clase (.clase) o ID (#id)', 'nova-sound-fx')));
            return;
        }
        
        // Si hay un ID, actualizar; si no, crear nuevo
        if (!empty($_POST['id'])) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'nova_sound_fx_css_mappings';
            
            $result = $wpdb->update(
                $table_name,
                $data,
                array('id' => intval($_POST['id'])),
                array('%s', '%s', '%d', '%d', '%d'),
                array('%d')
            );
            
            if ($result !== false) {
                wp_send_json_success(array('message' => __('Mapeo CSS actualizado exitosamente', 'nova-sound-fx')));
            } else {
                wp_send_json_error(array('message' => __('Error al actualizar el mapeo CSS', 'nova-sound-fx')));
            }
        } else {
            if (Nova_Sound_FX::save_css_mapping($data)) {
                wp_send_json_success(array('message' => __('Mapeo CSS guardado exitosamente', 'nova-sound-fx')));
            } else {
                wp_send_json_error(array('message' => __('Error al guardar el mapeo CSS', 'nova-sound-fx')));
            }
        }
    }
    
    /**
     * AJAX: Delete CSS mapping
     */
    public function ajax_delete_css_mapping() {
        check_ajax_referer('nova_sound_fx_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        $id = intval($_POST['id']);
        
        if (Nova_Sound_FX::delete_css_mapping($id)) {
            wp_send_json_success(array('message' => __('Mapeo CSS eliminado exitosamente', 'nova-sound-fx')));
        } else {
            wp_send_json_error(array('message' => __('Error al eliminar el mapeo CSS', 'nova-sound-fx')));
        }
    }
    
    /**
     * AJAX: Save transition
     */
    public function ajax_save_transition() {
        check_ajax_referer('nova_sound_fx_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        $data = array(
            'url_pattern' => sanitize_text_field($_POST['url_pattern']),
            'transition_type' => sanitize_text_field($_POST['transition_type']),
            'sound_id' => intval($_POST['sound_id']),
            'volume' => intval($_POST['volume']),
            'priority' => intval($_POST['priority'])
        );
        
        // Si hay un ID, actualizar; si no, crear nuevo
        if (!empty($_POST['id'])) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'nova_sound_fx_transitions';
            
            $result = $wpdb->update(
                $table_name,
                $data,
                array('id' => intval($_POST['id'])),
                array('%s', '%s', '%d', '%d', '%d'),
                array('%d')
            );
            
            if ($result !== false) {
                wp_send_json_success(array('message' => __('Transición actualizada exitosamente', 'nova-sound-fx')));
            } else {
                wp_send_json_error(array('message' => __('Error al actualizar la transición', 'nova-sound-fx')));
            }
        } else {
            if (Nova_Sound_FX::save_transition($data)) {
                wp_send_json_success(array('message' => __('Transición guardada exitosamente', 'nova-sound-fx')));
            } else {
                wp_send_json_error(array('message' => __('Error al guardar la transición', 'nova-sound-fx')));
            }
        }
    }
    
    /**
     * AJAX: Delete transition
     */
    public function ajax_delete_transition() {
        check_ajax_referer('nova_sound_fx_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        $id = intval($_POST['id']);
        
        if (Nova_Sound_FX::delete_transition($id)) {
            wp_send_json_success(array('message' => __('Transición eliminada exitosamente', 'nova-sound-fx')));
        } else {
            wp_send_json_error(array('message' => __('Error al eliminar la transición', 'nova-sound-fx')));
        }
    }
    
    /**
     * AJAX: Get sound library
     */
    public function ajax_get_sound_library() {
        check_ajax_referer('nova_sound_fx_admin', 'nonce');
        
        $args = array(
            'post_type' => 'attachment',
            'post_mime_type' => array('audio/mpeg', 'audio/wav', 'audio/x-wav', 'audio/wave', 'audio/mp3'),
            'post_status' => 'inherit',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_wp_attached_file',
                    'value' => '.mp3',
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_wp_attached_file',
                    'value' => '.wav',
                    'compare' => 'LIKE'
                )
            )
        );
        
        // Forzar actualización si se solicita
        if (!empty($_POST['refresh'])) {
            clean_post_cache(0);
        }
        
        $query = new WP_Query($args);
        $sounds = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $id = get_the_ID();
                $sounds[] = array(
                    'id' => $id,
                    'title' => get_the_title(),
                    'url' => wp_get_attachment_url($id),
                    'mime_type' => get_post_mime_type($id),
                    'date' => get_the_date('j M Y', $id)
                );
            }
            wp_reset_postdata();
        }
        
        wp_send_json_success($sounds);
    }
    
    /**
     * AJAX: Get settings
     */
    public function ajax_get_settings() {
        check_ajax_referer('nova_sound_fx_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        $settings = get_option('nova_sound_fx_settings', array(
            'enable_sounds' => true,
            'default_volume' => 50,
            'mobile_enabled' => false,
            'respect_prefers_reduced_motion' => true,
            'preview_mode' => false
        ));
        
        wp_send_json_success($settings);
    }
    
    /**
     * AJAX: Save settings
     */
    public function ajax_save_settings() {
        check_ajax_referer('nova_sound_fx_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        $settings = array(
            'enable_sounds' => isset($_POST['enable_sounds']) ? true : false,
            'default_volume' => intval($_POST['default_volume']),
            'mobile_enabled' => isset($_POST['mobile_enabled']) ? true : false,
            'respect_prefers_reduced_motion' => isset($_POST['respect_prefers_reduced_motion']) ? true : false,
            'preview_mode' => isset($_POST['preview_mode']) ? true : false
        );
        
        update_option('nova_sound_fx_settings', $settings);
        
        wp_send_json_success(array('message' => __('Configuración guardada exitosamente', 'nova-sound-fx')));
    }
}
