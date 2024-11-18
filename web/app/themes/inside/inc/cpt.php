<?php

// Register Custom Post Type PROJECT and his taxonomies
function project_post_type()
{
  $labels = array(
    'name'                  => _x('Projets', 'Post Type General Name', 'project'),
    'singular_name'         => _x('Projet', 'Post Type Singular Name', 'project'),
    'menu_name'             => __('Projets', 'project'),
    'name_admin_bar'        => __('Projet', 'project'),
    'archives'              => __('Archive des projets', 'project'),
    'attributes'            => __('Attributs', 'project'),
    'parent_item_colon'     => __('Projet parent', 'project'),
    'all_items'             => __('Tous les projets', 'project'),
    'add_new_item'          => __('Ajouter projet', 'project'),
    'add_new'               => __('Ajouter', 'project'),
    'new_item'              => __('Nouveau projet', 'project'),
    'edit_item'             => __('Éditer le projet', 'project'),
    'update_item'           => __('Mettre à jour le projet', 'project'),
    'view_item'             => __('Voir le projet', 'project'),
    'view_items'            => __('Voir les projets', 'project'),
    'search_items'          => __('Chercher le projet', 'project'),
    'not_found'             => __('Non trouvé', 'project'),
    'not_found_in_trash'    => __('Non trouvé dans la corbeille', 'project'),
    'featured_image'        => __('Vignette', 'project'),
    'set_featured_image'    => __('Choisir la vignette', 'project'),
    'remove_featured_image' => __('Effacer la vignette', 'project'),
    'use_featured_image'    => __('Utiliser comme vignette', 'project'),
    'insert_into_item'      => __('Insérer dans le projet', 'project'),
    'uploaded_to_this_item' => __('Uploaded dans ce projet', 'project'),
    'items_list'            => __('List des projets', 'project'),
    'items_list_navigation' => __('Navigation des projets', 'project'),
    'filter_items_list'     => __('Filtrer les projets', 'project'),
  );
  $rewrite = array(
    'slug'                  => 'projets',
    'with_front'            => true,
    'pages'                 => true,
    'feeds'                 => true,
  );
  $args = array(
    'label'                 => __('Projet', 'project'),
    'description'           => __('Travaux d\'écoles', 'project'),
    'labels'                => $labels,
    'supports'              => array('title', 'editor', 'thumbnail', 'author'),
    'taxonomies'            => array('year', 'subjects'),
    'hierarchical'          => false,
    'show_in_graphql'       => true,
    'graphql_single_name'   => 'project',
    'graphql_plural_name'   => 'projects',
    'public'                => true,
    'show_ui'               => true,
    'show_in_rest'          => true,
    'show_in_menu'          => true,
    'menu_position'         => 5,
    'menu_icon'             => 'dashicons-portfolio',
    'show_in_admin_bar'     => true,
    'show_in_nav_menus'     => true,
    'can_export'            => true,
    'has_archive'           => true,
    'exclude_from_search'   => false,
    'publicly_queryable'    => true,
    'rewrite'               => $rewrite,
    'capability_type'       => 'post',
  );
  register_post_type('project', $args);
}
add_action('init', 'project_post_type', 5);


register_taxonomy('year', array('project'), array(
  'labels' => array(
    'name' => _x('Années', 'taxonomy general name'),
    'singular_name' => _x('Année', 'taxonomy singular name'),
    'search_items' =>  __('Chercher Année'),
    'all_items' => __('Toutes les Années'),
    'parent_item' => __('Année parente'),
    'parent_item_colon' => __('Année parente:'),
    'edit_item' => __('Éditer l\'Année'),
    'update_item' => __('Mettre à jour l\'Année'),
    'add_new_item' => __('Nouvelle Année'),
    'new_item_name' => __('Nom de la nouvelle Année'),
    'menu_name' => __('Années'),
  ),
  'show_ui' => current_user_can('administrator'),
  'show_in_rest' => true,
  'show_admin_column' => true,
  'query_var' => true,
  'rewrite' => array('slug' => 'year'),
  'show_in_graphql'       => true,
  'graphql_single_name'   => 'year',
  'graphql_plural_name'   => 'years',
));

register_taxonomy('section', array('project'), array(
  'labels' => array(
    'name' => _x('Sections', 'taxonomy general name'),
    'singular_name' => _x('Section', 'taxonomy singular name'),
    'search_items' =>  __('Chercher Section'),
    'all_items' => __('Toutes les Sections'),
    'parent_item' => __('Section parente'),
    'parent_item_colon' => __('Section parente:'),
    'edit_item' => __('Éditer l\'Section'),
    'update_item' => __('Mettre à jour l\'Section'),
    'add_new_item' => __('Nouvelle Section'),
    'new_item_name' => __('Nom de la nouvelle Section'),
    'menu_name' => __('Sections'),
  ),
  'show_ui' => current_user_can('administrator'),
  'show_in_rest' => true,
  'show_admin_column' => true,
  'query_var' => true,
  'rewrite' => array('slug' => 'section'),
  'show_in_graphql'       => true,
  'graphql_single_name'   => 'section',
  'graphql_plural_name'   => 'sections',
));

register_taxonomy('subjects', array('project'), array(
  'labels' => array(
    'name' => _x('Branches', 'taxonomy general name'),
    'singular_name' => _x('Branche', 'taxonomy singular name'),
    'search_items' =>  __('Chercher branche'),
    'all_items' => __('Toutes les branches'),
    'parent_item' => __('Branche parente'),
    'parent_item_colon' => __('Branche parente:'),
    'edit_item' => __('Éditer l\'branche'),
    'update_item' => __('Mettre à jour l\'branche'),
    'add_new_item' => __('Nouvelle branche'),
    'new_item_name' => __('Nom de la nouvelle branche'),
    'menu_name' => __('Branches'),
  ),
  'show_ui' => current_user_can('administrator'),
  'show_in_rest' => true,
  'show_admin_column' => true,
  'query_var' => true,
  'rewrite' => array('slug' => 'subjects'),
  'show_in_graphql'       => true,
  'graphql_single_name'   => 'subject',
  'graphql_plural_name'   => 'subjects',
));

function remove_project_taxonomies_metabox()
{
  remove_meta_box('tagsdiv-year', 'project', 'side');
  remove_meta_box('tagsdiv-section', 'project', 'side');
  remove_meta_box('tagsdiv-subjects', 'project', 'side');
}
add_action('admin_menu', 'remove_project_taxonomies_metabox');

add_post_type_support("page", "excerpt");

remove_filter('the_excerpt', 'wpautop');

// Register Custom Post Type DEPARTMENT and his taxonomies
function department_post_type()
{
  $labels = array(
    'name'                  => _x('Départements', 'Post Type General Name', 'department'),
    'singular_name'         => _x('Département', 'Post Type Singular Name', 'department'),
    'menu_name'             => __('Départements', 'department'),
    'name_admin_bar'        => __('Département', 'department'),
    'archives'              => __('Archive des départements', 'department'),
    'attributes'            => __('Attributs', 'department'),
    'parent_item_colon'     => __('Département parent', 'department'),
    'all_items'             => __('Tous les départements', 'department'),
    'add_new_item'          => __('Ajouter département', 'department'),
    'add_new'               => __('Ajouter', 'department'),
    'new_item'              => __('Nouveau département', 'department'),
    'edit_item'             => __('Éditer le département', 'department'),
    'update_item'           => __('Mettre à jour le département', 'department'),
    'view_item'             => __('Voir le département', 'department'),
    'view_items'            => __('Voir les départements', 'department'),
    'search_items'          => __('Chercher le département', 'department'),
    'not_found'             => __('Non trouvé', 'department'),
    'not_found_in_trash'    => __('Non trouvé dans la corbeille', 'department'),
    'featured_image'        => __('Vignette', 'department'),
    'set_featured_image'    => __('Choisir la vignette', 'department'),
    'remove_featured_image' => __('Effacer la vignette', 'department'),
    'use_featured_image'    => __('Utiliser comme vignette', 'department'),
    'insert_into_item'      => __('Insérer dans le département', 'department'),
    'uploaded_to_this_item' => __('Uploaded dans ce département', 'department'),
    'items_list'            => __('List des départements', 'department'),
    'items_list_navigation' => __('Navigation des départements', 'department'),
    'filter_items_list'     => __('Filtrer les départements', 'department'),
  );
  $rewrite = array(
    'slug' => '/departments',
    'with_front' => false
  );
  $args = array(
    'label'                 => __('Département', 'department'),
    'description'           => __('Travaux d\'écoles', 'department'),
    'labels'                => $labels,
    'supports'              => array('title', 'editor', 'excerpt', 'thumbnail', 'page-attributes'),
    'taxonomies'            => array(),
    'hierarchical'          => false,
    'show_in_graphql'       => true,
    'graphql_single_name'   => 'department',
    'graphql_plural_name'   => 'departments',
    'public'                => true,
    'show_ui'               => true,
    'show_in_rest'          => true,
    'show_in_menu'          => true,
    'menu_position'         => 5,
    'menu_icon'             => 'dashicons-welcome-learn-more',
    'show_in_admin_bar'     => true,
    'show_in_nav_menus'     => true,
    'can_export'            => true,
    'has_archive'           => true,
    'exclude_from_search'   => false,
    'publicly_queryable'    => true,
    'rewrite'               => $rewrite,
    'capability_type'       => 'page',
  );
  register_post_type('department', $args);
}
add_action('init', 'department_post_type', 0);

add_filter('use_block_editor_for_post_type', 'prefix_disable_gutenberg', 10, 2);
function prefix_disable_gutenberg($current_status, $post_type)
{
  if ('post' === $post_type) return false;
  if ('project' === $post_type) return false;
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
      "SELECT COUNT(ID) FROM $wpdb->posts WHERE
       post_type = 'project' AND post_author = %d",
      $id
    ));
    return $count;
  }
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
