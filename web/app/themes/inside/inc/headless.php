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
 * Override the sample permalink URL structure for projects (for display in publish box).
 *
 * This ensures the URL shown under the title uses the frontend URL.
 * Returns array format: [URL with placeholder, post_name/slug]
 */
add_filter('get_sample_permalink', function ($permalink, $post_id, $title, $name, $post) {
  // Only for projects
  if (!$post || $post->post_type !== 'project') {
    return $permalink;
  }

  // If no slug yet, return the default
  if (empty($post->post_name)) {
    return $permalink;
  }

  // Get the frontend URL
  $frontend_url = home_url('/');

  // Construct the project permalink with placeholder
  // WordPress expects this format: [full URL with %postname% placeholder if needed, or just the URL, ...post_name]
  $project_url = trailingslashit($frontend_url) . 'projets/' . $post->post_name . '/';

  // Return as array: [base_url, post_name]
  return array($project_url, $post->post_name);
}, 10, 5);

/**
 * Ensure the sample permalink HTML is shown for draft/pending student projects.
 *
 * WordPress sometimes hides the permalink for pending posts. We override this
 * to ensure students can always see the preview link for their own draft/pending projects.
 */
add_filter('get_sample_permalink_html', function ($html, $post_id) {
  $post = get_post($post_id);

  // Debug logging
  if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
    error_log('=== DEBUG: get_sample_permalink_html called ===');
    error_log('Post ID: ' . $post_id);
    error_log('Post Type: ' . ($post ? $post->post_type : 'null'));
    error_log('Post Status: ' . ($post ? $post->post_status : 'null'));
    error_log('Post Name (slug): ' . ($post ? $post->post_name : 'null'));
  }

  // Only for projects
  if (!$post || $post->post_type !== 'project') {
    if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
      error_log('Not a project, returning default HTML');
    }
    return $html;
  }

  // Only if the post is draft/pending and belongs to current user
  $current_user = wp_get_current_user();
  if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
    error_log('Current User ID: ' . $current_user->ID);
    error_log('Current User Roles: ' . implode(', ', $current_user->roles));
    error_log('Post Author ID: ' . $post->post_author);
  }

  if (!$current_user->ID || (int) $post->post_author !== $current_user->ID) {
    if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
      error_log('User is not the post author, returning default HTML');
    }
    return $html;
  }

  // Check if user is student or teacher
  $is_student = in_array('student', $current_user->roles, true);
  $is_teacher = in_array('teacher', $current_user->roles, true);

  if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
    error_log('Is Student: ' . ($is_student ? 'yes' : 'no'));
    error_log('Is Teacher: ' . ($is_teacher ? 'yes' : 'no'));
  }

  if (!$is_student && !$is_teacher) {
    if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
      error_log('User is not student or teacher, returning default HTML');
    }
    return $html;
  }

  // Only for draft/pending/future posts
  if (!in_array($post->post_status, ['draft', 'pending', 'future'], true)) {
    if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
      error_log('Post status is not draft/pending/future (' . $post->post_status . '), returning default HTML');
    }
    return $html;
  }

  // If no slug, don't show
  if (empty($post->post_name)) {
    if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
      error_log('Post has no slug, returning default HTML');
    }
    return $html;
  }

  // Get the frontend URL
  $frontend_url = home_url('/');
  $preview_url = trailingslashit($frontend_url) . 'projets/' . $post->post_name . '/';

  if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
    error_log('Building custom permalink HTML');
    error_log('Preview URL: ' . $preview_url);
  }

  // Build the permalink HTML with proper WordPress structure
  // WordPress expects this in a div#sample-permalink-container
  $new_html = '<div id="sample-permalink-container">';
  $new_html .= '<strong>' . esc_html__('Permalink:', 'default') . '</strong> ';
  $new_html .= '<span id="sample-permalink"><a href="' . esc_url($preview_url) . '" target="_blank">' . esc_html($preview_url) . '</a></span> ';
  $new_html .= '<span id="edit-slug-buttons"><a href="#post_name" class="edit-permalink">' . esc_html__('Edit', 'default') . '</a></span>';
  $new_html .= '</div>';

  if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
    error_log('Custom permalink HTML generated successfully');
    error_log('Returning HTML: ' . substr($new_html, 0, 200) . '...');
  }

  return $new_html;
}, 10, 2);

/**
 * Display permalink in publish metabox for draft/pending projects.
 *
 * The get_sample_permalink_html filter may not work for draft/pending posts in all contexts.
 * This hook provides a fallback to ensure the permalink is always visible.
 */
add_action('post_submitbox_misc_actions', function () {
  global $post;

  if (!$post || $post->post_type !== 'project') {
    return;
  }

  // Only for draft/pending/future posts
  if (!in_array($post->post_status, ['draft', 'pending', 'future'], true)) {
    return;
  }

  // Only if belongs to current user
  $current_user = wp_get_current_user();
  if (!$current_user->ID || (int) $post->post_author !== $current_user->ID) {
    return;
  }

  // Only for student/teacher
  $is_student = in_array('student', $current_user->roles, true);
  $is_teacher = in_array('teacher', $current_user->roles, true);
  if (!$is_student && !$is_teacher) {
    return;
  }

  // Only if has slug
  if (empty($post->post_name)) {
    return;
  }

  // Build and display the permalink
  $frontend_url = home_url('/');
  $preview_url = trailingslashit($frontend_url) . 'projets/' . $post->post_name . '/';

  if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
    error_log('=== DEBUG: post_submitbox_misc_actions permalink output ===');
    error_log('Post ID: ' . $post->ID);
    error_log('Preview URL: ' . $preview_url);
  }

?>
  <div class="misc-pub-section" style="border-top: 1px solid #eee; padding-top: 10px; margin-top: 10px;">
    <strong><?php esc_html_e('Preview Link:', 'default'); ?></strong>
    <p style="margin: 5px 0 0 0;">
      <a href="<?php echo esc_url($preview_url); ?>" target="_blank" style="text-decoration: none;">
        <?php echo esc_html($preview_url); ?>
      </a>
    </p>
  </div>
<?php
});
