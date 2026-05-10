<?php

add_filter('use_block_editor_for_post_type', 'prefix_disable_gutenberg', 10, 2);
function prefix_disable_gutenberg($current_status, $post_type)
{
  if ('post' === $post_type) return false;
  if ('project' === $post_type) return false;
  if ('mandat' === $post_type) return false;
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
