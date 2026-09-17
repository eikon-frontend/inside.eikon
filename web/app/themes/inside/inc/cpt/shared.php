<?php

add_filter('use_block_editor_for_post_type', 'prefix_disable_gutenberg', 10, 2);
function prefix_disable_gutenberg($current_status, $post_type)
{
    if ('post' === $post_type) {
        return false;
    }
    if ('project' === $post_type) {
        return false;
    }
    if ('mandat' === $post_type) {
        return false;
    }
    return $current_status;
}

add_theme_support('post-thumbnails');

/*
 * Add Event Column
 */
function users_projects_column($cols)
{
    $cols['user_projects'] = 'Projets';
    return $cols;
}

/*
 * Print Event Column Value
 */
function user_projects_column_value($value, $column_name, $id)
{
    if ($column_name == 'user_projects') {
        static $project_counts = null;
        if ($project_counts === null) {
            global $wpdb;
            $results = $wpdb->get_results(
                "SELECT post_author, COUNT(ID) as count FROM {$wpdb->posts} WHERE post_type = 'project' GROUP BY post_author",
                OBJECT_K
            );
            $project_counts = array_map(function ($r) {
                return $r->count;
            }, $results);
        }

        $count = isset($project_counts[$id]) ? (int) $project_counts[$id] : 0;

        if ($count > 0) {
            $url = admin_url('edit.php?post_type=project&author=' . $id);
            return sprintf('<a href="%s">%d</a>', esc_url($url), $count);
        }
        return $count;
    }
    return $value;
}

add_filter('manage_users_custom_column', 'user_projects_column_value', 10, 3);
add_filter('manage_users_columns', 'users_projects_column');

function modify_page_post_type_args($args, $post_type)
{
    if ($post_type === 'page') {
        $args['hierarchical'] = false;
        $args['labels'] = array(
        'name'                  => 'Pages',
        'singular_name'         => 'Page',
        'menu_name'             => 'Pages',
        'name_admin_bar'        => 'Page',
        'add_new'               => 'Nouvelle page',
        'add_new_item'          => 'Nouvelle page',
        'new_item'              => 'Nouvelle page',
        'edit_item'             => 'Éditer la page',
        'view_item'             => 'Voir la page',
        'all_items'             => 'Toutes les pages',
        'search_items'          => 'Rechercher des pages',
        'parent_item_colon'     => 'Page Parente:',
        'not_found'             => 'Aucune page trouvée.',
        'not_found_in_trash'    => 'Aucune page trouvée dans la Corbeille.',
        'archives'              => 'Archives des pages',
        'insert_into_item'      => 'Insérer dans la page',
        'uploaded_to_this_item' => 'Téléversé dans cette page',
        'filter_items_list'     => 'Filtrer la liste des pages',
        'items_list_navigation' => 'Navigation de la liste des pages',
        'items_list'            => 'Liste des pages',
        );
    }
    return $args;
}
add_filter('register_post_type_args', 'modify_page_post_type_args', 10, 2);

// Remove tags support from posts
function eikon_unregister_tags()
{
    unregister_taxonomy_for_object_type('post_tag', 'post');
}
add_action('init', 'eikon_unregister_tags');

function set_default_page_order($query)
{
    if (is_admin() && $query->is_main_query() && $query->get('post_type') == 'page') {
        if (!isset($_GET['orderby'])) {
            $query->set('orderby', 'title');
            $query->set('order', 'ASC');
        }
    }
}
add_action('pre_get_posts', 'set_default_page_order');

/**
 * Register Custom Post Statuses for the workflow
 *
 * - "open" : Mandat is available for students to link their projects
 * - "in_review" : Mandat is closed, teacher reviews highlights
 * - "archive" : Content is archived, no longer visible publicly
 */
function eikon_register_custom_post_statuses()
{
    // Ouvert — Mandat is available for students
    register_post_status('open', array(
        'label'                     => _x('Ouvert', 'post status', 'inside'),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Ouvert <span class="count">(%s)</span>', 'Ouverts <span class="count">(%s)</span>'),
    ));

    // En relecture — Mandat is closed, teacher reviews
    register_post_status('in_review', array(
        'label'                     => _x('En relecture', 'post status', 'inside'),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('En relecture <span class="count">(%s)</span>', 'En relecture <span class="count">(%s)</span>'),
    ));

    // Archivé — Content is archived
    register_post_status('archive', array(
        'label'                     => _x('Archivé', 'post status', 'inside'),
        'public'                    => false,
        'exclude_from_search'       => true,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Archivé <span class="count">(%s)</span>', 'Archivés <span class="count">(%s)</span>'),
    ));
}
add_action('init', 'eikon_register_custom_post_statuses');
