<?php

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
