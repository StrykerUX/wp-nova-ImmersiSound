<?php
/**
 * Utility functions for Nova ImmersiSound
 */
class Nova_Sound_FX_Utils {
    
    /**
     * Validate CSS selector
     * Only allow class and ID selectors
     */
    public static function validate_css_selector($selector) {
        // Remove whitespace
        $selector = trim($selector);
        
        // Check if it's a valid class or ID selector
        $pattern = '/^[#.][\w-]+(\s*,\s*[#.][\w-]+)*$/';
        
        return preg_match($pattern, $selector);
    }
    
    /**
     * Sanitize CSS selector
     */
    public static function sanitize_css_selector($selector) {
        // Remove any potentially harmful characters
        $selector = wp_strip_all_tags($selector);
        $selector = trim($selector);
        
        // Only allow valid characters for CSS selectors
        $selector = preg_replace('/[^#.\w\s,-]/', '', $selector);
        
        return $selector;
    }
    
    /**
     * Get audio file types
     */
    public static function get_allowed_audio_types() {
        return array(
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'ogg' => 'audio/ogg',
            'm4a' => 'audio/mp4'
        );
    }
    
    /**
     * Check if file is valid audio
     */
    public static function is_valid_audio_file($file_path) {
        $allowed_types = self::get_allowed_audio_types();
        $file_type = wp_check_filetype($file_path);
        
        return isset($allowed_types[$file_type['ext']]);
    }
    
    /**
     * Get event types
     */
    public static function get_event_types() {
        return array(
            'hover' => __('Hover (Pasar el mouse)', 'nova-sound-fx'),
            'click' => __('Click', 'nova-sound-fx'),
            'active' => __('Active (Click sostenido)', 'nova-sound-fx'),
            'focus' => __('Focus (Enfocar elemento)', 'nova-sound-fx'),
            'blur' => __('Blur (Desenfocar elemento)', 'nova-sound-fx'),
            'mouseenter' => __('Mouse Enter (Entrar con mouse)', 'nova-sound-fx'),
            'mouseleave' => __('Mouse Leave (Salir con mouse)', 'nova-sound-fx'),
            'mousedown' => __('Mouse Down (Presionar botón del mouse)', 'nova-sound-fx'),
            'mouseup' => __('Mouse Up (Soltar botón del mouse)', 'nova-sound-fx')
        );
    }
    
    /**
     * Get transition types
     */
    public static function get_transition_types() {
        return array(
            'enter' => __('Entrada de página', 'nova-sound-fx'),
            'exit' => __('Salida de página', 'nova-sound-fx'),
            'both' => __('Ambas', 'nova-sound-fx')
        );
    }
    
    /**
     * Format file size
     */
    public static function format_file_size($bytes) {
        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 2) . ' KB';
        } else {
            return round($bytes / 1048576, 2) . ' MB';
        }
    }
    
    /**
     * Get sound metadata
     */
    public static function get_sound_metadata($attachment_id) {
        $metadata = wp_get_attachment_metadata($attachment_id);
        $file_path = get_attached_file($attachment_id);
        
        $sound_data = array(
            'id' => $attachment_id,
            'title' => get_the_title($attachment_id),
            'url' => wp_get_attachment_url($attachment_id),
            'mime_type' => get_post_mime_type($attachment_id),
            'file_size' => filesize($file_path),
            'formatted_size' => self::format_file_size(filesize($file_path)),
            'duration' => isset($metadata['length_formatted']) ? $metadata['length_formatted'] : 'N/A',
            'bitrate' => isset($metadata['bitrate']) ? $metadata['bitrate'] : 'N/A'
        );
        
        return $sound_data;
    }
    
    /**
     * Debug log
     */
    public static function log($message, $type = 'info') {
        if (defined('WP_DEBUG') && WP_DEBUG === true) {
            error_log('[Nova ImmersiSound ' . $type . '] ' . print_r($message, true));
        }
    }
    
    /**
     * Check plugin requirements
     */
    public static function check_requirements() {
        $errors = array();
        
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.0', '<')) {
            $errors[] = sprintf(
                __('Nova ImmersiSound requiere PHP 7.0 o superior. Tu versión actual es %s.', 'nova-sound-fx'),
                PHP_VERSION
            );
        }
        
        // Check WordPress version
        if (version_compare(get_bloginfo('version'), '5.0', '<')) {
            $errors[] = sprintf(
                __('Nova ImmersiSound requiere WordPress 5.0 o superior. Tu versión actual es %s.', 'nova-sound-fx'),
                get_bloginfo('version')
            );
        }
        
        // Check if JavaScript is enabled
        if (!wp_script_is('jquery', 'registered')) {
            $errors[] = __('jQuery no está disponible. Nova ImmersiSound requiere jQuery para funcionar.', 'nova-sound-fx');
        }
        
        return $errors;
    }
    
    /**
     * Get default settings
     */
    public static function get_default_settings() {
        return array(
            'enable_sounds' => true,
            'default_volume' => 50,
            'mobile_enabled' => false,
            'respect_prefers_reduced_motion' => true,
            'preview_mode' => false,
            'show_visual_feedback' => true,
            'preload_sounds' => true,
            'max_simultaneous_sounds' => 3
        );
    }
    
    /**
     * Validate URL pattern
     */
    public static function validate_url_pattern($pattern) {
        // Check if it's a regex pattern
        if (strpos($pattern, 'regex:') === 0) {
            $regex = substr($pattern, 6);
            // Test if regex is valid
            return @preg_match('/' . $regex . '/', '') !== false;
        }
        
        // For wildcard patterns, just check basic validity
        return !empty($pattern) && strlen($pattern) <= 255;
    }
    
    /**
     * Get plugin info
     */
    public static function get_plugin_info() {
        return array(
            'name' => 'Nova ImmersiSound',
            'version' => NOVA_SOUND_FX_VERSION,
            'author' => 'Tu Nombre',
            'website' => 'https://tu-sitio.com',
            'support_email' => 'soporte@tu-sitio.com'
        );
    }
    
    /**
     * Export settings
     */
    public static function export_settings() {
        $export_data = array(
            'version' => NOVA_SOUND_FX_VERSION,
            'settings' => get_option('nova_sound_fx_settings'),
            'css_mappings' => Nova_Sound_FX::get_css_mappings(),
            'transitions' => Nova_Sound_FX::get_transitions(),
            'export_date' => current_time('mysql')
        );
        
        return json_encode($export_data, JSON_PRETTY_PRINT);
    }
    
    /**
     * Import settings
     */
    public static function import_settings($json_data) {
        $data = json_decode($json_data, true);
        
        if (!$data || !isset($data['version'])) {
            return new WP_Error('invalid_data', __('Datos de importación inválidos', 'nova-sound-fx'));
        }
        
        // Update settings
        if (isset($data['settings'])) {
            update_option('nova_sound_fx_settings', $data['settings']);
        }
        
        // Import CSS mappings
        if (isset($data['css_mappings'])) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'nova_sound_fx_css_mappings';
            
            // Clear existing mappings
            $wpdb->query("TRUNCATE TABLE $table_name");
            
            // Insert new mappings
            foreach ($data['css_mappings'] as $mapping) {
                Nova_Sound_FX::save_css_mapping($mapping);
            }
        }
        
        // Import transitions
        if (isset($data['transitions'])) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'nova_sound_fx_transitions';
            
            // Clear existing transitions
            $wpdb->query("TRUNCATE TABLE $table_name");
            
            // Insert new transitions
            foreach ($data['transitions'] as $transition) {
                Nova_Sound_FX::save_transition($transition);
            }
        }
        
        return true;
    }
}
