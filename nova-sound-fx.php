<?php
/**
 * Plugin Name: Nova ImmersiSound
 * Plugin URI: https://imstryker.com
 * Description: Add immersive sound effects to your WordPress site with CSS selectors and page transitions
 * Version: 1.2.0
 * Author: ImStryker
 * Author URI: https://imstryker.com
 * License: GPL v2 or later
 * Text Domain: nova-sound-fx
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('NOVA_SOUND_FX_VERSION', '1.2.0');
define('NOVA_SOUND_FX_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NOVA_SOUND_FX_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NOVA_SOUND_FX_PLUGIN_FILE', __FILE__);

// Activation hook
register_activation_hook(__FILE__, 'nova_sound_fx_activate');
function nova_sound_fx_activate() {
    // Create database tables
    nova_sound_fx_create_tables();
    
    // Set default options - EVERYTHING OFF until user accepts terms
    add_option('nova_sound_fx_version', NOVA_SOUND_FX_VERSION);
    add_option('nova_sound_fx_settings', array(
        'enable_sounds' => false, // Off until setup
        'default_volume' => 50,
        'mobile_enabled' => false,
        'respect_prefers_reduced_motion' => true,
        'preview_mode' => false,
        'save_user_preferences' => false, // Off until consent
        'show_support_widget' => false, // Off until consent
        'show_admin_banner' => false, // Off until consent
        'terms_accepted' => false,
        'setup_complete' => false
    ));
    
    // Set activation redirect flag
    set_transient('nova_sound_fx_activation_redirect', true, 30);
    
    // Flush rewrite rules to prevent URL conflicts
    flush_rewrite_rules();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'nova_sound_fx_deactivate');
function nova_sound_fx_deactivate() {
    // Clean up temporary data
    flush_rewrite_rules();
}

// Add plugin action links
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'nova_sound_fx_action_links');
function nova_sound_fx_action_links($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=nova-sound-fx') . '">' . __('Settings', 'nova-sound-fx') . '</a>';
    
    // Only show support link if user has accepted terms
    $settings = get_option('nova_sound_fx_settings', array());
    if (!empty($settings['terms_accepted']) && !empty($settings['show_support_links'])) {
        $support_link = '<a href="https://buymeacoffee.com/imstryker" target="_blank" style="color: #11ba82; font-weight: bold;">' . __('Support', 'nova-sound-fx') . '</a>';
        array_unshift($links, $support_link);
    }
    
    array_unshift($links, $settings_link);
    
    return $links;
}

// Create database tables
function nova_sound_fx_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    // CSS Sound Mappings table
    $table_css_mappings = $wpdb->prefix . 'nova_sound_fx_css_mappings';
    $sql_css = "CREATE TABLE $table_css_mappings (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        css_selector varchar(255) NOT NULL,
        event_type varchar(50) NOT NULL,
        sound_id mediumint(9) NOT NULL,
        volume int(3) DEFAULT 100,
        delay int(5) DEFAULT 0,
        show_visual_effect tinyint(1) DEFAULT 1,
        show_speaker_icon tinyint(1) DEFAULT 1,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY css_selector (css_selector),
        KEY event_type (event_type)
    ) $charset_collate;";
    
    // Page Transitions table
    $table_transitions = $wpdb->prefix . 'nova_sound_fx_transitions';
    $sql_transitions = "CREATE TABLE $table_transitions (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        url_pattern varchar(255) NOT NULL,
        transition_type varchar(50) NOT NULL,
        sound_id mediumint(9) NOT NULL,
        volume int(3) DEFAULT 100,
        priority int(3) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY url_pattern (url_pattern),
        KEY transition_type (transition_type)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_css);
    dbDelta($sql_transitions);
}

// Include required files
require_once NOVA_SOUND_FX_PLUGIN_DIR . 'includes/class-nova-sound-fx.php';
require_once NOVA_SOUND_FX_PLUGIN_DIR . 'includes/class-nova-sound-fx-admin.php';
require_once NOVA_SOUND_FX_PLUGIN_DIR . 'includes/class-nova-sound-fx-public.php';
require_once NOVA_SOUND_FX_PLUGIN_DIR . 'includes/class-nova-sound-fx-shortcodes.php';
require_once NOVA_SOUND_FX_PLUGIN_DIR . 'includes/class-nova-sound-fx-ajax.php';
require_once NOVA_SOUND_FX_PLUGIN_DIR . 'includes/class-nova-sound-fx-utils.php';
require_once NOVA_SOUND_FX_PLUGIN_DIR . 'includes/class-nova-sound-fx-blocks.php';
require_once NOVA_SOUND_FX_PLUGIN_DIR . 'includes/class-nova-sound-fx-setup.php';

// Initialize the plugin
function nova_sound_fx_init() {
    // Check and run migrations if needed
    nova_sound_fx_check_version();
    
    // Handle setup wizard redirect - ALWAYS show on activation
    if (get_transient('nova_sound_fx_activation_redirect')) {
        delete_transient('nova_sound_fx_activation_redirect');
        // Always redirect to setup wizard on activation
        wp_safe_redirect(admin_url('admin.php?page=nova-sound-fx-setup'));
        exit;
    }
    
    $plugin = new Nova_Sound_FX();
    $plugin->run();
}
add_action('plugins_loaded', 'nova_sound_fx_init');

// Check version and run migrations
function nova_sound_fx_check_version() {
    $installed_version = get_option('nova_sound_fx_version', '1.0.0');
    
    if (version_compare($installed_version, NOVA_SOUND_FX_VERSION, '<')) {
        // Run migrations for versions between installed and current
        if (version_compare($installed_version, '1.1.0', '<')) {
            require_once NOVA_SOUND_FX_PLUGIN_DIR . 'includes/migrations/update-1.1.0.php';
        }
        
        // Update version in database
        update_option('nova_sound_fx_version', NOVA_SOUND_FX_VERSION);
    }
}
