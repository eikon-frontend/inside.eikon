<?php

/**
 * Plugin Name: ACF VOD Video Field
 * Plugin URI: #
 * Description: ACF field that allows selection of videos from the Infomaniak VOD system
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
        'url' => [
          'type' => 'String',
          'description' => __('The URL of the video.', 'vod-video-field'),
        ],
        'dashUrl' => [
          'type' => 'String',
          'description' => __('The DASH URL of the video.', 'vod-video-field'),
        ],
        'folder' => [
          'type' => 'String',
          'description' => __('The folder code of the video.', 'vod-video-field'),
        ],
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
          if (isset($root['vod'])) {
            $field_value = $root['vod'];
          } else {
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

            if ($post_id) {
              // Try getting the field value using multiple methods
              $field_value = get_field('vod', $post_id) ?: get_post_meta($post_id, 'vod', true) ?: get_field('field_vod', $post_id);
            }
          }

          if (empty($field_value)) {
            return null;
          }

          // Handle both string (JSON) and array values
          $field_data = is_string($field_value) ? json_decode($field_value, true) : $field_value;

          // Get the video details from the database
          global $wpdb;
          $table_name = $wpdb->prefix . 'vod_video';

          if (isset($field_data['id']['media'])) {
            $video_id = $field_data['id']['media'];

            $video = $wpdb->get_row($wpdb->prepare(
              "SELECT
                sname AS title,
                sImageUrlV2 AS thumbnail,
                sServerCode AS id,
                sVideoUrlV2 AS url,
                sVideoDashUrlV2 AS dashUrl,
                sFolderCode AS folder
              FROM $table_name
              WHERE sServerCode = %s",
              $video_id
            ));

            if ($video) {
              return array(
                'id' => $video->id,
                'title' => $video->title,
                'thumbnail' => esc_url($video->thumbnail),
                'url' => esc_url($video->url),
                'dashUrl' => esc_url($video->dashUrl),
                'folder' => $video->folder
              );
            }
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

  // Return the full field data structure
  return $field_data;
}, 10, 3);
