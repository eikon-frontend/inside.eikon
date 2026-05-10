<?php

// Set display_name to "Firstname Lastname" on profile save
add_action('profile_update', 'eikon_set_display_name');
add_action('user_register', 'eikon_set_display_name');

function eikon_set_display_name($user_id)
{
  $user = get_userdata($user_id);
  if (!$user || empty($user->first_name)) {
    return;
  }

  $display = trim($user->first_name . ' ' . $user->last_name);
  if ($display !== $user->display_name) {
    wp_update_user([
      'ID' => $user_id,
      'display_name' => $display,
    ]);
  }
}

// Removes the comment from the admin menu
add_action('admin_init', 'my_remove_admin_menus');

function my_remove_admin_menus()
{
  remove_menu_page('edit-comments.php');
}
