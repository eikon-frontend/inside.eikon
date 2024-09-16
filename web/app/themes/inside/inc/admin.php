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

/**
 * Custom function to validate user registration email addresses to restrict to edufr.ch
 *
 * @param WP_Error $errors An object containing any errors encountered during registration.
 * @param string $sanitized_user_login The sanitized username.
 * @param string $user_email The user's email address.
 * @return WP_Error The updated error object.
 */
add_filter('registration_errors', 'myplugin_registration_errors', 10, 3);
function myplugin_registration_errors($errors, $sanitized_user_login, $user_email)
{
  if (! preg_match('/( |^)[^ ]+@edufr\.ch( |$)/', $user_email)) {
    $errors->add('invalid_email', __('ERROR: Only valid "mydomain" email address is allowed.'));
    $user_email = '';
  }
  return $errors;
}
