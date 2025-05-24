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
 * Fetch video details from the Infomaniak VOD API.
 *
 * @param string $channel_id The channel ID.
 * @param string $video_id The video ID.
 * @return string|null The generated DASH stream URL or null if unavailable.
 */
function fetch_vod_video_url($channel_id, $video_id)
{
  $api_url = "https://api.infomaniak.com/1/vod/channel/{$channel_id}/media/{$video_id}";
  $headers = [
    'Authorization' => 'Bearer ' . getenv('INFOMANIAK_VOD_API_TOKEN'),
    'Content-Type' => 'application/json',
  ];

  $client = new \GuzzleHttp\Client();
  try {
    $response = $client->get($api_url, ['headers' => $headers]);
    $response_data = json_decode($response->getBody(), true);

    // Extract the IDs from the encoded_medias array
    if (!empty($response_data['data']['encoded_medias'])) {
      $encoded_medias = $response_data['data']['encoded_medias'];
      $media_ids = array_map(function ($media) {
        return $media['id'];
      }, $encoded_medias);

      // Construct the DASH URL
      $base_url = "https://play.vod2.infomaniak.com/dash/{$video_id}/{$response_data['data']['folder']['id']}";
      $media_ids_string = implode(',', $media_ids);
      return "{$base_url}/,{$media_ids_string},.urlset/manifest.mpd";
    }
  } catch (\GuzzleHttp\Exception\ClientException $e) {
    error_log('Client error: ' . $e->getResponse()->getBody()->getContents());
  } catch (\GuzzleHttp\Exception\ServerException $e) {
    error_log('Server error: ' . $e->getResponse()->getBody()->getContents());
  } catch (\Exception $e) {
    error_log('Unexpected error: ' . $e->getMessage());
  }

  return null;
}

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
            $video_id = $field_data['id']['media'] ?? null;
            $channel_id = "14234";

            // Fetch better quality stream URL using the refactored function
            $better_quality_url = null;
            if ($video_id && $channel_id) {
              $better_quality_url = fetch_vod_video_url($channel_id, $video_id);
            }

            return [
              'id' => $video_id,
              'title' => $field_data['title'] ?? null,
              'thumbnail' => $field_data['id']['thumbnail'] ?? null,
              'media' => $field_data['id']['media'] ?? null,
              'url' => $better_quality_url ?? null,
              'folder' => $field_data['id']['folder'] ?? null,
            ];
          }

          return null;
        },
      ]);
    }
  }
});
