<?php

/**
 * Classe generale regroupant les differentes fonctions du plugin wordpress.
 * En cas de problemes ou de questions, veuillez contacter support-vod-wordpress@infomaniak.ch
 *
 * @author Infomaniak vod team
 * @link http://infomaniak.com
 * @version 1.6.0
 * @copyright infomaniak.com
 */
define('VOD_RIGHT_CONTRIBUTOR', 1);
define('VOD_RIGHT_AUTHOR', 2);
define('VOD_RIGHT_EDITOR', 3);
define('VOD_RIGHT_ADMIN', 4);

class EasyVod
{
  public $version = "1.5.12";
  private $local_version;
  private $plugin_url;
  private $options;
  private $key;
  private $db;
  private $auto_sync;
  private $auto_sync_delay;

  function __construct()
  {
    $this->local_version = $this->version;
    $this->key = 'vod_infomaniak';
    $this->options = get_option('vod_infomaniak', array());
    $this->add_filters_and_hooks();
    $this->db = new EasyVod_db($this);
    $this->auto_sync = true;
    $this->auto_sync_delay = 3600;
    define("VOD_IK_SALT", "2saLsNw4s4MCG9BVjXCKxvZA");
    // Declare variables needed for encryption
    if (extension_loaded('openssl')) {
      $GLOBALS['encryptCipher'] = 'aes-256-cfb';
      $GLOBALS['encryptPassword'] = 'xx187rsd!ze$ze11ld8';
      $GLOBALS['encryptIv'] = '4zEz7z5!4a1s2e$s';
    }
  }

  /**
   * Check if auto-update is needed and perform synchronization if necessary
   * This method ensures videos and player data stay synchronized by checking timestamps
   * @access public
   */
  public function checkAutoUpdate()
  {
    if (empty($this->options['vod_api_lastUpdate'])) {
      $this->options['vod_api_lastUpdate'] = 0;
    }

    $current_time = time();
    $last_update = intval($this->options['vod_api_lastUpdate']);

    // Check if enough time has passed since last update
    if (($current_time - $last_update) > $this->auto_sync_delay) {
      $this->fastSynchro(true, false);
      $this->options['vod_api_lastUpdate'] = $current_time;
      update_option($this->key, $this->options);
    }
  }


  // Moved to private method below

  function add_filters_and_hooks()
  {

    register_activation_hook(__FILE__, array(&$this, 'install_db'));
    add_action('plugins_loaded', array(&$this, 'update_db'));

    load_plugin_textdomain('vod_infomaniak', FALSE, basename(dirname(__FILE__)) . '/languages');

    add_action('template_redirect', array(&$this, 'vod_template_redirect'));
    add_filter('query_vars', 'vod_query_vars');
    add_filter('the_content', array(&$this, 'check'), 100);
    add_filter('the_excerpt', array(&$this, 'check'), 100);

    if (get_site_option('vod_db_version') && get_site_option('vod_db_version') != "1.0.34") {
      $this->options['vod_api_connected'] = 'off';
      update_option('vod_infomaniak', $this->options);
      update_option('vod_db_version', $this->db_version);
    }

    if (is_admin()) {
      add_action('admin_menu', array(&$this, 'add_menu_items'));
      add_action('edit_form_advanced', array(&$this, 'buildForm'));
      add_action('edit_page_form', array(&$this, 'buildForm'));
      add_action('dialog-vod-form', array(&$this, 'buildForm'));

      /**
       * Delegate buildForm to EasyVod_Display
       */
      function buildForm()
      {
        if ($this->plugin_ready()) {
          require_once("vod.template.php");
          $aPlayers = $this->db->get_players();
          $aVideos = $this->db->get_videos();
          $aPlaylists = $this->db->get_playlists();
          $aFolders = $this->db->get_folders();
          $bCanUpload = $this->isCurrentUserCan('upload');
          EasyVod_Display::buildForm($this->options, $aPlayers, $aVideos, $aPlaylists, $aFolders, $bCanUpload);
        }
      }


      add_action('wp_ajax_importvod', array(&$this, 'printLastImport'));
      add_action('wp_ajax_vodsearchvideo', array(&$this, 'searchVideo'));
      add_action('wp_ajax_vodsearchplaylist', array(&$this, 'searchPlaylist'));
      add_action('wp_ajax_vodimportvideo', array(&$this, 'importPostVideo'));
      add_action('wp_ajax_vodimportvideoending', array(&$this, 'importPostVideoEnding'));
      add_action('wp_ajax_vodimportvideodispo', array(&$this, 'importPostVideoDispo'));
      add_action('wp_ajax_vodimportvideofromurl', array(&$this, 'importVideoFromUrl'));
      add_action('wp_ajax_vodgetmediastate', array(&$this, 'getMediaState'));
      add_action('wp_ajax_vodsynchrovideo', array(&$this, 'getSynchroVideo'));


      add_action('wp_ajax_vodsharelink', array(&$this, 'shareLink'));


      add_action('plugins_loaded', array(&$this, 'init_mce_video'));

      //On load css et js pour l'admin
      add_action('admin_enqueue_scripts', array(&$this, 'register_scripts'));
    }
  }

  function register_scripts()
  {
    //styles
    wp_register_style('ui-tabs', plugins_url('vod-infomaniak/css/jquery.ui.tabs.css'));
    wp_enqueue_style('vod-jquery-ui', plugins_url('vod-infomaniak/css/jquery-ui.css'), array(), $this->version, 'screen');
    wp_enqueue_style('ui-tabs');
    //scripts
    wp_enqueue_script('jquery-ui-dialog');
    wp_enqueue_script('jquery-ui-tabs');
    wp_enqueue_script('suggest');
    wp_enqueue_script('my-ajax-script', plugins_url('vod-infomaniak/js/editor_plugin.js'), ['jquery'], null, true);

    wp_localize_script('my-ajax-script', 'customData', [
      'ajaxUrl' => admin_url('admin-ajax.php'),
      'nonce'   => wp_create_nonce('my_action_nonce'),
    ]);
  }

  function init_mce_video()
  {
    if ($this->isCurrentUserCan('gestion')) {
      add_filter('mce_external_plugins', array($this, 'mce_register'));
      add_filter('mce_buttons', array($this, 'mce_add_button'), 0);
    }
  }

  // Add the TinyMCE button
  function mce_add_button($buttons)
  {
    array_push($buttons, "vodplugin");
    return $buttons;
  }

  // Register the plugin path
  function mce_register($plugins)
  {
    $plugins['vodplugin'] = plugins_url('vod-infomaniak/js/editor_plugin.js');
    return $plugins;
  }

  function isCurrentUserCan($page)
  {
    switch ($page) {
      case 'plugin':
        return current_user_can('vod_admin') || current_user_can('vod_import');
      case 'importation':
        return current_user_can('vod_import');
      case 'player':
        return current_user_can('vod_admin');
      case 'playlist':
        return current_user_can('vod_admin');
      case 'gestion':
        return current_user_can('vod_admin') || current_user_can('vod_import');
      case 'configuration':
        return current_user_can('manage_options');
      default:
        return true;
    }
  }

  function install_db()
  {
    $this->db->install_db();
    $bForceAllSynchro = true;
    $this->fastSynchro(true, true);
  }

  function update_db()
  {
    if (get_site_option('vod_db_version') != $this->db->db_version) {
      $this->db->delete_db();
      $this->install_db();
      $this->options['vod_api_lastUpdate'] = 0;  //set last update to 0 to force all api upload
      $bForceAllSynchro = true;
    }
  }

  /**
   * Fast synchronization of video data
   * @param bool $force Force synchronization regardless of timing
   * @param bool $full Perform a full sync instead of incremental
   */
  function fastSynchro($force = false, $full = false)
  {
    $oApi = $this->getAPI();
    if (!$oApi) {
      return false;
    }

    if ($full || $force || $oApi->folderModifiedSince($this->options['vod_api_lastUpdate'])) {
      $this->db->clean_folders();
      $aFolders = $oApi->getFolders();
      if (is_array($aFolders) && !empty($aFolders)) {
        foreach ($aFolders as $oFolder) {
          if (isset($oFolder['iFolderCode']) && isset($oFolder['sFolderPath']) && isset($oFolder['sFolderName'])) {
            $sAccess = isset($oFolder['sAccess']) ? $oFolder['sAccess'] : 'ALL';
            $sToken = isset($oFolder['sToken']) ? $oFolder['sToken'] : '';
            $this->db->insert_folder($oFolder['iFolderCode'], $oFolder['sFolderPath'], $oFolder['sFolderName'], $sAccess, $sToken);
          }
        }
      }
    }

    if ($full || $force || $oApi->playerModifiedSince($this->options['vod_api_lastUpdate'])) {
      $this->db->clean_players();
      $aPlayers = $oApi->getPlayers();
      if (is_array($aPlayers) && !empty($aPlayers)) {
        foreach ($aPlayers as $oPlayer) {
          if (isset($oPlayer['iPlayer']) && isset($oPlayer['sName'])) {
            $iWidth = isset($oPlayer['iWidth']) ? $oPlayer['iWidth'] : 720;
            $iHeight = isset($oPlayer['iHeight']) ? $oPlayer['iHeight'] : 360;
            $bStart = isset($oPlayer['bStart']) ? $oPlayer['bStart'] : false;
            $bLoop = isset($oPlayer['bLoop']) ? $oPlayer['bLoop'] : '';
            $dEdit = isset($oPlayer['dEdit']) ? $oPlayer['dEdit'] : date('Y-m-d H:i:s');
            $bSwitchQuality = isset($oPlayer['bSwitchQuality']) ? $oPlayer['bSwitchQuality'] : 1;
            $this->db->insert_player($oPlayer['iPlayer'], $oPlayer['sName'], $iWidth, $iHeight, $bStart, $bLoop, $dEdit, $bSwitchQuality, 2);
          }
        }
      }
    }

    return true;
  }

  /**
   * Full synchronization of all video data
   */
  function fullSynchro()
  {
    return $this->fastSynchro(true, true);
  }

  /**
   * Get or set a share URL for a video
   */
  function getOrSetShare($sVideo, $iPlayer)
  {
    if (empty($sVideo) || empty($iPlayer)) return '';

    $oShare = $this->db->getShare($sVideo, $iPlayer);
    if (!empty($oShare) && isset($oShare->sURL)) return $oShare->sURL;

    $oApi = $this->getAPI();
    if (!$oApi) return '';

    $shareResponse = $oApi->publishShare($sVideo, $iPlayer, $this->options['width'], $this->options['height']);
    if (empty($shareResponse)) return '';

    // Handle both object and array responses
    $sURL = is_array($shareResponse) ? ($shareResponse['sURL'] ?? '') : (is_object($shareResponse) ? ($shareResponse->sURL ?? '') : $shareResponse);
    if (empty($sURL)) return '';

    $this->db->setShare($sVideo, $iPlayer, $sURL);
    return $sURL;
  }

  /**
   * Get right value based on role
   */
  function getRightValue($role)
  {
    switch ($role) {
      case 'contributor':
        return VOD_RIGHT_CONTRIBUTOR;
      case 'author':
        return VOD_RIGHT_AUTHOR;
      case 'editor':
        return VOD_RIGHT_EDITOR;
      case 'administrator':
        return VOD_RIGHT_ADMIN;
      default:
        return VOD_RIGHT_CONTRIBUTOR;
    }
  }

  function add_menu_items()
  {
    if ($this->auto_sync) {
      $this->checkAutoUpdate();
    }

    if ($this->isCurrentUserCan('plugin')) {
      if (function_exists('add_menu_page')) {
        add_menu_page(__('Videos', 'vod_infomaniak'), __('Videos', 'vod_infomaniak'), 'edit_posts', __FILE__, array(&$this, 'vod_management_menu'));
      }

      if (function_exists('add_submenu_page')) {
        if ($this->isCurrentUserCan('gestion')) {
          add_submenu_page(__FILE__, __('Gestionnaire', 'vod_infomaniak'), __('Gestionnaire', 'vod_infomaniak'), 'edit_posts', __FILE__, array(&$this, 'vod_management_menu'));
        }
        if ($this->isCurrentUserCan('importation')) {
          add_submenu_page(__FILE__, __('Importation', 'vod_infomaniak'), __('Importation', 'vod_infomaniak'), 'edit_posts', 'import', array(&$this, 'vod_upload_menu'));
        }
        if ($this->isCurrentUserCan('player')) {
          add_submenu_page(__FILE__, __('Player video', 'vod_infomaniak'), __('Player video', 'vod_infomaniak'), 'edit_posts', 'Player', array(&$this, 'vod_implementation_menu'));
        }
        if ($this->isCurrentUserCan('playlist')) {
          add_submenu_page(__FILE__, __('Playlist', 'vod_infomaniak'), __('Playlist', 'vod_infomaniak'), 'edit_posts', 'Playlist', array(&$this, 'vod_playlist_menu'));
        }
        if ($this->isCurrentUserCan('configuration')) {
          add_submenu_page(__FILE__, __('Configuration', 'vod_infomaniak'), __('Configuration', 'vod_infomaniak'), 'manage_options', 'configuration', array(&$this, 'vod_admin_menu'));
        }
      }
    }
  }

  function shareLink()
  {
    die($this->getOrSetShare($_REQUEST['sVideo'], $_REQUEST['iPlayer']));
  }



  function getSynchroVideo()
  {
    $oApi = $this->getAPI();

    if ($_REQUEST['iTotalCounter'] == 0) {
      $this->db->clean_videos();
      $iTotalVideo = $oApi->countVideo();
    } else {
      $iTotalVideo = intval($_REQUEST['iTotalCounter']);
    }

    if ($iTotalVideo < 1000) {
      $iNumberByLot = 100;
    } else {
      $iNumberByLot = 1000;
    }

    if (!get_option('vod_api_version', false)) {
      update_option('vod_api_version', 2);
    }

    $aVideos = $oApi->getLastVideo($iNumberByLot, intval($_REQUEST['iPage']) * $iNumberByLot);

    if (!empty($aVideos)) {
      foreach ($aVideos as $oVideo) {
        if (get_option('vod_api_version', false) == 2) {
          try {
            $sShareURL = $this->getOrSetShare($oVideo['sFileServerCode'], 1);
          } catch (\Exception $oException) {
            $sShareURL = '';
          }

          // Get video path and extension
          if (is_null($oVideo['aEncodes'][0]['sPath'])) {
            $sPath = 'undefined';
          } else {
            $sPath = $oVideo['aEncodes'][0]['sPath'];
          }
          if (is_null($oVideo['aEncodes'][0]['eConteneur'])) {
            $sExtension = '';
          } else {
            $sExtension = $oVideo['aEncodes'][0]['eConteneur'];
          }

          // Construct dash URL
          $media_streams = array();
          if (isset($oVideo['aEncodes'])) {
            foreach ($oVideo['aEncodes'] as $encode) {
              if (isset($encode['sFileId'])) {
                $media_streams[] = $encode['sFileId'];
              }
            }
          }
          $dash_url = '';
          if (!empty($media_streams)) {
            $dash_url = "https://play.vod2.infomaniak.com/dash/{$oVideo['folderUuid']}/{$oVideo['sFileServerCode']}/," . implode(',', $media_streams) . ",.urlset/manifest.mpd";
          }

          // Construct MPD URL
          $media_streams = array();
          if (isset($oVideo['aEncodes'])) {
            foreach ($oVideo['aEncodes'] as $encode) {
              if (isset($encode['sFileId'])) {
                $media_streams[] = $encode['sFileId'];
              }
            }
          }
          $dash_url = '';
          if (!empty($media_streams)) {
            $dash_url = "https://play.vod2.infomaniak.com/dash/{$oVideo['folderUuid']}/{$oVideo['sFileServerCode']}/," . implode(',', $media_streams) . ",.urlset/manifest.mpd";
          }

          // Get share URL for the video
          $sShareURL = $this->getOrSetShare($oVideo['sFileServerCode'], 1);          // Try to get poster URL from REST API, fallback to SOAP image URL
          $fallbackImageUrl = $oVideo['aEncodes'][0]['sImageURL'];
          $finalImageUrl = $this->getPosterOrThumbnailUrl($this->options['vod_api_id'], $oVideo['sFileServerCode'], $fallbackImageUrl);

          $this->db->insert_video($oVideo['iFileCode'], $oVideo['iFolder'], $oVideo['sFileName'], $oVideo['sFileServerCode'], $sPath, $sExtension, $oVideo['fFileDuration'], $oVideo['dFileUpload'], $oVideo['folderUuid'], $oVideo['aEncodes'][0]['sVideoURL'], $finalImageUrl, $sShareURL, $dash_url);
        } else {
          $this->db->insert_video($oVideo['iFileCode'], $oVideo['iFolder'], $oVideo['sFileName'], $oVideo['sFileServerCode'], $oVideo['aEncodes'][0]['sPath'], $oVideo['aEncodes'][0]['eConteneur'], $oVideo['fFileDuration'], $oVideo['dFileUpload']);
        }
      }
    }

    header('Content-Type: application/json');
    die(json_encode(array('iTotalCounter' => $iTotalVideo, 'iPage' => intval($_REQUEST['iPage']), 'iLot' => $iNumberByLot)));
  }

  function getMediaState($sUid)
  {
    $aUpload->sState = 'START';
    $sServerCode = $_REQUEST['file'];
    $aUpload->iVideo = $_REQUEST['file'];

    $oApi = $this->getAPI();
    $oProgressAPI = $oApi->getMediaState($_REQUEST['file']);

    $enco_progress = array();
    $oProgress = json_decode($oProgressAPI, true);

    foreach ($oProgress["encodingTasks"] as $enco) {
      array_push($enco_progress, $enco["progress"]);
    }

    $iProgress = max($enco_progress);  // Get highest progress from all encodes

    if ($iProgress == 100) {
      // Get complete video information once encoding is done
      $oVideo = $oApi->getVideoInformation($_REQUEST['iFolder'], $_REQUEST['file']);
      $sShareURL = $this->getOrSetShare($oVideo['sFileServerCode'], 1);

      // Construct MPD URL
      $media_streams = array();
      if (isset($oVideo['aEncodes'])) {
        foreach ($oVideo['aEncodes'] as $encode) {
          if (isset($encode['sFileId'])) {
            $media_streams[] = $encode['sFileId'];
          }
        }
      }
      $dash_url = '';
      if (!empty($media_streams)) {
        $dash_url = "https://play.vod2.infomaniak.com/dash/{$oVideo['folderUuid']}/{$oVideo['sFileServerCode']}/," . implode(',', $media_streams) . ",.urlset/manifest.mpd";
      }

      // Insert or update the video with dash URL
      if (get_option('vod_api_version', false) == 2) {
        $this->db->insert_video($oVideo['iFileCode'], $oVideo['iFolder'], $oVideo['sFileName'], $oVideo['sFileServerCode'], $oVideo['folderUuid'], $oVideo['aEncodes'][0]['sPath'], $oVideo['aEncodes'][0]['eConteneur'], $oVideo['fFileDuration'], $oVideo['dFileUpload'], $oVideo['aEncodes'][0]['sVideoURL'], $oVideo['aEncodes'][0]['sImageURL'], $sShareURL, $dash_url);
      } else {
        $this->db->insert_video($oVideo['iFileCode'], $oVideo['iFolder'], $oVideo['sFileName'], $oVideo['sFileServerCode'], $oVideo['aEncodes'][0]['sPath'], $oVideo['aEncodes'][0]['eConteneur'], $oVideo['fFileDuration'], $oVideo['dFileUpload']);
      }

      $aUpload->sState = 'OK';
      $sServerCode = $oVideo['sFileServerCode'];
      $aUpload->iVideo = $oVideo['sFileName'];
      $iProgress = 100;

      $this->db->update_upload_status($oVideo['sFileServerCode'], "OK");
    }

    header('Content-Type: application/json');
    die(json_encode(array('state' => $aUpload->sState, 'video' => $aUpload->iVideo, 'uploadProgress' => $oProgress["uploadProgress"], 'progress' => $iProgress, 'sServerCode' => $sServerCode, 'cpt' => $_REQUEST['iCounter'])));
  }

  function importVideoFromUrl()
  {
    $oApi = $this->getAPI();
    $sUrl = esc_url_raw($_REQUEST['url']);

    $aOptions = array();
    $sInfo = pathinfo($sUrl);
    $sFilename =  basename($sUrl, '.' . $info['extension']);

    $oFolder = $this->db->getFolder($_REQUEST['iFolder']);
    $sFileID = $oApi->importFromUrl("/" . $oFolder->sPath, $sUrl, $aOption);
    if (!$sFileID) {
      die('error');
    }
    die($sFileID);
  }

  function importPostVideoDispo()
  {
    if (!current_user_can('manage_options') || !wp_verify_nonce($_GET['nonce'], 'my_action_nonce')) {
      die('error');
    }

    $aUpload = $this->db->get_upload_video($_REQUEST['sToken']);
    $oApi = $this->getAPI();

    $oProgressAPI = $oApi->getMediaState($_REQUEST['file']);

    $enco_progress = array();
    array_push($enco_progress, 0);  //on pousse au moins un élément
    $oProgress = json_decode($oProgressAPI, true);

    foreach ($oProgress["encodingTasks"] as $enco) {
      array_push($enco_progress, $enco["progress"]);
    }

    $iProgress = max($enco_progress);  //sur les n encodages, on prend celui qui est le plus avancé

    if ($iProgress == 100) {
      $oVideo = $oApi->getVideoInformation($_REQUEST['iFolder'], $_REQUEST['file']);
      $sShareURL = $this->getOrSetShare($oVideo['sFileServerCode'], 1);

      // Construct MPD URL
      $media_streams = array();
      if (isset($oVideo['aEncodes'])) {
        foreach ($oVideo['aEncodes'] as $encode) {
          if (isset($encode['sFileId'])) {
            $media_streams[] = $encode['sFileId'];
          }
        }
      }
      $dash_url = '';
      if (!empty($media_streams)) {
        $dash_url = "https://play.vod2.infomaniak.com/dash/{$oVideo['folderUuid']}/{$oVideo['sFileServerCode']}/," . implode(',', $media_streams) . ",.urlset/manifest.mpd";
      }

      // Try to get poster URL from REST API, fallback to SOAP image URL
      $fallbackImageUrl = ''; // Initialize to empty string
      if (isset($oVideo['aEncodes'][0]['sImageURL']) && !empty($oVideo['aEncodes'][0]['sImageURL'])) {
        $fallbackImageUrl = $oVideo['aEncodes'][0]['sImageURL'];

        // Also try to access the fallbackImageUrl when the media is ready.
        // This is a lightweight attempt by fetching headers.
        // The primary logic for using the fallback is still within getPosterOrThumbnailUrl.
        @get_headers($fallbackImageUrl);
      }
      // If $fallbackImageUrl remains empty (e.g. not set in $oVideo or empty),
      // it will be passed as such to getPosterOrThumbnailUrl, which should handle it.

      $finalImageUrl = $this->getPosterOrThumbnailUrl($this->options['vod_api_id'], $oVideo['sFileServerCode'], $fallbackImageUrl);

      $this->db->insert_video($oVideo['iFileCode'], $oVideo['iFolder'], $oVideo['sFileName'], $oVideo['sFileServerCode'], $oVideo['aEncodes'][0]['sPath'], $oVideo['aEncodes'][0]['eConteneur'], $oVideo['fFileDuration'], $oVideo['dFileUpload'], $oVideo['folderUuid'], $oVideo['aEncodes'][0]['sVideoURL'], $finalImageUrl, $sShareURL, $dash_url);

      $aUpload->sState = 'OK';
      $sServerCode = $oVideo['sFileServerCode'];
      $aUpload->iVideo = $oVideo['sFileName'];
      $iProgress = 100;

      $this->db->update_upload_status($oVideo['sFileServerCode'], "OK");
    }

    header('Content-Type: application/json');
    die(json_encode(array('state' => $aUpload->sState, 'video' => $aUpload->iVideo, 'progress' => $iProgress, 'sServerCode' => $sServerCode, 'cpt' => $_REQUEST['iCounter'])));
  }

  function importPostVideoEnding()
  {
    if (current_user_can('manage_options') && wp_verify_nonce($_GET['nonce'], 'my_action_nonce')) {
      echo $this->db->insert_upload($_REQUEST['sToken'], $_REQUEST['file']);
    } else {
      die('error');
    }
  }

  function importPostVideo()
  {
    $sServerCode = '';
    if (!empty($_REQUEST['upload']) && $_REQUEST['upload'] == "finish") {
      echo $this->db->insert_upload($_REQUEST['sToken'], $_REQUEST['file']);
    } else {
      if (!empty($_REQUEST['iFolder'])) {
        $oFolder = $this->db->getFolder($_REQUEST['iFolder']);
        if (empty($oFolder) || empty($oFolder->sName)) {
          die(__("Il n'est pas possible d'uploader dans ce dossier.", 'vod_infomaniak'));
        }
        $oApi = $this->getAPI();
        $sToken = $oApi->initUpload($oFolder->sPath);
        $oApi->addInfo($sToken, "wp_upload_post_" . $sToken);
        delete_transient('vod_last_import');
        echo $sToken;
      }
    }
    die();
  }

  function searchPlaylist()
  {
    $aResult = $this->db->search_playlist($_REQUEST['q'], 100);
    $tReturn = "";
    if (!empty($aResult)) {
      foreach ($aResult as $oPlaylist) {
        $sDuration = "";
        if (!empty($oPlaylist->iTotalDuration)) {
          $iDuration = intval($oPlaylist->iTotalDuration / 100);
          $iHours = intval($iDuration / 3600);
          $iMinutes = intval($iDuration / 60) % 60;
          $iSecondes = intval($iDuration) % 60;
          $sDuration .= $iHours > 0 ? $iHours . "h. " : '';
          $sDuration .= $iMinutes > 0 ? $iMinutes . "m. " : '';
          $sDuration .= $iSecondes > 0 ? $iSecondes . "s. " : '';
        }

        $tReturn .= '<tr class="vod_element_select" onclick="Vod_selectVideo(this, \'' . $oPlaylist->iPlaylistCode . '\',\'\',\'\');">';
        $tReturn .= '<td>' . ucfirst(stripslashes($oPlaylist->sPlaylistName)) . "</td>";
        $tReturn .= '<td>' . stripslashes($oPlaylist->sPlaylistDescription) . "</td>";
        $tReturn .= '<td align="center">' . $oPlaylist->iTotal . '</td>';
        $tReturn .= '<td align="right">' . $sDuration . '</td>';
        $tReturn .= '</tr>';
      }
    }
    echo $tReturn;
    die();
  }

  function searchVideo()
  {
    $aResult = $this->db->search_videos($_REQUEST['q'], 12, $this->options['vod_filter_folder']);
    $tReturn = "";
    if (!empty($aResult)) {
      foreach ($aResult as $oVideo) {
        if (get_option('vod_api_version', false) == 1) {
          $tReturn .= '<tr class="vod_element_select" onclick="Vod_selectVideo(this, \'' . $oVideo->sPath . $oVideo->sServerCode . '.' . strtolower($oVideo->sExtension) . '\',\'' . $oVideo->sToken . '\',\'' . $oVideo->iFolder . '\');">';
          $tReturn .= '<td><img width="100" src="http://vod.infomaniak.com/redirect/' . $this->options['vod_api_id'] . $oVideo->sPath . $oVideo->sServerCode . '.mini.jpg"/></td>';
        } else {
          $tReturn .= ("<tr class=\"vod_element_select\" onclick=\"Vod_selectVideo(this, '" . $oVideo->sServerCode . "','" . $oVideo->sToken . "','" . $oVideo->iFolder . "');\"><td><img width='100' src='" . $oVideo->sImageUrlV2 . "'/></td>");
        }

        $tReturn .= '<td>';
        $tReturn .= ucfirst(stripslashes($oVideo->sName));
        $tReturn .= '<br/><br/>';
        $tReturn .= '<img src="' . plugins_url('vod-infomaniak/img/ico-folder-open-16x16.png') . '" style="vertical-align:bottom"/>';
        $tReturn .= $oVideo->sPath;
        $tReturn .= '</td>';
        $tReturn .= '<td>' . $oVideo->dUpload . '</td>';
      }
    } else {
      $tReturn .= "<tr><td colspan='3'>Aucune Resultat</td></tr>";
    }
    echo $tReturn;
    die();
  }

  function check($the_content, $side = 0)
  {
    $tag = $this->options['tag'];
    if ($tag != '' && strpos($the_content, "[" . $tag) !== false) {
      preg_match_all("/\[$tag([^`]*?)\]([^`]*?)\[\/$tag\]/", $the_content, $matches, PREG_SET_ORDER);
      foreach ($matches as $match) {
        $the_content = preg_replace("/\[$tag([^`]*?)\]([^`]*?)\[\/$tag\]/", $this->tag($match[2], $match[1], '', '', $side), $the_content, 1);
      }
    }
    if (strpos($the_content, "[upload-vod") !== false) {
      $tag = "upload-vod";
      preg_match_all("/\[$tag([^`]*?)\]([^`]*?)\[\/$tag\]/", $the_content, $matches, PREG_SET_ORDER);
      foreach ($matches as $match) {
        $the_content = preg_replace("/\[$tag([^`]*?)\]([^`]*?)\[\/$tag\]/", $this->tag_upload($match[2], $match[1], '', '', $side), $the_content, 1);
      }
    }
    return $the_content;
  }

  function tag_upload($file, $params, $high = 'v', $time = '', $side = 0)
  {
    //On check que le tag upload ne doit pas etre remplacer par un tag vod
    if (!empty($file) && strpos($file, ':') !== false) {
      $decoupage = explode(":", $file);
      $sToken = $decoupage[0];
      $aUpload = $this->db->get_upload_video($sToken);
      if (intval($aUpload->iVideo) > 0) {
        $video = $this->db->getVideo($aUpload->iVideo);
        if (!empty($video)) {
          global $post;
          $sVideoPath = $video->sPath . $video->sServerCode . "." . strtolower($video->sExtension);
          $update_post = array();
          $update_post['ID'] = $post->ID;
          $result = str_replace("[upload-vod]" . $file . "[/upload-vod]", "[vod]" . $sVideoPath . "[/vod]", $post->post_content);
          if ($result != $post->post_content) {
            $post->post_content = $result;
            $update_post['post_content'] = $post->post_content;
            // Sans le @, warning suivant la config apache. Probleme soumis sur le forum officiel
            @wp_update_post($update_post);
          }
          return $this->tag($sVideoPath, $params, $high, $time, $side);
        }
      }
    }


    $width = empty($aTagParam['width']) ? $this->options['width'] : intval($aTagParam['width']);
    $height = empty($aTagParam['height']) ? $this->options['height'] : intval($aTagParam['height']);
    return "<div style='background: url(\"" . plugins_url('vod-infomaniak/img/topbg10.png') . "\") repeat;border-radius: 8px; text-align:center; color: #DDDDDD; font-weight: bold; background-color: #222222; width: " . $width . "px; height: " . $height . "px;'>
			<div style='font-size: 150%;padding-top: 100px;line-height:" . (($height - 200) / 2) . "px;vertical-align: middle;'>
				<span style='display:block;'>" . __("Video en cours de conversion", 'vod_infomaniak') . " ...</span>
				<img src='" . plugins_url('vod-infomaniak/img/ico-vod-64.png') . "' style='vertical-align:middle'/>
			</div>
		</div>";
  }

  function removeSmartQuotes($sContent)
  {
    $sContent = htmlentities($sContent);
    $sContent = str_replace(
      array(
        "'",
        '"',
        '’',
        '&amp;laquo;',
        '&amp;raquo;',
        '&amp;lsquo;',
        '&amp;rsquo;',
        '&amp;prime;',
        '&amp;Prime;',
        '&amp;nbsp;',
      ),
      '"',
      $sContent
    );
    $sContent = str_replace(array("&amp;#8217;", "&amp;#8242;"), "", $sContent);
    $sContent = preg_replace('/"+/mi', '"', $sContent); // remplace les xquotes

    return $sContent;
  }

  function tag($file, $params, $high = 'v', $time = '', $side = 0)
  {
    //Recuperation des parametres optionnels des tags
    $aTagParam = array();
    if (!empty($params)) {

      $params = $this->removeSmartQuotes($params); // remplace les xquotes

      $params = html_entity_decode($params);
      $params = strtolower(str_replace('"', "", $params));

      $aList = explode(" ", $params);
      foreach ($aList as $param) {
        if (strpos($param, "=") !== false) {
          $aCut = explode("=", $param);
          if (in_array($aCut[0], array("width", "height", "stretch", "autoplay", "loop", "player", "videoimage", "tokenfolder", "version"))) {
            $aTagParam[$aCut[0]] = $aCut[1];
          }
        }
      }
    }

    //Recuperation des differents parametres
    $iVod = $this->options['vod_api_icodeservice'];
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") {
      $sProtocol = "https";
    } else {
      $sProtocol = "http";
    }

    if (!empty($aTagParam['version']) && $aTagParam['version'] == "2") {
      // v2
      $this->options['player'] = 1;  //v2 force player 1 si non defini
      $player = empty($aTagParam['player']) ? $this->options['player'] : intval($aTagParam['player']);
      $autoplay = empty($aTagParam['autoplay']) ? $this->options['autoplay'] : intval($aTagParam['autoplay']);
      $loop = empty($aTagParam['loop']) ? $this->options['loop'] : intval($aTagParam['loop']);
      $width = empty($aTagParam['width']) ? $this->options['width'] : trim($aTagParam['width']);
      $height = empty($aTagParam['height']) ? $this->options['height'] : trim($aTagParam['height']);
      $video_url = $this->getOrSetShare($file, $player);
      //$folderProtected = $this->getFolderByVideo($file);

    } else {
      // v1
      $sUrl = $sProtocol . "://vod.infomaniak.com/iframe.php";

      $sAccountBase = $this->options['vod_api_id'];
      $sKey = "";
      if (!empty($aTagParam['tokenfolder']) && !is_numeric($file)) {
        $oFolder = $this->db->getFolder($aTagParam['tokenfolder']);
        if (!empty($oFolder)) {
          $fileInfo = pathinfo($file);
          $sFileName = basename($file, '.' . $fileInfo['extension']);
          $sKey = "&sKey=" . $this->getTemporaryKey($oFolder->sToken, $sFileName);
        }
      }

      $videoimage = empty($aTagParam['videoimage']) ? 1 : intval($aTagParam['videoimage']);
      $player = empty($aTagParam['player']) ? $this->options['player'] : intval($aTagParam['player']);
      $autoplay = empty($aTagParam['autoplay']) ? $this->options['autoplay'] : intval($aTagParam['autoplay']);
      $loop = empty($aTagParam['loop']) ? $this->options['loop'] : intval($aTagParam['loop']);
      $width = empty($aTagParam['width']) ? $this->options['width'] : trim($aTagParam['width']);
      $height = empty($aTagParam['height']) ? $this->options['height'] : trim($aTagParam['height']);
      $stretch = empty($aTagParam['stretch']) ? $this->options['stretch'] : intval($aTagParam['stretch']);

      if (is_numeric($file)) {
        $video_url = $sUrl . "?url=&playlist=" . $file;
      } else {
        // Build de l'url finale
        $file = ltrim($file);
        if (!preg_match('/^http(s)?:\/\//', $file)) {
          $sFile = $sAccountBase . "/" . $file;
        } else {
          $sFile = $file;
        }
        $video_url = $sUrl . "?url=" . $sFile;
        if ($videoimage) {
          $video_url .= "&preloadImage=" . str_replace(array(".flv", ".mp4"), ".jpg", $sFile);
        }
      }
      if (!empty($player)) {
        $video_url .= "&player=$player";
      } else {
        $video_url .= "&player=576";
      }
      if ($iVod) {
        $video_url .= "&vod=$iVod";
      }
      if (isset($aTagParam['autoplay'])) {
        $video_url .= "&autostart=$autoplay";
      }
      if (isset($aTagParam['loop'])) {
        $video_url .= "&loop=$loop";
      }
      if (isset($aTagParam['stretch'])) {
        $video_url .= "&strechheight=$stretch";
      }
      if (!empty($sKey)) {
        $video_url .= $sKey;
      }
    }
    if (empty($width)) {
      $width = 480;
    }
    $width = str_replace("'", "", $width);
    $height = str_replace("'", "", $height);
    if (empty($height) || $height == 288 || strpos($width, '%') > 1 || $width == 0 || $height == 0) {
      $height = 288;
      $iPercentRatio = 56.25;
    } else {
      $iPercentRatio = (100 / intval($width) * intval($height));
    }
    if (strpos($width, '%') == false && strpos($width, 'px') == false) {
      $width = $width . "px";
    }
    if (!empty($aTagParam['responsive']) && $aTagParam['responsive'] == "1") {
      $html_tag = '<div style="width: 100%; padding-bottom: 56.25%; height: 0; overflow: hidden;position: relative">
			   <iframe src="' . $video_url . '" width="100%" height="100%" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0" allowfullscreen crossorigin="anonymous" frameborder="0" allow="autoplay; fullscreen; picture-in-picture"></iframe>
				</div>';
    } else {
      $html_tag = '<div style="width:100%;max-width:' . $width . ';">
							<div class="videoWrapper" style="position: relative;padding-bottom: ' . $iPercentRatio . '%;height: 0;">
								<iframe style="position: absolute;top: 0;left: 0;width: 100%;height: 100%;" src="' . $video_url . '" frameborder="0" allowfullscreen></iframe>
							</div>
						</div>';
    }
    return $html_tag;
  }

  /**
   * Fetch poster URL from the new REST API v1
   * @param string $channelId The channel ID
   * @param string $mediaId The media ID
   * @return string|null The poster URL or null if not found
   */
  /**
   * Fetch poster URL from the Infomaniak VOD REST API v1
   *
   * @param string $channelId The VOD channel ID
   * @param string $mediaId The unique media identifier
   * @return string|null The poster URL or null if not found
   */
  /**
   * Get a video's poster URL from the REST API with fallback to thumbnail URL
   *
   * @param string $channelId The VOD channel ID
   * @param string $mediaId The unique media identifier
   * @param string $fallbackThumbnailUrl Fallback thumbnail URL if poster not available
   * @return string The poster URL if available, otherwise the fallback thumbnail URL
   */
  function fetchPosterUrlFromRestAPI($channelId, $mediaId)
  {
    $apiToken = getenv('INFOMANIAK_TOKEN_API');
    if (empty($apiToken)) {
      return null;
    }

    try {
      // Use environment channel ID if available
      $environmentChannelId = getenv('INFOMANIAK_CHANNEL_ID');
      if (!empty($environmentChannelId)) {
        $channelId = $environmentChannelId;
      }

      // Validate media ID
      $mediaId = trim($mediaId);
      if (empty($mediaId)) {
        return null;
      }

      $endpoint = "https://api.infomaniak.com/1/vod/channel/{$channelId}/media/{$mediaId}?with=poster";

      $response = wp_remote_get($endpoint, array(
        'headers' => array(
          'Authorization' => 'Bearer ' . $apiToken,
          'Content-Type' => 'application/json',
          'Accept' => 'application/json',
          'User-Agent' => 'VOD-WordPress-Plugin/' . $this->version
        ),
        'timeout' => 30
      ));

      if (is_wp_error($response)) {
        return null;
      }

      if (wp_remote_retrieve_response_code($response) !== 200) {
        return null;
      }

      $data = json_decode(wp_remote_retrieve_body($response), true);
      if (json_last_error() !== JSON_ERROR_NONE) {
        return null;
      }

      return isset($data['result']) && $data['result'] === 'success' && isset($data['data']['poster']['url'])
        ? $data['data']['poster']['url']
        : null;
    } catch (Exception $e) {
      return null;
    }
  }

  /**
   * Get poster URL with fallback to thumbnail URL
   * @param string $channelId The channel ID
   * @param string $mediaId The media ID
   * @param string $fallbackThumbnailUrl The fallback thumbnail URL from SOAP API
   * @return string The poster URL or fallback thumbnail URL
   */
  /**
   * Get poster URL with fallback to thumbnail URL
   *
   * @param string $channelId The channel ID
   * @param string $mediaId The media ID
   * @param string $fallbackThumbnailUrl The fallback thumbnail URL from SOAP API
   * @return string The poster URL or fallback thumbnail URL
   */
  /**
   * Get a video's poster URL from the REST API with fallback to thumbnail URL
   *
   * @param string $channelId The VOD channel ID
   * @param string $mediaId The unique media identifier
   * @param string $fallbackThumbnailUrl Fallback thumbnail URL if poster not available
   * @return string The poster URL if available, otherwise the fallback thumbnail URL
   */
  function getPosterOrThumbnailUrl($channelId, $mediaId, $fallbackThumbnailUrl)
  {
    $posterUrl = $this->fetchPosterUrlFromRestAPI($channelId, $mediaId);
    return !empty($posterUrl) ? $posterUrl : $fallbackThumbnailUrl;
  }

  function vod_admin_menu()
  {
    $site_url = get_option("siteurl");
    $aFolders = array();
    if (isset($_POST['submitted'])) {

      if (!isset($_POST['plugin_nonce']) || !wp_verify_nonce($_POST['plugin_nonce'], 'update_settings_action')) {
        wp_die(__('Vérification de sécurité échouée', 'text-domain'));
      }

      $bResult = false;

      if (empty($this->options['vod_api_callbackKey'])) {
        $this->options['vod_api_callbackKey'] = sha1(time() * rand());
      }
      if (empty($this->options['vod_api_c'])) {
        $this->options['vod_api_c'] = substr(sha1(time() * rand()), 0, 20);
      }

      $this->options['vod_api_login'] = stripslashes(htmlspecialchars($_POST['vod_api_login']));
      if (isset($_POST['vod_api_password']) && $_POST['vod_api_password'] != "XXXXXX") {
        $this->options['vod_api_password'] = $this->encrypt(stripslashes(htmlspecialchars($_POST['vod_api_password'])));
      }
      $this->options['vod_api_id'] = stripslashes(htmlspecialchars($_POST['vod_api_id']));

      try {
        $oApi = $this->getAPI();
        $bResult = $oApi->ping();
        if ($bResult) {
          $this->options['vod_api_connected'] = 'on';
          $this->options['vod_api_icodeservice'] = $oApi->getServiceItemID();
          $this->options['vod_api_group'] = $oApi->getGroupID();
          $this->options['vod_api_lastUpdate'] = 0;
          $this->options['vod_filter_folder'] = "";


          if (empty($_POST['logout']) === true) {
            //Installation de la base de donnée seulement si elle n'est pas à jour
            $this->update_db();
          }

          $this->fastSynchro(true, true);  //on met a jour si nouvelle co
          $this->fullSynchro();

          if (empty($this->options['vod_api_valid_callback']) || $this->options['vod_api_valid_callback'] == 'off') {
            //Clean de callback V1 s'il y en a encore
            if (get_option('vod_api_version', false) == 2) {
              //$sUrl = $oApi->getCallback();		//genere une erreur, depuis toujours ? car getcallback retourne un tableau et non pas une url
              //if (!empty($sUrl) && strpos($sUrl, str_replace('http://', '', $site_url)) !== false) {
              //	$oApi->setCallback("");
              //}

              //On va essayer d'ajouter un callback V2
              $sUrl2 = $oApi->getCallbackV2();
              $bCallbackV2 = true;
              if ($sUrl2 != false) {
                foreach ($sUrl2 as $oCallback) {
                  if (strpos($oCallback['sUrl'], $site_url) !== false) {
                    $bCallbackV2 = false;
                  }
                }
              }

              if ($bCallbackV2) {
                $oApi->setCallbackV2($site_url . "/?vod_page=callback&key=" . $this->options['vod_api_callbackKey']);
              }

              $this->options['vod_api_valid_callback'] == 'on';
            }
          }

          /*if ($this->db->count_video() == 0) {

                        	$oApi = $this->getAPI();

							//Update des videos
							$iNumberVideoApi = 200;
							$this->db->clean_videos();
							$iVideo = $oApi->countVideo();
							$iPageTotal = floor(($iVideo - 1) / $iNumberVideoApi);

                        	for ($iPage = 0; $iPage <= $iPageTotal; $iPage++) {
								$aVideos = $oApi->getLastVideo($iNumberVideoApi, $iPage * $iNumberVideoApi);
								foreach ($aVideos as $oVideo) {
									if (get_option( 'vod_api_version', false ) == 2) {
										$this->db->insert_video($oVideo['iFileCode'], $oVideo['iFolder'], $oVideo['sFileName'], $oVideo['sFileServerCode'], $oVideo['folderUuid'], $oVideo['aEncodes'][0]['sPath'], $oVideo['aEncodes'][0]['eConteneur'], $oVideo['fFileDuration'], $oVideo['dFileUpload'], $oVideo['aEncodes'][0]['sVideoURL'], $oVideo['aEncodes'][0]['sImageURL'], $sShareURL);
									}else{
										var_dump($oVideo);
										$this->db->insert_video($oVideo['iFileCode'], $oVideo['iFolder'], $oVideo['sFileName'], $oVideo['sFileServerCode'], $oVideo['aEncodes'][0]['sPath'], $oVideo['aEncodes'][0]['eConteneur'], $oVideo['fFileDuration'], $oVideo['dFileUpload']);
									}
								}
							}
                        }*/
        }
      } catch (Exception $oException) {
        echo "<h4 style='color: red;'>" . __('Erreur : Impossible de se connecter', 'vod_infomaniak') . '</h4>';
      }

      if (empty($_POST['logout']) === false) {
        $this->options['vod_api_connected'] = 'off';
      }

      update_option($this->key, $this->options);
    }


    if (isset($_POST['updateSynchro']) && $_POST['updateSynchro'] == 1) {
      require_once("vod.template.php");
      EasyVod_Display::buildSyncFolder();
      flush();
      ob_flush();

      $this->options['vod_api_lastUpdate'] = 0;
      $this->fastSynchro();
    }

    if (isset($_POST['updateSynchroVideo']) && $_POST['updateSynchroVideo'] == 1) {
      $this->options['vod_api_lastUpdate'] = 0;
      $this->fullSynchro();
    }

    if (isset($_POST['updateFilterFolder']) && $_POST['updateFilterFolder'] == 1) {
      if ($_POST['sFolderPath'] == -1) {
        $this->options['vod_filter_folder'] = "";
      } else {
        $this->options['vod_filter_folder'] = sanitize_text_field($_POST['sFolderPath']);
      }
      update_option($this->key, $this->options);
    }

    if (isset($_POST['updateRightPlugins']) && $_POST['updateRightPlugins'] == 1) {
      $this->options['vod_right_integration'] = $this->getRightValue($_POST['integration_role']);
      $this->options['vod_right_upload'] = $this->getRightValue($_POST['upload_role']);
      $this->options['vod_right_player'] = $this->getRightValue($_POST['player_role']);
      $this->options['vod_right_playlist'] = $this->getRightValue($_POST['playlist_role']);
      update_option($this->key, $this->options);
    }

    if ($this->options['vod_api_connected'] == "on") {
      $this->options['vod_count_player'] = $this->db->count_player();
      $this->options['vod_count_folder'] = $this->db->count_folder();
      $this->options['vod_count_video'] = $this->db->count_video();
      $this->options['vod_count_playlist'] = $this->db->count_playlists();
      $aFolders = $this->db->get_folders();
    }
    $actionurl = esc_url_raw($_SERVER['REQUEST_URI']);
    require_once("vod.template.php");
    EasyVod_Display::adminMenu($actionurl, $this->options, $site_url, $aFolders);
  }

  function plugin_ready()
  {

    //si plugin a jour et qu'on migre en v2, on force la deco, a retirer une fois ensemble migré
    /*if (get_option( 'vod_api_version', false ) == 2 && $this->db->countShare() == 0 && $this->options['vod_api_connected'] == 'on'){
				$this->options['vod_api_connected'] = 'off';
			    update_option($this->key, $this->options);
			}*/


    if (empty($this->options['vod_api_connected']) || $this->options['vod_api_connected'] == 'off') {
      echo "<h2>" . __('Probleme de configuration', 'vod_infomaniak') . "</h2><p>" . __("Veuillez-vous rendre dans <a href='admin.php?page=configuration'>Videos -> Configuration</a> afin de configurer votre compte.", 'vod_infomaniak') . '</p>';
      return false;
    }
    return true;
  }

  function getCmsInfo()
  {
    $oApi = $this->getAPI();
    $arr = array('wp-version' => get_bloginfo('version'), 'vod-version' => $this->version, 'vod-db' => get_site_option('vod_db_version'), 'vod-api-soap' => get_option('vod_api_version', false));

    if ($oApi->getCmsInfo() != json_encode($arr)) {
      $a = $this->setCmsInfo(json_encode($arr));
    }
  }

  function setCmsInfo($info)
  {
    $oApi = $this->getAPI();
    $oApi->setCmsInfo($info);
  }

  function vod_management_menu()
  {
    if ($this->plugin_ready()) {

      if (isset($_REQUEST['sAction'])) {
        if ($_REQUEST['sAction'] == "rename") {
          $oVideo = $this->db->getVideo(intval($_POST['dialog-modal-id']));
          if ($oVideo != false) {
            $oApi = $this->getAPI();
            $oApi->renameVideo($oVideo->iFolder, $oVideo->sFileServerCode, $_POST['dialog-modal-name']);
            $this->db->rename_video(intval($_POST['dialog-modal-id']), $_POST['dialog-modal-name']);


            echo "<script>";
            echo "jQuery(document).ready(function() {";
            echo "	openVodPopup('" . $oVideo->iVideo . "', '" . $_POST['dialog-modal-name'] . "','" . $oVideo->sPath . $oVideo->sServerCode . "', '" . strtolower($oVideo->sExtension) . "', '" . strtolower($oVideo->sAccess) . "', '" . $oVideo->sToken . "', '" . $oVideo->iFolder . "', '" . $oVideo->sImageUrlV2 . "', '" . $oVideo->sVideoUrlV2 . "', '" . $oVideo->sShareUrlV2 . "');";
            echo "});";
            echo "</script>";
          }
        } else {
          if ($_REQUEST['sAction'] == "delete") {
            $oVideo = $this->db->getVideo(intval($_POST['dialog-confirm-id']));

            if ($oVideo != false) {
              $oApi = $this->getAPI();
              // Attempt to delete the video from the VOD service
              $apiCallSuccessful = $oApi->deleteVideo($oVideo->iFolder, $oVideo->sFileServerCode);

              if ($apiCallSuccessful) {
                // If VOD service deletion was successful, delete from local database
                $this->db->delete_video(intval($_POST['dialog-confirm-id']));
                if (is_admin() && function_exists('add_settings_error')) {
                  add_settings_error(
                    'vod_infomaniak_notices',
                    'vod_api_delete_success',
                    sprintf(
                      __('La vidéo "%s" a été supprimée avec succès du service VOD et de votre site.', 'vod_infomaniak'),
                      esc_html($oVideo->sName)
                    ),
                    'updated'
                  );
                }
              } else {
                // If VOD service deletion failed, do not delete locally and notify the user
                if (is_admin() && function_exists('add_settings_error')) {
                  add_settings_error(
                    'vod_infomaniak_notices',
                    'vod_api_delete_failed',
                    sprintf(
                      __('La suppression de la vidéo "%s" sur le service VOD Infomaniak a échoué. La vidéo n\'a pas été retirée de votre site.', 'vod_infomaniak'),
                      esc_html($oVideo->sName)
                    ),
                    'error'
                  );
                }
                // Optionally, log the error for debugging purposes
                // error_log('VOD Infomaniak Plugin: Failed to delete video ' . esc_html($oVideo->sFileServerCode) . ' from folder ' . esc_html($oVideo->iFolder) . ' on the VOD service.');
              }
            }
          } else {
            if ($_REQUEST['sAction'] == "post") {
              $oVideo = $this->db->getVideo(intval($_POST['dialog-post-id']));
              if ($oVideo != false) {
                $sBalise = "vod";
                $oFolder = $this->db->getFolder($oVideo->iFolder);
                if ($oFolder != false) {
                  if (!empty($oFolder->sToken)) {
                    $sBalise = "vod tokenfolder='" . $oVideo->iFolder . "'";
                  }
                }

                // Create post object
                $my_post = array(
                  'post_title' => $oVideo->sName,
                  'post_content' => '[' . $sBalise . ']' . $oVideo->sPath . $oVideo->sServerCode . "." . strtolower($oVideo->sExtension) . '[/vod]'
                );

                // Insert the post into the database
                $id_draft = wp_insert_post($my_post);
                echo "<h3>" . __('Article correctement cree. Vous allez etre rediriger sur la page d\'edition', 'vod_infomaniak') . "</h3>";
                echo "<script type='text/javascript'>window.location = '" . admin_url('post.php?post=' . $id_draft . '&action=edit') . "';</script>";
                exit;
              }
            }
          }
        }
      }

      $iPage = !empty($_REQUEST['p']) ? intval($_REQUEST['p']) : 1;
      $iLimit = 20;
      $iVideoTotal = $this->db->count_video($this->options['vod_filter_folder']);
      $aVideos = $this->db->get_videos_byPage($iPage - 1, $iLimit, $this->options['vod_filter_folder']);
      for ($i = 0; $i < count($aVideos); $i++) {
        if (!empty($aVideos[$i]->sToken)) {
          $aVideos[$i]->sToken = $this->getTemporaryKey($aVideos[$i]->sToken, $aVideos[$i]->sServerCode);
        }
        if (empty($aVideos[$i]->sShareUrlV2)) {
          $aVideos[$i]->sShareUrlV2 = $this->getOrSetShare($aVideos[$i]->sServerCode, 1);
        }
      }
      require_once("vod.template.php");
      $sPagination = EasyVod_Display::buildPagination($iPage, $iLimit, $iVideoTotal);
      $actionurl = esc_url_raw($_SERVER['REQUEST_URI']);
      EasyVod_Display::managementMenu($actionurl, $sPagination, $this->options, $aVideos);
    }
  }

  function vod_upload_menu()
  {
    if ($this->plugin_ready()) {
      require_once("vod.template.php");
      if (isset($_REQUEST['sAction']) && $_REQUEST['sAction'] == "popupUpload" && !empty($_REQUEST['iFolderCode'])) {
        //Affichage du popup d'upload
        $oFolder = $this->db->getFolder($_REQUEST['iFolderCode']);
        if (empty($oFolder) || empty($oFolder->sName)) {
          die(__("Il n'est pas possible d'uploader dans ce dossier.", 'vod_infomaniak'));
        }
        $oApi = $this->getAPI();
        $sToken = $oApi->initUpload($oFolder->sPath);
        delete_transient('vod_last_import');
        EasyVod_Display::uploadPopup($sToken, $oFolder);
      } else {
        if (isset($_REQUEST['sAction']) && $_REQUEST['sAction'] == "popupImport" && !empty($_REQUEST['iFolderCode'])) {
          //Affichage du popup d'import
          $bResult = false;
          $oFolder = $this->db->getFolder($_REQUEST['iFolderCode']);
          if (empty($oFolder) || empty($oFolder->sName)) {
            die(__("Il n'est pas possible d'uploader dans ce dossier.", 'vod_infomaniak'));
          }
          if ($_REQUEST['submit'] == 1) {
            $oApi = $this->getAPI();
            $aOptions = array();
            if (!empty($_REQUEST['sLogin']) && !empty($_REQUEST['sPassword'])) {
              $aOption['login'] = sanitize_user($_REQUEST['sLogin']);
              $aOption['password'] = sanitize_text_field($_REQUEST['sPassword']);
            }
            $sUrl = esc_url_raw($_REQUEST['sProtocole'] . "://" . $_REQUEST['sUrl']);
            $bResult = $oApi->importFromUrl($oFolder->sPath, $sUrl, $aOption);
          }
          $actionurl = esc_url_raw($_SERVER['REQUEST_URI']);
          delete_transient('vod_last_import');
          EasyVod_Display::ImportPopup($actionurl, $oFolder, $bResult);
        } else {
          //Affichage de la page principal
          $aFolders = $this->db->get_folders($this->options['vod_filter_folder']);

          $actionurl = esc_url_raw($_SERVER['REQUEST_URI']);
          EasyVod_Display::uploadMenu($actionurl, $this->options, $aFolders, $this->getLastImport());
        }
      }
    }
  }

  function printLastImport()
  {
    echo $this->getLastImport();
    die();
  }

  function getLastImport()
  {
    require_once("vod.template.php");
    $aLastImport = get_transient('vod_last_import');
    if (false == $aLastImport) {
      $oApi = $this->getAPI();
      $aLastImport = $oApi->getLastImportation();
      $aLastImportCleaned = array();
      $aTmpLastLine = $aLastImport[0];
      $iProcessing = -1;
      $cpt = 0;

      foreach ($aLastImport as $aCurLastImport) {
        if ($aTmpLastLine["iVideo"] != $aCurLastImport["iVideo"]) {
          if ($iProcessing != -1) {
            $aLastImportCleaned[] = $aLastImport[$iProcessing];
          } else {
            $aLastImportCleaned[] = $aTmpLastLine;
          }
          $iProcessing = -1;
        }

        if (in_array($aCurLastImport["sProcessState"], array("PROCESSING", "WAITING"))) {
          $iProcessing = $cpt;
        }

        $aTmpLastLine = $aCurLastImport;
        $cpt++;
      }
      set_transient('vod_last_import', $aLastImportCleaned, 15);
      return EasyVod_Display::tabLastUpload($aLastImportCleaned);
    }
  }

  function vod_playlist_menu()
  {
    if ($this->plugin_ready()) {
      require_once("vod.template.php");

      if (isset($_REQUEST['create'])) {
        $oPlaylist = $this->db->getPlaylist(intval($_REQUEST['create']));

        if ($oPlaylist != false) {
          $sBalise = "vod";
          // Create post object
          $my_post = array(
            'post_title' => $oPlaylist->sPlaylistName,
            'post_content' => '[' . $sBalise . ']' . $oPlaylist->iPlaylistCode . '[/vod]'
          );

          // Insert the post into the database
          $id_draft = wp_insert_post($my_post);
          echo "<h3>" . __('Article correctement cree. Vous allez etre rediriger sur la page d\'edition', 'vod_infomaniak') . "</h3>";
          echo "<script type='text/javascript'>window.location = '" . admin_url('post.php?post=' . $id_draft . '&action=edit') . "';</script>";
          exit;
        }
      }


      $aPlaylist = $this->db->get_playlists();
      $actionurl = esc_url_raw($_SERVER['REQUEST_URI']);
      EasyVod_Display::playlistMenu($actionurl, $this->options, $aPlaylist);
    }
  }

  function vod_implementation_menu()
  {
    if ($this->plugin_ready()) {
      require_once("vod.template.php");
      $aPlayers = array();
      /*if (isset($_POST['submitted'])) {
					$oPlayer = $this->db->get_player(intval($_REQUEST['selectPlayer']));
					if (!empty($oPlayer)) {
						$this->options['player'] = $oPlayer->iPlayer;
						$this->options['width'] = $oPlayer->iWidth;
						$this->options['height'] = $oPlayer->iHeight;
						update_option($this->key, $this->options);
					}
				}
				$aPlayers = $this->db->get_players();
				*/
      $actionurl = esc_url_raw($_SERVER['REQUEST_URI']);
      EasyVod_Display::implementationMenu($actionurl, $this->options, $aPlayers);
    }
  }

  function getTemporaryKey($sToken, $sVideoName)
  {
    $iTime = time() + intval($this->options['vod_api_servTime']);
    return md5($sToken . $sVideoName . $_SERVER['REMOTE_ADDR'] . date("YmdH", $iTime));
  }

  function getAPI()
  {
    if (!isset($this->options['vod_api_login'])) {
      $this->options['vod_api_login'] = 'admin';
    }
    if (!isset($this->options['vod_api_password'])) {
      $this->options['vod_api_password'] = 'pass';
    }
    if (!isset($this->options['vod_api_id'])) {
      $this->options['vod_api_id'] = 'account';
    }
    require_once('vod.api.php');
    $sPassword = $this->decrypt($this->options['vod_api_password']);
    return new vod_api($this->options['vod_api_login'], $sPassword, $this->options['vod_api_id']);
  }

  function encrypt($text)
  {
    global $encryptIv, $encryptCipher, $encryptPassword;

    if (extension_loaded('openssl')) {
      return openssl_encrypt($text, $encryptCipher, $encryptPassword, 0, $encryptIv);
    } else {
      return trim(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, VOD_IK_SALT, $text, MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND))));
    }
  }

  function decrypt($text)
  {
    global $encryptIv, $encryptCipher, $encryptPassword;

    if (extension_loaded('openssl')) {
      return openssl_decrypt($text, $encryptCipher, $encryptPassword, 0, $encryptIv);
    } else {
      return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, VOD_IK_SALT, base64_decode($text), MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND)));
    }
  }

  function vod_template_redirect()
  {
    global $wp_query;
    $vod_page = isset($wp_query->query_vars['vod_page']) ? $wp_query->query_vars['vod_page'] : "";
    if ($vod_page == 'callback') {
      include(plugin_dir_path(__FILE__) . "vod_callback.php");
      exit;
    }
  }

  /**
   * Delegate buildForm to EasyVod_Display
   */
  function buildForm()
  {
    if ($this->plugin_ready()) {
      require_once("vod.template.php");
      $aPlayers = $this->db->get_players();
      $aVideos = $this->db->get_videos();
      $aPlaylists = $this->db->get_playlists();
      $aFolders = $this->db->get_folders();
      $bCanUpload = $this->isCurrentUserCan('upload');
      EasyVod_Display::buildForm($this->options, $aPlayers, $aVideos, $aPlaylists, $aFolders, $bCanUpload);
    }
  }
}


/**
 * Classe permettant la gestion des tables sql utilisé par ce plugin
 * En cas de problemes ou de questions, veuillez contacter support-vod-wordpress@infomaniak.ch
 *
 * @author infomaniak
 * @link http://statslive.infomaniak.ch/vod/api/
 * @version 1.0
 * @copyright infomaniak.ch
 *
 */

class EasyVod_db
{
  var $db_table_player;
  var $db_table_folder;
  var $db_table_video;
  var $db_table_playlist;
  var $db_table_upload;
  var $db_table_share;
  var $db_version = "1.0.34";
  private $vod;

  function __construct($vodInstance = null)
  {
    global $wpdb;
    $this->db_table_player = $wpdb->prefix . 'vod_player';
    $this->db_table_folder = $wpdb->prefix . 'vod_folder';
    $this->db_table_video = $wpdb->prefix . 'vod_video';
    $this->db_table_playlist = $wpdb->prefix . 'vod_playlist';
    $this->db_table_upload = $wpdb->prefix . 'vod_upload';
    $this->db_table_share = $wpdb->prefix . 'vod_share';
    $this->vod = $vodInstance;
  }


  function delete_db()
  {
    //require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    global $wpdb;

    $wpdb->query("DROP TABLE " . $this->db_table_player);
    $wpdb->query("DROP TABLE " . $this->db_table_folder);
    $wpdb->query("DROP TABLE " . $this->db_table_video);
    $wpdb->query("DROP TABLE " . $this->db_table_playlist);
    $wpdb->query("DROP TABLE " . $this->db_table_upload);
    //$wpdb->query("DROP TABLE " . $this->db_table_share);		//on ne doit jamais drop les share

  }

  function install_db()
  {
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $sql_player = "CREATE TABLE " . $this->db_table_player . " (
		 `iPlayer` INT UNSIGNED NOT NULL ,
		 `sName` VARCHAR( 255 ) NOT NULL ,
		 `iWidth` INT UNSIGNED NOT NULL ,
		 `iHeight` INT UNSIGNED NOT NULL ,
		 `bAutoPlay` TINYINT UNSIGNED NOT NULL ,
		 `bLoop` TINYINT UNSIGNED NOT NULL,
		 `bSwitchQuality` TINYINT UNSIGNED NOT NULL,
		 `dEdit` DATETIME NOT NULL,
		 `iVersion` INT UNSIGNED NOT NULL ,
		 PRIMARY KEY  (`iPlayer`),
		 KEY `sName` (`sName`)
		) CHARACTER SET utf8;";
    dbDelta($sql_player);

    $sql_folder = "CREATE TABLE " . $this->db_table_folder . " (
		 `iFolder` INT UNSIGNED NOT NULL ,
		 `sPath` VARCHAR( 255 ) NOT NULL ,
		 `sName` VARCHAR( 255 ) NOT NULL ,
		 `sAccess` VARCHAR( 255 ) NOT NULL ,
		 `sToken` VARCHAR( 255 ) NOT NULL,
				 PRIMARY KEY  (`iFolder`),
		 KEY `sName` (`sName`)
		) CHARACTER SET utf8;";
    dbDelta($sql_folder);





    $sql_video = "CREATE TABLE " . $this->db_table_video . " (
		 `iVideo` INT UNSIGNED NOT NULL ,
		 `iFolder` INT UNSIGNED NOT NULL ,
		 `sName` VARCHAR( 255 ) NOT NULL ,
		 `sPath` VARCHAR( 255 ) NOT NULL,
		 `sServerCode` VARCHAR( 255 ) NOT NULL,
		 `sFolderCode` VARCHAR( 255 ) NOT NULL,
		 `sExtension` VARCHAR( 4 ) NOT NULL,
		 `iDuration` INT UNSIGNED NOT NULL,
		 `dUpload` DATETIME NOT NULL,
		 `sImageUrlV2` VARCHAR( 255 ) NULL,
		 `sVideoUrlV2` VARCHAR( 255 ) NOT NULL,
		 `sVideoDashUrlV2` VARCHAR( 255 ) NULL,
		 `sShareUrlV2` VARCHAR( 255 ) NULL,
		 PRIMARY KEY  (`iVideo`),
		 KEY `iFolder` (`iFolder`),
		 KEY `sName` (`sName`),
		 KEY `sServerCode` (`sServerCode`),
		 KEY `sFolderCode` (`sFolderCode`)
 		 ) CHARACTER SET utf8;";
    dbDelta($sql_video);

    $sql_playlist = "CREATE TABLE " . $this->db_table_playlist . " (
		 `iPlaylistCode` INT UNSIGNED NOT NULL ,
		 `sPlaylistUuid` VARCHAR( 25 ) NOT NULL ,
		 `sPlaylistName` VARCHAR( 255 ) NOT NULL ,
		 `sPlaylistDescription` VARCHAR( 255 ) NOT NULL ,
		 `iTotal` INT UNSIGNED NOT NULL,
		 `iTotalDuration` INT UNSIGNED NOT NULL,
		 `sMode` VARCHAR( 255 ) NOT NULL,
		 `dCreated` DATETIME NOT NULL,
		 PRIMARY KEY  (`iPlaylistCode`),
		 KEY `sUid` (`sPlaylistUuid`)
		) CHARACTER SET utf8;";
    dbDelta($sql_playlist);

    $sql_upload = "CREATE TABLE " . $this->db_table_upload . " (
		 `iUpload` INT UNSIGNED NOT NULL AUTO_INCREMENT,
		 `sToken` VARCHAR( 255 ) NOT NULL,
		 `iPost` INT UNSIGNED NOT NULL,
		 `iVideo` VARCHAR( 25 ) NOT NULL,
		 `sState` VARCHAR( 25 ) NOT NULL,
		 PRIMARY KEY (iUpload),
		 UNIQUE KEY sToken (sToken)
		) CHARACTER SET utf8;";
    dbDelta($sql_upload);

    $sql_share = "CREATE TABLE " . $this->db_table_share . " (
		 `iShare` INT UNSIGNED NOT NULL AUTO_INCREMENT,
		 `sVideo` VARCHAR(  255 ) NOT NULL,
		 `iPlayer` INT UNSIGNED NOT NULL,
		 `sURL` VARCHAR( 255 ) NOT NULL,
		 PRIMARY KEY (iShare),

		 KEY `sVideo` (`sVideo`),
		 KEY `iPlayer` (`iPlayer`)
		) CHARACTER SET utf8;";
    dbDelta($sql_share);

    update_option("vod_db_version", $this->db_version);
  }

  /*
		* Gestion des players
		*/

  function get_players()
  {
    global $wpdb;
    return $wpdb->get_results("SELECT * FROM " . $this->db_table_player);
  }

  function get_player($iPlayer)
  {
    global $wpdb;
    return $wpdb->get_row("SELECT * FROM " . $this->db_table_player . " WHERE iPlayer=" . intval($iPlayer) . " LIMIT 1");
  }

  function clean_players()
  {
    global $wpdb;
    return $wpdb->query("DELETE FROM " . $this->db_table_player);
  }

  function insert_player($iPlayer, $sName, $iWidth, $iHeight, $bStart, $bLoop, $dEdit, $bSwitchQuality, $iVersion = 1)
  {
    global $wpdb;
    $wpdb->insert($this->db_table_player, array('iPlayer' => $iPlayer, 'sName' => $sName, 'iWidth' => $iWidth, 'iHeight' => $iHeight, 'bAutoPlay' => $bStart, 'bLoop' => $bLoop, 'dEdit' => $dEdit, 'bSwitchQuality' => $bSwitchQuality, 'iVersion' => $iVersion));
  }

  function count_player()
  {
    global $wpdb;
    return $wpdb->get_var("SELECT COUNT(*) FROM " . $this->db_table_player);
  }

  /*
		* Gestion des playlist
		*/

  function search_playlist($sTerm, $iLimit = 6)
  {
    global $wpdb;
    $sql = $wpdb->prepare("SELECT * FROM " . $this->db_table_playlist . " WHERE sPlaylistName LIKE %s OR sPlaylistDescription LIKE %s ORDER BY dCreated DESC LIMIT " . intval($iLimit), "%" . $sTerm . "%", "%" . $sTerm . "%");
    return $wpdb->get_results($sql);
  }

  function get_playlists()
  {
    global $wpdb;
    return $wpdb->get_results("SELECT * FROM " . $this->db_table_playlist);
  }

  function clean_playlists()
  {
    global $wpdb;
    return $wpdb->query("DELETE FROM " . $this->db_table_playlist);
  }

  function insert_playlist($iPlaylistCode, $sPlaylistName, $sPlaylistDescription, $iTotal, $sMode, $dCreated, $iTotalDuration, $sPlaylistUuid = "")
  {
    global $wpdb;
    $wpdb->insert($this->db_table_playlist, array('iPlaylistCode' => $iPlaylistCode, 'sPlaylistUuid' => $sPlaylistUuid, 'sPlaylistName' => $sPlaylistName, 'sPlaylistDescription' => $sPlaylistDescription, 'iTotal' => $iTotal, 'sMode' => $sMode, 'dCreated' => $dCreated, 'iTotalDuration' => $iTotalDuration));
  }

  function count_playlists()
  {
    global $wpdb;
    return $wpdb->get_var("SELECT COUNT(*) FROM " . $this->db_table_playlist);
  }

  /*
		* Gestion count des shares
		*/

  function countShare()
  {
    global $wpdb;
    return $wpdb->get_var("SELECT count(*) as nb FROM " . $this->db_table_share);
  }


  /*
		* Gestion des shares
		*/

  function getShare($sVideo, $iPlayer)
  {
    global $wpdb;
    $sql = $wpdb->prepare("SELECT * FROM " . $this->db_table_share . " WHERE sVideo= %s AND iPlayer=" . intval($iPlayer) . " LIMIT 1", $sVideo);
    return $wpdb->get_results($sql);
  }

  /*
		* La video courante se trouve t'elle dans un dossier protege par token
		*/
  function isFolderProtected($sVideo)
  {
    global $wpdb;
    $sql = $wpdb->prepare("SELECT f.* FROM " . $this->db_table_folder . " as f inner join " . $this->db_table_video . " as v on v.iFolder = f.iFolder WHERE v.sServerCode= %s LIMIT 1", $sVideo);
    return $wpdb->get_results($sql);
  }

  function setShare($sVideo, $iPlayer, $sURL)
  {
    global $wpdb;
    $wpdb->insert($this->db_table_share, array('sVideo' => $sVideo, 'iPlayer' => intval($iPlayer), 'sURL' => $sURL));
  }


  function updateShare($sVideo, $iPlayer, $sURL)
  {
    global $wpdb;
    $sql = $wpdb->prepare("UPDATE " . $this->db_table_share . " SET sURL=%s WHERE sVideo=%s AND iPlayer=%d LIMIT 1", $sURL, $sVideo, $iPlayer);
    $wpdb->query($sql);
  }

  /*
		* Gestion des dossiers
		*/

  function getFolder($iFolder)
  {
    global $wpdb;
    return $wpdb->get_row("SELECT * FROM " . $this->db_table_folder . " WHERE iFolder=" . intval($iFolder) . " LIMIT 1");
  }

  function get_folders($sFilter = "")
  {
    global $wpdb;
    if (!empty($sFilter)) {
      $sql = $wpdb->prepare("SELECT * FROM " . $this->db_table_folder . " WHERE sPath LIKE %s ORDER BY `sPath` ASC", $sFilter . "%");
      return $wpdb->get_results($sql);
    } else {
      return $wpdb->get_results("SELECT * FROM " . $this->db_table_folder . " ORDER BY `sPath` ASC");
    }
  }

  function clean_folders()
  {
    global $wpdb;
    return $wpdb->query("DELETE FROM " . $this->db_table_folder);
  }



  function insert_folder($iFolder, $sPath, $sName, $sAccess, $sToken)
  {
    global $wpdb;
    $wpdb->insert($this->db_table_folder, array('iFolder' => $iFolder, 'sPath' => $sPath, 'sName' => $sName, 'sAccess' => $sAccess, 'sToken' => $sToken));
  }

  function count_folder()
  {
    global $wpdb;
    return $wpdb->get_var("SELECT COUNT(*) FROM " . $this->db_table_folder);
  }

  /*
		* Gestion des videos
		*/

  function search_videos($sTerm, $iLimit = 6, $sFilter = "")
  {
    global $wpdb;
    if (!empty($sFilter)) {
      $sql = $wpdb->prepare("SELECT iFolder FROM " . $this->db_table_folder . " WHERE sPath LIKE %s ORDER BY sPath", $sFilter . '%');
      $aFolders = $wpdb->get_results($sql);
      $aFoldersList = array();
      foreach ($aFolders as $oFolder) {
        $aFoldersList[] = $oFolder->iFolder;
      }
      $sql = $wpdb->prepare("SELECT video.*, folder.sAccess, folder.sToken FROM " . $this->db_table_video . " as video
			INNER JOIN " . $this->db_table_folder . " as folder ON video.iFolder = folder.iFolder
			WHERE (video.sName LIKE %s OR sServerCode LIKE %s) AND video.iFolder IN ( " . implode(",", $aFoldersList) . ") ORDER BY dUpload DESC LIMIT " . intval($iLimit), "%" . $sTerm . "%", "%" . $sTerm . "%");
      return $wpdb->get_results($sql);
    } else {
      $sql = $wpdb->prepare("SELECT video.*, folder.sAccess, folder.sToken FROM " . $this->db_table_video . " as video
			INNER JOIN " . $this->db_table_folder . " as folder ON video.iFolder = folder.iFolder
			WHERE video.sName LIKE %s OR sServerCode LIKE %s ORDER BY dUpload DESC LIMIT " . intval($iLimit), "%" . $sTerm . "%", "%" . $sTerm . "%");
      return $wpdb->get_results($sql);
    }
  }

  function get_videos_byPage($iPage, $iLimit, $sFilter = "")
  {
    global $wpdb;
    if (!empty($sFilter)) {
      $sql = $wpdb->prepare("SELECT iFolder FROM " . $this->db_table_folder . " WHERE sPath LIKE %s ORDER BY sPath", $sFilter . '%');
      $aFolders = $wpdb->get_results($sql);
      $aFoldersList = array();
      foreach ($aFolders as $oFolder) {
        $aFoldersList[] = $oFolder->iFolder;
      }
      return $wpdb->get_results("SELECT video.*, folder.sAccess, folder.sToken FROM " . $this->db_table_video . " as video
			INNER JOIN " . $this->db_table_folder . " as folder ON video.iFolder = folder.iFolder
			WHERE video.iFolder IN ( " . implode(",", $aFoldersList) . ")
			ORDER BY `dUpload` DESC LIMIT " . intval($iPage * $iLimit) . ", " . intval($iLimit));
    } else {
      return $wpdb->get_results("SELECT video.*, folder.sAccess, folder.sToken FROM " . $this->db_table_video . " as video
			INNER JOIN " . $this->db_table_folder . " as folder ON video.iFolder = folder.iFolder
			ORDER BY `dUpload` DESC LIMIT " . intval($iPage * $iLimit) . ", " . intval($iLimit));
    }
  }

  function get_videos_byCodes($sServerCode, $iFolderCode)
  {
    global $wpdb;
    $sql = $wpdb->prepare("SELECT * FROM " . $this->db_table_video . " WHERE sServerCode=%s AND iFolder=%d", $sServerCode, $iFolderCode);
    return $wpdb->get_results($sql);
  }

  function getLastVideo()
  {
    global $wpdb;
    return $wpdb->get_row("SELECT * FROM " . $this->db_table_video . " ORDER BY dUpload DESC LIMIT 1");
  }

  function getVideo($iVideo)
  {
    global $wpdb;
    if (get_option('vod_api_version', false) == 1) {
      return $wpdb->get_row("SELECT * FROM " . $this->db_table_video . " WHERE iVideo=" . intval($iVideo) . " LIMIT 1");
    } else {
      return $wpdb->get_row("SELECT *, share.sUrl AS sShareUrlV2 FROM " . $this->db_table_video . " as video
					INNER JOIN " . $this->db_table_share . " as share ON video.sServerCode = share.sVideo
					WHERE video.iVideo=" . intval($iVideo) . " LIMIT 1");
    }
  }

  function getPlaylist($iPlaylistCode)
  {
    global $wpdb;
    return $wpdb->get_row("SELECT * FROM " . $this->db_table_playlist . " WHERE iPlaylistCode=" . intval($iPlaylistCode) . " LIMIT 1");
  }

  function getPlaylistByUuid($sPlaylistUuid)
  {
    global $wpdb;
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $this->db_table_playlist . " WHERE sPlaylistUuid=%s LIMIT 1", $sPlaylistUuid));
  }



  function get_videos()
  {
    global $wpdb;
    return $wpdb->get_results("SELECT * FROM " . $this->db_table_video . " ORDER BY `dUpload` DESC");
  }

  function clean_videos()
  {
    global $wpdb;
    return $wpdb->query("DELETE FROM " . $this->db_table_video);
  }

  function rename_video($iVideo, $sName)
  {
    global $wpdb;
    $sql = $wpdb->prepare("UPDATE " . $this->db_table_video . " SET sName=%s WHERE iVideo=%d LIMIT 1", $sName, $iVideo);
    $wpdb->query($sql);
  }

  function insert_video($iVideo, $iFolder, $sName, $sServerCode, $sPath, $sExtension, $iDuration, $dUpload, $sFolderCode = "", $sVideoURL = "", $sImageURL = "", $sShareURL = "", $sDashURL = "")
  {
    error_log("Attempting to insert video: ID=" . $iVideo . ", Name=" . $sName . ", ServerCode=" . $sServerCode . ", FolderCode=" . $sFolderCode);

    global $wpdb;

    // Ensure we have defaults for backward compatibility
    $sVideoURL = isset($sVideoURL) ? $sVideoURL : '';
    $sImageURL = isset($sImageURL) ? $sImageURL : '';
    $sFolderCode = isset($sFolderCode) ? $sFolderCode : '';
    $sDashURL = isset($sDashURL) ? $sDashURL : '';

    // If dash URL is not provided but we have folder UUID and encoded media info, construct it
    if (empty($sDashURL) && !empty($sFolderCode) && !empty($sServerCode)) {
      // Get video info to construct dash URL
      $oApi = $this->getAPI();
      if ($oApi) {
        $oVideo = $oApi->getVideoInformation($iFolder, $sServerCode);
        if (!empty($oVideo) && isset($oVideo['aEncodes'])) {
          $media_streams = array();
          // Extract the video IDs from the URLs
          foreach ($oVideo['aEncodes'] as $encode) {
            if (isset($encode['sVideoURL'])) {
              $parts = explode('/', $encode['sVideoURL']);
              $videoId = end($parts); // Get the last part of the URL which is the video ID
              if ($videoId) {
                $media_streams[] = $videoId;
              }
            }
          }
          if (!empty($media_streams)) {
            $sDashURL = "https://play.vod2.infomaniak.com/dash/{$sFolderCode}/{$sServerCode}/," . implode(',', $media_streams) . ",.urlset/manifest.mpd";
          }
        }
      }
    }

    $insert_data = array(
      'iVideo' => $iVideo,
      'iFolder' => $iFolder,
      'sName' => $sName,
      'sServerCode' => $sServerCode,
      'sFolderCode' => $sFolderCode,
      'sPath' => $sPath,
      'sExtension' => $sExtension,
      'iDuration' => $iDuration,
      'dUpload' => $dUpload,
      'sVideoUrlV2' => $sVideoURL,
      'sImageUrlV2' => $sImageURL,
      'sShareUrlV2' => $sShareURL,
      'sVideoDashUrlV2' => $sDashURL
    );

    $wpdb->insert($this->db_table_video, $insert_data);
  }

  function count_video($sFilter = "")
  {
    global $wpdb;
    if (!empty($sFilter)) {
      $sql = $wpdb->prepare("SELECT iFolder FROM " . $this->db_table_folder . " WHERE sPath LIKE %s ORDER BY sPath", $sFilter . '%');
      $aFolders = $wpdb->get_results($sql);
      $aFoldersList = array();
      foreach ($aFolders as $oFolder) {
        $aFoldersList[] = $oFolder->iFolder;
      }
      return $wpdb->get_var("SELECT COUNT(*) FROM " . $this->db_table_video . "
			WHERE iFolder IN ( " . implode(",", $aFoldersList) . ")");
    } else {
      return $wpdb->get_var("SELECT COUNT(*) FROM " . $this->db_table_video);
    }
  }

  function delete_video($iVideo = -1)
  {
    global $wpdb;
    return $wpdb->query("DELETE FROM " . $this->db_table_video . " WHERE iVideo = " . intval($iVideo) . " LIMIT 1");
  }

  /*
		 * Gestion des uploads
		 */

  function insert_upload($sToken, $iVideo)
  {
    global $wpdb;
    $wpdb->insert($this->db_table_upload, array('sToken' => $sToken, 'iVideo' => $iVideo, 'sState' => 'UPLOAD'));
  }

  function update_upload($sToken, $iPost)
  {
    global $wpdb;
    return $wpdb->query($wpdb->prepare("UPDATE " . $this->db_table_upload . " SET `iVideo`=%d WHERE `wp_vod_upload`.`sToken`=%s", $iPost, $sToken));
  }

  function update_upload_status($iVideo, $status)
  {
    global $wpdb;
    $sql = $wpdb->prepare("UPDATE " . $this->db_table_upload . " SET sState=%s WHERE iVideo=%d LIMIT 1", $status, $iVideo);
    $wpdb->query($sql);
  }

  function get_upload_video($sToken)
  {
    global $wpdb;
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $this->db_table_upload . " WHERE sToken=%s LIMIT 1", $sToken));
  }

  function get_upload_process()
  {
    global $wpdb;
    return $wpdb->get_results("SELECT * FROM " . $this->db_table_upload . " WHERE iVideo=0");
  }

  function getAPI()
  {
    if ($this->vod) {
      return $this->vod->getAPI();
    }
    return null;
  }
}
function vod_query_vars($qvars)
{
  $qvars[] = 'vod_page';
  return $qvars;
}
