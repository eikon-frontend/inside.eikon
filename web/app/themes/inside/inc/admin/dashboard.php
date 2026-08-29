<?php

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
    'Accédez à la documentation sur Notion:',
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
 * Add custom styling to dashboard widgets
 */
add_action('admin_print_styles', 'eikon_dashboard_custom_style');
function eikon_dashboard_custom_style()
{
  $font_url = get_template_directory_uri() . '/assets/fonts/NoiGrotesk-Medium.woff2';
  echo '<style>
    @font-face {
      font-family: "NoiGrotesk";
      src: url("' . esc_url($font_url) . '") format("woff2");
      font-weight: 500;
      font-style: normal;
    }
    
    #dashboard-widgets .postbox h2 {
      font-family: "NoiGrotesk", sans-serif;
    }

    #eikon_stats .dashicons-chart-bar::before { content: "\f185"; }
    #eikon_random_project .dashicons-format-gallery::before { content: "\f145"; }

    .eikon-dashboard-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 10px 20px;
      font-family: "NoiGrotesk", sans-serif;
      font-size: 16px;
      text-decoration: none;
      border-radius: 9999px;
      transition: all 0.2s ease;
      background: transparent;
      box-sizing: border-box;
      width: 100%;
    }
    
    .eikon-dashboard-btn.outline-blue {
      border: 2px solid #0000ff;
      color: #0000ff;
    }
    
    .eikon-dashboard-btn.outline-blue:hover {
      background: #0000ff;
      color: white;
    }
    
    .eikon-dashboard-btn.outline-white {
      border: 2px solid white;
      color: white;
    }
    
    .eikon-dashboard-btn.outline-white:hover {
      background: white;
      color: black;
    }
    
    .eikon-stat-card {
      padding: 20px;
      color: white;
      font-family: "NoiGrotesk", sans-serif;
    }
    
    .eikon-stat-number {
      font-size: 48px;
      line-height: 1;
      margin-bottom: 8px;
    }
    
    .eikon-stat-label {
      font-size: 18px;
    }
  </style>';
}

/**
 * Filter dashboard widget title to add dashicons
 */
add_filter('wp_dashboard_setup', 'eikon_add_dashicons_to_titles', 50);
function eikon_add_dashicons_to_titles()
{
  // We'll add the dashicons via JavaScript since we need to modify AFTER the widget is registered
  echo '<script>
    document.addEventListener("DOMContentLoaded", function() {
      var statsTitle = document.querySelector("#eikon_stats h2");
      if (statsTitle) {
        statsTitle.innerHTML = \'<span class="dashicons dashicons-chart-bar"></span> \' + statsTitle.innerText;
      }
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
?>
  <div style="font-family: 'NoiGrotesk', sans-serif; text-align: center;">
    <p style="margin-bottom: 0;">
      <a href="https://eikon-imd.notion.site/eikon-ch" target="_blank" rel="noopener noreferrer" class="eikon-dashboard-btn outline-blue">
        Guide d'utilisation inside.eikon &rarr;
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

  $teacher_count = count(get_users(['role' => 'teacher']));
  $student_count = count(get_users(['role' => 'student']));

  $published_projects = wp_count_posts('project')->publish;
  $draft_projects = wp_count_posts('project')->draft;

?>
  <div style="padding: 0;">
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0;">
      <div class="eikon-stat-card" style="background: #9700ff;">
        <div class="eikon-stat-number">
          <?php echo esc_html($total_projects); ?>
        </div>
        <div class="eikon-stat-label">
          Projets totaux
        </div>
      </div>

      <div class="eikon-stat-card" style="background: #ff3a00;">
        <div class="eikon-stat-number">
          <?php echo esc_html($published_projects); ?>
        </div>
        <div class="eikon-stat-label">
          Projets publiés
        </div>
      </div>

      <div class="eikon-stat-card" style="background: #ff6a13;">
        <div class="eikon-stat-number">
          <?php echo esc_html($draft_projects); ?>
        </div>
        <div class="eikon-stat-label">
          En brouillon
        </div>
      </div>

      <div class="eikon-stat-card" style="background: #0000ff;">
        <div class="eikon-stat-number">
          <?php echo esc_html($total_users_count); ?>
        </div>
        <div class="eikon-stat-label">
          Utilisateurs
        </div>
      </div>

      <div class="eikon-stat-card" style="background: #ff007b;">
        <div class="eikon-stat-number">
          <?php echo esc_html($teacher_count); ?>
        </div>
        <div class="eikon-stat-label">
          Enseignants
        </div>
      </div>

      <div class="eikon-stat-card" style="background: #00c3ff;">
        <div class="eikon-stat-number">
          <?php echo esc_html($student_count); ?>
        </div>
        <div class="eikon-stat-label">
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
    <div style="padding: 0; overflow: hidden; font-family: 'NoiGrotesk', sans-serif; background: #9700ff; color: white;">
      <?php if ($project_thumbnail) : ?>
        <div style="position: relative; overflow: hidden; aspect-ratio: 16/9;">
          <img src="<?php echo esc_url($project_thumbnail); ?>" alt="<?php echo esc_attr($project->post_title); ?>" style="width: 100%; height: 100%; object-fit: cover; display: block;">
        </div>
      <?php endif; ?>

      <div style="padding: 24px;">
        <h3 style="margin: 0 0 8px 0; font-size: 24px; font-weight: 500; color: white; line-height: 1.2;">
          <?php echo esc_html($project->post_title); ?>
        </h3>

        <div style="margin-bottom: 12px;">
          <p style="margin: 6px 0; font-size: 14px; display: flex; align-items: center;">
            <?php echo esc_html($author->display_name); ?>
          </p>
        </div>

        <div style="margin-bottom: 24px; display: flex; flex-wrap: wrap; gap: 8px;">
          <?php if ($year_terms) : ?>
            <span style="border: 1px solid rgba(255,255,255,0.3); padding: 4px 12px; border-radius: 9999px; font-size: 12px;">
              <?php echo esc_html($year_terms[0]->name); ?>
            </span>
          <?php endif; ?>

          <?php if ($section_terms) : ?>
            <span style="border: 1px solid rgba(255,255,255,0.3); padding: 4px 12px; border-radius: 9999px; font-size: 12px;">
              <?php echo esc_html($section_terms[0]->name); ?>
            </span>
          <?php endif; ?>

          <?php if ($subjects_terms) : ?>
            <span style="border: 1px solid rgba(255,255,255,0.3); padding: 4px 12px; border-radius: 9999px; font-size: 12px;">
              <?php echo esc_html($subjects_terms[0]->name); ?>
            </span>
          <?php endif; ?>
        </div>

        <a href="<?php echo esc_url($project_url); ?>" target="_blank" rel="noopener noreferrer" class="eikon-dashboard-btn outline-white">
          Découvrir le projet &rarr;
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
