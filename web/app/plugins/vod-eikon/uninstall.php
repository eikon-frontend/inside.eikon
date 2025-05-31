<?php

/**
 * VOD Eikon Uninstall Script
 *
 * This file is executed when the plugin is deleted via the WordPress admin.
 * It cleans up the database table and scheduled events.
 */

// Prevent direct access
if (!defined('WP_UNINSTALL_PLUGIN')) {
  exit;
}

// Remove the database table
global $wpdb;
$table_name = $wpdb->prefix . 'vod_eikon_videos';
$wpdb->query("DROP TABLE IF EXISTS {$table_name}");

// Clear scheduled events
wp_clear_scheduled_hook('vod_eikon_daily_sync');

// Clean up any plugin options (if any were added in the future)
delete_option('vod_eikon_version');
delete_option('vod_eikon_settings');
