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
          // Get the field value from either direct field or flexible content layout
          $field_value = null;
          $post_id = null;

          if (isset($root['vod'])) {
            $field_value = $root['vod'];
          } else {
            // Extract post ID from root
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
              // Try to get ID from context
              $post_id = $context->nodeid ?? null;

              // If still no post ID, try to get it from the info object
              if (!$post_id && isset($info->parentType) && isset($info->parentType->name)) {
                $parent_type = $info->parentType->name;
                if ($parent_type === 'PageFields') {
                  $post_id = get_queried_object_id();
                }
              }
            }

            if ($post_id) {
              // Try getting the field value using multiple methods

              // 1. Try ACF get_field first
              $field_value = get_field('vod', $post_id);

              // 2. If that's empty, try get_post_meta
              if (empty($field_value)) {
                $field_value = get_post_meta($post_id, 'vod', true);
              }

              // 3. If still empty, try ACF get_field with 'field_' prefix
              if (empty($field_value)) {
                $field_value = get_field('field_vod', $post_id);
              }
            }
          }

          // Ensure we have a value to work with
          if (empty($field_value)) {
            return null;
          }

          // Handle both string (JSON) and array values
          $field_data = is_string($field_value) ? json_decode($field_value, true) : $field_value;

          // For both direct fields and flexible content fields, get the video ID
          $video_id = null;
          if (isset($field_data['id']) && isset($field_data['id']['media'])) {
            $video_id = $field_data['id']['media'];
          } elseif (isset($field_data['media'])) {
            $video_id = $field_data['media'];
          }

          if (!$video_id) {
            return null;
          }

          // Build the return array based on the available data
          return [
            'id' => $video_id,
            'title' => $field_data['title'] ?? null,
            'thumbnail' => isset($field_data['id']['thumbnail']) ? $field_data['id']['thumbnail'] : (isset($field_data['thumbnail']) ? $field_data['thumbnail'] : null),
            'media' => $video_id,
            'url' => isset($field_data['id']['url']) ? $field_data['id']['url'] : (isset($field_data['url']) ? $field_data['url'] : null),
          ];
        },
      ]);
    }
  }
});
