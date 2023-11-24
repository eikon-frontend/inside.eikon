<?php

// Removes the comment from the admin menu
add_action('admin_init', 'my_remove_admin_menus');

function my_remove_admin_menus()
{
  remove_menu_page('edit-comments.php');
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
  // Image column
  if ('image' === $column) {
    echo get_the_post_thumbnail($post_id, array(80, 80));
  }
}
