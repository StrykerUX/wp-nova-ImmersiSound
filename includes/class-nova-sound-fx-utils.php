<?php
/**
 * Utility functions for Nova Sound FX
 */
class Nova_Sound_FX_Utils {
    
    /**
     * Get all supported audio mime types
     */
    public static function get_supported_mime_types() {
        return array(
            'audio/mpeg',
            'audio/mp3',
            'audio/wav',
            'audio/wave',
            'audio/x-wav',
            'audio/ogg',
            'audio/webm'
        );
    }
    
    /**
     * Validate CSS selector
     */
    public static function is_valid_css_selector($selector) {
        // Basic validation - could be expanded
        if (empty($selector)) {
            return false;
        }
        
        // Check for common invalid patterns
        $invalid_patterns = array(
            '/^[0-9]/',          // Starts with number
            '/[\x00-\x1F\x7F]/', // Control characters
            '/<script/i',        // Script tags
            '/javascript:/i',     // JavaScript protocol
        );
        
        foreach ($invalid_patterns as $pattern) {
            if (preg_match($pattern, $selector)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Validate URL pattern
     */
    public static function is_valid_url_pattern($pattern) {
        if (empty($pattern)) {
            return false;
        }
        
        // If it's a regex pattern, validate regex
        if (strpos($pattern, 'regex:') === 0) {
            $regex = substr($pattern, 6);
            return @preg_match('/' . $regex . '/', '') !== false;
        }
        
        return true;
    }
    
    /**
     * Get file size formatted
     */
    public static function format_file_size($bytes) {
        $units = array('B', 'KB', 'MB', 'GB');
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    /**
     * Get audio file duration
     */
    public static function get_audio_duration($file_path) {
        if (!file_exists($file_path)) {
            return false;
        }
        
        // This would require additional libraries like getID3
        // For now, return false
        return false;
    }
    
    /**
     * Sanitize volume value
     */
    public static function sanitize_volume($volume) {
        return max(0, min(100, intval($volume)));
    }
    
    /**
     * Get default sound categories
     */
    public static function get_sound_categories() {
        return array(
            'hover' => __('Hover Effects', 'nova-sound-fx'),
            'click' => __('Click Effects', 'nova-sound-fx'),
            'transition' => __('Page Transitions', 'nova-sound-fx'),
            'notification' => __('Notifications', 'nova-sound-fx'),
            'ambient' => __('Ambient', 'nova-sound-fx'),
            'ui' => __('UI Feedback', 'nova-sound-fx'),
            'error' => __('Error Sounds', 'nova-sound-fx'),
            'success' => __('Success Sounds', 'nova-sound-fx'),
            'other' => __('Other', 'nova-sound-fx')
        );
    }
    
    /**
     * Get event types
     */
    public static function get_event_types() {
        return array(
            'hover' => __('Hover', 'nova-sound-fx'),
            'click' => __('Click', 'nova-sound-fx'),
            'focus' => __('Focus', 'nova-sound-fx'),
            'blur' => __('Blur', 'nova-sound-fx'),
            'mouseenter' => __('Mouse Enter', 'nova-sound-fx'),
            'mouseleave' => __('Mouse Leave', 'nova-sound-fx'),
            'mousedown' => __('Mouse Down', 'nova-sound-fx'),
            'mouseup' => __('Mouse Up', 'nova-sound-fx'),
            'change' => __('Change', 'nova-sound-fx'),
            'submit' => __('Submit', 'nova-sound-fx'),
            'keydown' => __('Key Down', 'nova-sound-fx'),
            'keyup' => __('Key Up', 'nova-sound-fx')
        );
    }
    
    /**
     * Get URL pattern examples
     */
    public static function get_url_pattern_examples() {
        return array(
            '/404' => __('404 error page', 'nova-sound-fx'),
            '/contact' => __('Contact page', 'nova-sound-fx'),
            '*/shop/*' => __('All shop pages', 'nova-sound-fx'),
            '*/product/*' => __('All product pages', 'nova-sound-fx'),
            'regex:.*\.pdf$' => __('All PDF links', 'nova-sound-fx'),
            'regex:^/blog/.*' => __('All blog posts', 'nova-sound-fx'),
            '*' => __('All pages', 'nova-sound-fx')
        );
    }
    
    /**
     * Check if browser supports Web Audio API
     */
    public static function browser_supports_web_audio() {
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            return true; // Assume support if we can't detect
        }
        
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        
        // Check for old browsers that don't support Web Audio API
        $unsupported = array(
            'MSIE [6-9]',
            'Android [2-3]',
            'Safari/[1-8]'
        );
        
        foreach ($unsupported as $pattern) {
            if (preg_match('/' . $pattern . '/i', $user_agent)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Generate unique element ID
     */
    public static function generate_unique_id($prefix = 'nova-') {
        return $prefix . uniqid();
    }
    
    /**
     * Log debug information
     */
    public static function log($message, $type = 'info') {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        $log_file = WP_CONTENT_DIR . '/nova-sound-fx-debug.log';
        $timestamp = current_time('mysql');
        $log_entry = sprintf("[%s] [%s] %s\n", $timestamp, strtoupper($type), $message);
        
        error_log($log_entry, 3, $log_file);
    }
    
    /**
     * Get plugin asset URL
     */
    public static function get_asset_url($path) {
        return NOVA_SOUND_FX_PLUGIN_URL . $path;
    }
    
    /**
     * Check if current page is plugin admin page
     */
    public static function is_plugin_admin_page() {
        if (!is_admin()) {
            return false;
        }
        
        $screen = get_current_screen();
        return $screen && strpos($screen->id, 'nova-sound-fx') !== false;
    }
    
    /**
     * Export settings
     */
    public static function export_settings() {
        $export_data = array(
            'version' => NOVA_SOUND_FX_VERSION,
            'settings' => get_option('nova_sound_fx_settings', array()),
            'css_mappings' => Nova_Sound_FX::get_css_mappings(),
            'transitions' => Nova_Sound_FX::get_transitions(),
            'timestamp' => current_time('mysql')
        );
        
        return json_encode($export_data, JSON_PRETTY_PRINT);
    }
    
    /**
     * Import settings
     */
    public static function import_settings($json_data) {
        $data = json_decode($json_data, true);
        
        if (!$data || !isset($data['version'])) {
            return new WP_Error('invalid_data', __('Invalid import data', 'nova-sound-fx'));
        }
        
        // Import settings
        if (isset($data['settings'])) {
            update_option('nova_sound_fx_settings', $data['settings']);
        }
        
        // Import CSS mappings
        if (isset($data['css_mappings']) && is_array($data['css_mappings'])) {
            foreach ($data['css_mappings'] as $mapping) {
                Nova_Sound_FX::save_css_mapping($mapping);
            }
        }
        
        // Import transitions
        if (isset($data['transitions']) && is_array($data['transitions'])) {
            foreach ($data['transitions'] as $transition) {
                Nova_Sound_FX::save_transition($transition);
            }
        }
        
        return true;
    }
}
