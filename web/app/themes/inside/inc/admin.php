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
  remove_meta_box('dashboard_right_now', 'dashboard', 'normal');

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
    'Statistiques Inside.eikon.ch',
    'eikon_stats_widget_content'
  );

  // Add random featured project widget
  wp_add_dashboard_widget(
    'eikon_random_project',
    'Projet aléatoire du jour',
    'eikon_random_project_widget_content'
  );
}

/**
 * Add dashicon styling to dashboard widget titles
 */
add_action('admin_print_styles', 'eikon_dashboard_dashicons_style');
function eikon_dashboard_dashicons_style()
{
  echo '<style>
    #eikon_stats .dashicons-chart-bar::before { content: "\f185"; }
    #eikon_random_project .dashicons-format-gallery::before { content: "\f145"; }
  </style>';
}

/**
 * Filter dashboard widget title to add dashicons
 */
add_filter('wp_dashboard_setup', 'eikon_add_dashicons_to_titles', 50);
function eikon_add_dashicons_to_titles()
{
  global $wp_dashboard_control_panel;

  // We'll add the dashicons via jQuery since we need to modify AFTER the widget is registered
  echo '<script>
    document.addEventListener("DOMContentLoaded", function() {
      // Stats widget
      var statsTitle = document.querySelector("#eikon_stats h2");
      if (statsTitle) {
        statsTitle.innerHTML = \'<span class="dashicons dashicons-chart-bar"></span> \' + statsTitle.innerText;
      }
      // Random project widget
      var projectTitle = document.querySelector("#eikon_random_project h2");
      if (projectTitle) {
        projectTitle.innerHTML = \'<span class="dashicons dashicons-format-gallery"></span> \' + projectTitle.innerText;
      }
    });
  </script>';
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
    $author = get_userdata($project->post_author);
    $year_terms = get_the_terms($project->ID, 'year');
    $section_terms = get_the_terms($project->ID, 'section');
    $subjects_terms = get_the_terms($project->ID, 'subjects');
  ?>
    <div style="padding: 0; overflow: hidden;">
      <!-- Featured Image with Overlay -->
      <?php if ($project_thumbnail) : ?>
        <div style="position: relative; margin: 0 0 16px 0; border-radius: 8px; overflow: hidden; aspect-ratio: 16/9; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
          <img src="<?php echo esc_url($project_thumbnail); ?>" alt="<?php echo esc_attr($project->post_title); ?>" style="width: 100%; height: 100%; object-fit: cover;">
          <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(180deg, rgba(0,0,0,0) 60%, rgba(0,0,0,0.3) 100%);"></div>
        </div>
      <?php endif; ?>

      <!-- Content Section -->
      <div style="padding: 0;">
        <!-- Title -->
        <h3 style="margin: 0 0 8px 0; font-size: 18px; font-weight: 600; color: #1f2937; line-height: 1.4;">
          <?php echo esc_html($project->post_title); ?>
        </h3>

        <!-- Author and Metadata -->
        <div style="margin-bottom: 12px;">
          <p style="margin: 6px 0; font-size: 12px; color: #6b7280; display: flex; align-items: center;">
            <span style="display: inline-block; margin-right: 4px;">👤</span>
            <?php echo esc_html($author->display_name); ?>
          </p>
        </div>

        <!-- Tags/Badges Row -->
        <div style="margin-bottom: 14px; display: flex; flex-wrap: wrap; gap: 6px;">
          <?php if ($year_terms) : ?>
            <span style="background: #f3f4f6; color: #374151; padding: 4px 10px; border-radius: 14px; font-size: 11px; font-weight: 500;">
              📅 <?php echo esc_html($year_terms[0]->name); ?>
            </span>
          <?php endif; ?>

          <?php if ($section_terms) : ?>
            <span style="background: #ede9fe; color: #6d28d9; padding: 4px 10px; border-radius: 14px; font-size: 11px; font-weight: 500;">
              🎓 <?php echo esc_html($section_terms[0]->name); ?>
            </span>
          <?php endif; ?>

          <?php if ($subjects_terms) : ?>
            <span style="background: #fce7f3; color: #831843; padding: 4px 10px; border-radius: 14px; font-size: 11px; font-weight: 500;">
              🎨 <?php echo esc_html($subjects_terms[0]->name); ?>
            </span>
          <?php endif; ?>
        </div>

        <!-- CTA Button -->
        <a href="<?php echo esc_url($project_url); ?>" target="_blank" rel="noopener noreferrer" style="display: flex; align-items: center; justify-content: center; width: 100%; padding: 10px 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px; border: none; cursor: pointer; box-shadow: 0 2px 6px rgba(102, 126, 234, 0.3); transition: all 0.2s ease; box-sizing: border-box;">
          Découvrir le projet <span style="margin-left: 6px;">→</span>
        </a>
      </div>
    </div>
  <?php
    wp_reset_postdata();
  } else {
  ?>
    <div style="padding: 24px; text-align: center; color: #6b7280;">
      <div style="font-size: 40px; margin-bottom: 8px;">🎨</div>
      <p style="margin: 0 0 4px 0; font-weight: 600; color: #374151;">Aucun projet publié</p>
      <p style="margin: 0; font-size: 13px;">Les projets apparaîtront ici</p>
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

/**
 * Add "Classe" column to the users list table
 */
add_filter('manage_users_columns', 'eikon_add_classe_column');
function eikon_add_classe_column($columns)
{
  $columns['classe'] = __('Classe');
  return $columns;
}

add_filter('manage_users_custom_column', 'eikon_show_classe_column', 10, 3);
function eikon_show_classe_column($value, $column_name, $user_id)
{
  if ('classe' === $column_name) {
    $classe = get_user_meta($user_id, 'classe', true);
    return $classe ? esc_html($classe) : '—';
  }
  return $value;
}

/**
 * Add a "Classe" filter dropdown above the users list table
 */
add_action('views_users', 'eikon_add_classe_filter_links');
function eikon_add_classe_filter_links($views)
{
  $role = isset($_GET['role']) ? sanitize_text_field($_GET['role']) : '';

  if ('student' !== $role) {
    return $views;
  }

  $classes = ['imd11', 'imd12', 'imd21', 'imd31', 'imd32', 'mp2', 'prepa'];
  $selected = isset($_GET['classe_filter']) ? sanitize_text_field($_GET['classe_filter']) : '';

  // Build classe links HTML
  $classe_links = [];

  // "Tous" link
  $all_url = add_query_arg(['role' => 'student'], admin_url('users.php'));
  $all_count = count(get_users(['role' => 'student', 'fields' => 'ID']));
  $all_class = empty($selected) ? 'current' : '';
  $classe_links[] = sprintf(
    '<a href="%s" class="%s">Toutes les classes <span class="count">(%d)</span></a>',
    esc_url($all_url),
    esc_attr($all_class),
    $all_count
  );

  foreach ($classes as $classe) {
    $count = count(get_users([
      'role'       => 'student',
      'meta_key'   => 'classe',
      'meta_value' => $classe,
      'fields'     => 'ID',
    ]));
    if ($count === 0) {
      continue;
    }
    $url = add_query_arg(['role' => 'student', 'classe_filter' => $classe], admin_url('users.php'));
    $css_class = ($selected === $classe) ? 'current' : '';
    $classe_links[] = sprintf(
      '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
      esc_url($url),
      esc_attr($css_class),
      esc_html($classe),
      $count
    );
  }

  // Append classe links as extra HTML inside the last view item
  $last_key = array_key_last($views);
  $views[$last_key] .= '</ul><ul class="subsubsub" style="clear:both; width:100%;">'
    . implode(' | ', $classe_links);

  return $views;
}

/**
 * Filter the users list by "Classe" ACF field
 */
add_filter('pre_get_users', 'eikon_filter_users_by_classe');
function eikon_filter_users_by_classe($query)
{
  global $pagenow;

  if (!is_admin() || 'users.php' !== $pagenow) {
    return;
  }

  if (empty($_GET['classe_filter'])) {
    return;
  }

  $classe = sanitize_text_field($_GET['classe_filter']);

  $meta_query = $query->get('meta_query') ?: [];
  $meta_query[] = [
    'key'     => 'classe',
    'value'   => $classe,
    'compare' => '=',
  ];
  $query->set('meta_query', $meta_query);
}
