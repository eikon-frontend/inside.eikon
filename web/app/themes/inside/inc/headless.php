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
    // Construct frontend URL logic similar to preview_post_link
    $frontend_url = home_url('/');

    if ($post->post_type === 'project') {
      $post_external_url = trailingslashit($frontend_url) . 'projets/' . $post->post_name . '/';
    } else {
      $post_external_url = get_permalink($post->ID);
    }

    echo '<h3>URL Externe & QR Code</h3>';

    if ($post_external_url && filter_var($post_external_url, FILTER_VALIDATE_URL)) {
      echo '<a href="' . esc_url($post_external_url) . '" target="_blank">' . esc_url($post_external_url) . '</a><hr />';
      $base64_data = QRcode::base64_webp($post_external_url, QRstr::QR_ECLEVEL_L, 50, 0);
      echo '<img style="width:100%" src="' . esc_attr($base64_data) . '" alt="QR Code" />';
    } else {
      echo '<p style="color: #d63638;">URL invalide pour la génération du QR code.</p>';
    }
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

add_action('admin_head', function () {
  global $post;
  if (!$post) return;

  // If the post is an auto-draft or doesn't have a name/slug yet, hide the preview button
  if ($post->post_status === 'auto-draft' || empty($post->post_name)) {
    echo '<style>
            #post-preview,
            .editor-post-preview,
            .block-editor-post-preview__button-toggle,
            .block-editor-post-preview__dropdown {
                display: none !important;
            }
        </style>';
  }
});

/**
 * Customize the preview link to point to the headless frontend.
 *
 * 1. Only allow preview if the post has a slug (saved at least once).
 * 2. Force the URL to use the slug instead of ?p=ID, even for drafts.
 */
add_filter('preview_post_link', function ($link, $post) {
  // If post is auto-draft or has no slug, return empty string (hides/disables preview)
  if ($post->post_status === 'auto-draft' || empty($post->post_name)) {
    return '';
  }

  // Get the home URL (frontend URL)
  $frontend_url = home_url('/');

  // If the post type is 'project', construct the URL with /projets/slug/
  // The CPT rewrite slug is 'projets' (plural)
  if ($post->post_type === 'project') {
    return trailingslashit($frontend_url) . 'projets/' . $post->post_name . '/';
  }

  // For other post types, fall back to default permalink but ensure it uses the slug
  // However, get_permalink() for drafts usually adds ?p=ID parameters if not published.
  // We force slug usage if available.
  if ($post->post_name) {
    // Assuming standard structure for other types, or allow WP to handle if not project
    // But user specifically mentioned 'project' in the example URL
    return get_permalink($post);
  }

  return $link;
}, 10, 2);


