<?php
/**
 * Public functionality - Versión Mejorada
 * Con correcciones para evitar conflictos de URL y mejorar la carga de sonidos
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
        
        // No cargar en páginas de admin
        if (is_admin()) {
            return;
        }
        
        wp_enqueue_script(
            'nova-sound-fx-public',
            NOVA_SOUND_FX_PLUGIN_URL . 'public/js/nova-sound-fx-public.js',
            array('jquery'),
            $this->version,
            true
        );
        
        // Localize script con datos mejorados
        wp_localize_script('nova-sound-fx-public', 'novaSoundFX', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('nova_sound_fx_public'),
            'settings' => $settings,
            'currentUrl' => $this->get_current_url(),
            'isMobile' => wp_is_mobile(),
            'sounds' => $this->get_all_sounds_data(),
            'isPreviewMode' => !empty($settings['preview_mode']) && current_user_can('manage_options'),
            'i18n' => array(
                'consentTitle' => __('🎵 Experiencia de Audio Mejorada', 'nova-sound-fx'),
                'consentDescription' => __('Este sitio utiliza efectos de sonido interactivos para mejorar tu experiencia de navegación. Los sonidos se reproducirán cuando interactúes con elementos específicos de la página.', 'nova-sound-fx'),
                'consentBenefit1' => __('Feedback inmediato en interacciones', 'nova-sound-fx'),
                'consentBenefit2' => __('Control total del volumen', 'nova-sound-fx'),
                'consentBenefit3' => __('Puedes desactivarlo en cualquier momento', 'nova-sound-fx'),
                'consentAccept' => __('Activar Sonidos', 'nova-sound-fx'),
                'consentReject' => __('Continuar en Silencio', 'nova-sound-fx'),
                'consentRemember' => __('Recordar mi preferencia', 'nova-sound-fx'),
                'soundsActivated' => __('¡Sonidos activados! 🎵', 'nova-sound-fx'),
                'soundsDeactivated' => __('Sonidos desactivados. Puedes activarlos desde el control flotante.', 'nova-sound-fx'),
                'volumeLabel' => __('Volumen', 'nova-sound-fx'),
                'settingsTitle' => __('Configuración de Audio', 'nova-sound-fx'),
                'resetConsent' => __('Restablecer preferencias de consentimiento', 'nova-sound-fx'),
                'resetAll' => __('Restablecer Todo', 'nova-sound-fx'),
                'close' => __('Cerrar', 'nova-sound-fx'),
                'resetConfirm' => __('¿Estás seguro de que quieres restablecer todas las preferencias?', 'nova-sound-fx'),
                'preferencesReset' => __('Preferencias restablecidas', 'nova-sound-fx'),
                'preferencesSaved' => __('¡Preferencias guardadas!', 'nova-sound-fx'),
                'toggleAudio' => __('Control de Audio', 'nova-sound-fx'),
                'settings' => __('Configuración', 'nova-sound-fx')
            )
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
        
        // No output en páginas de admin
        if (is_admin()) {
            return;
        }
        
        $css_mappings = Nova_Sound_FX::get_css_mappings();
        $transitions = Nova_Sound_FX::get_transitions();
        
        // Agregar clase de modo preview al body si está activo
        if (!empty($settings['preview_mode']) && current_user_can('manage_options')) {
            echo '<script>document.body.classList.add("nova-sound-fx-preview");</script>';
        }
        
        ?>
        <script type="text/javascript">
            window.NovaSoundFXData = {
                cssMappings: <?php echo json_encode($this->prepare_mappings_for_output($css_mappings)); ?>,
                transitions: <?php echo json_encode($this->prepare_transitions_for_output($transitions)); ?>,
                version: '<?php echo esc_js($this->version); ?>',
                homeUrl: '<?php echo esc_js(home_url()); ?>'
            };
        </script>
        <?php
    }
    
    /**
     * Get current URL without causing conflicts
     */
    private function get_current_url() {
        global $wp;
        return home_url(add_query_arg(array($_GET), $wp->request));
    }
    
    /**
     * Prepare mappings for output
     */
    private function prepare_mappings_for_output($mappings) {
        $prepared = array();
        
        foreach ($mappings as $mapping) {
            // Verificar que el sonido existe
            if (!empty($mapping->sound_url)) {
                $prepared[] = array(
                    'id' => $mapping->id,
                    'css_selector' => $mapping->css_selector,
                    'event_type' => $mapping->event_type,
                    'sound_id' => $mapping->sound_id,
                    'sound_url' => $mapping->sound_url,
                    'volume' => intval($mapping->volume),
                    'delay' => intval($mapping->delay),
                    'show_visual_effect' => isset($mapping->show_visual_effect) ? intval($mapping->show_visual_effect) : 1,
                    'show_speaker_icon' => isset($mapping->show_speaker_icon) ? intval($mapping->show_speaker_icon) : 1
                );
            }
        }
        
        return $prepared;
    }
    
    /**
     * Prepare transitions for output
     */
    private function prepare_transitions_for_output($transitions) {
        $prepared = array();
        
        foreach ($transitions as $transition) {
            // Verificar que el sonido existe
            if (!empty($transition->sound_url)) {
                $prepared[] = array(
                    'id' => $transition->id,
                    'url_pattern' => $transition->url_pattern,
                    'transition_type' => $transition->transition_type,
                    'sound_id' => $transition->sound_id,
                    'sound_url' => $transition->sound_url,
                    'volume' => intval($transition->volume),
                    'priority' => intval($transition->priority)
                );
            }
        }
        
        return $prepared;
    }
    
    /**
     * Get all sounds data for frontend
     */
    private function get_all_sounds_data() {
        $sounds = array();
        
        // Get all CSS mappings
        $mappings = Nova_Sound_FX::get_css_mappings();
        foreach ($mappings as $mapping) {
            if (!empty($mapping->sound_url) && !empty($mapping->sound_id)) {
                $sounds[$mapping->sound_id] = array(
                    'id' => $mapping->sound_id,
                    'url' => $mapping->sound_url,
                    'title' => $mapping->sound_title
                );
            }
        }
        
        // Get all transitions
        $transitions = Nova_Sound_FX::get_transitions();
        foreach ($transitions as $transition) {
            if (!empty($transition->sound_url) && !empty($transition->sound_id)) {
                $sounds[$transition->sound_id] = array(
                    'id' => $transition->sound_id,
                    'url' => $transition->sound_url,
                    'title' => $transition->sound_title
                );
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
    
    /**
     * Output Buy Me a Coffee widget
     */
    public function output_bmc_widget() {
        $settings = get_option('nova_sound_fx_settings', array());
        
        // Check if BMC widget is enabled
        if (empty($settings['bmc_widget_enabled'])) {
            return;
        }
        
        // Check pages setting
        $widget_pages = isset($settings['bmc_widget_pages']) ? $settings['bmc_widget_pages'] : 'all';
        
        // Determine if we should show the widget on this page
        $show_widget = false;
        switch ($widget_pages) {
            case 'all':
                $show_widget = true;
                break;
            case 'home':
                $show_widget = is_front_page() || is_home();
                break;
            case 'posts':
                $show_widget = is_single();
                break;
            case 'pages':
                $show_widget = is_page();
                break;
        }
        
        if (!$show_widget) {
            return;
        }
        
        $position = isset($settings['bmc_widget_position']) ? $settings['bmc_widget_position'] : 'Right';
        
        ?>
        <script data-name="BMC-Widget" 
                data-cfasync="false" 
                src="https://cdnjs.buymeacoffee.com/1.0.0/widget.prod.min.js" 
                data-id="imstryker" 
                data-description="Support me on Buy me a coffee!" 
                data-message="🚀 Sponsor future improvements" 
                data-color="#5F7FFF" 
                data-position="<?php echo esc_attr($position); ?>" 
                data-x_margin="18" 
                data-y_margin="18"></script>
        <?php
    }
}
