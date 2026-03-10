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
  remove_meta_box('dashboard_site_activity', 'dashboard', 'normal');
  remove_meta_box('dashboard_glance', 'dashboard', 'normal');

  // Remove widgets from plugins (common ones)
  remove_meta_box('events-widget', 'dashboard', 'normal');

  // Add custom documentation widget
  wp_add_dashboard_widget(
    'eikon_documentation',
    'Documentation Inside.eikon.ch',
    'eikon_documentation_widget_content'
  );

  // Add fun stats widget
  wp_add_dashboard_widget(
    'eikon_stats',
    '📊 Statistiques Inside.eikon.ch',
    'eikon_stats_widget_content'
  );

  // Add random featured project widget
  wp_add_dashboard_widget(
    'eikon_random_project',
    '🎨 Projet aléatoire du jour',
    'eikon_random_project_widget_content'
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

/**
 * Display fun statistics widget
 */
function eikon_stats_widget_content()
{
  $total_projects = wp_count_posts('project')->publish + wp_count_posts('project')->draft + wp_count_posts('project')->pending;
  $total_users = count_users();
  $total_users_count = $total_users['total_users'];

  // Count by user role
  $teacher_count = count(get_users(['role' => 'teacher']));
  $student_count = count(get_users(['role' => 'student']));

  // Get published projects count
  $published_projects = wp_count_posts('project')->publish;
  $draft_projects = wp_count_posts('project')->draft;

?>
  <div style="padding: 0;">
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">

      <!-- Total Projects -->
      <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 16px; border-radius: 8px; color: white; text-align: center;">
        <div style="font-size: 32px; font-weight: bold;">
          <?php echo esc_html($total_projects); ?>
        </div>
        <div style="font-size: 12px; opacity: 0.9; margin-top: 4px;">
          Projets totaux
        </div>
      </div>

      <!-- Published Projects -->
      <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 16px; border-radius: 8px; color: white; text-align: center;">
        <div style="font-size: 32px; font-weight: bold;">
          <?php echo esc_html($published_projects); ?>
        </div>
        <div style="font-size: 12px; opacity: 0.9; margin-top: 4px;">
          Projets publiés
        </div>
      </div>

      <!-- Draft Projects -->
      <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); padding: 16px; border-radius: 8px; color: white; text-align: center;">
        <div style="font-size: 32px; font-weight: bold;">
          <?php echo esc_html($draft_projects); ?>
        </div>
        <div style="font-size: 12px; opacity: 0.9; margin-top: 4px;">
          En brouillon
        </div>
      </div>

      <!-- Total Users -->
      <div style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); padding: 16px; border-radius: 8px; color: white; text-align: center;">
        <div style="font-size: 32px; font-weight: bold;">
          <?php echo esc_html($total_users_count); ?>
        </div>
        <div style="font-size: 12px; opacity: 0.9; margin-top: 4px;">
          Utilisateurs
        </div>
      </div>

      <!-- Teachers -->
      <div style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); padding: 16px; border-radius: 8px; color: white; text-align: center;">
        <div style="font-size: 32px; font-weight: bold;">
          <?php echo esc_html($teacher_count); ?>
        </div>
        <div style="font-size: 12px; opacity: 0.9; margin-top: 4px;">
          Enseignants
        </div>
      </div>

      <!-- Students -->
      <div style="background: linear-gradient(135deg, #30b0fe 0%, #4834d4 100%); padding: 16px; border-radius: 8px; color: white; text-align: center;">
        <div style="font-size: 32px; font-weight: bold;">
          <?php echo esc_html($student_count); ?>
        </div>
        <div style="font-size: 12px; opacity: 0.9; margin-top: 4px;">
          Étudiants
        </div>
      </div>

    </div>

    <p style="text-align: center; font-size: 12px; color: #666; margin: 0;">
      ✨ Bravo pour votre engagement dans Inside Eikon !
    </p>
  </div>
  <?php
}

/**
 * Display random featured project widget
 */
function eikon_random_project_widget_content()
{
  // Get a random published project
  $args = [
    'post_type' => 'project',
    'posts_per_page' => 1,
    'post_status' => 'publish',
    'orderby' => 'rand',
  ];

  $query = new WP_Query($args);

  if ($query->have_posts()) {
    $query->the_post();
    $project = get_post();
    $project_url = get_permalink($project->ID);
    $project_thumbnail = get_the_post_thumbnail_url($project->ID, 'medium');
  ?>
    <div style="padding: 0;">
      <?php if ($project_thumbnail) : ?>
        <div style="margin-bottom: 12px; border-radius: 8px; overflow: hidden; aspect-ratio: 16/9;">
          <img src="<?php echo esc_url($project_thumbnail); ?>" alt="<?php echo esc_attr($project->post_title); ?>" style="width: 100%; height: 100%; object-fit: cover;">
        </div>
      <?php endif; ?>

      <h3 style="margin: 12px 0; font-size: 16px; color: #1f2937;">
        <?php echo esc_html($project->post_title); ?>
      </h3>

      <?php
      // Get authorship info
      $author = get_userdata($project->post_author);
      $year_terms = get_the_terms($project->ID, 'year');
      $section_terms = get_the_terms($project->ID, 'section');
      ?>

      <p style="margin: 8px 0; font-size: 13px; color: #666;">
        <strong>Par :</strong> <?php echo esc_html($author->display_name); ?>
      </p>

      <?php if ($year_terms) : ?>
        <p style="margin: 8px 0; font-size: 13px; color: #666;">
          <strong>Année :</strong> <?php echo esc_html($year_terms[0]->name); ?>
        </p>
      <?php endif; ?>

      <?php if ($section_terms) : ?>
        <p style="margin: 8px 0; font-size: 13px; color: #666;">
          <strong>Classe :</strong> <?php echo esc_html($section_terms[0]->name); ?>
        </p>
      <?php endif; ?>

      <a href="<?php echo esc_url($project_url); ?>" target="_blank" rel="noopener noreferrer" class="button button-primary" style="width: 100%; text-align: center; box-sizing: border-box; margin-top: 12px;">
        Découvrir le projet →
      </a>
    </div>
  <?php
    wp_reset_postdata();
  } else {
  ?>
    <div style="padding: 0; text-align: center; color: #666;">
      <p>Aucun projet publié pour le moment.</p>
      <p style="font-size: 12px;">Revenez bientôt ! 🎨</p>
    </div>
<?php
  }
}

/**
 * Add Dashboard link to admin bar
 */
add_action('admin_bar_menu', 'eikon_add_dashboard_admin_bar', 11);
function eikon_add_dashboard_admin_bar($wp_admin_bar)
{
  // Only add if not already there
  if ($wp_admin_bar->get_node('dashboard')) {
    return;
  }

  $wp_admin_bar->add_node([
    'id' => 'dashboard',
    'title' => '📊 Tableau de bord',
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
