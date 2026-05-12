<?php

// Register Custom Post Type MANDAT and its taxonomies
function mandat_post_type()
{
  $labels = array(
    'name'                  => _x('Mandats', 'Post Type General Name', 'mandat'),
    'singular_name'         => _x('Mandat', 'Post Type Singular Name', 'mandat'),
    'menu_name'             => __('Mandats', 'mandat'),
    'name_admin_bar'        => __('Mandat', 'mandat'),
    'archives'              => __('Archive des mandats', 'mandat'),
    'attributes'            => __('Attributs', 'mandat'),
    'parent_item_colon'     => __('Mandat parent', 'mandat'),
    'all_items'             => __('Tous les mandats', 'mandat'),
    'add_new_item'          => __('Ajouter mandat', 'mandat'),
    'add_new'               => __('Ajouter', 'mandat'),
    'new_item'              => __('Nouveau mandat', 'mandat'),
    'edit_item'             => __('Éditer le mandat', 'mandat'),
    'update_item'           => __('Mettre à jour le mandat', 'mandat'),
    'view_item'             => __('Voir le mandat', 'mandat'),
    'view_items'            => __('Voir les mandats', 'mandat'),
    'search_items'          => __('Chercher le mandat', 'mandat'),
    'not_found'             => __('Non trouvé', 'mandat'),
    'not_found_in_trash'    => __('Non trouvé dans la corbeille', 'mandat'),
    'featured_image'        => __('Vignette', 'mandat'),
    'set_featured_image'    => __('Choisir la vignette', 'mandat'),
    'remove_featured_image' => __('Effacer la vignette', 'mandat'),
    'use_featured_image'    => __('Utiliser comme vignette', 'mandat'),
    'insert_into_item'      => __('Insérer dans le mandat', 'mandat'),
    'uploaded_to_this_item' => __('Uploaded dans ce mandat', 'mandat'),
    'items_list'            => __('List des mandats', 'mandat'),
    'items_list_navigation' => __('Navigation des mandats', 'mandat'),
    'filter_items_list'     => __('Filtrer les mandats', 'mandat'),
  );

  $rewrite = array(
    'slug'       => 'mandats',
    'with_front' => true,
    'pages'      => true,
    'feeds'      => true,
  );

  $args = array(
    'label'                 => __('Mandat', 'mandat'),
    'description'           => __('Brief pédagogique', 'mandat'),
    'labels'                => $labels,
    'supports'              => array('title', 'editor', 'author', 'revisions'),
    'taxonomies'            => array('year', 'subjects', 'section'),
    'hierarchical'          => false,
    'show_in_graphql'       => true,
    'graphql_single_name'   => 'mandat',
    'graphql_plural_name'   => 'mandats',
    'public'                => false,
    'show_ui'               => true,
    'show_in_rest'          => true,
    'show_in_menu'          => true,
    'menu_position'         => 5,
    'menu_icon'             => 'dashicons-welcome-write-blog',
    'show_in_admin_bar'     => true,
    'show_in_nav_menus'     => false,
    'can_export'            => true,
    'has_archive'           => false,
    'exclude_from_search'   => true,
    'publicly_queryable'    => false,
    'rewrite'               => $rewrite,
    'capability_type'       => array('mandat', 'mandats'),
    'map_meta_cap'          => true,
  );

  register_post_type('mandat', $args);
}
add_action('init', 'mandat_post_type', 4);

function remove_mandat_taxonomies_metabox()
{
  remove_meta_box('tagsdiv-year', 'mandat', 'side');
  remove_meta_box('tagsdiv-section', 'mandat', 'side');
  remove_meta_box('tagsdiv-subjects', 'mandat', 'side');
}
add_action('admin_menu', 'remove_mandat_taxonomies_metabox');
