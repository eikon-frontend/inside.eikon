<?php

/**
 * ACF VOD Video Field Class
 *
 * This file defines the custom field type for selecting videos from VOD Eikon plugin.
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
    $this->description = __('Select videos from the VOD Eikon plugin', 'vod-video-field');

    // Field defaults
    $this->defaults = array(
      'return_format' => 'object',
      'published_only' => 1
    );

    // Translation strings
    $this->l10n = array(
      'select_video' => __('Sélectionner une vidéo', 'vod-video-field'),
      'search_videos' => __('Rechercher des vidéos...', 'vod-video-field'),
      'no_videos_found' => __('Aucune vidéo trouvée', 'vod-video-field'),
      'remove_video' => __('Supprimer la vidéo', 'vod-video-field'),
      'loading' => __('Chargement...', 'vod-video-field'),
      'error' => __('Erreur lors du chargement des vidéos', 'vod-video-field'),
    );

    $this->icon = 'format-video';

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
        'object' => __('Video Object', 'vod-video-field'),
        'id'     => __('Video ID', 'vod-video-field'),
        'url'    => __('MPD URL', 'vod-video-field'),
      ),
    ));

    // Published Only
    acf_render_field_setting($field, array(
      'label'         => __('Show Published Videos Only', 'vod-video-field'),
      'instructions'  => __('Only show videos that have completed encoding and thumbnail generation', 'vod-video-field'),
      'type'          => 'true_false',
      'name'          => 'published_only',
      'ui'            => 1,
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
    // Check if VOD Eikon plugin is active
    if (!$this->is_vod_eikon_active()) {
      echo '<div class="notice notice-error"><p>' . __('VOD Eikon plugin is required but not active.', 'vod-video-field') . '</p></div>';
      return;
    }

    // Get the value
    $value = isset($field['value']) ? $field['value'] : '';

    // Get field name
    $input_name = $field['name'];

    // If the name doesn't start with acf, wrap it
    if (strpos($input_name, 'acf[') !== 0) {
      $input_name = 'acf[' . $input_name . ']';
    }

    // Start field wrapper with data attributes for field settings
    $published_only = isset($field['published_only']) ? (int) $field['published_only'] : 1;
    echo '<div class="acf-input" data-published-only="' . esc_attr($published_only) . '">';

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

    // Hidden input - store the video data as JSON
    $input_value = '';
    if (!empty($value)) {
      if (is_string($value)) {
        $input_value = $value;
      } else {
        $input_value = wp_json_encode($value);
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

      $thumbnail = isset($video_data['poster']) ? $video_data['poster'] : '';
      if ($thumbnail) {
        printf(
          '<img src="%s" alt="%s">',
          esc_url($thumbnail),
          esc_attr($video_data['title'] ?? '')
        );
      } else {
        echo '<div class="vod-video-placeholder"><span class="dashicons dashicons-format-video"></span></div>';
      }

      echo '</div>';
      echo '<div class="vod-video-details">';
      echo '<h4>' . esc_html($video_data['title'] ?? '') . '</h4>';
      if (isset($video_data['vod_id'])) {
        echo '<p><small>VOD ID: ' . esc_html($video_data['vod_id']) . '</small></p>';
      }
      echo '<div class="vod-video-actions">';
      echo '<a href="#" class="vod-video-remove button">' . esc_html($this->l10n['remove_video']) . '</a>';
      echo '</div>';
      echo '</div>';
      echo '</div>';
    } else {
      echo '<div class="vod-video-empty">';
      echo '<p>' . esc_html__('Aucune vidéo sélectionnée', 'vod-video-field') . '</p>';
      echo '</div>';
    }

    // Selection button
    echo '<div class="vod-video-select">';
    echo '<a href="#" class="vod-video-button button">' . esc_html($this->l10n['select_video']) . '</a>';
    echo '</div>';

    // Modal
    echo '<div class="vod-video-modal">';
    echo '<div class="vod-video-modal-content">';
    echo '<div class="vod-video-modal-header">';
    echo '<h3>' . esc_html($this->l10n['select_video']) . '</h3>';
    echo '<a href="#" class="vod-video-modal-close">&times;</a>';
    echo '</div>';

    echo '<div class="vod-video-modal-search">';
    echo '<input type="text" class="vod-video-search-input" placeholder="' . esc_attr($this->l10n['search_videos']) . '">';
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
   * AJAX callback to search for videos from VOD Eikon plugin
   */
  public function ajax_search_videos()
  {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'acf_vod_video_search_nonce')) {
      wp_send_json_error(array('message' => __('Invalid security token', 'vod-video-field')));
    }

    // Check if VOD Eikon plugin is active
    if (!$this->is_vod_eikon_active()) {
      wp_send_json_error(array('message' => __('VOD Eikon plugin is not active', 'vod-video-field')));
    }

    $search_term = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $published_only = isset($_POST['published_only']) ? (bool) $_POST['published_only'] : true;

    // Get videos from VOD Eikon plugin
    $videos = $this->get_vod_eikon_videos($search_term, $published_only);

    wp_send_json_success(array('videos' => $videos));
  }

  /**
   * Get videos from VOD Eikon plugin
   *
   * @param string $search_term Optional search term to filter videos
   * @param bool $published_only Whether to only return published videos (default: true)
   * @return array Array of video data
   */
  private function get_vod_eikon_videos($search_term = '', $published_only = true)
  {
    global $wpdb;
    $table_name = $wpdb->prefix . 'vod_eikon_videos';

    // Build query
    $query = "SELECT * FROM {$table_name}";
    $params = array();
    $where_conditions = array();

    // Filter by published status
    if ($published_only) {
      $where_conditions[] = "published = 1";
    }

    // Add search filter
    if (!empty($search_term)) {
      $where_conditions[] = "name LIKE %s";
      $params[] = '%' . $wpdb->esc_like($search_term) . '%';
    }

    // Add WHERE clause if we have conditions
    if (!empty($where_conditions)) {
      $query .= " WHERE " . implode(' AND ', $where_conditions);
    }

    $query .= " ORDER BY created_at DESC LIMIT 20";

    if (!empty($params)) {
      $query = $wpdb->prepare($query, ...$params);
    }

    $results = $wpdb->get_results($query);

    // Format the results for the field
    $videos = array();
    foreach ($results as $video) {
      $videos[] = array(
        'id' => $video->id,
        'vod_id' => $video->vod_id,
        'title' => $video->name,
        'poster' => $video->poster,
        'mpd_url' => $video->mpd_url,
        'created_at' => $video->created_at,
        'updated_at' => $video->updated_at,
      );
    }

    return $videos;
  }

  /**
   * Check if VOD Eikon plugin is active
   *
   * @return bool
   */
  private function is_vod_eikon_active()
  {
    return class_exists('VOD_Eikon') || function_exists('vod_eikon_get_videos');
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

    // Format based on return format setting
    $return_format = isset($field['return_format']) ? $field['return_format'] : 'object';

    switch ($return_format) {
      case 'id':
        return isset($video_data['vod_id']) ? $video_data['vod_id'] : '';

      case 'url':
        return isset($video_data['mpd_url']) ? $video_data['mpd_url'] : '';

      case 'object':
      default:
        return $video_data;
    }
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

    // If it's a string, try to decode it
    if (is_string($value)) {
      $decoded = json_decode($value, true);
      if (json_last_error() !== JSON_ERROR_NONE) {
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

    if (!isset($value['vod_id']) && !isset($value['id'])) {
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

    return $value;
  }
}
