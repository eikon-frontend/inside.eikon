<?php

use QRcode\QRcode;
use QRcode\QRstr;

function add_custom_meta_box()
{
  add_meta_box(
    'infos-box',
    'Informations du projet',
    'info_box',
    'project',
    'side',
    'low'
  ); //spaces were here
}
add_action('add_meta_boxes', 'add_custom_meta_box');

function info_box($post)
{
  if ($post->post_name) {
    $base_url = "https://eikon.ch";
    $post_external_url = $base_url . "/projets/" . $post->post_name;
    echo '<h3>URL Externe & QR Code</h3>';
    echo '<a href="' . $post_external_url . '" target="_blank">' . $post_external_url . '</a><hr />';
    $base64_data = QRcode::base64_webp($post_external_url, QRstr::QR_ECLEVEL_L, 50, 0);
    echo '<img src="' . $base64_data . '" />';
  } else {
    echo "Enregistrez d'abord le projet pour obtenir l'URL externe et le QR Code.";
  }
}
