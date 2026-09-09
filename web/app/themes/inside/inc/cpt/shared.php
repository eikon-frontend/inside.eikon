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
        global $wpdb;
        $count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(ID) FROM $wpdb->posts WHERE post_type = 'project' AND post_author = %d",
            $id
        ));
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
 * Register Custom Post Status "Archive"
 */
function eikon_register_archive_post_status()
{
    register_post_status('archive', array(
        'label'                     => _x('Archivé', 'post status', 'inside'),
        'public'                    => false, // Pas affiché publiquement (ni dans GraphQL par défaut)
        'exclude_from_search'       => true,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Archivé <span class="count">(%s)</span>', 'Archivés <span class="count">(%s)</span>'),
        'show_in_graphql'           => false, // Exclure de GraphQL
    ));
}
add_action('init', 'eikon_register_archive_post_status');

/**
 * Add "Archive" status to the classic editor dropdown
 */
function eikon_append_archive_post_status()
{
    global $post;

    // Seulement pour les types de posts où l'éditeur classique est utilisé et qu'on veut archiver
    if (! in_array($post->post_type, array( 'project', 'post' ))) {
        return;
    }

    $label = _x('Archivé', 'post status', 'inside');
    $is_archive = ( $post->post_status === 'archive' ) ? 'true' : 'false';

    ?>
    <script>
    jQuery(document).ready(function($){
        var is_archive = <?php echo $is_archive; ?>;
        var label = '<?php echo esc_js($label); ?>';
        
        // Ajouter l'option dans le menu déroulant
        if ($('select#post_status').length > 0) {
            $('select#post_status').append('<option value="archive"' + (is_archive ? ' selected="selected"' : '') + '>' + label + '</option>');
        }
        
        // Mettre à jour le texte affiché si l'article est actuellement archivé
        if (is_archive) {
            $('#post-status-display').text(label);
        }
    });
    </script>
    <?php
}
add_action('admin_footer-post.php', 'eikon_append_archive_post_status');
add_action('admin_footer-post-new.php', 'eikon_append_archive_post_status');

/**
 * Add "Archive" status to Quick Edit
 */
function eikon_append_archive_post_status_quick_edit()
{
    global $post_type;

    // Seulement pour project et post (désactivé pour mandat)
    if (! in_array($post_type, array( 'project', 'post' ))) {
        return;
    }

    $label = _x('Archivé', 'post status', 'inside');

    ?>
    <script>
    jQuery(document).ready(function($){
        var label = '<?php echo esc_js($label); ?>';
        // Add to Quick Edit dropdown if not already present
        if ($('select[name="_status"] option[value="archive"]').length === 0) {
            $('select[name="_status"]').append('<option value="archive">' + label + '</option>');
        }
    });
    </script>
    <?php
}
add_action('admin_footer-edit.php', 'eikon_append_archive_post_status_quick_edit');
