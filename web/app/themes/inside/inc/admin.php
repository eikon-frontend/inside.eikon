<?php

// Removes the comment from the admin menu
add_action('admin_init', 'my_remove_admin_menus');

function my_remove_admin_menus()
{
  remove_menu_page('edit-comments.php');
}

// Clean up dashboard widgets and add custom documentation widget
add_action('wp_dashboard_setup', 'eikon_customize_dashboard');

function eikon_customize_dashboard()
{
  // Remove default WordPress dashboard widgets
  remove_meta_box('dashboard_draft', 'dashboard', 'side');
  remove_meta_box('dashboard_activity', 'dashboard', 'normal');
  remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
  remove_meta_box('dashboard_primary', 'dashboard', 'side');
  remove_meta_box('dashboard_secondary', 'dashboard', 'normal');

  // Remove widgets from plugins (common ones)
  remove_meta_box('events-widget', 'dashboard', 'normal');

  // Add custom documentation widget
  wp_add_dashboard_widget(
    'eikon_documentation',
    'Documentation Inside.eikon.ch',
    'eikon_documentation_widget_content'
  );
}

/**
 * Display documentation widget with role-based URL
 */
function eikon_documentation_widget_content()
{
  $current_user = wp_get_current_user();
  $user_roles = $current_user->roles;

  // Determine URL based on user role
  if (in_array('student', $user_roles, true)) {
    $doc_url = 'https://eikon-imd.notion.site/inside-eikon-ch-student';
    $role_text = 'pour étudiants';
  } else {
    $doc_url = 'https://eikon-imd.notion.site';
    $role_text = 'pour enseignants';
  }
  ?>
  <div style="padding: 0;">
    <p style="margin-top: 0;">
      Accédez à la documentation complète <strong><?php echo esc_html($role_text); ?></strong> :
    </p>
    <p style="margin-bottom: 0;">
      <a href="<?php echo esc_url($doc_url); ?>" target="_blank" rel="noopener noreferrer" class="button button-primary" style="width: 100%; text-align: center; box-sizing: border-box;">
        Ouvrir la documentation
      </a>
    </p>
  </div>
  <?php
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
    $errors->add('invalid_email', __("Seule l'adresse e-mail « edufr.ch » est autorisée."));
    $user_email = '';
  }
  return $errors;
}
