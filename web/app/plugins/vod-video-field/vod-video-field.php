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
        'media' => [
          'type' => 'String',
          'description' => __('The media id URL of the video.', 'vod-video-field'),
        ],
        'url' => [
          'type' => 'String',
          'description' => __('The URL of the video.', 'vod-video-field'),
        ],
      ],
    ]);

    // Register the video field with the custom object type
    register_graphql_field('PageFields', 'video', [
      'type' => 'VODVideo',
      'description' => __('The VOD Video field, returning video details.', 'vod-video-field'),
      'resolve' => function ($root, $args, $context, $info) {
        // Attempt to fetch the ID from the root object or fallback to global post ID
        $field_value = get_fields(get_the_ID());

        // Dynamically use the ACF field name set in the configuration
        $field_name = $info->fieldName;

        // Ensure the field returns the required subfields or null if not set
        if (isset($field_value[$field_name])) {
          $field_data = is_string($field_value[$field_name])
            ? json_decode($field_value[$field_name], true)
            : $field_value[$field_name];

          // Extract specific subfields
          return [
            'id' => $field_data['id']['id'] ?? null,
            'title' => $field_data['title'] ?? null,
            'thumbnail' => $field_data['id']['thumbnail'] ?? null,
            'media' => $field_data['id']['media'] ?? null,
            'url' => $field_data['id']['url'] ?? null,
          ];
        }

        return null;
      },
    ]);
  }
});
