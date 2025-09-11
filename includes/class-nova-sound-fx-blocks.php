<?php
/**
 * Gutenberg blocks functionality for Nova ImmersiSound
 */
class Nova_Sound_FX_Blocks {
    
    private $version;
    
    public function __construct($version) {
        $this->version = $version;
        $this->init();
    }
    
    /**
     * Initialize blocks
     */
    public function init() {
        add_action('init', array($this, 'register_blocks'));
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));
    }
    
    /**
     * Register Gutenberg blocks
     */
    public function register_blocks() {
        // Verificar que Gutenberg esté disponible
        if (!function_exists('register_block_type')) {
            return;
        }
        
        // Registrar bloque de controles de sonido
        register_block_type('nova-sound-fx/controls', array(
            'editor_script' => 'nova-sound-fx-blocks',
            'editor_style' => 'nova-sound-fx-blocks-editor',
            'render_callback' => array($this, 'render_controls_block'),
            'attributes' => array(
                'style' => array(
                    'type' => 'string',
                    'default' => 'minimal'
                ),
                'position' => array(
                    'type' => 'string',
                    'default' => 'bottom-right'
                ),
                'theme' => array(
                    'type' => 'string',
                    'default' => 'light'
                ),
                'showVolume' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'showSave' => array(
                    'type' => 'boolean',
                    'default' => true
                )
            )
        ));
        
        // Registrar bloque de botón con sonido
        register_block_type('nova-sound-fx/sound-button', array(
            'editor_script' => 'nova-sound-fx-blocks',
            'editor_style' => 'nova-sound-fx-blocks-editor',
            'render_callback' => array($this, 'render_sound_button_block'),
            'attributes' => array(
                'text' => array(
                    'type' => 'string',
                    'default' => 'Click me!'
                ),
                'soundId' => array(
                    'type' => 'number',
                    'default' => 0
                ),
                'eventType' => array(
                    'type' => 'string',
                    'default' => 'click'
                ),
                'volume' => array(
                    'type' => 'number',
                    'default' => 100
                ),
                'className' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'buttonStyle' => array(
                    'type' => 'string',
                    'default' => 'primary'
                )
            )
        ));
    }
    
    /**
     * Enqueue block editor assets
     */
    public function enqueue_block_editor_assets() {
        wp_enqueue_script(
            'nova-sound-fx-blocks',
            NOVA_SOUND_FX_PLUGIN_URL . 'admin/js/nova-sound-fx-blocks.js',
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n'),
            $this->version
        );
        
        wp_enqueue_style(
            'nova-sound-fx-blocks-editor',
            NOVA_SOUND_FX_PLUGIN_URL . 'admin/css/nova-sound-fx-blocks-editor.css',
            array('wp-edit-blocks'),
            $this->version
        );
        
        // Localizar datos para el editor
        wp_localize_script('nova-sound-fx-blocks', 'novaSoundFXBlocks', array(
            'sounds' => $this->get_sounds_for_editor(),
            'eventTypes' => Nova_Sound_FX_Utils::get_event_types(),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('nova_sound_fx_admin')
        ));
    }
    
    /**
     * Get sounds for block editor
     */
    private function get_sounds_for_editor() {
        $args = array(
            'post_type' => 'attachment',
            'post_mime_type' => array('audio/mpeg', 'audio/wav', 'audio/x-wav', 'audio/wave'),
            'post_status' => 'inherit',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        );
        
        $query = new WP_Query($args);
        $sounds = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $id = get_the_ID();
                $sounds[] = array(
                    'value' => $id,
                    'label' => get_the_title(),
                    'url' => wp_get_attachment_url($id)
                );
            }
            wp_reset_postdata();
        }
        
        return $sounds;
    }
    
    /**
     * Render controls block
     */
    public function render_controls_block($attributes) {
        $shortcode_atts = array(
            'style' => $attributes['style'],
            'position' => $attributes['position'],
            'theme' => $attributes['theme'],
            'show_volume' => $attributes['showVolume'] ? 'yes' : 'no',
            'show_save' => $attributes['showSave'] ? 'yes' : 'no'
        );
        
        $shortcodes = new Nova_Sound_FX_Shortcodes($this->version);
        return $shortcodes->render_controls($shortcode_atts);
    }
    
    /**
     * Render sound button block
     */
    public function render_sound_button_block($attributes) {
        $button_classes = array(
            'nova-sound-button',
            'wp-block-button__link'
        );
        
        if (!empty($attributes['className'])) {
            $button_classes[] = $attributes['className'];
        }
        
        if (!empty($attributes['buttonStyle'])) {
            $button_classes[] = 'is-style-' . $attributes['buttonStyle'];
        }
        
        // Generar ID único para el botón
        $button_id = 'nova-sound-button-' . uniqid();
        
        // Agregar mapeo de sonido dinámicamente si hay un sonido seleccionado
        $sound_script = '';
        if (!empty($attributes['soundId'])) {
            $sound_url = wp_get_attachment_url($attributes['soundId']);
            if ($sound_url) {
                $sound_script = sprintf(
                    '<script>
                    document.addEventListener("DOMContentLoaded", function() {
                        if (window.novaSoundFXInstance) {
                            window.novaSoundFXInstance.addSoundMapping({
                                css_selector: "#%s",
                                event_type: "%s",
                                sound_url: "%s",
                                volume: %d,
                                delay: 0
                            });
                        }
                    });
                    </script>',
                    esc_js($button_id),
                    esc_js($attributes['eventType']),
                    esc_js($sound_url),
                    intval($attributes['volume'])
                );
            }
        }
        
        $button_html = sprintf(
            '<div class="wp-block-button">
                <button id="%s" class="%s" type="button">%s</button>
            </div>%s',
            esc_attr($button_id),
            esc_attr(implode(' ', $button_classes)),
            esc_html($attributes['text']),
            $sound_script
        );
        
        return $button_html;
    }
}
