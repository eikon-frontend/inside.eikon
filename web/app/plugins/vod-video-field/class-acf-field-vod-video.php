<?php

/**
 * ACF VOD Video Field Class
 *
 * This file defines the custom field type for selecting videos from Infomaniak VOD.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
  exit;
}

/**
 * acf_field_vod_video class
 */
class acf_field_vod_video extends acf_field
{
  /**
   * Controls field type visibility in REST requests.
   *
   * @var bool
   */
  public $show_in_rest = true;

  /**
   * Constructor
   */
  public function __construct()
  {
    // Field name (no spaces, underscores allowed)
    $this->name = 'vod_video';

    // Field label (for public-facing UI)
    $this->label = __('VOD Video', 'vod-video-field');

    // Field category
    $this->category = 'content';

    // Field description
    $this->description = __('Select videos from the VOD Infomaniak plugin', 'vod-video-field');

    // Field defaults
    $this->defaults = array(
      'return_format' => 'array',
    );

    // Translation strings
    $this->l10n = array(
      'select_video' => __('Select Video', 'vod-video-field'),
      'search_videos' => __('Search Videos', 'vod-video-field'),
      'no_videos_found' => __('No videos found', 'vod-video-field'),
      'loading' => __('Loading...', 'vod-video-field'),
      'error' => __('Error loading videos', 'vod-video-field'),
      'no_video_selected' => __('No video selected', 'vod-video-field'),
      'remove_video' => __('Remove Video', 'vod-video-field'),
    );

    parent::__construct();

    // Add AJAX handlers
    add_action('wp_ajax_acf_vod_video_search', array($this, 'ajax_search_videos'));
  }

  /**
   * Render the field settings
   *
   * @param array $field The field settings array
   * @return void
   */
  public function render_field_settings($field)
  {
    // Return Format
    acf_render_field_setting($field, array(
      'label'         => __('Return Format', 'vod-video-field'),
      'instructions'  => __('Specify the return format for the selected value', 'vod-video-field'),
      'type'          => 'radio',
      'name'          => 'return_format',
      'layout'        => 'horizontal',
      'choices'       => array(
        'array'  => __('Video Array', 'vod-video-field'),
        'id'     => __('Video ID', 'vod-video-field'),
        'url'    => __('Video URL', 'vod-video-field'),
      ),
    ));
  }

  /**
   * Render the field input
   *
   * @param array $field The field settings array
   * @return void
   */
  public function render_field($field)
  {
    // Enhanced debugging
    error_log('ACF VOD Video render_field - =================== START RENDERING FIELD ===================');
    error_log('ACF VOD Video render_field - Field key: ' . $field['key']);
    error_log('ACF VOD Video render_field - Field name: ' . $field['name']);
    error_log('ACF VOD Video render_field - Raw field value: ' . print_r(isset($field['value']) ? $field['value'] : '', true));

    // Get the value from the field array, ensuring consistent handling
    $value = isset($field['value']) ? $field['value'] : '';
    error_log('ACF VOD Video render_field - Initial value: ' . print_r($value, true));

    // If no value in field array, try getting it from post meta using field key
    if (empty($value) && !empty($field['key']) && isset($GLOBALS['post']) && !empty($GLOBALS['post']->ID)) {
      $value = get_post_meta($GLOBALS['post']->ID, $field['key'], true);
      error_log('ACF VOD Video render_field - Value from post meta by key: ' . print_r($value, true));

      // If still no value, try field name as fallback
      if (empty($value) && !empty($field['name'])) {
        $value = get_post_meta($GLOBALS['post']->ID, $field['name'], true);
        error_log('ACF VOD Video render_field - Value from post meta by name: ' . print_r($value, true));
      }
    }

    // Merge with defaults
    $field = array_merge($this->defaults, $field);

    // Get the selected video data if available
    $selected_video = array();
    if (!empty($value)) {
      global $wpdb;
      $table_name = $wpdb->prefix . 'vod_video';
      $video = $wpdb->get_row($wpdb->prepare(
        "SELECT sname AS title, sImageUrlV2 AS thumbnail, sVideoUrlV2 AS url
           FROM $table_name
           WHERE MD5(sVideoUrlV2) = %s",
        $value
      ));

      if ($video) {
        $selected_video = array(
          'id' => $value,
          'title' => $video->title,
          'thumbnail' => $video->thumbnail,
          'url' => $video->url,
        );
        error_log('ACF VOD Video render_field - Found selected video: ' . print_r($selected_video, true));
      } else {
        // Fallback for when video isn't found - keep the value for consistency
        $selected_video = array(
          'id' => $value,
          'title' => 'Video ID: ' . $value,
          'thumbnail' => '',
          'url' => '',
        );
        error_log('ACF VOD Video render_field - Video not found in database: ' . $value);
      }
    }

    // Unique field identifier
    $field_id = esc_attr($field['id']);
    $field_key = esc_attr($field['key']);

    // Extract the base field name (without ACF array format if present)
    $raw_field_name = $field['name'];
    if (preg_match('/^acf\[(.+?)\]$/', $raw_field_name, $matches)) {
      $field_name = esc_attr($matches[1]); // Extract the name inside acf[]
      error_log('ACF VOD Video render_field - Extracted base name from array format: ' . $field_name);
    } else {
      $field_name = esc_attr($raw_field_name); // Already a base name
    }

    $js_safe_field_id = str_replace('-', '_', $field_id);

    // Debug field identifiers
    error_log('ACF VOD Video Field - ID: ' . $field_id);
    error_log('ACF VOD Video Field - Key: ' . $field_key);
    error_log('ACF VOD Video Field - Raw name: ' . $raw_field_name);
    error_log('ACF VOD Video Field - Base name: ' . $field_name);
    error_log('ACF VOD Video Field - JS Safe ID: ' . $js_safe_field_id);

    // Debug field identifiers for troubleshooting
    error_log('ACF VOD Video render_field - Field identifiers:');
    error_log('ACF VOD Video render_field - ID: ' . $field_id);
    error_log('ACF VOD Video render_field - Key: ' . $field_key);
    error_log('ACF VOD Video render_field - Base name: ' . $field_name);
    error_log('ACF VOD Video render_field - Input name format: acf[' . $field_key . ']');
    error_log('ACF VOD Video render_field - Value: ' . esc_attr($value));

    // Start ACF standard input wrapper
    echo '<div class="acf-input">';

    // Hidden input with correct ACF naming format
    // IMPORTANT: ACF expects name="acf[field_key]" format in form submissions
    printf(
      '<input type="hidden" id="%s" name="acf[%s]" value="%s" class="vod-video-input" data-key="%s" data-name="%s">',
      $field_id,
      $field_key, // Use field key for ACF's array format
      esc_attr($value),
      $field_key,
      $field_name
    );

    // Video preview container with field identifiers
    printf(
      '<div class="vod-video-container" data-field-id="%s" data-field-key="%s" data-field-name="%s">',
      esc_attr($js_safe_field_id),
      $field_key,
      $field_name
    );

    // Display selected video if available
    if (!empty($selected_video)) {
      echo '<div class="vod-video-preview">';
      echo '<div class="vod-video-thumbnail">';
      if (!empty($selected_video['thumbnail'])) {
        echo '<img src="' . esc_url($selected_video['thumbnail']) . '" alt="' . esc_attr($selected_video['title']) . '">';
      } else {
        echo '<div class="vod-video-placeholder"></div>';
      }
      echo '</div>';
      echo '<div class="vod-video-details">';
      echo '<h4>' . esc_html($selected_video['title']) . '</h4>';
      echo '<div class="vod-video-actions">';
      echo '<a href="#" class="vod-video-remove button">' . __('Remove Video', 'vod-video-field') . '</a>';
      echo '</div>';
      echo '</div>';
      echo '</div>';
    } else {
      echo '<div class="vod-video-empty">';
      echo '<p>' . __('No video selected', 'vod-video-field') . '</p>';
      echo '</div>';
    }

    // Video selection button
    echo '<div class="vod-video-select">';
    echo '<a href="#" class="vod-video-button button">' . __('Select Video', 'vod-video-field') . '</a>';
    echo '</div>';

    // Video search modal (hidden by default)
    echo '<div class="vod-video-modal" style="display:none;">';
    echo '<div class="vod-video-modal-content">';
    echo '<div class="vod-video-modal-header">';
    echo '<h3>' . __('Select a Video', 'vod-video-field') . '</h3>';
    echo '<a href="#" class="vod-video-modal-close">&times;</a>';
    echo '</div>';
    echo '<div class="vod-video-modal-search">';
    echo '<input type="text" class="vod-video-search-input" placeholder="' . esc_attr__('Search videos...', 'vod-video-field') . '">';
    echo '</div>';
    echo '<div class="vod-video-modal-results">';
    echo '<div class="vod-video-loading">' . __('Loading videos...', 'vod-video-field') . '</div>';
    echo '<div class="vod-video-results"></div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';

    echo '</div>'; // Close vod-video-container
    echo '</div>'; // Close acf-input wrapper

    // Add field settings for JavaScript
    printf(
      '<script type="text/javascript">var vodVideoFieldSettings_%s = %s;</script>',
      esc_js($js_safe_field_id),
      wp_json_encode(array(
        'field_id' => $field_id,
        'field_key' => $field_key,
        'field_name' => $field_name,
        'selected_video' => $selected_video,
        'i18n' => $this->l10n,
      ))
    );
  }
  /**
   * Load required assets for the field
   */
  public function input_admin_enqueue_scripts()
  {
    $dir = ACF_VOD_VIDEO_FIELD_URL;
    $version = ACF_VOD_VIDEO_FIELD_VERSION;

    // Register & include CSS
    wp_register_style('acf-vod-video-field', "{$dir}assets/css/vod-video-field.css", array('acf-input'), $version);
    wp_enqueue_style('acf-vod-video-field');

    // Register & include JS
    wp_register_script('acf-vod-video-field', "{$dir}assets/js/vod-video-field.js", array('acf-input', 'jquery'), $version, true);
    wp_enqueue_script('acf-vod-video-field');

    // Localize script
    wp_localize_script('acf-vod-video-field', 'acf_vod_video_field', array(
      'ajax_url' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('acf_vod_video_search_nonce'),
      'i18n' => $this->l10n,
    ));
  }

  /**
   * AJAX callback to search for videos
   */
  public function ajax_search_videos()
  {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'acf_vod_video_search_nonce')) {
      wp_send_json_error(array('message' => __('Invalid security token', 'vod-video-field')));
    }

    $search_term = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

    global $wpdb;
    $table_name = $wpdb->prefix . 'vod_video';

    // Query the database for videos
    $query = $wpdb->prepare(
      "SELECT sname AS title, sImageUrlV2 AS thumbnail, sVideoUrlV2 AS url
             FROM $table_name
             WHERE sname LIKE %s",
      '%' . $wpdb->esc_like($search_term) . '%'
    );
    $results = $wpdb->get_results($query);

    // Format the results
    $videos = array_map(function ($row) {
      return array(
        'id' => md5($row->url), // Generate a unique ID based on the URL
        'title' => $row->title,
        'thumbnail' => $row->thumbnail,
        'url' => $row->url,
      );
    }, $results);

    wp_send_json_success(array('videos' => $videos));
  }

  /**
   * Format the value for API
   *
   * @param mixed $value The raw value from the database
   * @param int $post_id The post ID
   * @param array $field The field array
   * @return mixed The formatted value
   */
  public function format_value($value, $post_id, $field)
  {
    // Bail early if no value
    if (empty($value)) {
      return $value;
    }

    // Format the value based on the return format setting
    if ($field['return_format'] === 'id') {
      return $value;
    }

    // Get the video data
    $video_id = $value;

    // Here we would normally retrieve the complete video details
    // This is a placeholder for the actual implementation
    $video = array(
      'id' => $video_id,
      'title' => 'Video Title',
      'thumbnail' => '',
      'url' => '',
      'embed_url' => '',
      'description' => '',
      'duration' => '',
    );

    // Return URL only
    if ($field['return_format'] === 'url') {
      return $video['url'];
    }

    // Return the full video array
    return $video;
  }

  /**
   * Update the field value in the database
   *
   * @param mixed $value The value to save
   * @param int $post_id The post ID
   * @param array $field The field array
   * @return mixed The value to save
   */
  public function update_value($value, $post_id, $field)
  {
    // Debug raw POST data to see exactly what's being submitted
    error_log('ACF VOD Video update_value - =================== START UPDATE ===================');
    error_log('ACF VOD Video update_value - Raw POST data: ' . print_r($_POST, true));
    error_log('ACF VOD Video update_value - Complete field array: ' . print_r($field, true));
    error_log('ACF VOD Video update_value - Initial value passed: ' . print_r($value, true));

    // Debug field identifiers
    error_log('ACF VOD Video update_value - Field key: ' . $field['key']);
    error_log('ACF VOD Video update_value - Field name: ' . $field['name']);
    error_log('ACF VOD Video update_value - Post ID: ' . $post_id);

    // Ensure we have valid field data
    if (empty($field['key'])) {
      error_log('ACF VOD Video update_value - Error: Missing field key');
      return false;
    }

    // Check if value exists in POST data using field key (primary method)
    if (isset($_POST['acf'])) {
      error_log('ACF VOD Video update_value - Found acf array in POST');

      // Primary approach: Look for field key in acf array
      // This matches our input name="acf[field_key]" format
      if (isset($_POST['acf'][$field['key']])) {
        $value = $_POST['acf'][$field['key']];
        error_log('ACF VOD Video update_value - Found value in POST[acf] by key: ' . print_r($value, true));
      }
      // Fallback approach: Look for field name (for backward compatibility)
      elseif (isset($_POST['acf'][$field['name']])) {
        $value = $_POST['acf'][$field['name']];
        error_log('ACF VOD Video update_value - Found value in POST[acf] by name: ' . print_r($value, true));
      }
      // Less likely, but check direct POST field as last resort
      elseif (isset($_POST[$field['name']])) {
        $value = $_POST[$field['name']];
        error_log('ACF VOD Video update_value - Found value directly in POST by name: ' . print_r($value, true));
      } else {
        error_log('ACF VOD Video update_value - Could not find value in POST data under known keys');
        error_log('ACF VOD Video update_value - Available acf keys: ' . print_r(array_keys($_POST['acf']), true));
      }
    } else {
      error_log('ACF VOD Video update_value - No acf array in POST data');
    }

    // Handle empty values
    if (empty($value)) {
      error_log('ACF VOD Video update_value - Empty value detected, cleaning up meta');

      // Delete all possible meta keys
      delete_post_meta($post_id, $field['key']);
      delete_post_meta($post_id, '_' . $field['key']);
      delete_post_meta($post_id, $field['name']);
      delete_post_meta($post_id, '_' . $field['name']);

      error_log('ACF VOD Video update_value - Cleaned up meta keys');
      return '';
    }

    // Ensure the value is a string and sanitize
    $value = is_string($value) ? $value : strval($value);
    $sanitized_value = sanitize_text_field($value);
    error_log('ACF VOD Video update_value - Sanitized value: ' . $sanitized_value);

    // Verify video exists in database
    global $wpdb;
    $table_name = $wpdb->prefix . 'vod_video';

    // Fetch video details from the database
    $video = $wpdb->get_row($wpdb->prepare(
      "SELECT sname AS title, sImageUrlV2 AS thumbnail, sVideoUrlV2 AS url
       FROM $table_name
       WHERE MD5(sVideoUrlV2) = %s",
      $sanitized_value
    ));

    if ($video) {
      // Create a JSON object with video details
      $video_data = json_encode(array(
        'id' => $sanitized_value,
        'title' => $video->title,
        'thumbnail' => $video->thumbnail,
        'url' => $video->url,
      ));

      // Save the JSON object in post meta
      update_post_meta($post_id, $field['key'], $video_data);
      update_post_meta($post_id, '_' . $field['key'], $field['key']);
      update_post_meta($post_id, $field['name'], $video_data);
      update_post_meta($post_id, '_' . $field['name'], $field['key']);

      error_log('ACF VOD Video update_value - Saved JSON: ' . $video_data);
      return $video_data;
    } else {
      error_log('ACF VOD Video update_value - Video not found in database');
      return '';
    }
  }

  /**
   * Load the field value from the database
   *
   * @param mixed $value The value from the database
   * @param int $post_id The post ID
   * @param array $field The field array
   * @return mixed The value to load
   */
  public function load_value($value, $post_id, $field)
  {
    error_log('ACF VOD Video load_value - =================== START LOADING VALUE ===================');
    error_log('ACF VOD Video load_value - Post ID: ' . $post_id);
    error_log('ACF VOD Video load_value - Field key: ' . $field['key']);
    error_log('ACF VOD Video load_value - Field name: ' . $field['name']);
    error_log('ACF VOD Video load_value - Initial value: ' . print_r($value, true));

    // Ensure we have valid field data
    if (empty($field['key'])) {
      error_log('ACF VOD Video load_value - Error: Missing field key');
      return false;
    }

    // First try to load using field key (our primary storage method)
    $final_value = get_post_meta($post_id, $field['key'], true);
    error_log('ACF VOD Video load_value - Value from field key: ' . print_r($final_value, true));

    // If no value found by key, try field name as fallback
    if (empty($final_value) && !empty($field['name'])) {
      $final_value = get_post_meta($post_id, $field['name'], true);
      error_log('ACF VOD Video load_value - Value from field name fallback: ' . print_r($final_value, true));
    }

    // If still empty, return the original value
    if (empty($final_value) && !empty($value)) {
      $final_value = $value;
      error_log('ACF VOD Video load_value - Using original passed value: ' . print_r($final_value, true));
    }

    // Decode JSON if the value is stored as JSON
    if (!empty($final_value)) {
      $decoded_value = json_decode($final_value, true);
      if (json_last_error() === JSON_ERROR_NONE) {
        error_log('ACF VOD Video load_value - Decoded JSON: ' . print_r($decoded_value, true));
        return $decoded_value;
      } else {
        error_log('ACF VOD Video load_value - Failed to decode JSON, returning raw value');
      }
    }

    // If we have a value, verify it exists in the videos table
    if (!empty($final_value)) {
      global $wpdb;
      $table_name = $wpdb->prefix . 'vod_video';
      $video_exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE MD5(sVideoUrlV2) = %s",
        $final_value
      ));

      error_log('ACF VOD Video load_value - Video exists in DB: ' . ($video_exists ? 'yes' : 'no'));

      if (!$video_exists) {
        error_log('ACF VOD Video load_value - Warning: Value exists in postmeta but not in videos table');
      }
    }

    error_log('ACF VOD Video load_value - Final value: ' . print_r($final_value, true));
    error_log('ACF VOD Video load_value - =================== END LOADING VALUE ===================');

    return $final_value;
  }
}
