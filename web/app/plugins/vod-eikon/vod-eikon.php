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

    // Debug endpoint for testing API response
    add_action('wp_ajax_debug_vod_api', array($this, 'debug_api_response'));

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
      'VOD Eikon Videos',
      'VOD Videos',
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
      <h1>VOD Eikon Videos</h1>

      <div class="vod-eikon-actions">
        <button id="sync-videos" class="button button-primary">
          <span class="dashicons dashicons-update"></span>
          Synchronize Videos
        </button>
        <button id="debug-api" class="button button-secondary">
          <span class="dashicons dashicons-search"></span>
          Debug API Response
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
<?php
  }

  public function sync_videos_from_api()
  {
    $channel_id = getenv('INFOMANIAK_CHANNEL_ID');
    $api_token = getenv('INFOMANIAK_TOKEN_API');

    if (!$channel_id || !$api_token) {
      error_log('VOD Eikon: Missing environment variables INFOMANIAK_CHANNEL_ID or INFOMANIAK_TOKEN_API');
      return false;
    }

    $api_url = "https://api.infomaniak.com/1/vod/channel/{$channel_id}/media";

    $response = wp_remote_get($api_url, array(
      'headers' => array(
        'Authorization' => 'Bearer ' . $api_token,
        'Accept' => 'application/json'
      ),
      'timeout' => 30
    ));

    if (is_wp_error($response)) {
      error_log('VOD Eikon API Error: ' . $response->get_error_message());
      return false;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!$data || !isset($data['data'])) {
      error_log('VOD Eikon: Invalid API response');
      return false;
    }

    // Debug: Log the API response structure
    error_log('VOD Eikon API Response: ' . print_r($data, true));

    global $wpdb;
    $synced_count = 0;

    foreach ($data['data'] as $video_data) {
      // Debug: Log each video data structure
      error_log('VOD Eikon Video Data: ' . print_r($video_data, true));

      $vod_id = sanitize_text_field($video_data['id'] ?? '');
      $name = sanitize_text_field($video_data['title'] ?? $video_data['name'] ?? '');

      // Try multiple possible field names for poster
      $poster = '';
      foreach (['poster', 'thumbnail', 'poster_url', 'thumb', 'image', 'cover'] as $field) {
        if (!empty($video_data[$field])) {
          $poster = esc_url_raw($video_data[$field]);
          break;
        }
      }

      // Try multiple possible field names for MPD URL
      $mpd_url = '';
      foreach (['mpd_url', 'dash_url', 'streaming_url', 'manifest_url', 'mpd', 'dash'] as $field) {
        if (!empty($video_data[$field])) {
          $mpd_url = esc_url_raw($video_data[$field]);
          break;
        }
      }

      // Check for nested URLs object
      if (empty($mpd_url) && isset($video_data['urls'])) {
        foreach (['mpd', 'dash', 'manifest'] as $field) {
          if (!empty($video_data['urls'][$field])) {
            $mpd_url = esc_url_raw($video_data['urls'][$field]);
            break;
          }
        }
      }

      if (empty($vod_id) || empty($name)) {
        continue;
      }

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

  public function ajax_sync_videos()
  {
    check_ajax_referer('vod_eikon_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_die('Unauthorized');
    }

    $synced_count = $this->sync_videos_from_api();

    if ($synced_count !== false) {
      wp_send_json_success(array(
        'message' => "Successfully synchronized {$synced_count} new videos.",
        'videos' => $this->get_videos_from_db()
      ));
    } else {
      wp_send_json_error(array(
        'message' => 'Failed to synchronize videos. Check error logs for details.'
      ));
    }
  }

  public function ajax_delete_video()
  {
    check_ajax_referer('vod_eikon_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_die('Unauthorized');
    }

    $video_id = intval($_POST['video_id']);

    global $wpdb;
    $result = $wpdb->delete(
      $this->table_name,
      array('id' => $video_id),
      array('%d')
    );

    if ($result !== false) {
      wp_send_json_success(array(
        'message' => 'Video deleted successfully.'
      ));
    } else {
      wp_send_json_error(array(
        'message' => 'Failed to delete video.'
      ));
    }
  }

  public function debug_api_response()
  {
    if (!current_user_can('manage_options')) {
      wp_die('Unauthorized');
    }

    $result = $this->sync_videos_from_api();

    wp_send_json_success(array(
      'message' => "Debug sync completed. Check error logs for API response details.",
      'synced_count' => $result
    ));
  }

  private function get_videos_from_db()
  {
    global $wpdb;

    return $wpdb->get_results(
      "SELECT * FROM {$this->table_name} ORDER BY created_at DESC"
    );
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
}

// Initialize the plugin
new VOD_Eikon();

// Include helper functions
require_once VOD_EIKON_PLUGIN_DIR . 'includes/functions.php';
