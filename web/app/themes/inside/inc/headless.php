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
    $post_external_url = get_permalink($post->ID);
    echo '<h3>URL Externe & QR Code</h3>';
    echo '<a href="' . $post_external_url . '" target="_blank">' . $post_external_url . '</a><hr />';
    $base64_data = QRcode::base64_webp($post_external_url, QRstr::QR_ECLEVEL_L, 50, 0);
    echo '<img style="width:100%" src="' . $base64_data . '" />';
  } else {
    echo "Enregistrez d'abord le projet pour obtenir l'URL externe et le QR Code.";
  }
}

function custom_rest_url($url)
{
  return getenv('WP_HOME_ADMIN') . '/wp-json/';
}

add_filter('rest_url', 'custom_rest_url');

function display_documentation_notice()
{
  // Check if the notice has been dismissed
  if (get_user_meta(get_current_user_id(), 'inside_documentation_notice_dismissed', true)) {
    return;
  }

?>
  <div class="notice notice-info is-dismissible">
    <p><span class="dashicons dashicons-welcome-learn-more"></span><strong>Documentation Inside.eikon.ch</strong></p>
    <p>Pour savoir comment éditer le site et ajouter des projets au portfolio, consultez la documentation sur le
      <a href="https://www.notion.so/eikon-imd/e071042b616246b68f3e811758305160?v=a8ccb994f5454e968d8b8d0e060978a6&pvs=4#1fc709599a81802fb975e14ace44b1c3"
        target="_blank">notion de l'école
      </a>
    </p>
  </div>
  <script>
    jQuery(document).ready(function($) {
      $(document).on('click', '.notice-info .notice-dismiss', function() {
        $.ajax({
          url: ajaxurl,
          data: {
            action: 'dismiss_documentation_notice'
          }
        });
      });
    });
  </script>
<?php
}

function handle_documentation_notice_dismissal()
{
  update_user_meta(get_current_user_id(), 'inside_documentation_notice_dismissed', true);
}

add_action('admin_notices', 'display_documentation_notice');
add_action('wp_ajax_dismiss_documentation_notice', 'handle_documentation_notice_dismissal');
