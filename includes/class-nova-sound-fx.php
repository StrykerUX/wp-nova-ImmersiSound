<?php
/**
 * Main plugin class
 */
class Nova_Sound_FX {
    
    protected $version;
    protected $admin;
    protected $public;
    protected $shortcodes;
    
    public function __construct() {
        $this->version = NOVA_SOUND_FX_VERSION;
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_shortcode_hooks();
    }
    
    private function load_dependencies() {
        $this->admin = new Nova_Sound_FX_Admin($this->version);
        $this->public = new Nova_Sound_FX_Public($this->version);
        $this->shortcodes = new Nova_Sound_FX_Shortcodes($this->version);
    }
    
    private function define_admin_hooks() {
        // Admin menu
        add_action('admin_menu', array($this->admin, 'add_admin_menu'));
        
        // Admin scripts and styles
        add_action('admin_enqueue_scripts', array($this->admin, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($this->admin, 'enqueue_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_nova_sound_fx_save_css_mapping', array($this->admin, 'ajax_save_css_mapping'));
        add_action('wp_ajax_nova_sound_fx_delete_css_mapping', array($this->admin, 'ajax_delete_css_mapping'));
        add_action('wp_ajax_nova_sound_fx_save_transition', array($this->admin, 'ajax_save_transition'));
        add_action('wp_ajax_nova_sound_fx_delete_transition', array($this->admin, 'ajax_delete_transition'));
        add_action('wp_ajax_nova_sound_fx_get_sound_library', array($this->admin, 'ajax_get_sound_library'));
        add_action('wp_ajax_nova_sound_fx_save_settings', array($this->admin, 'ajax_save_settings'));
    }
    
    private function define_public_hooks() {
        // Frontend scripts and styles
        add_action('wp_enqueue_scripts', array($this->public, 'enqueue_styles'));
        add_action('wp_enqueue_scripts', array($this->public, 'enqueue_scripts'));
        
        // Add sound data to frontend
        add_action('wp_footer', array($this->public, 'output_sound_data'));
        
        // AJAX for public use
        add_action('wp_ajax_nova_sound_fx_get_sounds', array($this->public, 'ajax_get_sounds'));
        add_action('wp_ajax_nopriv_nova_sound_fx_get_sounds', array($this->public, 'ajax_get_sounds'));
    }
    
    private function define_shortcode_hooks() {
        add_shortcode('nova_sound_fx_controls', array($this->shortcodes, 'render_controls'));
    }
    
    public function run() {
        // Plugin is loaded and ready
    }
    
    /**
     * Get all CSS mappings from database
     */
    public static function get_css_mappings() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'nova_sound_fx_css_mappings';
        
        $results = $wpdb->get_results(
            "SELECT m.*, p.guid as sound_url, p.post_title as sound_title 
             FROM $table_name m 
             LEFT JOIN {$wpdb->posts} p ON m.sound_id = p.ID 
             WHERE p.post_type = 'attachment' 
             ORDER BY m.created_at DESC"
        );
        
        return $results;
    }
    
    /**
     * Get all page transitions from database
     */
    public static function get_transitions() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'nova_sound_fx_transitions';
        
        $results = $wpdb->get_results(
            "SELECT t.*, p.guid as sound_url, p.post_title as sound_title 
             FROM $table_name t 
             LEFT JOIN {$wpdb->posts} p ON t.sound_id = p.ID 
             WHERE p.post_type = 'attachment' 
             ORDER BY t.priority DESC, t.created_at DESC"
        );
        
        return $results;
    }
    
    /**
     * Save CSS mapping
     */
    public static function save_css_mapping($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'nova_sound_fx_css_mappings';
        
        return $wpdb->insert(
            $table_name,
            array(
                'css_selector' => sanitize_text_field($data['css_selector']),
                'event_type' => sanitize_text_field($data['event_type']),
                'sound_id' => intval($data['sound_id']),
                'volume' => intval($data['volume']),
                'delay' => intval($data['delay'])
            ),
            array('%s', '%s', '%d', '%d', '%d')
        );
    }
    
    /**
     * Save page transition
     */
    public static function save_transition($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'nova_sound_fx_transitions';
        
        return $wpdb->insert(
            $table_name,
            array(
                'url_pattern' => sanitize_text_field($data['url_pattern']),
                'transition_type' => sanitize_text_field($data['transition_type']),
                'sound_id' => intval($data['sound_id']),
                'volume' => intval($data['volume']),
                'priority' => intval($data['priority'])
            ),
            array('%s', '%s', '%d', '%d', '%d')
        );
    }
    
    /**
     * Delete CSS mapping
     */
    public static function delete_css_mapping($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'nova_sound_fx_css_mappings';
        
        return $wpdb->delete($table_name, array('id' => intval($id)), array('%d'));
    }
    
    /**
     * Delete transition
     */
    public static function delete_transition($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'nova_sound_fx_transitions';
        
        return $wpdb->delete($table_name, array('id' => intval($id)), array('%d'));
    }
}
