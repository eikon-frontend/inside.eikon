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
    add_action('wp_ajax_update_incomplete_videos', array($this, 'ajax_update_incomplete_videos'));
    add_action('wp_ajax_delete_vod_video', array($this, 'ajax_delete_video'));
    add_action('wp_ajax_get_vod_player', array($this, 'ajax_get_vod_player'));
    add_action('wp_ajax_nopriv_get_vod_player', array($this, 'ajax_get_vod_player'));
    add_action('wp_ajax_upload_vod_video', array($this, 'ajax_upload_video'));

    // Debug endpoint for testing API response
    add_action('wp_ajax_debug_vod_api', array($this, 'debug_api_response'));

    // Test endpoint to verify AJAX is working
    add_action('wp_ajax_test_vod_ajax', array($this, 'test_ajax_endpoint'));

    // Add debug endpoint for upload limits
    add_action('wp_ajax_debug_upload_limits', array($this, 'debug_upload_limits'));

    // Add test endpoint for incomplete video processing
    add_action('wp_ajax_test_incomplete_video_processing', array($this, 'test_incomplete_video_processing'));

    // Schedule daily sync if not already scheduled
    if (!wp_next_scheduled('vod_eikon_daily_sync')) {
      wp_schedule_event(time(), 'daily', 'vod_eikon_daily_sync');
    }
    add_action('vod_eikon_daily_sync', array($this, 'sync_videos_from_api'));

    // Schedule hourly update for incomplete videos if not already scheduled
    if (!wp_next_scheduled('vod_eikon_update_incomplete_videos')) {
      wp_schedule_event(time(), 'hourly', 'vod_eikon_update_incomplete_videos');
    }
    add_action('vod_eikon_update_incomplete_videos', array($this, 'update_incomplete_videos'));

    // Add hook for checking individual video processing status
    add_action('vod_eikon_check_video_processing', array($this, 'check_video_processing_status'));
  }

  public function activate()
  {
    $this->create_database_table();
    $this->sync_videos_from_api();
  }

  public function deactivate()
  {
    wp_clear_scheduled_hook('vod_eikon_daily_sync');
    wp_clear_scheduled_hook('vod_eikon_update_incomplete_videos');
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
      'nonce' => wp_create_nonce('vod_eikon_nonce'),
      'max_upload_size' => $this->get_server_upload_limit(),
      'max_upload_size_formatted' => $this->format_bytes($this->get_server_upload_limit())
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
              <?php
              // Get count of incomplete videos for the button label
              global $wpdb;
              $incomplete_count = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$this->table_name}
                 WHERE (poster = '' OR poster IS NULL)
                 OR (mpd_url = '' OR mpd_url IS NULL)"
              );
              ?>
              <button id="update-incomplete-videos" class="button button-primary<?php echo $incomplete_count > 0 ? ' button-primary' : ' button-secondary'; ?>">
                <span class="dashicons dashicons-update-alt"></span>
                Update Incomplete Videos
                <?php if ($incomplete_count > 0): ?>
                  <span class="incomplete-count">(<?php echo $incomplete_count; ?>)</span>
                <?php endif; ?>
              </button>
              <button id="debug-api" class="button button-secondary">
                <span class="dashicons dashicons-search"></span>
                Debug API Response
              </button>
              <button id="debug-upload-limits" class="button button-secondary">
                <span class="dashicons dashicons-info"></span>
                Upload Limits Info
              </button>
              <button id="test-ajax" class="button button-secondary">
                <span class="dashicons dashicons-admin-tools"></span>
                Test AJAX
              </button>
              <button id="test-processing" class="button button-secondary">
                <span class="dashicons dashicons-analytics"></span>
                Processing Stats
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
                    <?php foreach ($videos as $video):
                      $is_incomplete = empty($video->poster) || empty($video->mpd_url);
                      $row_class = $is_incomplete ? 'incomplete-video' : '';
                    ?>
                      <tr data-video-id="<?php echo esc_attr($video->id); ?>" class="<?php echo $row_class; ?>">
                        <td>
                          <?php if ($video->poster): ?>
                            <img src="<?php echo esc_url($video->poster); ?>" alt="<?php echo esc_attr($video->name); ?>" style="max-width: 80px; height: auto;">
                          <?php else: ?>
                            <span class="dashicons dashicons-format-video" style="color: #dc3545;" title="Poster missing - video may still be processing"></span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <?php echo esc_html($video->name); ?>
                          <?php if ($is_incomplete): ?>
                            <span class="processing-indicator" title="Video is still processing">
                              <span class="dashicons dashicons-clock" style="color: #ffc107; font-size: 14px;"></span>
                              <small style="color: #ffc107;">Processing</small>
                            </span>
                          <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($video->vod_id); ?></td>
                        <td>
                          <?php if ($video->mpd_url): ?>
                            <code><?php echo esc_html($video->mpd_url); ?></code>
                          <?php else: ?>
                            <em style="color: #dc3545;">No MPD URL available</em>
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

              <div class="notice notice-info">
                <p>
                  <span class="dashicons dashicons-info"></span>
                  <strong>Processing Time:</strong> After upload, videos need time to process on Infomaniak's servers.
                  The poster image and playback URL will be available once processing is complete (usually within 5-30 minutes).
                  The system will automatically check for updates at regular intervals.
                </p>
              </div>

              <form id="vod-upload-form" enctype="multipart/form-data">
                <table class="form-table">
                  <tr>
                    <th scope="row">
                      <label for="video-file">Video File</label>
                    </th>
                    <td>
                      <input type="file" id="video-file" name="video_file" accept="video/*" required>
                      <p class="description">Select a video file to upload. Maximum file size: <?php echo $this->format_bytes($this->get_server_upload_limit()); ?></p>
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

    // Check file size against server limits
    $max_upload_size = $this->get_server_upload_limit();
    if ($file['size'] > $max_upload_size) {
      error_log('VOD Eikon: File too large: ' . $file['size'] . ' bytes');
      wp_send_json_error(array(
        'message' => 'File size exceeds ' . $this->format_bytes($max_upload_size) . ' limit.'
      ));
    }

    error_log('VOD Eikon: Starting upload to Infomaniak API');
    // Upload to Infomaniak VOD API
    $upload_result = $this->upload_to_infomaniak($file, $title, $description, $channel_id, $api_token);

    if ($upload_result['success']) {
      error_log('VOD Eikon: Upload successful, syncing videos');
      // Sync videos to update the database with the new upload
      $this->sync_videos_from_api();

      // Schedule a delayed update to check for processing completion
      $video_id = $upload_result['video_id'];
      if (!empty($video_id)) {
        // Schedule checks at 2 minutes, 5 minutes, 10 minutes, and 30 minutes
        wp_schedule_single_event(time() + 120, 'vod_eikon_check_video_processing', array($video_id)); // 2 minutes
        wp_schedule_single_event(time() + 300, 'vod_eikon_check_video_processing', array($video_id)); // 5 minutes
        wp_schedule_single_event(time() + 600, 'vod_eikon_check_video_processing', array($video_id)); // 10 minutes
        wp_schedule_single_event(time() + 1800, 'vod_eikon_check_video_processing', array($video_id)); // 30 minutes
      }

      wp_send_json_success(array(
        'message' => 'Video uploaded successfully! The video may take a few minutes to process. Poster image and playback URL will be available once processing is complete.',
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
   * Get the actual server upload limits
   */
  private function get_server_upload_limit()
  {
    // Get upload_max_filesize and post_max_size from PHP configuration
    $upload_max = $this->parse_size(ini_get('upload_max_filesize'));
    $post_max = $this->parse_size(ini_get('post_max_size'));

    // WordPress also has a limit
    $wp_max = wp_max_upload_size();

    // Return the smallest limit (most restrictive)
    $max_size = min($upload_max, $post_max, $wp_max);

    return $max_size;
  }

  /**
   * Convert PHP size string to bytes
   */
  private function parse_size($size)
  {
    $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
    $size = preg_replace('/[^0-9\.]/', '', $size);

    if ($unit) {
      return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
    } else {
      return round($size);
    }
  }

  /**
   * Format bytes to human readable format
   */
  private function format_bytes($size, $precision = 2)
  {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');

    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
      $size /= 1024;
    }

    return round($size, $precision) . ' ' . $units[$i];
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

  /**
   * Debugging endpoint to check upload limits
   */
  public function debug_upload_limits()
  {
    // Verify nonce for security
    if (!wp_verify_nonce($_POST['nonce'], 'vod_eikon_nonce')) {
      wp_send_json_error(array(
        'message' => 'Invalid security token.'
      ));
    }

    $limit_info = $this->get_upload_limit_info();

    wp_send_json_success(array(
      'message' => 'Upload limits retrieved successfully.',
      'limits' => $limit_info
    ));
  }

  /**
   * Get detailed upload limit information for debugging
   */
  public function get_upload_limit_info()
  {
    $upload_max = $this->parse_size(ini_get('upload_max_filesize'));
    $post_max = $this->parse_size(ini_get('post_max_size'));
    $wp_max = wp_max_upload_size();
    $effective_limit = min($upload_max, $post_max, $wp_max);

    return array(
      'upload_max_filesize' => array(
        'raw' => ini_get('upload_max_filesize'),
        'bytes' => $upload_max,
        'formatted' => $this->format_bytes($upload_max)
      ),
      'post_max_size' => array(
        'raw' => ini_get('post_max_size'),
        'bytes' => $post_max,
        'formatted' => $this->format_bytes($post_max)
      ),
      'wp_max_upload_size' => array(
        'bytes' => $wp_max,
        'formatted' => $this->format_bytes($wp_max)
      ),
      'effective_limit' => array(
        'bytes' => $effective_limit,
        'formatted' => $this->format_bytes($effective_limit)
      )
    );
  }

  /**
   * Update incomplete videos in the database
   */
  public function update_incomplete_videos()
  {
    error_log('VOD Eikon: Starting update of incomplete videos');

    global $wpdb;

    // Find videos that are missing poster OR mpd_url (indicating they may still be processing)
    $incomplete_videos = $wpdb->get_results(
      "SELECT vod_id FROM {$this->table_name}
       WHERE (poster = '' OR poster IS NULL)
       OR (mpd_url = '' OR mpd_url IS NULL)
       ORDER BY created_at DESC
       LIMIT 10"
    );

    if (empty($incomplete_videos)) {
      error_log('VOD Eikon: No incomplete videos found');
      return;
    }

    error_log('VOD Eikon: Found ' . count($incomplete_videos) . ' incomplete videos to update');

    $channel_id = getenv('INFOMANIAK_CHANNEL_ID');
    $api_token = getenv('INFOMANIAK_TOKEN_API');

    if (!$channel_id || !$api_token) {
      error_log('VOD Eikon: Missing API credentials for updating incomplete videos');
      return;
    }

    $updated_count = 0;

    foreach ($incomplete_videos as $video) {
      $vod_id = $video->vod_id;

      // Get individual video data from API
      $api_url = "https://api.infomaniak.com/1/vod/channel/{$channel_id}/media/{$vod_id}?with=poster";

      $response = wp_remote_get($api_url, array(
        'headers' => array(
          'Authorization' => 'Bearer ' . $api_token,
          'Accept' => 'application/json'
        ),
        'timeout' => 30
      ));

      if (is_wp_error($response)) {
        error_log('VOD Eikon: Error fetching video ' . $vod_id . ': ' . $response->get_error_message());
        continue;
      }

      $body = wp_remote_retrieve_body($response);
      $data = json_decode($body, true);

      if (!$data || !isset($data['data'])) {
        error_log('VOD Eikon: Invalid API response for video ' . $vod_id);
        continue;
      }

      $video_data = $data['data'];

      // Check if video is still processing (no encoded_medias or empty poster)
      $has_encoded_medias = !empty($video_data['encoded_medias']) && is_array($video_data['encoded_medias']);
      $has_poster = !empty($video_data['poster']);

      if (!$has_encoded_medias && !$has_poster) {
        error_log('VOD Eikon: Video ' . $vod_id . ' is still processing, skipping');
        continue;
      }

      // Extract poster URL
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

      // Only update if we have new data
      if (!empty($poster) || !empty($mpd_url)) {
        $update_data = array();
        $update_format = array();

        if (!empty($poster)) {
          $update_data['poster'] = $poster;
          $update_format[] = '%s';
        }

        if (!empty($mpd_url)) {
          $update_data['mpd_url'] = $mpd_url;
          $update_format[] = '%s';
        }

        $result = $wpdb->update(
          $this->table_name,
          $update_data,
          array('vod_id' => $vod_id),
          $update_format,
          array('%s')
        );

        if ($result !== false) {
          $updated_count++;
          error_log('VOD Eikon: Updated video ' . $vod_id . ' with ' . implode(', ', array_keys($update_data)));
        } else {
          error_log('VOD Eikon: Failed to update video ' . $vod_id);
        }
      } else {
        error_log('VOD Eikon: No new data available for video ' . $vod_id);
      }

      // Add small delay to avoid hitting API rate limits
      usleep(500000); // 0.5 seconds
    }

    error_log('VOD Eikon: Updated ' . $updated_count . ' incomplete videos');
  }

  public function ajax_update_incomplete_videos()
  {
    // Verify nonce for security
    if (!wp_verify_nonce($_POST['nonce'], 'vod_eikon_nonce')) {
      wp_die(json_encode(array(
        'success' => false,
        'data' => array('message' => 'Invalid security token.')
      )));
    }

    $this->update_incomplete_videos();

    // Get count of remaining incomplete videos
    global $wpdb;
    $remaining_incomplete = $wpdb->get_var(
      "SELECT COUNT(*) FROM {$this->table_name}
       WHERE (poster = '' OR poster IS NULL)
       OR (mpd_url = '' OR mpd_url IS NULL)"
    );

    if ($remaining_incomplete > 0) {
      wp_send_json_success(array(
        'message' => sprintf('Update completed. %d videos still have missing data (may still be processing).', $remaining_incomplete)
      ));
    } else {
      wp_send_json_success(array(
        'message' => 'All videos have been updated successfully!'
      ));
    }
  }

  /**
   * Test endpoint for incomplete video processing
   */
  public function test_incomplete_video_processing()
  {
    // Verify nonce for security
    if (!wp_verify_nonce($_POST['nonce'], 'vod_eikon_nonce')) {
      wp_send_json_error(array(
        'message' => 'Invalid security token.'
      ));
    }

    global $wpdb;

    // Get some statistics about video processing
    $total_videos = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
    $complete_videos = $wpdb->get_var(
      "SELECT COUNT(*) FROM {$this->table_name}
       WHERE poster != '' AND poster IS NOT NULL
       AND mpd_url != '' AND mpd_url IS NOT NULL"
    );
    $incomplete_videos = $total_videos - $complete_videos;

    // Get a sample of incomplete videos
    $sample_incomplete = $wpdb->get_results(
      "SELECT vod_id, name, poster, mpd_url, created_at FROM {$this->table_name}
       WHERE (poster = '' OR poster IS NULL)
       OR (mpd_url = '' OR mpd_url IS NULL)
       ORDER BY created_at DESC
       LIMIT 5",
      ARRAY_A
    );

    wp_send_json_success(array(
      'message' => 'Video processing statistics retrieved successfully.',
      'statistics' => array(
        'total_videos' => $total_videos,
        'complete_videos' => $complete_videos,
        'incomplete_videos' => $incomplete_videos,
        'completion_rate' => $total_videos > 0 ? round(($complete_videos / $total_videos) * 100, 1) : 0
      ),
      'sample_incomplete' => $sample_incomplete
    ));
  }

  /**
   * Check processing status for a specific video by VOD ID
   * This is called by scheduled events after video upload
   */
  public function check_video_processing_status($vod_id)
  {
    error_log('VOD Eikon: Checking processing status for video: ' . $vod_id);

    global $wpdb;

    // Get current video data from database
    $current_video = $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM {$this->table_name} WHERE vod_id = %s",
      $vod_id
    ));

    if (!$current_video) {
      error_log('VOD Eikon: Video not found in database: ' . $vod_id);
      return;
    }

    // If video already has both poster and MPD URL, no need to check
    if (!empty($current_video->poster) && !empty($current_video->mpd_url)) {
      error_log('VOD Eikon: Video already complete: ' . $vod_id);
      return;
    }

    $channel_id = getenv('INFOMANIAK_CHANNEL_ID');
    $api_token = getenv('INFOMANIAK_TOKEN_API');

    if (!$channel_id || !$api_token) {
      error_log('VOD Eikon: Missing API credentials for checking video: ' . $vod_id);
      return;
    }

    // Get video data from API
    $api_url = "https://api.infomaniak.com/1/vod/channel/{$channel_id}/media/{$vod_id}?with=poster";

    $response = wp_remote_get($api_url, array(
      'headers' => array(
        'Authorization' => 'Bearer ' . $api_token,
        'Accept' => 'application/json'
      ),
      'timeout' => 30
    ));

    if (is_wp_error($response)) {
      error_log('VOD Eikon: Error fetching video ' . $vod_id . ': ' . $response->get_error_message());
      return;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!$data || !isset($data['data'])) {
      error_log('VOD Eikon: Invalid API response for video ' . $vod_id);
      return;
    }

    $video_data = $data['data'];

    // Extract poster URL
    $poster = '';
    if (!empty($video_data['poster'])) {
      if (is_string($video_data['poster'])) {
        $poster = esc_url_raw($video_data['poster']);
      } elseif (is_array($video_data['poster'])) {
        foreach (['url', 'src', 'href', 'link'] as $field) {
          if (!empty($video_data['poster'][$field]) && is_string($video_data['poster'][$field])) {
            $poster = esc_url_raw($video_data['poster'][$field]);
            break;
          }
        }
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

    // Check if we have new data to update
    $has_new_data = false;
    $update_data = array();
    $update_format = array();

    if (!empty($poster) && empty($current_video->poster)) {
      $update_data['poster'] = $poster;
      $update_format[] = '%s';
      $has_new_data = true;
    }

    if (!empty($mpd_url) && empty($current_video->mpd_url)) {
      $update_data['mpd_url'] = $mpd_url;
      $update_format[] = '%s';
      $has_new_data = true;
    }

    if ($has_new_data) {
      $result = $wpdb->update(
        $this->table_name,
        $update_data,
        array('vod_id' => $vod_id),
        $update_format,
        array('%s')
      );

      if ($result !== false) {
        error_log('VOD Eikon: Updated video ' . $vod_id . ' with ' . implode(', ', array_keys($update_data)));
      } else {
        error_log('VOD Eikon: Failed to update video ' . $vod_id);
      }
    } else {
      error_log('VOD Eikon: No new data available for video ' . $vod_id . ' - still processing');
    }
  }
}

// Initialize the plugin
new VOD_Eikon();

// Include helper functions
require_once VOD_EIKON_PLUGIN_DIR . 'includes/functions.php';
