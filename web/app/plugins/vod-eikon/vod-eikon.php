<?php

/**
 * Plugin Name: VOD Eikon
 * Plugin URI: https://eikon.ch
 * Description: Gérez les vidéos Infomaniak VOD directement depuis WordPress. Téléchargez, listez et supprimez des vidéos en utilisant l'API Infomaniak VOD.
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
    add_action('wp_ajax_sync_single_video', array($this, 'ajax_sync_single_video'));
    add_action('wp_ajax_delete_vod_video', array($this, 'ajax_delete_video'));
    add_action('wp_ajax_get_vod_player', array($this, 'ajax_get_vod_player'));
    add_action('wp_ajax_nopriv_get_vod_player', array($this, 'ajax_get_vod_player'));
    add_action('wp_ajax_upload_vod_video', array($this, 'ajax_upload_video'));

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
      'Vidéos',
      'Vidéos',
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
      <h1>Gestionnaire de Vidéos VOD</h1>

      <!-- Tabs Navigation -->
      <div class="vod-tabs-wrapper">
        <ul class="vod-tabs-nav">
          <li class="vod-tab-nav active" data-tab="videos">
            <a href="#videos-tab">
              <span class="dashicons dashicons-format-video"></span>
              Bibliothèque Vidéo
            </a>
          </li>
          <li class="vod-tab-nav" data-tab="upload">
            <a href="#upload-tab">
              <span class="dashicons dashicons-upload"></span>
              Télécharger une Vidéo
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
                Synchroniser
              </button>
              <button id="update-incomplete-videos" class="button button-primary">
                <span class="dashicons dashicons-update-alt"></span>
                MàJ des données incomplètes
              </button>
              <span id="sync-status"></span>
            </div>

            <div class="vod-eikon-videos">
              <?php if (empty($videos)): ?>
                <p>Aucune vidéo trouvée. Cliquez sur "Synchroniser les Vidéos" pour récupérer depuis l'API VOD Infomaniak.</p>
              <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                  <thead>
                    <tr>
                      <th>Affiche</th>
                      <th>Nom</th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($videos as $video):
                      $is_incomplete = empty($video->poster) || empty($video->mpd_url);
                      $row_class = $is_incomplete ? 'incomplete-video' : '';
                    ?>
                      <tr data-video-id="<?php echo esc_attr($video->id); ?>" class="<?php echo $row_class; ?> video-main-row">
                        <td>
                          <?php if ($video->poster): ?>
                            <img src="<?php echo esc_url($video->poster); ?>" alt="<?php echo esc_attr($video->name); ?>" style="max-width: 80px; height: auto;">
                          <?php else: ?>
                            <span class="dashicons dashicons-format-video" style="color: #dc3545;" title="Affiche manquante - la vidéo est peut-être encore en cours de traitement"></span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <?php echo esc_html($video->name); ?>
                          <?php if ($is_incomplete): ?>
                            <span class="processing-indicator" title="La vidéo est encore en cours de traitement">
                              <span class="dashicons dashicons-clock" style="color: #ffc107; font-size: 14px;"></span>
                              <small style="color: #ffc107;">Traitement</small>
                            </span>
                          <?php endif; ?>
                        </td>
                        <td class="actions-column">
                          <span class="action-icon sync-single-video" data-video-id="<?php echo esc_attr($video->id); ?>" data-vod-id="<?php echo esc_attr($video->vod_id); ?>" title="Synchroniser cette vidéo">
                            <span class="dashicons dashicons-update"></span>
                          </span>
                          <span class="action-icon delete-video" data-video-id="<?php echo esc_attr($video->id); ?>" title="Supprimer cette vidéo">
                            <span class="dashicons dashicons-trash"></span>
                          </span>
                          <span class="action-icon toggle-details" data-video-id="<?php echo esc_attr($video->id); ?>" title="Afficher/Masquer les détails">
                            <span class="dashicons dashicons-arrow-down-alt2"></span>
                          </span>
                        </td>
                      </tr>
                      <!-- Details Row (Hidden by default) -->
                      <tr id="details-<?php echo esc_attr($video->id); ?>" class="video-details-row" style="display: none;">
                        <td colspan="3">
                          <div class="video-details-content">
                            <table class="details-table">
                              <tbody>
                                <tr>
                                  <td class="detail-label">ID VOD:</td>
                                  <td class="detail-value"><?php echo esc_html($video->vod_id); ?></td>
                                </tr>
                                <tr>
                                  <td class="detail-label">URL MPD:</td>
                                  <td class="detail-value">
                                    <?php if ($video->mpd_url): ?>
                                      <code class="copyable-url"><?php echo esc_html($video->mpd_url); ?></code>
                                      <button class="button button-small copy-url" data-url="<?php echo esc_attr($video->mpd_url); ?>">
                                        <span class="dashicons dashicons-admin-page"></span>
                                        Copier
                                      </button>
                                    <?php else: ?>
                                      <em style="color: #dc3545;">Aucune URL MPD disponible</em>
                                    <?php endif; ?>
                                  </td>
                                </tr>
                                <tr>
                                  <td class="detail-label">Date de création:</td>
                                  <td class="detail-value"><?php echo esc_html(date('d/m/Y H:i', strtotime($video->created_at))); ?></td>
                                </tr>
                                <tr>
                                  <td class="detail-label">Statut:</td>
                                  <td class="detail-value">
                                    <?php if ($is_incomplete): ?>
                                      <span class="status-incomplete">En cours de traitement</span>
                                    <?php else: ?>
                                      <span class="status-complete">Prêt</span>
                                    <?php endif; ?>
                                  </td>
                                </tr>
                              </tbody>
                            </table>
                          </div>
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
              <h2>Télécharger une Vidéo vers Infomaniak VOD</h2>
              <p>Téléchargez un fichier vidéo vers votre chaîne Infomaniak VOD. Formats supportés : MP4, MOV, AVI, MKV.</p>

              <div class="notice notice-info">
                <p>
                  <span class="dashicons dashicons-info"></span>
                  <strong>Temps de Traitement :</strong> Après le téléchargement, les vidéos ont besoin de temps pour être traitées sur les serveurs d'Infomaniak.
                  L'image d'affiche et l'URL de lecture seront disponibles une fois le traitement terminé (généralement entre 5 et 30 minutes).
                  Le système vérifiera automatiquement les mises à jour à intervalles réguliers.
                </p>
              </div>

              <form id="vod-upload-form" enctype="multipart/form-data">
                <table class="form-table">
                  <tr>
                    <th scope="row">
                      <label for="video-file">Fichier Vidéo</label>
                    </th>
                    <td>
                      <input type="file" id="video-file" name="video_file" accept="video/*" required>
                      <p class="description">Sélectionnez un fichier vidéo à télécharger. Taille maximale du fichier : <?php echo $this->format_bytes($this->get_server_upload_limit()); ?></p>
                    </td>
                  </tr>
                </table>

                <div class="vod-upload-actions">
                  <button type="submit" id="upload-video" class="button button-primary">
                    <span class="dashicons dashicons-upload"></span>
                    Télécharger la Vidéo
                  </button>
                  <button type="button" id="cancel-upload" class="button button-secondary" style="display: none;">
                    Annuler le Téléchargement
                  </button>
                </div>

                <div id="upload-progress" class="vod-upload-progress" style="display: none;">
                  <div class="progress-bar">
                    <div class="progress-fill"></div>
                  </div>
                  <p class="progress-text">Téléchargement... 0%</p>
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

    $api_url = "https://api.infomaniak.com/1/vod/channel/{$channel_id}/media?with=poster,streams,encoded_medias";

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
    $removed_count = 0;

    // Get all existing videos from database to track which ones should be removed
    $existing_videos = $wpdb->get_results("SELECT id, vod_id FROM {$this->table_name}");
    $existing_vod_ids = array();
    foreach ($existing_videos as $video) {
      $existing_vod_ids[$video->vod_id] = $video->id;
    }

    // Track which videos are still active on the server
    $active_vod_ids = array();

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

      // Mark this video as active
      $active_vod_ids[] = $vod_id;

      // Handle poster field - it can be a string URL, an object with 'url' field, or an array containing URLs
      $poster = '';
      if (!empty($video_data['poster'])) {
        if (is_string($video_data['poster'])) {
          $poster = esc_url_raw($video_data['poster']);
        } elseif (is_array($video_data['poster'])) {
          // Check common poster URL fields in the array/object
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

    // Remove videos from database that are no longer on the server or have been discarded
    foreach ($existing_vod_ids as $vod_id => $db_id) {
      if (!in_array($vod_id, $active_vod_ids)) {
        $result = $wpdb->delete(
          $this->table_name,
          array('id' => $db_id),
          array('%d')
        );

        if ($result !== false) {
          $removed_count++;
        }
      }
    }

    if ($removed_count > 0) {
    }

    return array(
      'synced' => $synced_count,
      'removed' => $removed_count
    );
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


    // Check if there are streams data available with direct DASH URLs
    $streams_data = $video_data['streams'] ?? null;

    if (!empty($streams_data) && is_array($streams_data)) {
      // Try to find DASH stream data in streams
      foreach ($streams_data as $stream) {
        if (isset($stream['type']) && strtolower($stream['type']) === 'dash') {
          if (!empty($stream['url'])) {
            return $stream['url'];
          }
        }
      }
    }

    // Fallback: Construct MPD URL using basic media IDs and file size sorting
    $media_ids = array();

    foreach ($video_data['encoded_medias'] as $media) {
      if (!empty($media['id'])) {
        $media_ids[] = $media['id'];
      }
    }

    if (empty($media_ids)) {
      return '';
    }

    // Sort media IDs by file size (largest first) as a proxy for quality
    $sized_medias = array();
    foreach ($video_data['encoded_medias'] as $media) {
      if (!empty($media['id']) && !empty($media['size'])) {
        $sized_medias[] = array(
          'id' => $media['id'],
          'size' => intval($media['size'])
        );
      }
    }

    if (!empty($sized_medias)) {
      // Sort by size descending (larger files likely higher quality)
      usort($sized_medias, function ($a, $b) {
        return $b['size'] - $a['size'];
      });
      $media_ids = array_column($sized_medias, 'id');
    }

    // Use the video ID as the encoding ID (common pattern for Infomaniak VOD)
    $encoding_id = $vod_id;

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

    $sync_result = $this->sync_videos_from_api();

    if ($sync_result !== false) {
      $synced_count = $sync_result['synced'];
      $removed_count = $sync_result['removed'];

      $message = sprintf('Successfully synchronized %d videos.', $synced_count);

      if ($removed_count > 0) {
        $message .= sprintf(' Supprimé %d vidéos qui ont été rejetées ou qui n\'existent plus sur le serveur.', $removed_count);
      }

      wp_send_json_success(array(
        'message' => $message
      ));
    } else {
      wp_send_json_error(array(
        'message' => 'Échec de la synchronisation des vidéos. Veuillez vérifier votre configuration API.'
      ));
    }
  }

  public function ajax_sync_single_video()
  {
    // Verify nonce for security
    if (!wp_verify_nonce($_POST['nonce'], 'vod_eikon_nonce')) {
      wp_die(json_encode(array(
        'success' => false,
        'data' => array('message' => 'Invalid security token.')
      )));
    }

    $vod_id = sanitize_text_field($_POST['vod_id'] ?? '');

    if (empty($vod_id)) {
      wp_send_json_error(array(
        'message' => 'ID VOD invalide.'
      ));
    }

    // Update the single video using the existing function
    $this->check_video_processing_status($vod_id);

    // Get the updated video from database to check if anything changed
    global $wpdb;
    $updated_video = $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM {$this->table_name} WHERE vod_id = %s",
      $vod_id
    ));

    if (!$updated_video) {
      wp_send_json_error(array(
        'message' => 'Vidéo introuvable.'
      ));
    }

    $is_complete = !empty($updated_video->poster) && !empty($updated_video->mpd_url);

    if ($is_complete) {
      wp_send_json_success(array(
        'message' => 'Vidéo synchronisée avec succès !',
        'video' => $updated_video
      ));
    } else {
      wp_send_json_success(array(
        'message' => 'Synchronisation effectuée. La vidéo est peut-être encore en cours de traitement.',
        'video' => $updated_video
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
        'message' => 'ID de vidéo invalide.'
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
        'message' => 'Vidéo introuvable.'
      ));
    }

    // Delete from Infomaniak VOD API first
    $api_delete_result = $this->delete_video_from_api($video->vod_id);

    if (!$api_delete_result) {
      wp_send_json_error(array(
        'message' => 'Échec de la suppression de la vidéo du service VOD Infomaniak.'
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
        'message' => 'Vidéo supprimée avec succès du service VOD Infomaniak et de la base de données locale.'
      ));
    } else {
      wp_send_json_error(array(
        'message' => 'La vidéo a été supprimée du service VOD Infomaniak mais n\'a pas pu être supprimée de la base de données locale.'
      ));
    }
  }

  public function ajax_get_vod_player()
  {
    $vod_id = sanitize_text_field($_GET['vod_id'] ?? '');

    if (empty($vod_id)) {
      wp_die('ID de vidéo invalide');
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


    // Verify nonce for security
    if (!wp_verify_nonce($_POST['nonce'], 'vod_eikon_nonce')) {
      wp_send_json_error(array(
        'message' => 'Jeton de sécurité invalide.'
      ));
    }

    // Check if user can upload files
    if (!current_user_can('upload_files')) {
      wp_send_json_error(array(
        'message' => 'Vous n\'avez pas l\'autorisation de télécharger des fichiers.'
      ));
    }

    $channel_id = getenv('INFOMANIAK_CHANNEL_ID');
    $api_token = getenv('INFOMANIAK_TOKEN_API');


    // Also try $_ENV as fallback
    if (!$channel_id) {
      $channel_id = $_ENV['INFOMANIAK_CHANNEL_ID'] ?? '';
    }

    if (!$api_token) {
      $api_token = $_ENV['INFOMANIAK_TOKEN_API'] ?? '';
    }

    if (!$channel_id || !$api_token) {
      wp_send_json_error(array(
        'message' => 'Variables d\'environnement manquantes INFOMANIAK_CHANNEL_ID ou INFOMANIAK_TOKEN_API.'
      ));
    }

    // Validate inputs
    $title = '';
    $description = '';

    // Check if file was uploaded
    if (!isset($_FILES['video_file']) || $_FILES['video_file']['error'] !== UPLOAD_ERR_OK) {
      wp_send_json_error(array(
        'message' => 'Aucun fichier téléchargé ou erreur de téléchargement.'
      ));
    }

    $file = $_FILES['video_file'];

    // Generate title from filename if not provided
    if (empty($title)) {
      $title = pathinfo($file['name'], PATHINFO_FILENAME);
      $title = sanitize_text_field($title);
    }



    // Validate file type
    $allowed_types = array('video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/x-matroska');
    $file_type = $file['type'];

    if (!in_array($file_type, $allowed_types)) {
      wp_send_json_error(array(
        'message' => 'Type de fichier invalide. Veuillez télécharger uniquement des fichiers MP4, MOV, AVI ou MKV.'
      ));
    }

    // Check file size against server limits
    $max_upload_size = $this->get_server_upload_limit();
    if ($file['size'] > $max_upload_size) {
      wp_send_json_error(array(
        'message' => 'La taille du fichier dépasse la limite de ' . $this->format_bytes($max_upload_size) . '.'
      ));
    }

    // Upload to Infomaniak VOD API
    $upload_result = $this->upload_to_infomaniak($file, $title, $description, $channel_id, $api_token);

    if ($upload_result['success']) {
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
        'message' => 'Vidéo téléchargée avec succès ! La vidéo peut prendre quelques minutes à traiter. L\'image d\'affiche et l\'URL de lecture seront disponibles une fois le traitement terminé.',
        'video_id' => $upload_result['video_id']
      ));
    } else {
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


    // Prepare the file for upload
    $cfile = new CURLFile($file['tmp_name'], $file['type'], $file['name']);

    $post_fields = array(
      'file' => $cfile,
      'title' => $title
    );

    if (!empty($description)) {
      $post_fields['description'] = $description;
    }


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
          }
        }
        return 0; // Return 0 to continue
      }
    ));

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    $curl_info = curl_getinfo($ch);


    curl_close($ch);

    if ($curl_error) {
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

      return array(
        'success' => false,
        'message' => $error_message
      );
    }

    $response_data = json_decode($response, true);

    if (!$response_data || !isset($response_data['data'])) {
      return array(
        'success' => false,
        'message' => 'Invalid response from upload API'
      );
    }

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
   * Update incomplete videos in the database
   */
  public function update_incomplete_videos()
  {

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
      return;
    }


    $channel_id = getenv('INFOMANIAK_CHANNEL_ID');
    $api_token = getenv('INFOMANIAK_TOKEN_API');

    if (!$channel_id || !$api_token) {
      return;
    }

    $updated_count = 0;

    foreach ($incomplete_videos as $video) {
      $vod_id = $video->vod_id;

      // Get individual video data from API - try with different parameters
      $api_url = "https://api.infomaniak.com/1/vod/channel/{$channel_id}/media/{$vod_id}?with=poster,streams,encoded_medias,thumbnails,images";

      $response = wp_remote_get($api_url, array(
        'headers' => array(
          'Authorization' => 'Bearer ' . $api_token,
          'Accept' => 'application/json'
        ),
        'timeout' => 30
      ));

      if (is_wp_error($response)) {
        continue;
      }

      $body = wp_remote_retrieve_body($response);
      $data = json_decode($body, true);

      if (!$data || !isset($data['data'])) {
        continue;
      }

      $video_data = $data['data'];

      // Debug: Check if there are other fields that might contain poster/thumbnail info
      $potential_poster_fields = ['poster', 'thumbnail', 'thumbnails', 'image', 'images', 'preview'];
      $found_fields = array();
      foreach ($potential_poster_fields as $field) {
        if (isset($video_data[$field])) {
          $found_fields[] = $field;
        }
      }
      if (empty($found_fields)) {
      }      // Try to manually trigger poster generation via API if poster is empty
      if (empty($video_data['poster'])) {
        // Check if video is sufficiently processed before attempting poster generation
        $processing_progress = isset($video_data['progress']) ? intval($video_data['progress']) : 0;

        if ($processing_progress < 80) {
        } else {

          // Method 1: Try to generate poster using Infomaniak API with different time values
          $generate_poster_url = "https://api.infomaniak.com/1/vod/channel/{$channel_id}/media/{$vod_id}/poster";

          $poster_times = [3, 5, 10]; // Try different timestamp positions
          $poster_generated = false;

          foreach ($poster_times as $time) {

            $poster_response = wp_remote_post($generate_poster_url, array(
              'headers' => array(
                'Authorization' => 'Bearer ' . $api_token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
              ),
              'body' => json_encode(array('time' => $time)),
              'timeout' => 30
            ));

            if (!is_wp_error($poster_response)) {
              $poster_code = wp_remote_retrieve_response_code($poster_response);
              $poster_body = wp_remote_retrieve_body($poster_response);

              if ($poster_code == 200 || $poster_code == 201) {
                $poster_generated = true;
                break;
              }
            } else {
            }
          }

          // Method 2: Try alternative thumbnail endpoint
          $thumbnail_url = "https://api.infomaniak.com/1/vod/channel/{$channel_id}/media/{$vod_id}/thumbnail";

          $thumbnail_response = wp_remote_post($thumbnail_url, array(
            'headers' => array(
              'Authorization' => 'Bearer ' . $api_token,
              'Accept' => 'application/json',
              'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array('time' => 5)),
            'timeout' => 30
          ));

          if (!is_wp_error($thumbnail_response)) {
            $thumb_code = wp_remote_retrieve_response_code($thumbnail_response);
            $thumb_body = wp_remote_retrieve_body($thumbnail_response);
          }

          // Method 3: Check if there's a specific thumbnails endpoint
          $thumbnails_list_url = "https://api.infomaniak.com/1/vod/channel/{$channel_id}/media/{$vod_id}/thumbnails";

          $thumbnails_response = wp_remote_get($thumbnails_list_url, array(
            'headers' => array(
              'Authorization' => 'Bearer ' . $api_token,
              'Accept' => 'application/json'
            ),
            'timeout' => 30
          ));

          if (!is_wp_error($thumbnails_response)) {
            $thumbnails_code = wp_remote_retrieve_response_code($thumbnails_response);
            $thumbnails_body = wp_remote_retrieve_body($thumbnails_response);

            if ($thumbnails_code == 200) {
              $thumbnails_data = json_decode($thumbnails_body, true);
              if (isset($thumbnails_data['data']) && !empty($thumbnails_data['data'])) {
              }
            }
          }

          // Wait a moment and re-fetch the video data if any generation was triggered
          if ($poster_generated) {
            sleep(3);

            $refetch_response = wp_remote_get($api_url, array(
              'headers' => array(
                'Authorization' => 'Bearer ' . $api_token,
                'Accept' => 'application/json'
              ),
              'timeout' => 30
            ));

            if (!is_wp_error($refetch_response)) {
              $refetch_body = wp_remote_retrieve_body($refetch_response);
              $refetch_data = json_decode($refetch_body, true);
              if ($refetch_data && isset($refetch_data['data'])) {
                $video_data = $refetch_data['data'];
              }
            }
          }
        }
      }

      // Debug: log the full video data structure to understand what's available

      // Method 4: Try to construct poster URLs using common Infomaniak patterns based on video data
      if (empty($video_data['poster']) && !empty($video_data['encoded_medias'])) {

        // Look for any URL patterns in encoded_medias that might hint at poster locations
        foreach ($video_data['encoded_medias'] as $media) {
          if (isset($media['url'])) {
            $media_url = $media['url'];

            // Try to derive poster URL from media URL patterns
            $url_patterns = [
              // Replace media file with poster
              str_replace(['.mp4', '.webm', '.m4v'], '.jpg', $media_url),
              str_replace(['/media/', '/stream/'], '/poster/', $media_url),
              str_replace(['/media/', '/stream/'], '/thumbnail/', $media_url),
              // Try poster subdirectory
              dirname($media_url) . '/poster.jpg',
              dirname($media_url) . '/thumbnail.jpg',
              dirname($media_url) . '/preview.jpg'
            ];

            foreach ($url_patterns as $test_url) {
              if (filter_var($test_url, FILTER_VALIDATE_URL)) {
                $response = wp_remote_head($test_url, array('timeout' => 5));
                if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) == 200) {
                  $video_data['poster'] = $test_url; // Update the video data
                  break 2; // Break out of both loops
                }
              }
            }
          }
        }
      }

      // Check if video is still processing (no encoded_medias or empty poster)
      $has_encoded_medias = !empty($video_data['encoded_medias']) && is_array($video_data['encoded_medias']);
      $has_poster = !empty($video_data['poster']);
      $processing_progress = isset($video_data['progress']) ? intval($video_data['progress']) : 0;
      $video_state = isset($video_data['state']) ? intval($video_data['state']) : 0;

      // Log processing status for better debugging

      if (!$has_encoded_medias && !$has_poster) {
        continue;
      }

      // If video is not fully processed but has some encoded medias, still try to get what we can
      if ($processing_progress < 100) {
      }      // Extract poster URL
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
            foreach ($video_data['poster'] as $key => $value) {
              if (is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
                $poster = esc_url_raw($value);
                break;
              }
            }
          }
        }
      } else {

        // Try to construct poster URL from video ID (common pattern for VOD services)
        // Infomaniak might use a predictable poster URL pattern
        $potential_poster_urls = [
          "https://vod2.infomaniak.com/thumbnail/{$vod_id}/poster.jpg",
          "https://vod2.infomaniak.com/poster/{$vod_id}.jpg",
          "https://img.infomaniak.com/vod/{$vod_id}/poster.jpg",
          "https://play.vod2.infomaniak.com/thumbnail/{$vod_id}/poster.jpg",
          "https://vod.infomaniak.com/thumbnail/{$vod_id}/poster.jpg"
        ];

        foreach ($potential_poster_urls as $test_url) {
          // Test if poster URL is accessible
          $response = wp_remote_head($test_url, array('timeout' => 5));
          if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) == 200) {
            $poster = $test_url;
            break;
          }
        }

        if (empty($poster)) {
        }
      }

      // Construct MPD URL from encoded_medias data
      $mpd_url = $this->construct_mpd_url($vod_id, $video_data);

      if (empty($mpd_url)) {
      }

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
        } else {
        }
      } else {
      }

      // Add small delay to avoid hitting API rate limits
      usleep(500000); // 0.5 seconds
    }
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
        'message' => sprintf('Mise à jour terminée. %d vidéos ont encore des données manquantes (peut-être encore en cours de traitement).', $remaining_incomplete)
      ));
    } else {
      wp_send_json_success(array(
        'message' => 'Toutes les vidéos ont été mises à jour avec succès !'
      ));
    }
  }

  /**
   * Check processing status for a specific video by VOD ID
   * This is called by scheduled events after video upload
   */
  public function check_video_processing_status($vod_id)
  {

    global $wpdb;

    // Get current video data from database
    $current_video = $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM {$this->table_name} WHERE vod_id = %s",
      $vod_id
    ));

    if (!$current_video) {
      return;
    }

    // If video already has both poster and MPD URL, no need to check
    if (!empty($current_video->poster) && !empty($current_video->mpd_url)) {
      return;
    }

    $channel_id = getenv('INFOMANIAK_CHANNEL_ID');
    $api_token = getenv('INFOMANIAK_TOKEN_API');

    if (!$channel_id || !$api_token) {
      return;
    }

    // Get video data from API
    $api_url = "https://api.infomaniak.com/1/vod/channel/{$channel_id}/media/{$vod_id}?with=poster,streams,encoded_medias";

    $response = wp_remote_get($api_url, array(
      'headers' => array(
        'Authorization' => 'Bearer ' . $api_token,
        'Accept' => 'application/json'
      ),
      'timeout' => 30
    ));

    if (is_wp_error($response)) {
      return;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!$data || !isset($data['data'])) {
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
      } else {
      }
    } else {
    }
  }
}

// Initialize the plugin
new VOD_Eikon();

// Include helper functions
require_once VOD_EIKON_PLUGIN_DIR . 'includes/functions.php';
