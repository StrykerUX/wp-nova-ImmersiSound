<?php
/**
 * Admin functionality
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
                    <?php _e('Sound Library', 'nova-sound-fx'); ?>
                </a>
                <a href="#css-mapping" class="nav-tab" data-tab="css-mapping">
                    <?php _e('CSS Sound Mapping', 'nova-sound-fx'); ?>
                </a>
                <a href="#page-transitions" class="nav-tab" data-tab="page-transitions">
                    <?php _e('Page Transitions', 'nova-sound-fx'); ?>
                </a>
                <a href="#settings" class="nav-tab" data-tab="settings">
                    <?php _e('Settings', 'nova-sound-fx'); ?>
                </a>
            </div>
            
            <div class="tab-content">
                <!-- Sound Library Tab -->
                <div id="sound-library" class="tab-pane active">
                    <h2><?php _e('Sound Library', 'nova-sound-fx'); ?></h2>
                    <p><?php _e('Upload and manage your sound effects. Supported formats: MP3, WAV', 'nova-sound-fx'); ?></p>
                    
                    <div class="sound-upload-section">
                        <button id="nova-upload-sound" class="button button-primary">
                            <?php _e('Upload New Sound', 'nova-sound-fx'); ?>
                        </button>
                        
                        <div class="sound-categories">
                            <label><?php _e('Filter by category:', 'nova-sound-fx'); ?></label>
                            <select id="sound-category-filter">
                                <option value=""><?php _e('All Categories', 'nova-sound-fx'); ?></option>
                                <option value="hover"><?php _e('Hover Effects', 'nova-sound-fx'); ?></option>
                                <option value="click"><?php _e('Click Effects', 'nova-sound-fx'); ?></option>
                                <option value="transition"><?php _e('Page Transitions', 'nova-sound-fx'); ?></option>
                                <option value="notification"><?php _e('Notifications', 'nova-sound-fx'); ?></option>
                                <option value="ambient"><?php _e('Ambient', 'nova-sound-fx'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <div id="sound-library-grid" class="sound-grid">
                        <!-- Sound items will be loaded here via AJAX -->
                    </div>
                </div>
                
                <!-- CSS Mapping Tab -->
                <div id="css-mapping" class="tab-pane">
                    <h2><?php _e('CSS Sound Mapping', 'nova-sound-fx'); ?></h2>
                    <p><?php _e('Assign sound effects to CSS selectors and events', 'nova-sound-fx'); ?></p>
                    
                    <button id="add-css-mapping" class="button button-primary">
                        <?php _e('Add New Mapping', 'nova-sound-fx'); ?>
                    </button>
                    
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('CSS Selector', 'nova-sound-fx'); ?></th>
                                <th><?php _e('Event', 'nova-sound-fx'); ?></th>
                                <th><?php _e('Sound', 'nova-sound-fx'); ?></th>
                                <th><?php _e('Volume', 'nova-sound-fx'); ?></th>
                                <th><?php _e('Delay (ms)', 'nova-sound-fx'); ?></th>
                                <th><?php _e('Actions', 'nova-sound-fx'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="css-mappings-list">
                            <!-- Mappings will be loaded here -->
                        </tbody>
                    </table>
                </div>
                
                <!-- Page Transitions Tab -->
                <div id="page-transitions" class="tab-pane">
                    <h2><?php _e('Page Transitions', 'nova-sound-fx'); ?></h2>
                    <p><?php _e('Set up sounds for page entries and exits', 'nova-sound-fx'); ?></p>
                    
                    <div class="transition-settings">
                        <h3><?php _e('Global Transitions', 'nova-sound-fx'); ?></h3>
                        <div class="global-transitions">
                            <div class="transition-row">
                                <label><?php _e('Default Page Entry Sound:', 'nova-sound-fx'); ?></label>
                                <select id="global-entry-sound" class="sound-select">
                                    <option value=""><?php _e('None', 'nova-sound-fx'); ?></option>
                                </select>
                                <input type="range" id="global-entry-volume" min="0" max="100" value="50">
                                <span class="volume-value">50%</span>
                            </div>
                            <div class="transition-row">
                                <label><?php _e('Default Page Exit Sound:', 'nova-sound-fx'); ?></label>
                                <select id="global-exit-sound" class="sound-select">
                                    <option value=""><?php _e('None', 'nova-sound-fx'); ?></option>
                                </select>
                                <input type="range" id="global-exit-volume" min="0" max="100" value="50">
                                <span class="volume-value">50%</span>
                            </div>
                        </div>
                        
                        <h3><?php _e('URL-Specific Transitions', 'nova-sound-fx'); ?></h3>
                        <button id="add-url-transition" class="button button-primary">
                            <?php _e('Add URL Pattern', 'nova-sound-fx'); ?>
                        </button>
                        
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('URL Pattern', 'nova-sound-fx'); ?></th>
                                    <th><?php _e('Type', 'nova-sound-fx'); ?></th>
                                    <th><?php _e('Sound', 'nova-sound-fx'); ?></th>
                                    <th><?php _e('Volume', 'nova-sound-fx'); ?></th>
                                    <th><?php _e('Priority', 'nova-sound-fx'); ?></th>
                                    <th><?php _e('Actions', 'nova-sound-fx'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="url-transitions-list">
                                <!-- URL transitions will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Settings Tab -->
                <div id="settings" class="tab-pane">
                    <h2><?php _e('Settings', 'nova-sound-fx'); ?></h2>
                    
                    <form id="nova-sound-fx-settings">
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="enable-sounds"><?php _e('Enable Sounds', 'nova-sound-fx'); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" id="enable-sounds" name="enable_sounds" value="1" checked>
                                    <p class="description"><?php _e('Master switch to enable/disable all sound effects', 'nova-sound-fx'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="default-volume"><?php _e('Default Volume', 'nova-sound-fx'); ?></label>
                                </th>
                                <td>
                                    <input type="range" id="default-volume" name="default_volume" min="0" max="100" value="50">
                                    <span class="volume-value">50%</span>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="mobile-enabled"><?php _e('Enable on Mobile', 'nova-sound-fx'); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" id="mobile-enabled" name="mobile_enabled" value="1">
                                    <p class="description"><?php _e('Enable sound effects on mobile devices', 'nova-sound-fx'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="respect-prefers-reduced-motion"><?php _e('Respect Accessibility Settings', 'nova-sound-fx'); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" id="respect-prefers-reduced-motion" name="respect_prefers_reduced_motion" value="1" checked>
                                    <p class="description"><?php _e('Disable sounds when user has prefers-reduced-motion enabled', 'nova-sound-fx'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="preview-mode"><?php _e('Preview Mode', 'nova-sound-fx'); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" id="preview-mode" name="preview_mode" value="1">
                                    <p class="description"><?php _e('Enable sounds only for administrators (for testing)', 'nova-sound-fx'); ?></p>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" class="button button-primary"><?php _e('Save Settings', 'nova-sound-fx'); ?></button>
                        </p>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Modal for CSS Mapping -->
        <div id="css-mapping-modal" class="nova-modal" style="display:none;">
            <div class="nova-modal-content">
                <span class="nova-modal-close">&times;</span>
                <h2><?php _e('Add CSS Sound Mapping', 'nova-sound-fx'); ?></h2>
                <form id="css-mapping-form">
                    <input type="hidden" id="mapping-id" value="">
                    <table class="form-table">
                        <tr>
                            <th><label for="css-selector"><?php _e('CSS Selector', 'nova-sound-fx'); ?></label></th>
                            <td>
                                <input type="text" id="css-selector" class="regular-text" placeholder=".button, #header">
                                <p class="description"><?php _e('Enter a valid CSS selector', 'nova-sound-fx'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="event-type"><?php _e('Event Type', 'nova-sound-fx'); ?></label></th>
                            <td>
                                <select id="event-type">
                                    <option value="hover"><?php _e('Hover', 'nova-sound-fx'); ?></option>
                                    <option value="click"><?php _e('Click', 'nova-sound-fx'); ?></option>
                                    <option value="focus"><?php _e('Focus', 'nova-sound-fx'); ?></option>
                                    <option value="blur"><?php _e('Blur', 'nova-sound-fx'); ?></option>
                                    <option value="mouseenter"><?php _e('Mouse Enter', 'nova-sound-fx'); ?></option>
                                    <option value="mouseleave"><?php _e('Mouse Leave', 'nova-sound-fx'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="mapping-sound"><?php _e('Sound Effect', 'nova-sound-fx'); ?></label></th>
                            <td>
                                <select id="mapping-sound" class="sound-select">
                                    <option value=""><?php _e('Select a sound', 'nova-sound-fx'); ?></option>
                                </select>
                                <button type="button" id="preview-mapping-sound" class="button"><?php _e('Preview', 'nova-sound-fx'); ?></button>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="mapping-volume"><?php _e('Volume', 'nova-sound-fx'); ?></label></th>
                            <td>
                                <input type="range" id="mapping-volume" min="0" max="100" value="100">
                                <span class="volume-value">100%</span>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="mapping-delay"><?php _e('Delay (ms)', 'nova-sound-fx'); ?></label></th>
                            <td>
                                <input type="number" id="mapping-delay" min="0" max="5000" value="0">
                                <p class="description"><?php _e('Delay before playing the sound', 'nova-sound-fx'); ?></p>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php _e('Save Mapping', 'nova-sound-fx'); ?></button>
                        <button type="button" class="button nova-modal-cancel"><?php _e('Cancel', 'nova-sound-fx'); ?></button>
                    </p>
                </form>
            </div>
        </div>
        
        <!-- Modal for URL Transitions -->
        <div id="url-transition-modal" class="nova-modal" style="display:none;">
            <div class="nova-modal-content">
                <span class="nova-modal-close">&times;</span>
                <h2><?php _e('Add URL Transition', 'nova-sound-fx'); ?></h2>
                <form id="url-transition-form">
                    <input type="hidden" id="transition-id" value="">
                    <table class="form-table">
                        <tr>
                            <th><label for="url-pattern"><?php _e('URL Pattern', 'nova-sound-fx'); ?></label></th>
                            <td>
                                <input type="text" id="url-pattern" class="regular-text" placeholder="/404, */contact/*, regex:.*\.pdf$">
                                <p class="description"><?php _e('Use * for wildcards, prefix with regex: for regex patterns', 'nova-sound-fx'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="transition-type"><?php _e('Transition Type', 'nova-sound-fx'); ?></label></th>
                            <td>
                                <select id="transition-type">
                                    <option value="enter"><?php _e('Page Enter', 'nova-sound-fx'); ?></option>
                                    <option value="exit"><?php _e('Page Exit', 'nova-sound-fx'); ?></option>
                                    <option value="both"><?php _e('Both', 'nova-sound-fx'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="transition-sound"><?php _e('Sound Effect', 'nova-sound-fx'); ?></label></th>
                            <td>
                                <select id="transition-sound" class="sound-select">
                                    <option value=""><?php _e('Select a sound', 'nova-sound-fx'); ?></option>
                                </select>
                                <button type="button" id="preview-transition-sound" class="button"><?php _e('Preview', 'nova-sound-fx'); ?></button>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="transition-volume"><?php _e('Volume', 'nova-sound-fx'); ?></label></th>
                            <td>
                                <input type="range" id="transition-volume" min="0" max="100" value="100">
                                <span class="volume-value">100%</span>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="transition-priority"><?php _e('Priority', 'nova-sound-fx'); ?></label></th>
                            <td>
                                <input type="number" id="transition-priority" min="0" max="100" value="0">
                                <p class="description"><?php _e('Higher priority patterns override lower ones', 'nova-sound-fx'); ?></p>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php _e('Save Transition', 'nova-sound-fx'); ?></button>
                        <button type="button" class="button nova-modal-cancel"><?php _e('Cancel', 'nova-sound-fx'); ?></button>
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
                'confirm_delete' => __('Are you sure you want to delete this?', 'nova-sound-fx'),
                'error' => __('An error occurred. Please try again.', 'nova-sound-fx'),
                'saved' => __('Settings saved successfully!', 'nova-sound-fx')
            )
        ));
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
            'css_selector' => $_POST['css_selector'],
            'event_type' => $_POST['event_type'],
            'sound_id' => $_POST['sound_id'],
            'volume' => $_POST['volume'],
            'delay' => $_POST['delay']
        );
        
        if (Nova_Sound_FX::save_css_mapping($data)) {
            wp_send_json_success(array('message' => __('CSS mapping saved successfully', 'nova-sound-fx')));
        } else {
            wp_send_json_error(array('message' => __('Failed to save CSS mapping', 'nova-sound-fx')));
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
            wp_send_json_success(array('message' => __('CSS mapping deleted successfully', 'nova-sound-fx')));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete CSS mapping', 'nova-sound-fx')));
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
            'url_pattern' => $_POST['url_pattern'],
            'transition_type' => $_POST['transition_type'],
            'sound_id' => $_POST['sound_id'],
            'volume' => $_POST['volume'],
            'priority' => $_POST['priority']
        );
        
        if (Nova_Sound_FX::save_transition($data)) {
            wp_send_json_success(array('message' => __('Transition saved successfully', 'nova-sound-fx')));
        } else {
            wp_send_json_error(array('message' => __('Failed to save transition', 'nova-sound-fx')));
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
            wp_send_json_success(array('message' => __('Transition deleted successfully', 'nova-sound-fx')));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete transition', 'nova-sound-fx')));
        }
    }
    
    /**
     * AJAX: Get sound library
     */
    public function ajax_get_sound_library() {
        check_ajax_referer('nova_sound_fx_admin', 'nonce');
        
        $args = array(
            'post_type' => 'attachment',
            'post_mime_type' => array('audio/mpeg', 'audio/wav', 'audio/x-wav', 'audio/wave'),
            'post_status' => 'inherit',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
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
                    'date' => get_the_date()
                );
            }
            wp_reset_postdata();
        }
        
        wp_send_json_success($sounds);
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
        
        wp_send_json_success(array('message' => __('Settings saved successfully', 'nova-sound-fx')));
    }
}
