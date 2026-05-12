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

// ---------------------------------------------------------------------------
// Filtre par mandat dans le listing projet
// ---------------------------------------------------------------------------

add_action('restrict_manage_posts', 'eikon_project_filter_by_mandat');
function eikon_project_filter_by_mandat($post_type)
{
  if ('project' !== $post_type) {
    return;
  }

  $selected = sanitize_text_field($_GET['eikon_filter_mandat'] ?? '');

  $mandats = get_posts(array(
    'post_type'      => 'mandat',
    'post_status'    => array('publish', 'draft', 'pending', 'future', 'private'),
    'posts_per_page' => -1,
    'orderby'        => 'title',
    'order'          => 'ASC',
    'no_found_rows'  => true,
  ));

  echo '<select name="eikon_filter_mandat" id="eikon_filter_mandat">';
  echo '<option value="">' . esc_html__('Tous les mandats') . '</option>';
  echo '<option value="none"' . selected($selected, 'none', false) . '>' . esc_html__('Sans mandat') . '</option>';

  foreach ($mandats as $mandat) {
    if (!$mandat instanceof WP_Post) {
      continue;
    }
    $value = (string) $mandat->ID;

    $year_terms = wp_get_post_terms($mandat->ID, 'year', array('fields' => 'names'));
    $section_terms = wp_get_post_terms($mandat->ID, 'section', array('fields' => 'names'));
    $meta_parts = array();
    if (!empty($year_terms) && !is_wp_error($year_terms)) {
      $meta_parts[] = implode(', ', $year_terms);
    }
    if (!empty($section_terms) && !is_wp_error($section_terms)) {
      $meta_parts[] = implode(', ', $section_terms);
    }
    $label = get_the_title($mandat);
    if (!empty($meta_parts)) {
      $label .= ' (' . implode(', ', $meta_parts) . ')';
    }

    echo '<option value="' . esc_attr($value) . '"' . selected($selected, $value, false) . '>'
      . esc_html($label)
      . '</option>';
  }

  echo '</select>';
}

add_action('pre_get_posts', 'eikon_project_filter_by_mandat_query');
function eikon_project_filter_by_mandat_query($query)
{
  if (!is_admin() || !$query->is_main_query()) {
    return;
  }

  if ('project' !== $query->get('post_type')) {
    return;
  }

  $filter = sanitize_text_field($_GET['eikon_filter_mandat'] ?? '');
  if ('' === $filter) {
    return;
  }

  if ('none' === $filter) {
    $query->set('meta_query', array(
      array(
        'key'     => 'eikon_current_mandat_id',
        'compare' => 'NOT EXISTS',
      ),
    ));
    return;
  }

  $mandat_id = absint($filter);
  if ($mandat_id > 0) {
    $query->set('meta_query', array(
      array(
        'key'     => 'eikon_current_mandat_id',
        'value'   => (string) $mandat_id,
        'compare' => '=',
      ),
    ));
  }
}

// ---------------------------------------------------------------------------
// Colonnes du listing projet
// ---------------------------------------------------------------------------

add_filter('manage_project_posts_columns', 'add_image_column');
function add_image_column($columns)
{
  unset($columns['taxonomy-year'], $columns['taxonomy-section'], $columns['taxonomy-subjects']);

  $context = array('project_context' => __('Contexte'));
  $mandat = array('project_mandat' => __('Mandat'));

  if (isset($columns['date'])) {
    $date_column = $columns['date'];
    unset($columns['date']);
    $columns = array_merge($columns, $context, $mandat);
    $columns['date'] = $date_column;
  } else {
    $columns = array_merge($columns, $context, $mandat);
  }

  $columns['image'] = __('Image');
  return $columns;
}

add_action('manage_project_posts_custom_column', 'add_image_column_content', 10, 2);
function add_image_column_content($column, $post_id)
{
  if ('image' === $column) {
    echo get_the_post_thumbnail($post_id, array(80, 80));
    return;
  }

  if ('project_context' === $column) {
    $years = wp_get_post_terms($post_id, 'year', array('fields' => 'names'));
    $sections = wp_get_post_terms($post_id, 'section', array('fields' => 'names'));
    $subjects = wp_get_post_terms($post_id, 'subjects', array('fields' => 'names'));

    $lines = array();
    if (!empty($years) && !is_wp_error($years)) {
      $lines[] = '<strong>Année:</strong> ' . esc_html(implode(', ', $years));
    }
    if (!empty($sections) && !is_wp_error($sections)) {
      $lines[] = '<strong>Classe:</strong> ' . esc_html(implode(', ', $sections));
    }
    if (!empty($subjects) && !is_wp_error($subjects)) {
      $lines[] = '<strong>Branche:</strong> ' . esc_html(implode(', ', $subjects));
    }

    echo !empty($lines)
      ? '<div style="line-height:1.45; display:grid; gap:2px;">' . implode('<br>', $lines) . '</div>'
      : '<span style="color:#6b7280;">-</span>';
    return;
  }

  if ('project_mandat' === $column) {
    $mandat_id = (int) get_post_meta($post_id, 'eikon_current_mandat_id', true);
    if ($mandat_id <= 0) {
      echo '<span style="color:#6b7280;">-</span>';
      return;
    }

    $mandat = get_post($mandat_id);
    if (!$mandat instanceof WP_Post || 'mandat' !== $mandat->post_type) {
      echo '<span style="color:#6b7280;">-</span>';
      return;
    }

    $edit_link = get_edit_post_link($mandat_id, '');
    $title = get_the_title($mandat_id);

    $year_terms = wp_get_post_terms($mandat_id, 'year', array('fields' => 'names'));
    $section_terms = wp_get_post_terms($mandat_id, 'section', array('fields' => 'names'));
    $subject_terms = wp_get_post_terms($mandat_id, 'subjects', array('fields' => 'names'));
    $summary_parts = array();

    if (!empty($year_terms) && !is_wp_error($year_terms)) {
      $summary_parts[] = 'Année: ' . implode(', ', $year_terms);
    }
    if (!empty($section_terms) && !is_wp_error($section_terms)) {
      $summary_parts[] = 'Classe: ' . implode(', ', $section_terms);
    }
    if (!empty($subject_terms) && !is_wp_error($subject_terms)) {
      $summary_parts[] = 'Branche: ' . implode(', ', $subject_terms);
    }

    echo '<div style="line-height:1.45; display:grid; gap:2px;">';
    if (!empty($edit_link)) {
      echo '<a href="' . esc_url($edit_link) . '"><strong>' . esc_html($title) . '</strong></a>';
    } else {
      echo '<strong>' . esc_html($title) . '</strong>';
    }
    if (!empty($summary_parts)) {
      echo '<span style="color:#4b5563; font-size:12px;">' . esc_html(implode(' | ', $summary_parts)) . '</span>';
    }
    echo '</div>';
  }
}
