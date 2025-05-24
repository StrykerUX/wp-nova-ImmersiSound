<?php
/**
 * AJAX functionality for Nova Sound FX
 */
class Nova_Sound_FX_Ajax {
    
    private $version;
    
    public function __construct($version) {
        $this->version = $version;
        $this->init();
    }
    
    /**
     * Initialize AJAX handlers
     */
    public function init() {
        // Admin AJAX handlers
        add_action('wp_ajax_nova_sound_fx_test_sound', array($this, 'ajax_test_sound'));
        add_action('wp_ajax_nova_sound_fx_export_settings', array($this, 'ajax_export_settings'));
        add_action('wp_ajax_nova_sound_fx_import_settings', array($this, 'ajax_import_settings'));
        add_action('wp_ajax_nova_sound_fx_get_element_count', array($this, 'ajax_get_element_count'));
        
        // Public AJAX handlers
        add_action('wp_ajax_nova_sound_fx_log_event', array($this, 'ajax_log_event'));
        add_action('wp_ajax_nopriv_nova_sound_fx_log_event', array($this, 'ajax_log_event'));
    }
    
    /**
     * AJAX: Test sound playback
     */
    public function ajax_test_sound() {
        check_ajax_referer('nova_sound_fx_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        $sound_url = isset($_POST['sound_url']) ? esc_url_raw($_POST['sound_url']) : '';
        
        if (empty($sound_url)) {
            wp_send_json_error(array('message' => __('URL de sonido inválida', 'nova-sound-fx')));
            return;
        }
        
        // Verificar que el archivo existe
        $response = wp_remote_head($sound_url);
        
        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => __('No se pudo verificar el archivo de sonido', 'nova-sound-fx')));
            return;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code !== 200) {
            wp_send_json_error(array('message' => __('El archivo de sonido no existe', 'nova-sound-fx')));
            return;
        }
        
        wp_send_json_success(array(
            'message' => __('Sonido verificado correctamente', 'nova-sound-fx'),
            'url' => $sound_url
        ));
    }
    
    /**
     * AJAX: Export settings
     */
    public function ajax_export_settings() {
        check_ajax_referer('nova_sound_fx_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        $export_data = Nova_Sound_FX_Utils::export_settings();
        
        wp_send_json_success(array(
            'filename' => 'nova-sound-fx-export-' . date('Y-m-d') . '.json',
            'data' => $export_data
        ));
    }
    
    /**
     * AJAX: Import settings
     */
    public function ajax_import_settings() {
        check_ajax_referer('nova_sound_fx_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        if (!isset($_FILES['import_file'])) {
            wp_send_json_error(array('message' => __('No se recibió ningún archivo', 'nova-sound-fx')));
            return;
        }
        
        $file = $_FILES['import_file'];
        
        // Verificar tipo de archivo
        if ($file['type'] !== 'application/json') {
            wp_send_json_error(array('message' => __('El archivo debe ser JSON', 'nova-sound-fx')));
            return;
        }
        
        // Leer contenido del archivo
        $json_data = file_get_contents($file['tmp_name']);
        
        if (empty($json_data)) {
            wp_send_json_error(array('message' => __('El archivo está vacío', 'nova-sound-fx')));
            return;
        }
        
        // Importar configuraciones
        $result = Nova_Sound_FX_Utils::import_settings($json_data);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            return;
        }
        
        wp_send_json_success(array('message' => __('Configuraciones importadas exitosamente', 'nova-sound-fx')));
    }
    
    /**
     * AJAX: Get element count for selector
     */
    public function ajax_get_element_count() {
        check_ajax_referer('nova_sound_fx_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        $selector = isset($_POST['selector']) ? sanitize_text_field($_POST['selector']) : '';
        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : home_url();
        
        if (empty($selector)) {
            wp_send_json_error(array('message' => __('Selector vacío', 'nova-sound-fx')));
            return;
        }
        
        // Validar selector
        if (!Nova_Sound_FX_Utils::validate_css_selector($selector)) {
            wp_send_json_error(array('message' => __('Selector CSS inválido', 'nova-sound-fx')));
            return;
        }
        
        // Esta funcionalidad requeriría ejecutar JavaScript en el servidor
        // Por ahora, retornamos una respuesta genérica
        wp_send_json_success(array(
            'message' => __('Para ver el conteo de elementos, guarda el mapeo y verifica en el frontend', 'nova-sound-fx'),
            'selector' => $selector
        ));
    }
    
    /**
     * AJAX: Log sound event (for analytics)
     */
    public function ajax_log_event() {
        check_ajax_referer('nova_sound_fx_public', 'nonce');
        
        $event_type = isset($_POST['event_type']) ? sanitize_text_field($_POST['event_type']) : '';
        $selector = isset($_POST['selector']) ? sanitize_text_field($_POST['selector']) : '';
        $sound_id = isset($_POST['sound_id']) ? intval($_POST['sound_id']) : 0;
        
        // Aquí podrías implementar un sistema de analytics
        // Por ahora, solo registramos en el log si está en modo debug
        Nova_Sound_FX_Utils::log(array(
            'event' => 'sound_played',
            'type' => $event_type,
            'selector' => $selector,
            'sound_id' => $sound_id,
            'timestamp' => current_time('mysql'),
            'user_agent' => $_SERVER['HTTP_USER_AGENT']
        ), 'analytics');
        
        wp_send_json_success();
    }
    
    /**
     * Get analytics data
     */
    public static function get_analytics_summary() {
        // Esta función podría implementarse para mostrar estadísticas
        // de uso de sonidos en el panel de administración
        return array(
            'total_plays' => 0,
            'most_played_sound' => 'N/A',
            'most_triggered_event' => 'N/A',
            'last_played' => 'N/A'
        );
    }
}
