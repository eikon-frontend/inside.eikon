<?php

/**
 * VOD Eikon Helper Functions
 *
 * This file provides convenient functions for accessing VOD videos in themes and other plugins.
 */

if (!defined('ABSPATH')) {
  exit;
}

/**
 * Get all VOD videos from the database
 *
 * @return array Array of video objects
 */
function vod_eikon_get_videos()
{
  global $wpdb;
  $table_name = $wpdb->prefix . 'vod_eikon_videos';

  return $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY created_at DESC");
}

/**
 * Get a specific video by its VOD ID
 *
 * @param string $vod_id The VOD ID from Infomaniak
 * @return object|null Video object or null if not found
 */
function vod_eikon_get_video($vod_id)
{
  global $wpdb;
  $table_name = $wpdb->prefix . 'vod_eikon_videos';

  return $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$table_name} WHERE vod_id = %s",
    $vod_id
  ));
}

/**
 * Generate a DashJS player HTML for a specific video
 *
 * @param string $vod_id The VOD ID
 * @param array $options Player options (width, height, autoplay, etc.)
 * @return string HTML for the video player
 */
function vod_eikon_player($vod_id, $options = array())
{
  $video = vod_eikon_get_video($vod_id);

  if (!$video || empty($video->mpd_url)) {
    return '<p>Video not found or MPD URL not available.</p>';
  }

  // Default options
  $defaults = array(
    'width' => '100%',
    'height' => '400px',
    'autoplay' => false,
    'controls' => true,
    'poster' => $video->poster
  );

  $options = wp_parse_args($options, $defaults);

  $player_id = 'vod-player-' . esc_attr($vod_id);
  $width = esc_attr($options['width']);
  $height = esc_attr($options['height']);
  $poster = esc_url($options['poster']);
  $mpd_url = esc_url($video->mpd_url);

  $html = '<div class="vod-eikon-player-container">';
  $html .= '<video id="' . $player_id . '" width="' . $width . '" height="' . $height . '"';

  if ($options['controls']) {
    $html .= ' controls';
  }

  if ($options['autoplay']) {
    $html .= ' autoplay';
  }

  if ($poster) {
    $html .= ' poster="' . $poster . '"';
  }

  $html .= '></video>';
  $html .= '</div>';

  // Add DashJS script if not already loaded
  $html .= '<script>';
  $html .= 'if (typeof dashjs === "undefined") {';
  $html .= '  var script = document.createElement("script");';
  $html .= '  script.src = "https://cdn.dashjs.org/latest/dash.all.min.js";';
  $html .= '  script.onload = function() { initPlayer' . $player_id . '(); };';
  $html .= '  document.head.appendChild(script);';
  $html .= '} else {';
  $html .= '  initPlayer' . $player_id . '();';
  $html .= '}';

  $html .= 'function initPlayer' . $player_id . '() {';
  $html .= '  var video = document.getElementById("' . $player_id . '");';
  $html .= '  var player = dashjs.MediaPlayer().create();';
  $html .= '  player.initialize(video, "' . $mpd_url . '", ' . ($options['autoplay'] ? 'true' : 'false') . ');';
  $html .= '}';
  $html .= '</script>';

  return $html;
}

/**
 * Display a grid of video thumbnails
 *
 * @param array $options Grid options
 * @return string HTML for the video grid
 */
function vod_eikon_video_grid($options = array())
{
  $videos = vod_eikon_get_videos();

  if (empty($videos)) {
    return '<p>No videos available.</p>';
  }

  $defaults = array(
    'columns' => 3,
    'show_title' => true,
    'link_to_player' => true
  );

  $options = wp_parse_args($options, $defaults);
  $columns = intval($options['columns']);

  $html = '<div class="vod-eikon-grid" style="display: grid; grid-template-columns: repeat(' . $columns . ', 1fr); gap: 20px; margin: 20px 0;">';

  foreach ($videos as $video) {
    $html .= '<div class="vod-eikon-grid-item" style="text-align: center;">';

    if ($video->poster) {
      $html .= '<img src="' . esc_url($video->poster) . '" alt="' . esc_attr($video->name) . '" style="width: 100%; height: auto; border-radius: 8px; margin-bottom: 10px;">';
    }

    if ($options['show_title']) {
      $html .= '<h4 style="margin: 10px 0;">' . esc_html($video->name) . '</h4>';
    }

    if ($options['link_to_player'] && $video->mpd_url) {
      $html .= '<button onclick="openVideoModal(\'' . esc_js($video->vod_id) . '\')" style="background: #0073aa; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">Play Video</button>';
    }

    $html .= '</div>';
  }

  $html .= '</div>';

  // Add modal functionality if link_to_player is enabled
  if ($options['link_to_player']) {
    $html .= '<div id="vod-modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8);">';
    $html .= '<div style="position: relative; margin: 5% auto; width: 80%; max-width: 800px;">';
    $html .= '<span onclick="closeVideoModal()" style="position: absolute; top: -40px; right: 0; color: white; font-size: 30px; cursor: pointer;">&times;</span>';
    $html .= '<div id="vod-modal-content"></div>';
    $html .= '</div>';
    $html .= '</div>';

    $html .= '<script>';
    $html .= 'function openVideoModal(vodId) {';
    $html .= '  var modal = document.getElementById("vod-modal");';
    $html .= '  var content = document.getElementById("vod-modal-content");';
    $html .= '
        // Fetch video player HTML via AJAX or generate inline
        fetch("' . admin_url('admin-ajax.php') . '?action=get_vod_player&vod_id=" + vodId)
        .then(response => response.text())
        .then(html => {
            content.innerHTML = html;
            modal.style.display = "block";
        });';
    $html .= '}';
    $html .= 'function closeVideoModal() {';
    $html .= '  document.getElementById("vod-modal").style.display = "none";';
    $html .= '  document.getElementById("vod-modal-content").innerHTML = "";';
    $html .= '}';
    $html .= '</script>';
  }

  return $html;
}
