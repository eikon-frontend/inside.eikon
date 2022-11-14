<?php

// Register Custom Post Type
function project_post_type() {

	$labels = array(
		'name'                  => _x( 'Projets', 'Post Type General Name', 'project' ),
		'singular_name'         => _x( 'Projet', 'Post Type Singular Name', 'project' ),
		'menu_name'             => __( 'Projets', 'project' ),
		'name_admin_bar'        => __( 'Projet', 'project' ),
		'archives'              => __( 'Archive des projets', 'project' ),
		'attributes'            => __( 'Attributs', 'project' ),
		'parent_item_colon'     => __( 'Projet parent', 'project' ),
		'all_items'             => __( 'Tous les projets', 'project' ),
		'add_new_item'          => __( 'Ajouter projet', 'project' ),
		'add_new'               => __( 'Ajouter', 'project' ),
		'new_item'              => __( 'Nouveau projet', 'project' ),
		'edit_item'             => __( 'Éditer le projet', 'project' ),
		'update_item'           => __( 'Mettre à jour le projet', 'project' ),
		'view_item'             => __( 'Voir le projet', 'project' ),
		'view_items'            => __( 'Voir les projets', 'project' ),
		'search_items'          => __( 'Chercher le projet', 'project' ),
		'not_found'             => __( 'Non trouvé', 'project' ),
		'not_found_in_trash'    => __( 'Non trouvé dans la corbeille', 'project' ),
		'featured_image'        => __( 'Vignette', 'project' ),
		'set_featured_image'    => __( 'Choisir la vignette', 'project' ),
		'remove_featured_image' => __( 'Effacer la vignette', 'project' ),
		'use_featured_image'    => __( 'Utiliser comme vignette', 'project' ),
		'insert_into_item'      => __( 'Insérer dans le projet', 'project' ),
		'uploaded_to_this_item' => __( 'Uploaded dans ce projet', 'project' ),
		'items_list'            => __( 'List des projets', 'project' ),
		'items_list_navigation' => __( 'Navigation des projets', 'project' ),
		'filter_items_list'     => __( 'Filtrer les projets', 'project' ),
	);
	$rewrite = array(
		'slug'                  => 'projets',
		'with_front'            => true,
		'pages'                 => true,
		'feeds'                 => true,
	);
	$args = array(
		'label'                 => __( 'Projet', 'project' ),
		'description'           => __( 'Travaux d\'écoles', 'project' ),
		'labels'                => $labels,
		'supports'              => array( 'title', 'editor', 'thumbnail', 'excerpt'),
		'taxonomies'            => array( 'year', 'subjects' ),
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
		'capability_type'       => 'page',
	);
	register_post_type( 'project', $args );

}
add_action( 'init', 'project_post_type', 0 );

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
  'show_ui' => true,
  'show_in_rest' => true,
  'show_admin_column' => true,
  'query_var' => true,
  'rewrite' => array('slug' => 'year'),
  'show_in_graphql'       => true,
  'graphql_single_name'   => 'year',
  'graphql_plural_name'   => 'years',
));

register_taxonomy('section',array('project'), array(
    'labels' => array(
                'name' => _x( 'Sections', 'taxonomy general name' ),
                'singular_name' => _x( 'Section', 'taxonomy singular name' ),
                'search_items' =>  __( 'Chercher Section' ),
                'all_items' => __( 'Toutes les Sections' ),
                'parent_item' => __( 'Section parente' ),
                'parent_item_colon' => __( 'Section parente:' ),
                'edit_item' => __( 'Éditer l\'Section' ),
                'update_item' => __( 'Mettre à jour l\'Section' ),
                'add_new_item' => __( 'Nouvelle Section' ),
                'new_item_name' => __( 'Nom de la nouvelle Section' ),
                'menu_name' => __( 'Sections' ),
              ),
    'show_ui' => true,
    'show_in_rest' => true,
    'show_admin_column' => true,
    'query_var' => true,
    'rewrite' => array( 'slug' => 'section' ),
    'show_in_graphql'       => true,
    'graphql_single_name'   => 'section',
    'graphql_plural_name'   => 'sections',
  ));

register_taxonomy('subjects',array('project'), array(
    'labels' => array(
                'name' => _x( 'Branches', 'taxonomy general name' ),
                'singular_name' => _x( 'Branche', 'taxonomy singular name' ),
                'search_items' =>  __( 'Chercher branche' ),
                'all_items' => __( 'Toutes les branches' ),
                'parent_item' => __( 'Branche parente' ),
                'parent_item_colon' => __( 'Branche parente:' ),
                'edit_item' => __( 'Éditer l\'branche' ),
                'update_item' => __( 'Mettre à jour l\'branche' ),
                'add_new_item' => __( 'Nouvelle branche' ),
                'new_item_name' => __( 'Nom de la nouvelle branche' ),
                'menu_name' => __( 'Branches' ),
              ),
    'show_ui' => true,
    'show_in_rest' => true,
    'show_admin_column' => true,
    'query_var' => true,
    'rewrite' => array( 'slug' => 'subjects' ),
    'show_in_graphql'       => true,
    'graphql_single_name'   => 'subject',
    'graphql_plural_name'   => 'subjects',
  ));

add_post_type_support("page", "excerpt");
