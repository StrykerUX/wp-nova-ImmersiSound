<?php
/**
 * Migration script for version 1.1.0
 * Adds new columns for visual options
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Run migration for version 1.1.0
 */
function nova_sound_fx_migrate_1_1_0() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'nova_sound_fx_css_mappings';
    
    // Check if columns already exist
    $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
    $column_names = array_map(function($col) { return $col->Field; }, $columns);
    
    // Add show_visual_effect column if it doesn't exist
    if (!in_array('show_visual_effect', $column_names)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN show_visual_effect tinyint(1) DEFAULT 1");
    }
    
    // Add show_speaker_icon column if it doesn't exist
    if (!in_array('show_speaker_icon', $column_names)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN show_speaker_icon tinyint(1) DEFAULT 1");
    }
    
    // Update version option
    update_option('nova_sound_fx_version', '1.1.0');
}

// Run migration
nova_sound_fx_migrate_1_1_0();
