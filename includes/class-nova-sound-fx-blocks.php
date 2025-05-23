<?php
/**
 * Gutenberg Block Integration
 */
class Nova_Sound_FX_Blocks {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'register_blocks'));
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));
    }
    
    /**
     * Register Gutenberg blocks
     */
    public function register_blocks() {
        if (!function_exists('register_block_type')) {
            return;
        }
        
        // Register Sound Button Block
        register_block_type('nova-sound-fx/sound-button', array(
            'editor_script' => 'nova-sound-fx-blocks',
            'editor_style' => 'nova-sound-fx-blocks-editor',
            'style' => 'nova-sound-fx-blocks',
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
                'volume' => array(
                    'type' => 'number',
                    'default' => 100
                ),
                'className' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'align' => array(
                    'type' => 'string',
                    'default' => 'none'
                )
            )
        ));
        
        // Register Sound Controls Block
        register_block_type('nova-sound-fx/sound-controls', array(
            'editor_script' => 'nova-sound-fx-blocks',
            'editor_style' => 'nova-sound-fx-blocks-editor',
            'render_callback' => array($this, 'render_sound_controls_block'),
            'attributes' => array(
                'style' => array(
                    'type' => 'string',
                    'default' => 'minimal'
                ),
                'position' => array(
                    'type' => 'string',
                    'default' => 'inline'
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
    }
    
    /**
     * Enqueue block editor assets
     */
    public function enqueue_block_editor_assets() {
        wp_enqueue_script(
            'nova-sound-fx-blocks',
            NOVA_SOUND_FX_PLUGIN_URL . 'admin/js/nova-sound-fx-blocks.js',
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n'),
            NOVA_SOUND_FX_VERSION
        );
        
        wp_enqueue_style(
            'nova-sound-fx-blocks-editor',
            NOVA_SOUND_FX_PLUGIN_URL . 'admin/css/nova-sound-fx-blocks-editor.css',
            array('wp-edit-blocks'),
            NOVA_SOUND_FX_VERSION
        );
        
        // Get available sounds for the block editor
        $sounds = $this->get_sounds_for_blocks();
        
        wp_localize_script('nova-sound-fx-blocks', 'novaSoundFXBlocks', array(
            'sounds' => $sounds,
            'pluginUrl' => NOVA_SOUND_FX_PLUGIN_URL
        ));
    }
    
    /**
     * Get sounds for block editor
     */
    private function get_sounds_for_blocks() {
        $args = array(
            'post_type' => 'attachment',
            'post_mime_type' => Nova_Sound_FX_Utils::get_supported_mime_types(),
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
     * Render sound button block
     */
    public function render_sound_button_block($attributes) {
        $text = esc_html($attributes['text']);
        $sound_id = intval($attributes['soundId']);
        $volume = intval($attributes['volume']);
        $class_name = esc_attr($attributes['className']);
        $align = esc_attr($attributes['align']);
        
        if (!$sound_id) {
            return '<p>' . __('Please select a sound for this button.', 'nova-sound-fx') . '</p>';
        }
        
        $sound_url = wp_get_attachment_url($sound_id);
        if (!$sound_url) {
            return '<p>' . __('Sound not found.', 'nova-sound-fx') . '</p>';
        }
        
        $button_classes = 'nova-sound-button wp-block-button__link';
        if ($class_name) {
            $button_classes .= ' ' . $class_name;
        }
        
        $wrapper_classes = 'wp-block-button';
        if ($align && $align !== 'none') {
            $wrapper_classes .= ' align' . $align;
        }
        
        ob_start();
        ?>
        <div class="<?php echo esc_attr($wrapper_classes); ?>">
            <button 
                class="<?php echo esc_attr($button_classes); ?>"
                data-sound-url="<?php echo esc_url($sound_url); ?>"
                data-volume="<?php echo esc_attr($volume); ?>"
                onclick="if(window.NovaSoundFX) { window.NovaSoundFX.play('<?php echo esc_js($sound_url); ?>', { volume: <?php echo esc_js($volume / 100); ?> }); }"
            >
                <?php echo $text; ?>
            </button>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render sound controls block
     */
    public function render_sound_controls_block($attributes) {
        $style = esc_attr($attributes['style']);
        $position = esc_attr($attributes['position']);
        $theme = esc_attr($attributes['theme']);
        $show_volume = $attributes['showVolume'] ? 'yes' : 'no';
        $show_save = $attributes['showSave'] ? 'yes' : 'no';
        
        // For inline position, don't use fixed positioning
        if ($position === 'inline') {
            $position = '';
        }
        
        $shortcode_atts = array(
            'style' => $style,
            'theme' => $theme,
            'show_volume' => $show_volume,
            'show_save' => $show_save
        );
        
        if ($position) {
            $shortcode_atts['position'] = $position;
        }
        
        $shortcode_string = '[nova_sound_fx_controls';
        foreach ($shortcode_atts as $key => $value) {
            $shortcode_string .= ' ' . $key . '="' . $value . '"';
        }
        $shortcode_string .= ']';
        
        return do_shortcode($shortcode_string);
    }
}

// Initialize blocks
new Nova_Sound_FX_Blocks();
