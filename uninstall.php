<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package Nova_Sound_FX
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check user capabilities
if (!current_user_can('activate_plugins')) {
    return;
}

global $wpdb;

// Remove plugin options
delete_option('nova_sound_fx_version');
delete_option('nova_sound_fx_settings');

// Remove custom database tables
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}nova_sound_fx_css_mappings");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}nova_sound_fx_transitions");

// Clear any cached data
wp_cache_flush();
