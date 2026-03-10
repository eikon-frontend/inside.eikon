<?php

use QRcode\QRcode;
use QRcode\QRstr;

function add_custom_meta_box()
{
  // Single "Review" metabox combining completion checklist and QR code
  add_meta_box(
    'project-review',
    'Examen du projet',
    'project_review_metabox',
    'project',
    'side',
    'high'
  );
}
add_action('add_meta_boxes', 'add_custom_meta_box');

/**
 * Display project review metabox (completion checklist + QR code)
 */
function project_review_metabox($post)
{
  // Display completion checklist
  project_completion_checklist_content($post);

  echo '<hr style="margin: 16px 0; border: none; border-top: 1px solid #e5e7eb;">';

  // Display QR code
  info_box_content($post);
}

/**
 * Display project completion checklist content
 */
function project_completion_checklist_content($post)
{
  // Get post data
  $has_title = !empty($post->post_title);
  $has_thumbnail = has_post_thumbnail($post->ID);
  $has_content = !empty($post->post_content);

  // Check for ACF taxonomy fields (year, section, subjects)
  $has_year = !empty(get_the_terms($post->ID, 'year'));
  $has_section = !empty(get_the_terms($post->ID, 'section'));
  $has_subjects = !empty(get_the_terms($post->ID, 'subjects'));
  $has_taxonomy_fields = $has_year && $has_section && $has_subjects;

  // Get ACF fields
  $project_fields = get_field('projets', $post->ID);
  $has_gallery = false;

  if (is_array($project_fields)) {
    foreach ($project_fields as $field) {
      if (isset($field['acf_fc_layout']) && $field['acf_fc_layout'] === 'galerie' && !empty($field['photos'])) {
        $has_gallery = true;
        break;
      }
    }
  }

  // Calculate completion percentage
  $total_items = 5;
  $completed_items = 0;
  $completed_items += $has_title ? 1 : 0;
  $completed_items += $has_thumbnail ? 1 : 0;
  $completed_items += $has_content ? 1 : 0;
  $completed_items += $has_gallery ? 1 : 0;
  $completed_items += $has_taxonomy_fields ? 1 : 0;

  $completion_percent = ($completed_items / $total_items) * 100;
  $is_complete = $completion_percent === 100;

  // Helper function to render checklist item
  $render_item = function ($label, $is_complete) {
    $icon = $is_complete ? '✓' : '◯';
    $color = $is_complete ? '#10b981' : '#d1d5db';
    $text_color = $is_complete ? '#065f46' : '#6b7280';
?>
    <div style="display: flex; align-items: center; margin: 4px 0; padding: 6px; background: <?php echo $is_complete ? '#ecfdf5' : '#f9fafb'; ?>; border-radius: 4px; border-left: 3px solid <?php echo $color; ?>;">
      <span style="color: <?php echo $color; ?>; font-size: 16px; font-weight: bold; margin-right: 8px; min-width: 18px; text-align: center;">
        <?php echo esc_html($icon); ?>
      </span>
      <span style="color: <?php echo $text_color; ?>; font-size: 13px;">
        <?php echo esc_html($label); ?>
      </span>
    </div>
  <?php
  };
  ?>
  <div style="margin: 0;">
    <!-- Completion Progress -->
    <div style="margin-bottom: 12px;">
      <div style="display: flex; justify-content: space-between; margin-bottom: 6px; align-items: center;">
        <strong style="font-size: 13px; color: #1f2937;">Complétude du projet</strong>
        <span style="font-size: 15px; font-weight: bold; color: <?php echo $is_complete ? '#10b981' : '#f59e0b'; ?>;">
          <?php echo (int) $completion_percent; ?>%
        </span>
      </div>
      <div style="width: 100%; height: 6px; background: #e5e7eb; border-radius: 4px; overflow: hidden;">
        <div style="width: <?php echo (int) $completion_percent; ?>%; height: 100%; background: <?php echo $is_complete ? '#10b981' : '#f59e0b'; ?>; transition: width 0.3s ease;"></div>
      </div>
    </div>

    <!-- Checklist Items -->
    <div style="border-top: 1px solid #e5e7eb; padding-top: 8px;">
      <?php
      $render_item('Titre du projet', $has_title);
      $render_item('Image à la une', $has_thumbnail);
      $render_item('Description du projet', $has_content);
      $render_item('Galerie du projet', $has_gallery);
      $render_item('Métadonnées (année, classe, branche)', $has_taxonomy_fields);
      ?>
    </div>

    <!-- Status Message -->
    <div style="margin-top: 8px; padding: 8px; border-radius: 4px; background: <?php echo $is_complete ? '#d1fae5' : '#fef3c7'; ?>; border: 1px solid <?php echo $is_complete ? '#6ee7b7' : '#fcd34d'; ?>;">
      <p style="margin: 0; font-size: 12px; color: <?php echo $is_complete ? '#065f46' : '#92400e'; ?>;">
        <?php
        if ($is_complete) {
          echo '✓ Ton projet est complet.';
        } else {
          echo '⚠ Complète les sections manquantes.';
        }
        ?>
      </p>
    </div>
  </div>
<?php
}

function info_box_content($post)
{
  if ($post->post_name) {
    // Construct frontend URL logic similar to preview_post_link
    $frontend_url = home_url('/');

    if ($post->post_type === 'project') {
      $post_external_url = trailingslashit($frontend_url) . 'projets/' . $post->post_name . '/';
    } else {
      $post_external_url = get_permalink($post->ID);
    }

    echo '<h4 style="margin: 0 0 12px 0; font-size: 12px; color: #1f2937;">URL Externe & QR Code</h4>';
    echo '<div style="text-align: center;">';
    if ($post_external_url && filter_var($post_external_url, FILTER_VALIDATE_URL)) {
      echo '<p style="margin-bottom: 12px; word-break: break-all; font-size: 12px; color: #6b7280;">';
      echo '<a href="' . esc_url($post_external_url) . '" target="_blank" style="color: #0891b2; text-decoration: none;">' . esc_url($post_external_url) . '</a>';
      echo '</p>';
      // Suppress PHP 8.0+ deprecation warning for imagedestroy() in QR code library
      $previous_error_reporting = error_reporting(E_ALL & ~E_DEPRECATED);
      $base64_data = QRcode::base64_webp($post_external_url, QRstr::QR_ECLEVEL_L, 50, 0);
      error_reporting($previous_error_reporting);
      echo '<img style="width: 100%; max-width: 200px; border-radius: 4px; border: 1px solid #e5e7eb;" src="' . esc_attr($base64_data) . '" alt="QR Code" />';
      echo '<p style="margin-top: 12px; font-size: 12px; color: #6b7280;">Scannez pour accéder au projet</p>';
    } else {
      echo '<p style="color: #d63638;">URL invalide pour la génération du QR code.</p>';
    }
    echo '</div>';
  } else {
    echo '<p style="padding: 12px; background: #fef3c7; border-left: 3px solid #fcd34d; border-radius: 4px; color: #92400e; font-size: 12px;">Enregistrez d\'abord le projet pour obtenir l\'URL externe et le QR Code.</p>';
  }
}

function custom_rest_url($url)
{
  return getenv('WP_HOME_ADMIN') . '/wp-json/';
}

add_filter('rest_url', 'custom_rest_url');

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
 * Fix the permalink/sample permalink shown in the editor for project posts.
 * This affects the clickable permalink displayed below the post title.
 */
add_filter('get_sample_permalink', function ($permalink, $post_id, $title, $name, $post) {
  // Only apply to project posts
  if (!$post || $post->post_type !== 'project' || empty($post->post_name)) {
    return $permalink;
  }

  // Build the frontend URL for the project
  $frontend_url = home_url('/');
  $project_url = trailingslashit($frontend_url) . 'projets/' . $post->post_name . '/';

  // Return array with [full_url, display_slug]
  return array($project_url, $post->post_name . '/');
}, 10, 5);
