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

/**
 * Override the sample permalink to point to the frontend (for projects).
 *
 * This customizes what's displayed in the publish box underneath the title.
 * For draft/pending projects, this ensures the URL shown is the frontend URL.
 */
add_filter('get_sample_permalink', function ($permalink, $post_id, $title, $name, $post) {
  if (!$post || $post->post_type !== 'project') {
    return $permalink;
  }

  // If no slug yet, return the default
  if (empty($post->post_name)) {
    return $permalink;
  }

  // Get the frontend URL
  $frontend_url = home_url('/');

  // Construct the project permalink
  $project_permalink = trailingslashit($frontend_url) . 'projets/' . $post->post_name . '/';

  return $project_permalink;
}, 10, 5);

/**
 * Filter the permalink anchor tag HTML to ensure proper linking.
 *
 * Ensures that when the permalink is displayed in the publish box, it's properly
 * linked to the frontend preview URL instead of the admin edit page.
 */
add_filter('get_sample_permalink_html', function ($html, $post_id, $new_title = '', $new_slug = '') {
  $post = get_post($post_id);

  if (!$post || $post->post_type !== 'project') {
    return $html;
  }

  // If no slug, disable the link
  if (empty($post->post_name)) {
    return $html;
  }

  // Get the correct preview URL
  $preview_url = preview_post_link($post);

  if (empty($preview_url)) {
    return $html;
  }

  // Build the correct HTML with proper link
  $frontend_url = home_url('/');
  $project_url = trailingslashit($frontend_url) . 'projets/' . $post->post_name . '/';

  // Create the new HTML with the correct URL
  $new_html = '<strong>' . esc_html__('Permalink:', 'default') . '</strong> ';
  $new_html .= '<span id="sample-permalink"><a href="' . esc_url($preview_url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($project_url) . '</a></span> ';
  $new_html .= '<span id="edit-slug-buttons"><a href="#post_name" class="edit-permalink" aria-label="' . esc_attr__('Edit permalink') . '">' . esc_html__('Edit', 'default') . '</a></span>';

  return $new_html;
}, 10, 4);
