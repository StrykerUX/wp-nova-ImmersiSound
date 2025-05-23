<?php
/**
 * AJAX Handler Class
 */
class Nova_Sound_FX_Ajax {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_ajax_handlers();
    }
    
    /**
     * Initialize AJAX handlers
     */
    private function init_ajax_handlers() {
        // Admin AJAX handlers
        $admin_actions = array(
            'nova_sound_fx_save_css_mapping',
            'nova_sound_fx_delete_css_mapping',
            'nova_sound_fx_save_transition', 
            'nova_sound_fx_delete_transition',
            'nova_sound_fx_get_sound_library',
            'nova_sound_fx_save_settings',
            'nova_sound_fx_get_mappings',
            'nova_sound_fx_get_transitions',
            'nova_sound_fx_update_sound_meta',
            'nova_sound_fx_bulk_delete'
        );
        
        foreach ($admin_actions as $action) {
            add_action('wp_ajax_' . $action, array($this, 'handle_' . $action));
        }
        
        // Public AJAX handlers
        $public_actions = array(
            'nova_sound_fx_get_sounds',
            'nova_sound_fx_log_error',
            'nova_sound_fx_track_usage'
        );
        
        foreach ($public_actions as $action) {
            add_action('wp_ajax_' . $action, array($this, 'handle_' . $action));
            add_action('wp_ajax_nopriv_' . $action, array($this, 'handle_' . $action));
        }
    }
    
    /**
     * Verify AJAX nonce and capabilities
     */
    private function verify_ajax_request($capability = 'manage_options', $nonce_action = 'nova_sound_fx_admin') {
        if (!check_ajax_referer($nonce_action, 'nonce', false)) {
            wp_die('Security check failed');
        }
        
        if ($capability && !current_user_can($capability)) {
            wp_die('Insufficient permissions');
        }
        
        return true;
    }
    
    /**
     * Handle get mappings request
     */
    public function handle_nova_sound_fx_get_mappings() {
        $this->verify_ajax_request();
        
        $mappings = Nova_Sound_FX::get_css_mappings();
        wp_send_json_success($mappings);
    }
    
    /**
     * Handle get transitions request
     */
    public function handle_nova_sound_fx_get_transitions() {
        $this->verify_ajax_request();
        
        $transitions = Nova_Sound_FX::get_transitions();
        wp_send_json_success($transitions);
    }
    
    /**
     * Handle update sound meta
     */
    public function handle_nova_sound_fx_update_sound_meta() {
        $this->verify_ajax_request();
        
        $sound_id = intval($_POST['sound_id']);
        $meta_key = sanitize_text_field($_POST['meta_key']);
        $meta_value = sanitize_text_field($_POST['meta_value']);
        
        if ($sound_id && $meta_key) {
            update_post_meta($sound_id, 'nova_sound_fx_' . $meta_key, $meta_value);
            wp_send_json_success(array('message' => 'Sound metadata updated'));
        } else {
            wp_send_json_error(array('message' => 'Invalid parameters'));
        }
    }
    
    /**
     * Handle bulk delete
     */
    public function handle_nova_sound_fx_bulk_delete() {
        $this->verify_ajax_request();
        
        $type = sanitize_text_field($_POST['type']);
        $ids = array_map('intval', $_POST['ids']);
        
        if (empty($ids)) {
            wp_send_json_error(array('message' => 'No items selected'));
        }
        
        $deleted = 0;
        
        if ($type === 'mappings') {
            foreach ($ids as $id) {
                if (Nova_Sound_FX::delete_css_mapping($id)) {
                    $deleted++;
                }
            }
        } elseif ($type === 'transitions') {
            foreach ($ids as $id) {
                if (Nova_Sound_FX::delete_transition($id)) {
                    $deleted++;
                }
            }
        }
        
        wp_send_json_success(array(
            'message' => sprintf('%d items deleted', $deleted),
            'deleted' => $deleted
        ));
    }
    
    /**
     * Handle error logging from frontend
     */
    public function handle_nova_sound_fx_log_error() {
        $this->verify_ajax_request(null, 'nova_sound_fx_public');
        
        $error = array(
            'message' => sanitize_text_field($_POST['message']),
            'url' => sanitize_text_field($_POST['url']),
            'line' => intval($_POST['line']),
            'column' => intval($_POST['column']),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT']),
            'timestamp' => current_time('mysql')
        );
        
        // Log error to custom log file
        $log_file = WP_CONTENT_DIR . '/nova-sound-fx-errors.log';
        $log_entry = sprintf(
            "[%s] %s at %s:%d:%d (UA: %s)\n",
            $error['timestamp'],
            $error['message'],
            $error['url'],
            $error['line'],
            $error['column'],
            $error['user_agent']
        );
        
        error_log($log_entry, 3, $log_file);
        
        wp_send_json_success(array('logged' => true));
    }
    
    /**
     * Handle usage tracking
     */
    public function handle_nova_sound_fx_track_usage() {
        $this->verify_ajax_request(null, 'nova_sound_fx_public');
        
        $event = array(
            'type' => sanitize_text_field($_POST['event_type']),
            'element' => sanitize_text_field($_POST['element']),
            'sound_id' => intval($_POST['sound_id']),
            'timestamp' => current_time('mysql')
        );
        
        // Store usage data (could be extended to save to database)
        do_action('nova_sound_fx_usage_tracked', $event);
        
        wp_send_json_success(array('tracked' => true));
    }
}

// Initialize AJAX handler
new Nova_Sound_FX_Ajax();
