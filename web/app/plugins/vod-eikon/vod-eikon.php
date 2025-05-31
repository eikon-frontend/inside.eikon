<?php

/**
 * Plugin Name: VOD Eikon
 * Plugin URI: https://eikon.ch
 * Description: Manage Infomaniak VOD videos directly from WordPress. Upload, list, and delete videos using the Infomaniak VOD API.
 * Version: 1.0.0
 * Author: EIKON
 * Author URI: https://eikon.ch
 * Text Domain: vod-eikon
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

// Define plugin constants
define('VOD_EIKON_VERSION', '1.0.0');
define('VOD_EIKON_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VOD_EIKON_PLUGIN_URL', plugin_dir_url(__FILE__));

class VOD_Eikon
{

  private $table_name;

  public function __construct()
  {
    global $wpdb;
    $this->table_name = $wpdb->prefix . 'vod_eikon_videos';

    add_action('plugins_loaded', array($this, 'init'));
    register_activation_hook(__FILE__, array($this, 'activate'));
    register_deactivation_hook(__FILE__, array($this, 'deactivate'));
  }

  public function init()
  {
    add_action('admin_menu', array($this, 'add_admin_menu'));
    add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    add_action('wp_ajax_sync_vod_videos', array($this, 'ajax_sync_videos'));
    add_action('wp_ajax_delete_vod_video', array($this, 'ajax_delete_video'));
    add_action('wp_ajax_get_vod_player', array($this, 'ajax_get_vod_player'));
    add_action('wp_ajax_nopriv_get_vod_player', array($this, 'ajax_get_vod_player'));
    add_action('wp_ajax_upload_vod_video', array($this, 'ajax_upload_video'));

    // Debug endpoint for testing API response
    add_action('wp_ajax_debug_vod_api', array($this, 'debug_api_response'));

    // Test endpoint to verify AJAX is working
    add_action('wp_ajax_test_vod_ajax', array($this, 'test_ajax_endpoint'));

    // Schedule daily sync if not already scheduled
    if (!wp_next_scheduled('vod_eikon_daily_sync')) {
      wp_schedule_event(time(), 'daily', 'vod_eikon_daily_sync');
    }
    add_action('vod_eikon_daily_sync', array($this, 'sync_videos_from_api'));
  }

  public function activate()
  {
    $this->create_database_table();
    $this->sync_videos_from_api();
  }

  public function deactivate()
  {
    wp_clear_scheduled_hook('vod_eikon_daily_sync');
  }

  private function create_database_table()
  {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$this->table_name} (
            id int(11) NOT NULL AUTO_INCREMENT,
            vod_id varchar(255) NOT NULL,
            name varchar(255) NOT NULL,
            poster varchar(500) DEFAULT '',
            mpd_url varchar(500) DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY vod_id (vod_id)
        ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
  }

  public function add_admin_menu()
  {
    add_media_page(
      'Videos',
      'Videos',
      'manage_options',
      'vod-eikon',
      array($this, 'admin_page')
    );
  }

  public function enqueue_admin_scripts($hook)
  {
    if ('media_page_vod-eikon' !== $hook) {
      return;
    }

    wp_enqueue_script('jquery');
    wp_enqueue_script(
      'vod-eikon-admin',
      VOD_EIKON_PLUGIN_URL . 'assets/admin.js',
      array('jquery'),
      VOD_EIKON_VERSION,
      true
    );

    wp_localize_script('vod-eikon-admin', 'vodEikon', array(
      'ajax_url' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('vod_eikon_nonce')
    ));

    wp_enqueue_style(
      'vod-eikon-admin',
      VOD_EIKON_PLUGIN_URL . 'assets/admin.css',
      array(),
      VOD_EIKON_VERSION
    );
  }

  public function admin_page()
  {
    $videos = $this->get_videos_from_db();
?>
    <div class="wrap">
      <h1>VOD Videos Manager</h1>

      <!-- Tabs Navigation -->
      <div class="vod-tabs-wrapper">
        <ul class="vod-tabs-nav">
          <li class="vod-tab-nav active" data-tab="videos">
            <a href="#videos-tab">
              <span class="dashicons dashicons-format-video"></span>
              Video Library
            </a>
          </li>
          <li class="vod-tab-nav" data-tab="upload">
            <a href="#upload-tab">
              <span class="dashicons dashicons-upload"></span>
              Upload Video
            </a>
          </li>
        </ul>

        <!-- Tab Content -->
        <div class="vod-tab-content">

          <!-- Videos Tab -->
          <div id="videos-tab" class="vod-tab-panel active">
            <div class="vod-eikon-actions">
              <button id="sync-videos" class="button button-primary">
                <span class="dashicons dashicons-update"></span>
                Synchronize Videos
              </button>
              <button id="debug-api" class="button button-secondary">
                <span class="dashicons dashicons-search"></span>
                Debug API Response
              </button>
              <button id="test-ajax" class="button button-secondary">
                <span class="dashicons dashicons-admin-tools"></span>
                Test AJAX
              </button>
              <span id="sync-status"></span>
            </div>

            <div class="vod-eikon-videos">
              <?php if (empty($videos)): ?>
                <p>No videos found. Click "Synchronize Videos" to fetch from Infomaniak VOD API.</p>
              <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                  <thead>
                    <tr>
                      <th>Poster</th>
                      <th>Name</th>
                      <th>VOD ID</th>
                      <th>MPD URL</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($videos as $video): ?>
                      <tr data-video-id="<?php echo esc_attr($video->id); ?>">
                        <td>
                          <?php if ($video->poster): ?>
                            <img src="<?php echo esc_url($video->poster); ?>" alt="<?php echo esc_attr($video->name); ?>" style="max-width: 80px; height: auto;">
                          <?php else: ?>
                            <span class="dashicons dashicons-format-video"></span>
                          <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($video->name); ?></td>
                        <td><?php echo esc_html($video->vod_id); ?></td>
                        <td>
                          <?php if ($video->mpd_url): ?>
                            <code><?php echo esc_html($video->mpd_url); ?></code>
                          <?php else: ?>
                            <em>No MPD URL available</em>
                          <?php endif; ?>
                        </td>
                        <td>
                          <button class="button delete-video" data-video-id="<?php echo esc_attr($video->id); ?>">
                            Delete
                          </button>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              <?php endif; ?>
            </div>
          </div>

          <!-- Upload Tab -->
          <div id="upload-tab" class="vod-tab-panel">
            <div class="vod-upload-section">
              <h2>Upload Video to Infomaniak VOD</h2>
              <p>Upload a video file to your Infomaniak VOD channel. Supported formats: MP4, MOV, AVI, MKV.</p>

              <form id="vod-upload-form" enctype="multipart/form-data">
                <table class="form-table">
                  <tr>
                    <th scope="row">
                      <label for="video-file">Video File</label>
                    </th>
                    <td>
                      <input type="file" id="video-file" name="video_file" accept="video/*" required>
                      <p class="description">Select a video file to upload. Maximum file size: 2GB</p>
                    </td>
                  </tr>
                </table>

                <div class="vod-upload-actions">
                  <button type="submit" id="upload-video" class="button button-primary">
                    <span class="dashicons dashicons-upload"></span>
                    Upload Video
                  </button>
                  <button type="button" id="cancel-upload" class="button button-secondary" style="display: none;">
                    Cancel Upload
                  </button>
                </div>

                <div id="upload-progress" class="vod-upload-progress" style="display: none;">
                  <div class="progress-bar">
                    <div class="progress-fill"></div>
                  </div>
                  <p class="progress-text">Uploading... 0%</p>
                </div>

                <div id="upload-status" class="vod-upload-status"></div>
              </form>
            </div>
          </div>

        </div>
      </div>
    </div>
<?php
  }

  public function sync_videos_from_api()
  {
    $channel_id = getenv('INFOMANIAK_CHANNEL_ID');
    $api_token = getenv('INFOMANIAK_TOKEN_API');

    if (!$channel_id || !$api_token) {
      return false;
    }

    $api_url = "https://api.infomaniak.com/1/vod/channel/{$channel_id}/media?with=poster";

    $response = wp_remote_get($api_url, array(
      'headers' => array(
        'Authorization' => 'Bearer ' . $api_token,
        'Accept' => 'application/json'
      ),
      'timeout' => 30
    ));

    if (is_wp_error($response)) {
      return false;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!$data || !isset($data['data'])) {
      return false;
    }

    global $wpdb;
    $synced_count = 0;

    foreach ($data['data'] as $video_data) {
      $vod_id = sanitize_text_field($video_data['id'] ?? '');
      $name = sanitize_text_field($video_data['title'] ?? $video_data['name'] ?? '');

      if (empty($vod_id) || empty($name)) {
        continue;
      }

      // Filter out videos that are not in the root folder (path != "/")
      // Only sync videos that are in the root folder
      $folder_path = $video_data['folder']['path'] ?? '';
      if ($folder_path !== '/') {
        continue;
      }

      // Filter out videos that are in the trash (have a discarded_at timestamp)
      $discarded_at = $video_data['discarded_at'] ?? null;
      if (!empty($discarded_at)) {
        continue;
      }

      // Handle poster field - it might be a string URL or an array containing URLs
      $poster = '';
      if (!empty($video_data['poster'])) {
        if (is_string($video_data['poster'])) {
          $poster = esc_url_raw($video_data['poster']);
        } elseif (is_array($video_data['poster'])) {
          // Check common poster URL fields in the array
          foreach (['url', 'src', 'href', 'link'] as $field) {
            if (!empty($video_data['poster'][$field]) && is_string($video_data['poster'][$field])) {
              $poster = esc_url_raw($video_data['poster'][$field]);
              break;
            }
          }
          // If no standard field found, try the first string value in the array
          if (empty($poster)) {
            foreach ($video_data['poster'] as $value) {
              if (is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
                $poster = esc_url_raw($value);
                break;
              }
            }
          }
        }
      }

      // Construct MPD URL from encoded_medias data
      $mpd_url = $this->construct_mpd_url($vod_id, $video_data);

      // Check if video already exists
      $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$this->table_name} WHERE vod_id = %s",
        $vod_id
      ));

      if ($existing) {
        // Update existing video
        $wpdb->update(
          $this->table_name,
          array(
            'name' => $name,
            'poster' => $poster,
            'mpd_url' => $mpd_url
          ),
          array('vod_id' => $vod_id),
          array('%s', '%s', '%s'),
          array('%s')
        );
      } else {
        // Insert new video
        $wpdb->insert(
          $this->table_name,
          array(
            'vod_id' => $vod_id,
            'name' => $name,
            'poster' => $poster,
            'mpd_url' => $mpd_url
          ),
          array('%s', '%s', '%s', '%s')
        );
        $synced_count++;
      }
    }

    return $synced_count;
  }

  /**
   * Construct MPD URL from video data
   * Format: https://play.vod2.infomaniak.com/dash/{video_id}/{encoding_id}/,{media_id1},{media_id2},...,.urlset/manifest.mpd
   */
  private function construct_mpd_url($vod_id, $video_data)
  {
    // Check if we have encoded_medias data
    if (empty($video_data['encoded_medias']) || !is_array($video_data['encoded_medias'])) {
      return '';
    }

    // Get the encoding ID from the first encoded media (they should all share the same encoding)
    $first_media = $video_data['encoded_medias'][0] ?? null;
    if (!$first_media || empty($first_media['encoding_stream']['encoding']['id'])) {
      return '';
    }

    $encoding_id = $first_media['encoding_stream']['encoding']['id'];

    // Collect all media IDs from encoded_medias, sorted by quality (highest first)
    $media_ids = array();
    $qualities = array();

    foreach ($video_data['encoded_medias'] as $media) {
      if (!empty($media['id']) && !empty($media['encoding_stream']['video_height'])) {
        $media_ids[] = $media['id'];
        $qualities[] = intval($media['encoding_stream']['video_height']);
      }
    }

    // Sort media IDs by quality descending (1080p, 720p, 480p, 360p)
    if (count($media_ids) > 1) {
      array_multisort($qualities, SORT_DESC, $media_ids);
    }

    if (empty($media_ids)) {
      return '';
    }

    // Construct the MPD URL
    $media_list = ',' . implode(',', $media_ids) . ',';
    $mpd_url = "https://play.vod2.infomaniak.com/dash/{$vod_id}/{$encoding_id}/{$media_list}.urlset/manifest.mpd";

    return $mpd_url;
  }

  private function delete_video_from_api($vod_id)
  {
    $channel_id = getenv('INFOMANIAK_CHANNEL_ID');
    $api_token = getenv('INFOMANIAK_TOKEN_API');

    if (!$channel_id || !$api_token) {
      return false;
    }

    $api_url = "https://api.infomaniak.com/1/vod/channel/{$channel_id}/media/{$vod_id}";

    $response = wp_remote_request($api_url, array(
      'method' => 'DELETE',
      'headers' => array(
        'Authorization' => 'Bearer ' . $api_token,
        'Content-Type' => 'application/json'
      ),
      'timeout' => 30
    ));

    if (is_wp_error($response)) {
      return false;
    }

    $response_code = wp_remote_retrieve_response_code($response);

    // API returns 204 No Content on successful deletion
    if ($response_code === 204) {
      return true;
    } else {
      return false;
    }
  }


  private function get_videos_from_db()
  {
    global $wpdb;

    return $wpdb->get_results(
      "SELECT * FROM {$this->table_name} ORDER BY created_at DESC"
    );
  }

  public function ajax_sync_videos()
  {
    // Verify nonce for security
    if (!wp_verify_nonce($_POST['nonce'], 'vod_eikon_nonce')) {
      wp_die(json_encode(array(
        'success' => false,
        'data' => array('message' => 'Invalid security token.')
      )));
    }

    $synced_count = $this->sync_videos_from_api();

    if ($synced_count !== false) {
      wp_send_json_success(array(
        'message' => sprintf('Successfully synchronized %d videos.', $synced_count)
      ));
    } else {
      wp_send_json_error(array(
        'message' => 'Failed to synchronize videos. Please check your API configuration.'
      ));
    }
  }

  public function ajax_delete_video()
  {
    // Verify nonce for security
    if (!wp_verify_nonce($_POST['nonce'], 'vod_eikon_nonce')) {
      wp_die(json_encode(array(
        'success' => false,
        'data' => array('message' => 'Invalid security token.')
      )));
    }

    $video_id = intval($_POST['video_id']);

    if (empty($video_id)) {
      wp_send_json_error(array(
        'message' => 'Invalid video ID.'
      ));
    }

    global $wpdb;

    // First, get the video details to retrieve the VOD ID
    $video = $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM {$this->table_name} WHERE id = %d",
      $video_id
    ));

    if (!$video) {
      wp_send_json_error(array(
        'message' => 'Video not found.'
      ));
    }

    // Delete from Infomaniak VOD API first
    $api_delete_result = $this->delete_video_from_api($video->vod_id);

    if (!$api_delete_result) {
      wp_send_json_error(array(
        'message' => 'Failed to delete video from Infomaniak VOD service.'
      ));
    }

    // If API deletion was successful, delete from local database
    $result = $wpdb->delete(
      $this->table_name,
      array('id' => $video_id),
      array('%d')
    );

    if ($result !== false) {
      wp_send_json_success(array(
        'message' => 'Video deleted successfully from both Infomaniak VOD service and local database.'
      ));
    } else {
      wp_send_json_error(array(
        'message' => 'Video was deleted from Infomaniak VOD service but failed to delete from local database.'
      ));
    }
  }

  public function debug_api_response()
  {
    // Verify nonce for security
    if (!wp_verify_nonce($_POST['nonce'], 'vod_eikon_nonce')) {
      wp_die(json_encode(array(
        'success' => false,
        'data' => array('message' => 'Invalid security token.')
      )));
    }

    $channel_id = getenv('INFOMANIAK_CHANNEL_ID');
    $api_token = getenv('INFOMANIAK_TOKEN_API');

    if (!$channel_id || !$api_token) {
      wp_send_json_error(array(
        'message' => 'Missing environment variables INFOMANIAK_CHANNEL_ID or INFOMANIAK_TOKEN_API.'
      ));
    }

    $api_url = "https://api.infomaniak.com/1/vod/channel/{$channel_id}/media?with=poster";

    $response = wp_remote_get($api_url, array(
      'headers' => array(
        'Authorization' => 'Bearer ' . $api_token,
        'Accept' => 'application/json'
      ),
      'timeout' => 30
    ));

    if (is_wp_error($response)) {
      wp_send_json_error(array(
        'message' => 'API Error: ' . $response->get_error_message()
      ));
    }

    $response_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if ($response_code !== 200) {
      wp_send_json_error(array(
        'message' => "API returned status code {$response_code}. Response: " . substr($body, 0, 500)
      ));
    }

    if (!$data || !isset($data['data'])) {
      wp_send_json_error(array(
        'message' => 'Invalid API response structure. Response: ' . substr($body, 0, 500)
      ));
    }

    wp_send_json_success(array(
      'message' => sprintf(
        'API response successful. Found %d videos. Response code: %d',
        count($data['data']),
        $response_code
      )
    ));
  }

  public function ajax_get_vod_player()
  {
    $vod_id = sanitize_text_field($_GET['vod_id'] ?? '');

    if (empty($vod_id)) {
      wp_die('Invalid video ID');
    }

    echo vod_eikon_player($vod_id, array(
      'width' => '100%',
      'height' => '450px',
      'autoplay' => true
    ));

    wp_die();
  }

  // Public method to get videos for use in themes/other plugins
  public function get_videos()
  {
    return $this->get_videos_from_db();
  }

  // Public method to get a specific video by VOD ID
  public function get_video_by_vod_id($vod_id)
  {
    global $wpdb;

    return $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM {$this->table_name} WHERE vod_id = %s",
      $vod_id
    ));
  }

  // Public method to get a specific video by ID (internal use)
  public function get_video_by_id($id)
  {
    global $wpdb;

    return $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM {$this->table_name} WHERE id = %d",
      $id
    ));
  }

  /**
   * AJAX handler for video upload
   */
  public function ajax_upload_video()
  {
    // Increase execution time and memory limits for video upload
    set_time_limit(0); // No time limit
    ini_set('memory_limit', '512M');

    // Simple file logging test
    file_put_contents('/Users/jminguely/Sites/inside.eikon/web/app/debug.log', '[' . date('Y-m-d H:i:s') . '] VOD Eikon: ajax_upload_video method called!' . PHP_EOL, FILE_APPEND);

    error_log('VOD Eikon: ajax_upload_video started');
    error_log('VOD Eikon: $_POST data: ' . print_r($_POST, true));
    error_log('VOD Eikon: $_FILES data: ' . print_r($_FILES, true));

    // Verify nonce for security
    if (!wp_verify_nonce($_POST['nonce'], 'vod_eikon_nonce')) {
      error_log('VOD Eikon: Nonce verification failed');
      wp_send_json_error(array(
        'message' => 'Invalid security token.'
      ));
    }

    // Check if user can upload files
    if (!current_user_can('upload_files')) {
      error_log('VOD Eikon: User does not have upload_files capability');
      wp_send_json_error(array(
        'message' => 'You do not have permission to upload files.'
      ));
    }

    $channel_id = getenv('INFOMANIAK_CHANNEL_ID');
    $api_token = getenv('INFOMANIAK_TOKEN_API');

    error_log('VOD Eikon: Channel ID: ' . ($channel_id ? 'SET (' . substr($channel_id, 0, 5) . '...)' : 'NOT SET'));
    error_log('VOD Eikon: API Token: ' . ($api_token ? 'SET (' . substr($api_token, 0, 10) . '...)' : 'NOT SET'));

    // Also try $_ENV as fallback
    if (!$channel_id) {
      $channel_id = $_ENV['INFOMANIAK_CHANNEL_ID'] ?? '';
      error_log('VOD Eikon: Fallback Channel ID from $_ENV: ' . ($channel_id ? 'SET' : 'NOT SET'));
    }

    if (!$api_token) {
      $api_token = $_ENV['INFOMANIAK_TOKEN_API'] ?? '';
      error_log('VOD Eikon: Fallback API Token from $_ENV: ' . ($api_token ? 'SET' : 'NOT SET'));
    }

    if (!$channel_id || !$api_token) {
      error_log('VOD Eikon: Missing environment variables');
      wp_send_json_error(array(
        'message' => 'Missing environment variables INFOMANIAK_CHANNEL_ID or INFOMANIAK_TOKEN_API.'
      ));
    }

    // Validate inputs
    $title = '';
    $description = '';

    // Check if file was uploaded
    if (!isset($_FILES['video_file']) || $_FILES['video_file']['error'] !== UPLOAD_ERR_OK) {
      error_log('VOD Eikon: File upload error. Error code: ' . ($_FILES['video_file']['error'] ?? 'not set'));
      wp_send_json_error(array(
        'message' => 'No file uploaded or upload error occurred.'
      ));
    }

    $file = $_FILES['video_file'];

    // Generate title from filename if not provided
    if (empty($title)) {
      $title = pathinfo($file['name'], PATHINFO_FILENAME);
      $title = sanitize_text_field($title);
    }

    error_log('VOD Eikon: Generated title: ' . $title);
    error_log('VOD Eikon: Description: ' . $description);

    error_log('VOD Eikon: File details - Name: ' . $file['name'] . ', Size: ' . $file['size'] . ', Type: ' . $file['type'] . ', Tmp: ' . $file['tmp_name']);

    // Validate file type
    $allowed_types = array('video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/x-matroska');
    $file_type = $file['type'];

    if (!in_array($file_type, $allowed_types)) {
      error_log('VOD Eikon: Invalid file type: ' . $file_type);
      wp_send_json_error(array(
        'message' => 'Invalid file type. Please upload MP4, MOV, AVI, or MKV files only.'
      ));
    }

    // Check file size (2GB limit)
    if ($file['size'] > 2 * 1024 * 1024 * 1024) {
      error_log('VOD Eikon: File too large: ' . $file['size'] . ' bytes');
      wp_send_json_error(array(
        'message' => 'File size exceeds 2GB limit.'
      ));
    }

    error_log('VOD Eikon: Starting upload to Infomaniak API');
    // Upload to Infomaniak VOD API
    $upload_result = $this->upload_to_infomaniak($file, $title, $description, $channel_id, $api_token);

    if ($upload_result['success']) {
      error_log('VOD Eikon: Upload successful, syncing videos');
      // Sync videos to update the database with the new upload
      $this->sync_videos_from_api();

      wp_send_json_success(array(
        'message' => 'Video uploaded successfully!',
        'video_id' => $upload_result['video_id']
      ));
    } else {
      error_log('VOD Eikon: Upload failed: ' . $upload_result['message']);
      wp_send_json_error(array(
        'message' => $upload_result['message']
      ));
    }
  }

  /**
   * Upload video file to Infomaniak VOD API
   */
  private function upload_to_infomaniak($file, $title, $description, $channel_id, $api_token)
  {
    $api_url = "https://api.infomaniak.com/1/vod/channel/{$channel_id}/upload";

    error_log('VOD Eikon: API URL: ' . $api_url);
    error_log('VOD Eikon: File temp path: ' . $file['tmp_name']);
    error_log('VOD Eikon: File exists: ' . (file_exists($file['tmp_name']) ? 'YES' : 'NO'));

    // Prepare the file for upload
    $cfile = new CURLFile($file['tmp_name'], $file['type'], $file['name']);

    $post_fields = array(
      'file' => $cfile,
      'title' => $title
    );

    if (!empty($description)) {
      $post_fields['description'] = $description;
    }

    error_log('VOD Eikon: Post fields prepared (excluding file): ' . print_r(array_diff_key($post_fields, ['file' => '']), true));

    // Initialize cURL
    $ch = curl_init();

    curl_setopt_array($ch, array(
      CURLOPT_URL => $api_url,
      CURLOPT_POST => true,
      CURLOPT_POSTFIELDS => $post_fields,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HTTPHEADER => array(
        'Authorization: Bearer ' . $api_token,
        'Accept: application/json',
        'User-Agent: WordPress VOD Eikon Plugin/1.0'
      ),
      CURLOPT_TIMEOUT => 600, // 10 minutes timeout
      CURLOPT_CONNECTTIMEOUT => 30, // 30 seconds connection timeout
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_SSL_VERIFYPEER => false,
      CURLOPT_VERBOSE => true, // Enable verbose logging
      CURLOPT_NOPROGRESS => false, // Enable progress function
      CURLOPT_PROGRESSFUNCTION => function ($resource, $download_total, $downloaded, $upload_total, $uploaded) {
        if ($upload_total > 0) {
          $percent = round(($uploaded / $upload_total) * 100, 1);
          if ($percent % 10 == 0) { // Log every 10%
            error_log("VOD Eikon: Upload progress: {$percent}% ({$uploaded}/{$upload_total} bytes)");
          }
        }
        return 0; // Return 0 to continue
      }
    ));

    error_log('VOD Eikon: Executing cURL request...');
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    $curl_info = curl_getinfo($ch);

    error_log('VOD Eikon: cURL execution completed');
    error_log('VOD Eikon: cURL response - HTTP Code: ' . $http_code);
    error_log('VOD Eikon: cURL error: ' . ($curl_error ?: 'None'));
    error_log('VOD Eikon: Response length: ' . strlen($response));
    error_log('VOD Eikon: API Response: ' . substr($response, 0, 1000)); // Log first 1000 chars

    curl_close($ch);

    if ($curl_error) {
      error_log('VOD Eikon: cURL error occurred: ' . $curl_error);
      return array(
        'success' => false,
        'message' => 'Upload failed: ' . $curl_error
      );
    }

    if ($http_code !== 200 && $http_code !== 201) {
      $error_message = 'Upload failed with HTTP code: ' . $http_code;

      if ($response) {
        $response_data = json_decode($response, true);
        if (isset($response_data['error'])) {
          if (is_array($response_data['error'])) {
            $error_message .= ' - ' . json_encode($response_data['error']);
          } else {
            $error_message .= ' - ' . $response_data['error'];
          }
        }
        if (isset($response_data['message'])) {
          $error_message .= ' - ' . $response_data['message'];
        }
      }

      error_log('VOD Eikon: HTTP error: ' . $error_message);
      return array(
        'success' => false,
        'message' => $error_message
      );
    }

    $response_data = json_decode($response, true);
    error_log('VOD Eikon: Parsed response data: ' . print_r($response_data, true));

    if (!$response_data || !isset($response_data['data'])) {
      error_log('VOD Eikon: Invalid response structure');
      return array(
        'success' => false,
        'message' => 'Invalid response from upload API'
      );
    }

    error_log('VOD Eikon: Upload completed successfully');
    return array(
      'success' => true,
      'video_id' => $response_data['data']['id'] ?? '',
      'message' => 'Upload successful'
    );
  }

  /**
   * Test AJAX endpoint to verify basic functionality
   */
  public function test_ajax_endpoint()
  {
    error_log('VOD Eikon: Test AJAX endpoint reached successfully');
    wp_send_json_success(array(
      'message' => 'AJAX is working correctly!'
    ));
  }
}

// Initialize the plugin
new VOD_Eikon();

// Include helper functions
require_once VOD_EIKON_PLUGIN_DIR . 'includes/functions.php';
