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
   * Icon for the field type.
   *
   * @var string
   */
  public $icon;

  /**
   * Constructor
   */
  public function __construct()
  {
    // Field name (no spaces, underscores allowed)
    $this->name = 'vod_video';

    // Field label (for public-facing UI)
    $this->label = __('Vidéo', 'vod-video-field');

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
      'remove_video' => __('Retirer la vidéo', 'vod-video-field'),
    );

    $this->icon = 'text';

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
   * @param array $field The field array
   * @return void
   */
  public function render_field($field)
  {
    // Get the value
    $value = isset($field['value']) ? $field['value'] : '';

    // Get field name
    $input_name = $field['name'];

    // If the name doesn't start with acf, wrap it
    if (strpos($input_name, 'acf[') !== 0) {
      $input_name = 'acf[' . $input_name . ']';
    }

    // Start field wrapper
    echo '<div class="acf-input">';

    // Parse the value if it exists
    $video_data = null;
    if (!empty($value)) {
      if (is_string($value)) {
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
          $video_data = $decoded;
        }
      } elseif (is_array($value)) {
        $video_data = $value;
      }
    }

    // Hidden input - store the original structure
    $input_value = '';
    if (!empty($value)) {
      if (is_string($value) && $value[0] === '{') {
        // If it's already a JSON string, use it as is
        $input_value = $value;
      } else if (is_array($value)) {
        // Check if we need to reconstruct the original structure
        if (isset($value['id']) && !is_array($value['id']) && isset($value['url'])) {
          // Convert from flat structure back to nested
          $nested_value = array(
            'id' => array(
              'media' => $value['id'],
              'thumbnail' => $value['thumbnail'],
              'url' => $value['url'],
              'folder' => $value['folder'],
            ),
            'title' => $value['title'],
          );
          $input_value = wp_json_encode($nested_value);
        } else {
          // Already in the correct structure
          $input_value = wp_json_encode($value);
        }
      }
    }
    printf(
      '<input type="hidden" id="%s" class="vod-video-input" name="%s" value="%s" data-key="%s">',
      esc_attr($field['id']),
      esc_attr($input_name),
      esc_attr($input_value),
      esc_attr($field['key'])
    );

    // Video preview container
    echo '<div class="vod-video-container">';

    // Display selected video preview if available
    if ($video_data && isset($video_data['id'])) {
      echo '<div class="vod-video-preview">';
      echo '<div class="vod-video-thumbnail">';

      $thumbnail = isset($video_data['id']['thumbnail']) ? $video_data['id']['thumbnail'] : '';
      if ($thumbnail) {
        printf(
          '<img src="%s" alt="%s">',
          esc_url($thumbnail),
          esc_attr($video_data['title'] ?? '')
        );
      } else {
        echo '<div class="vod-video-placeholder"></div>';
      }

      echo '</div>';
      echo '<div class="vod-video-details">';
      echo '<h4>' . esc_html($video_data['title'] ?? '') . '</h4>';
      echo '<div class="vod-video-actions">';
      echo '<a href="#" class="vod-video-remove button">' . esc_html__('Retirer la vidéo', 'vod-video-field') . '</a>';
      echo '</div>';
      echo '</div>';
      echo '</div>';
    } else {
      echo '<div class="vod-video-empty">';
      echo '<p>' . esc_html__('Aucune vidéo', 'vod-video-field') . '</p>';
      echo '</div>';
    }

    // Selection button
    echo '<div class="vod-video-select">';
    echo '<a href="#" class="vod-video-button button">' . esc_html__('Selectionner une vidéo', 'vod-video-field') . '</a>';
    echo '</div>';

    // Modal
    echo '<div class="vod-video-modal">';
    echo '<div class="vod-video-modal-content">';
    echo '<div class="vod-video-modal-header">';
    echo '<h3>' . esc_html__('Sélectionner une vidéo', 'vod-video-field') . '</h3>';
    echo '<a href="#" class="vod-video-modal-close">&times;</a>';
    echo '</div>';

    echo '<div class="vod-video-modal-search">';
    echo '<input type="text" class="vod-video-search-input" placeholder="' . esc_attr__('Rechercher des vidéos...', 'vod-video-field') . '">';
    echo '</div>';

    echo '<div class="vod-video-modal-results">';
    echo '<div class="vod-video-results"></div>';
    echo '</div>';

    echo '</div>'; // Close modal-content
    echo '</div>'; // Close modal

    echo '</div>'; // Close vod-video-container
    echo '</div>'; // Close acf-input
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

    // Query the database for videos with non-null URLs
    $query = $wpdb->prepare(
      "SELECT
        sname AS title,
        sImageUrlV2 AS thumbnail,
        sServerCode AS id,
        sVideoUrlV2 AS url,
        sFolderCode AS folder
      FROM $table_name
      WHERE sname LIKE %s
        AND sImageUrlV2 IS NOT NULL
        AND sVideoUrlV2 IS NOT NULL
      ORDER BY sname ASC",
      '%' . $wpdb->esc_like($search_term) . '%'
    );

    $results = $wpdb->get_results($query);

    // Format the results and ensure all values are present
    $videos = array_map(function ($row) {
      return array(
        'id' => $row->id,
        'title' => $row->title,
        'thumbnail' => esc_url($row->thumbnail),
        'url' => esc_url($row->url),
        'media' => $row->id,
        'folder' => $row->folder
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

    // Parse the stored JSON data if needed
    $video_data = null;
    if (is_string($value)) {
      $decoded = json_decode($value, true);
      if (json_last_error() === JSON_ERROR_NONE) {
        $video_data = $decoded;
      } else {
        return $value;
      }
    } elseif (is_array($value)) {
      $video_data = $value;
    } else {
      return $value;
    }

    // Keep the original structure
    if (isset($video_data['id']) && is_array($video_data['id'])) {
      // Return URL only if that's the format requested
      if ($field['return_format'] === 'url') {
        return $video_data['id']['url'] ?? '';
      }

      // Return the full data structure
      return $video_data;
    }

    return $value;
  }

  /**
   * Update the value before it is saved to the database
   *
   * @param mixed $value The value to update
   * @param int $post_id The post ID where the value is saved
   * @param array $field The field array holding all the field options
   * @return mixed The modified value
   */
  public function update_value($value, $post_id, $field)
  {
    // Handle incoming value
    if (empty($value)) {
      return null;
    }

    // If it's a string, try to decode it, handling both escaped and unescaped JSON
    if (is_string($value)) {
      // First try direct decode
      $decoded = json_decode($value, true);
      if (json_last_error() !== JSON_ERROR_NONE) {
        // If that fails, try with stripslashes
        $decoded = json_decode(stripslashes($value), true);
      }

      if (json_last_error() === JSON_ERROR_NONE) {
        $value = $decoded;
      }
    }

    // Validate the structure
    if (!is_array($value)) {
      return null;
    }

    if (!isset($value['id']) || !isset($value['id']['media'])) {
      return null;
    }

    return $value;
  }

  /**
   * Load the value from the database
   *
   * @param mixed $value The value to load
   * @param int $post_id The post ID where the value is saved
   * @param array $field The field array holding all the field options
   * @return mixed The modified value
   */
  public function load_value($value, $post_id, $field)
  {
    if (empty($value)) {
      return null;
    }

    // Format value based on return format
    $formatted_value = $this->format_value($value, $post_id, $field);
    return $formatted_value;
  }
}
