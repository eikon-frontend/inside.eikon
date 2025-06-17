<?php

/**
 * Plugin Name: ACF VOD Video Field
 * Plugin URI: #
 * Description: ACF field that allows selection of videos from the VOD Eikon plugin
 * Version: 1.0.0
 * Author: EIKON
 * Author URI: https://eikon.ch
 * Text Domain: vod-video-field
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
  exit;
}

// Check if ACF is active
if (!class_exists('ACF')) {
  return;
}

// Check if VOD Eikon plugin is active
function vod_video_field_check_dependencies()
{
  if (!function_exists('vod_eikon_get_videos')) {
    add_action('admin_notices', function () {
      echo '<div class="notice notice-error"><p>';
      echo '<strong>VOD Video Field:</strong> This plugin requires the VOD Eikon plugin to be installed and activated.';
      echo '</p></div>';
    });
    return false;
  }
  return true;
}

// Define plugin constants
define('ACF_VOD_VIDEO_FIELD_VERSION', '1.0.0');
define('ACF_VOD_VIDEO_FIELD_URL', plugin_dir_url(__FILE__));
define('ACF_VOD_VIDEO_FIELD_PATH', plugin_dir_path(__FILE__));

// Include field
include_once(ACF_VOD_VIDEO_FIELD_PATH . 'class-acf-field-vod-video.php');

/**
 * Initialize the plugin
 */
function acf_vod_video_field_init()
{
  // Check dependencies before registering the field
  if (!vod_video_field_check_dependencies()) {
    return;
  }

  // Register the field type with ACF
  acf_register_field_type('acf_field_vod_video');
}
add_action('acf/include_field_types', 'acf_vod_video_field_init');

/**
 * Load plugin text domain
 */
function acf_vod_video_field_load_textdomain()
{
  load_plugin_textdomain('vod-video-field', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'acf_vod_video_field_load_textdomain');

/**
 * Register GraphQL field type using register_graphql_acf_field_type
 */
add_action('graphql_register_types', function () {
  if (function_exists('register_graphql_object_type') && function_exists('register_graphql_field')) {
    // Define a custom GraphQL object type for the video field
    register_graphql_object_type('VODVideo', [
      'description' => __('Details of the VOD Video field.', 'vod-video-field'),
      'fields' => [
        'id' => [
          'type' => 'String',
          'description' => __('The ID of the video.', 'vod-video-field'),
        ],
        'title' => [
          'type' => 'String',
          'description' => __('The title of the video.', 'vod-video-field'),
        ],
        'thumbnail' => [
          'type' => 'String',
          'description' => __('The thumbnail URL of the video.', 'vod-video-field'),
        ],
        'dashUrl' => [
          'type' => 'String',
          'description' => __('The DASH URL of the video.', 'vod-video-field'),
        ]
      ],
    ]);

    // Register the video field with the custom object type for multiple type names
    foreach (['PageFields', 'DepartmentFields', 'PortfolioGalerieVodLayout'] as $type_name) {
      register_graphql_field($type_name, 'vod', [
        'type' => 'VODVideo',
        'description' => __('The VOD Video field, returning video details.', 'vod-video-field'),
        'resolve' => function ($root, $args, $context, $info) {
          $field_value = null;
          $post_id = null;

          // Get the field value from either direct field or flexible content layout
          if (array_key_exists('vod', $root)) {
            // Use array_key_exists instead of isset to handle null values properly
            $field_value = $root['vod'];
          } else {
            // Only fall back to post-based lookup for direct post fields, not flexible content
            // Extract post ID from root using various methods
            if (is_array($root) && isset($root['databaseId'])) {
              $post_id = $root['databaseId'];
            } elseif (is_array($root) && isset($root['ID'])) {
              $post_id = $root['ID'];
            } elseif (is_array($root) && isset($root['postId'])) {
              $post_id = $root['postId'];
            } elseif (is_object($root) && method_exists($root, 'ID')) {
              $post_id = $root->ID;
            } elseif (is_object($root) && property_exists($root, 'databaseId')) {
              $post_id = $root->databaseId;
            } else {
              $post_id = $context->nodeid ?? get_queried_object_id();
            }

            // Only try to get field value from post if we have a post ID and this is not a flexible content layout
            if ($post_id && !isset($root['fieldName'])) {
              // Try getting the field value using multiple methods
              $field_value = get_field('vod', $post_id) ?: get_post_meta($post_id, 'vod', true) ?: get_field('field_vod', $post_id);
            }
          }

          if (empty($field_value)) {
            return null;
          }

          // Handle both string (JSON) and array values
          $field_data = is_string($field_value) ? json_decode($field_value, true) : $field_value;

          // If the field value is just a VOD ID (string), query the VOD Eikon table
          if (is_string($field_data) || is_numeric($field_data)) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'vod_eikon_videos';

            $video = $wpdb->get_row($wpdb->prepare(
              "SELECT vod_id, name, poster, mpd_url
              FROM $table_name
              WHERE vod_id = %s AND published = 1",
              $field_data
            ));

            if ($video) {
              return array(
                'id' => $video->vod_id,
                'title' => $video->name,
                'thumbnail' => esc_url($video->poster),
                'dashUrl' => esc_url($video->mpd_url)
              );
            }
          }
          // If the field value is an object with video data, check if we need to refresh it
          elseif (is_array($field_data) && isset($field_data['vod_id'])) {
            // Check if we should refresh the data (e.g., if poster URL is old or missing)
            $should_refresh = false;

            // If no poster URL or it seems to be an outdated format, refresh from database
            if (
              empty($field_data['poster']) ||
              (isset($field_data['updated_at']) && strtotime($field_data['updated_at']) < strtotime('-1 hour'))
            ) {
              $should_refresh = true;
            }

            if ($should_refresh) {
              // Get fresh data from database
              global $wpdb;
              $table_name = $wpdb->prefix . 'vod_eikon_videos';

              $video = $wpdb->get_row($wpdb->prepare(
                "SELECT vod_id, name, poster, mpd_url, updated_at
                FROM $table_name
                WHERE vod_id = %s AND published = 1",
                $field_data['vod_id']
              ));

              if ($video) {
                return array(
                  'id' => $video->vod_id,
                  'title' => $video->name,
                  'thumbnail' => esc_url($video->poster),
                  'dashUrl' => esc_url($video->mpd_url)
                );
              }
            }

            // Return cached data if no refresh needed or refresh failed
            $result = array(
              'id' => $field_data['vod_id'], // Use vod_id as the main id for GraphQL
              'title' => $field_data['title'] ?? '',
              'thumbnail' => isset($field_data['poster']) ? esc_url($field_data['poster']) : '',
              'dashUrl' => isset($field_data['mpd_url']) ? esc_url($field_data['mpd_url']) : ''
            );
            return $result;
          }

          return null;
        },
      ]);
    }
  }
});

/**
 * Format ACF value for VOD Video field
 */
add_filter('acf/format_value/type=vod_video', function ($value, $post_id, $field) {
  if (empty($value)) {
    return null;
  }

  // Handle both string (JSON) and array values
  $field_data = is_string($value) ? json_decode($value, true) : $value;

  // If the value is just a VOD ID (string or numeric), fetch full video data from VOD Eikon
  if (is_string($field_data) || is_numeric($field_data)) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'vod_eikon_videos';

    $video = $wpdb->get_row($wpdb->prepare(
      "SELECT vod_id, name, poster, mpd_url
      FROM $table_name
      WHERE vod_id = %s AND published = 1",
      $field_data
    ));

    if ($video) {
      return array(
        'vod_id' => $video->vod_id,
        'title' => $video->name,
        'poster' => $video->poster,
        'mpd_url' => $video->mpd_url
      );
    }
  }

  // Return the full field data structure if it's already an object
  return $field_data;
}, 10, 3);
