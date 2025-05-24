<?php
/**
 * Plugin Name: Nova Sound FX
 * Plugin URI: https://github.com/yourusername/nova-sound-fx
 * Description: Add immersive sound effects to your WordPress site with CSS selectors and page transitions
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * Text Domain: nova-sound-fx
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('NOVA_SOUND_FX_VERSION', '1.0.0');
define('NOVA_SOUND_FX_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NOVA_SOUND_FX_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NOVA_SOUND_FX_PLUGIN_FILE', __FILE__);

// Activation hook
register_activation_hook(__FILE__, 'nova_sound_fx_activate');
function nova_sound_fx_activate() {
    // Create database tables
    nova_sound_fx_create_tables();
    
    // Set default options
    add_option('nova_sound_fx_version', NOVA_SOUND_FX_VERSION);
    add_option('nova_sound_fx_settings', array(
        'enable_sounds' => true,
        'default_volume' => 50,
        'mobile_enabled' => false,
        'respect_prefers_reduced_motion' => true,
        'preview_mode' => false
    ));
    
    // Flush rewrite rules to prevent URL conflicts
    flush_rewrite_rules();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'nova_sound_fx_deactivate');
function nova_sound_fx_deactivate() {
    // Clean up temporary data
    flush_rewrite_rules();
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

// Initialize the plugin
function nova_sound_fx_init() {
    $plugin = new Nova_Sound_FX();
    $plugin->run();
}
add_action('plugins_loaded', 'nova_sound_fx_init');
