<?php
/**
 * Public functionality
 */
class Nova_Sound_FX_Public {
    
    private $version;
    
    public function __construct($version) {
        $this->version = $version;
    }
    
    /**
     * Enqueue public styles
     */
    public function enqueue_styles() {
        $settings = get_option('nova_sound_fx_settings', array());
        
        // Check if sounds are enabled
        if (empty($settings['enable_sounds'])) {
            return;
        }
        
        // Check preview mode
        if (!empty($settings['preview_mode']) && !current_user_can('manage_options')) {
            return;
        }
        
        wp_enqueue_style(
            'nova-sound-fx-public',
            NOVA_SOUND_FX_PLUGIN_URL . 'public/css/nova-sound-fx-public.css',
            array(),
            $this->version
        );
    }
    
    /**
     * Enqueue public scripts
     */
    public function enqueue_scripts() {
        $settings = get_option('nova_sound_fx_settings', array());
        
        // Check if sounds are enabled
        if (empty($settings['enable_sounds'])) {
            return;
        }
        
        // Check preview mode
        if (!empty($settings['preview_mode']) && !current_user_can('manage_options')) {
            return;
        }
        
        wp_enqueue_script(
            'nova-sound-fx-public',
            NOVA_SOUND_FX_PLUGIN_URL . 'public/js/nova-sound-fx-public.js',
            array('jquery'),
            $this->version,
            true
        );
        
        // Localize script
        wp_localize_script('nova-sound-fx-public', 'novaSoundFX', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('nova_sound_fx_public'),
            'settings' => $settings,
            'currentUrl' => home_url(add_query_arg(array())),
            'isMobile' => wp_is_mobile(),
            'sounds' => $this->get_all_sounds_data()
        ));
    }
    
    /**
     * Output sound data to frontend
     */
    public function output_sound_data() {
        $settings = get_option('nova_sound_fx_settings', array());
        
        // Check if sounds are enabled
        if (empty($settings['enable_sounds'])) {
            return;
        }
        
        // Check preview mode
        if (!empty($settings['preview_mode']) && !current_user_can('manage_options')) {
            return;
        }
        
        $css_mappings = Nova_Sound_FX::get_css_mappings();
        $transitions = Nova_Sound_FX::get_transitions();
        
        ?>
        <script type="text/javascript">
            window.NovaSoundFXData = {
                cssMappings: <?php echo json_encode($css_mappings); ?>,
                transitions: <?php echo json_encode($transitions); ?>,
                version: '<?php echo esc_js($this->version); ?>'
            };
        </script>
        <?php
    }
    
    /**
     * Get all sounds data for frontend
     */
    private function get_all_sounds_data() {
        $sounds = array();
        
        // Get all CSS mappings
        $mappings = Nova_Sound_FX::get_css_mappings();
        foreach ($mappings as $mapping) {
            if (!empty($mapping->sound_url)) {
                $sounds[$mapping->sound_id] = $mapping->sound_url;
            }
        }
        
        // Get all transitions
        $transitions = Nova_Sound_FX::get_transitions();
        foreach ($transitions as $transition) {
            if (!empty($transition->sound_url)) {
                $sounds[$transition->sound_id] = $transition->sound_url;
            }
        }
        
        return $sounds;
    }
    
    /**
     * AJAX: Get sounds for public use
     */
    public function ajax_get_sounds() {
        // This endpoint is public, no capability check needed
        check_ajax_referer('nova_sound_fx_public', 'nonce');
        
        $sounds = $this->get_all_sounds_data();
        wp_send_json_success($sounds);
    }
}
