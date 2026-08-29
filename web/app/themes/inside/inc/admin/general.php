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

// Disable screen options for non-admins
add_filter('screen_options_show_screen', function ($show_screen) {
  if (!current_user_can('manage_options')) {
    return false;
  }
  return $show_screen;
});

// Add custom CSS to admin to disable metabox moving for non-admins
add_action('admin_print_styles', function () {
  if (!current_user_can('manage_options')) {
    echo '<style>
      .meta-box-sortables .hndle { pointer-events: none; cursor: default; }
      .meta-box-sortables .postbox-header { pointer-events: none; }
      .meta-box-sortables .postbox-header .handle-actions { display: none; }
    </style>';
  }
});

// Register Eikon admin color scheme and remove others
add_action('admin_init', function () {
  global $_wp_admin_css_colors;

  wp_admin_css_color(
    'eikon',
    'Eikon',
    get_template_directory_uri() . '/css/admin-eikon-colors.css',
    array('#0000DE', '#FF2C00', '#FF5F1C', '#000000')
  );

  // Remove all other color schemes
  if (isset($_wp_admin_css_colors) && is_array($_wp_admin_css_colors)) {
    foreach ($_wp_admin_css_colors as $color => $info) {
      if ($color !== 'eikon') {
        unset($_wp_admin_css_colors[$color]);
      }
    }
  }
});

// Force Eikon color scheme for all users
add_filter('get_user_option_admin_color', function ($color_scheme) {
  return 'eikon';
});
