<?php
/**
 * Nova Sound FX Uninstall
 * 
 * Este archivo se ejecuta cuando el plugin es desinstalado
 * Limpia todas las tablas y opciones creadas por el plugin
 */

// Si no es llamado por WordPress, abortar
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Verificar permisos
if (!current_user_can('activate_plugins')) {
    return;
}

// Eliminar opciones del plugin
delete_option('nova_sound_fx_version');
delete_option('nova_sound_fx_settings');

// Eliminar tablas de la base de datos
global $wpdb;

$tables = array(
    $wpdb->prefix . 'nova_sound_fx_css_mappings',
    $wpdb->prefix . 'nova_sound_fx_transitions'
);

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS $table");
}

// Limpiar datos transitorios
delete_transient('nova_sound_fx_sounds_cache');
delete_transient('nova_sound_fx_version_check');

// Limpiar meta datos de usuario (si se han usado)
$user_meta_keys = array(
    'nova_sound_fx_dismissed_notices',
    'nova_sound_fx_tour_completed'
);

foreach ($user_meta_keys as $meta_key) {
    delete_metadata('user', 0, $meta_key, '', true);
}

// Limpiar capacidades personalizadas (si se han agregado)
$role = get_role('administrator');
if ($role) {
    $role->remove_cap('manage_nova_sound_fx');
}

// Limpiar archivos de caché (si existen)
$upload_dir = wp_upload_dir();
$cache_dir = $upload_dir['basedir'] . '/nova-sound-fx-cache';

if (is_dir($cache_dir)) {
    nova_sound_fx_remove_directory($cache_dir);
}

/**
 * Función auxiliar para eliminar directorios recursivamente
 */
function nova_sound_fx_remove_directory($dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (is_dir($dir . "/" . $object)) {
                    nova_sound_fx_remove_directory($dir . "/" . $object);
                } else {
                    unlink($dir . "/" . $object);
                }
            }
        }
        rmdir($dir);
    }
}

// Limpiar tareas programadas
wp_clear_scheduled_hook('nova_sound_fx_daily_cleanup');
wp_clear_scheduled_hook('nova_sound_fx_analytics_process');

// Limpiar rewrite rules
flush_rewrite_rules();

// Log de desinstalación (opcional)
if (defined('WP_DEBUG') && WP_DEBUG === true) {
    error_log('Nova Sound FX: Plugin desinstalado completamente');
}
