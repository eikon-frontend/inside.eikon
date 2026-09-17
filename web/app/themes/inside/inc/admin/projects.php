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
    echo '<option value="">' . esc_html__('Tous les projets') . '</option>';
    echo '<option value="none"' . selected($selected, 'none', false) . '>' . esc_html__('Sans mandat') . '</option>';

    foreach ($mandats as $mandat) {
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

// Legal declarations UI and JS validation have been moved to workflow.php
// Server-side validation, save hooks and admin notices remain below.

// Server-side guard: demote to pending if fields are missing on publish.
add_filter('wp_insert_post_data', 'eikon_project_legal_validate_publish', 10, 2);
function eikon_project_legal_validate_publish(array $data, array $postarr): array
{
    if ('project' !== $data['post_type'] || 'publish' !== $data['post_status']) {
        return $data;
    }

  // Skip programmatic saves (WP-CLI, REST, imports) that have no form nonce.
    if (
        !isset($_POST['eikon_project_legal_nonce']) ||
        !wp_verify_nonce(
            sanitize_text_field(wp_unslash($_POST['eikon_project_legal_nonce'])),
            'eikon_project_legal_save'
        )
    ) {
        return $data;
    }

    $post_id = absint($postarr['ID'] ?? 0);

  // Fall back to existing meta so already-published posts aren't blocked on update.
    $ai = isset($_POST['eikon_contains_ai_content'])
    ? sanitize_text_field(wp_unslash($_POST['eikon_contains_ai_content']))
    : get_post_meta($post_id, 'eikon_contains_ai_content', true);

    $copyright = isset($_POST['eikon_copyright_cleared'])
    ? sanitize_text_field(wp_unslash($_POST['eikon_copyright_cleared']))
    : get_post_meta($post_id, 'eikon_copyright_cleared', true);

    if (!in_array($ai, array('oui', 'non'), true) || !in_array($copyright, array('oui', 'non'), true)) {
        $data['post_status'] = 'pending';
        set_transient('eikon_legal_error_' . get_current_user_id(), true, 60);
    }

    return $data;
}

add_action('admin_notices', 'eikon_project_legal_validation_notice');
function eikon_project_legal_validation_notice()
{
    $screen = get_current_screen();
    if (!$screen || 'project' !== $screen->post_type) {
        return;
    }

    if (!get_transient('eikon_legal_error_' . get_current_user_id())) {
        return;
    }

    delete_transient('eikon_legal_error_' . get_current_user_id());
    echo '<div class="notice notice-error is-dismissible"><p>';
    echo '<strong>Publication impossible&nbsp;:</strong> Vous devez répondre aux deux déclarations légales avant de publier ce projet.';
    echo '</p></div>';
}

add_action('save_post_project', 'eikon_project_legal_save_meta', 10, 2);
function eikon_project_legal_save_meta(int $post_id, WP_Post $post)
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (
        !isset($_POST['eikon_project_legal_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['eikon_project_legal_nonce'])), 'eikon_project_legal_save')
    ) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $allowed = array('oui', 'non', '');

    $ai_value = isset($_POST['eikon_contains_ai_content'])
    ? sanitize_text_field(wp_unslash($_POST['eikon_contains_ai_content']))
    : '';
    if (!in_array($ai_value, $allowed, true)) {
        $ai_value = '';
    }

    $copyright_value = isset($_POST['eikon_copyright_cleared'])
    ? sanitize_text_field(wp_unslash($_POST['eikon_copyright_cleared']))
    : '';
    if (!in_array($copyright_value, $allowed, true)) {
        $copyright_value = '';
    }

    if ('' === $ai_value) {
        delete_post_meta($post_id, 'eikon_contains_ai_content');
    } else {
        update_post_meta($post_id, 'eikon_contains_ai_content', $ai_value);
    }

    if ('' === $copyright_value) {
        delete_post_meta($post_id, 'eikon_copyright_cleared');
    } else {
        update_post_meta($post_id, 'eikon_copyright_cleared', $copyright_value);
    }
}

// --- Migration: eikon_contains_copyright_content → eikon_copyright_cleared (one-shot, inverted) ---
add_action('init', 'eikon_migrate_copyright_meta');
function eikon_migrate_copyright_meta()
{
    if (get_option('eikon_copyright_migrated')) {
        return;
    }

    $projects = get_posts([
        'post_type'      => 'project',
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'meta_key'       => 'eikon_contains_copyright_content',
        'fields'         => 'ids',
    ]);

    foreach ($projects as $pid) {
        $old = get_post_meta($pid, 'eikon_contains_copyright_content', true);
        if ('' === $old) {
            continue;
        }
        // Invert: old "oui" (= problem) → new "non" (= not cleared)
        //         old "non" (= OK)      → new "oui" (= cleared)
        $new_value = ('oui' === $old) ? 'non' : 'oui';
        update_post_meta($pid, 'eikon_copyright_cleared', $new_value);
        delete_post_meta($pid, 'eikon_contains_copyright_content');
    }

    update_option('eikon_copyright_migrated', true);
}
