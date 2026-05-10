<?php

/**
 * Add a "Description" label above the editor content area for projects
 */
function eikon_add_description_label()
{
  $post = get_post();

  if (!$post || $post->post_type !== 'project') {
    return;
  }

  echo '<div style="margin-top: 16px; margin-bottom: 0; padding: 0;">';
  echo '<label style="display: block; font-weight: 600; font-size: 16px; margin-bottom: 4px;">Description du projet</label>';
  echo '<p style="margin: 0; font-size: 12px; color: #6b7280;">Décrivez votre projet en 50-100 mots.</p>';
  echo '</div>';
}
add_action('edit_form_after_title', 'eikon_add_description_label');

/**
 * Add Dashboard link to admin bar
 */
add_action('admin_bar_menu', 'eikon_add_dashboard_admin_bar', 11);
function eikon_add_dashboard_admin_bar($wp_admin_bar)
{
  if ($wp_admin_bar->get_node('dashboard')) {
    return;
  }

  $wp_admin_bar->add_node([
    'id' => 'dashboard',
    'title' => '<span class="dashicons dashicons-dashboard"></span> Tableau de bord',
    'href' => admin_url(),
    'meta' => ['class' => 'dashboard-link'],
  ]);
}

add_filter('manage_project_posts_columns', 'add_image_column');
function add_image_column($columns)
{
  $columns['image'] = __('Image');
  return $columns;
}

add_action('manage_project_posts_custom_column', 'add_image_column_content', 10, 2);
function add_image_column_content($column, $post_id)
{
  if ('image' === $column) {
    echo get_the_post_thumbnail($post_id, array(80, 80));
  }
}
