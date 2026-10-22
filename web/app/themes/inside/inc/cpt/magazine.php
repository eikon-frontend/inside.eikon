<?php

/**
 * Register Custom Post Type MAGAZINE, Taxonomies and Rewrites.
 */

// Add custom rewrite tag %edition%
function magazine_rewrite_tag()
{
    add_rewrite_tag('%edition%', '([^/]+)', 'edition=');
}
add_action('init', 'magazine_rewrite_tag', 2);

// Register Taxonomy EDITION
function edition_taxonomy()
{
    $labels = array(
        'name'                       => _x('Éditions', 'Taxonomy General Name', 'inside'),
        'singular_name'              => _x('Édition', 'Taxonomy Singular Name', 'inside'),
        'menu_name'                  => __('Éditions', 'inside'),
        'all_items'                  => __('Toutes les éditions', 'inside'),
        'parent_item'                => __('Édition parente', 'inside'),
        'parent_item_colon'          => __('Édition parente :', 'inside'),
        'new_item_name'              => __('Nom de la nouvelle édition', 'inside'),
        'add_new_item'               => __('Ajouter une édition', 'inside'),
        'edit_item'                  => __('Modifier l\'édition', 'inside'),
        'update_item'                => __('Mettre à jour l\'édition', 'inside'),
        'view_item'                  => __('Voir l\'édition', 'inside'),
        'separate_items_with_commas' => __('Séparer les éditions avec des virgules', 'inside'),
        'add_or_remove_items'        => __('Ajouter ou supprimer des éditions', 'inside'),
        'choose_from_most_used'      => __('Choisir parmi les plus utilisées', 'inside'),
        'popular_items'              => __('Éditions populaires', 'inside'),
        'search_items'               => __('Rechercher des éditions', 'inside'),
        'not_found'                  => __('Aucune édition trouvée', 'inside'),
        'no_terms'                   => __('Aucune édition', 'inside'),
        'items_list'                 => __('Liste des éditions', 'inside'),
        'items_list_navigation'      => __('Navigation de la liste des éditions', 'inside'),
    );

    $args = array(
        'labels'                     => $labels,
        'hierarchical'               => true,
        'public'                     => true,
        'show_ui'                    => true,
        'show_admin_column'          => true,
        'show_in_nav_menus'          => false,
        'show_tagcloud'              => false,
        'show_in_rest'               => true,
        'show_in_graphql'            => true,
        'graphql_single_name'        => 'magazineEdition',
        'graphql_plural_name'        => 'magazineEditions',
        'rewrite'                    => array('slug' => 'edition', 'with_front' => false),
    );

    register_taxonomy('edition', array('magazine'), $args);
}
add_action('init', 'edition_taxonomy', 3);

// Register Custom Post Type MAGAZINE
function magazine_post_type()
{
    $labels = array(
        'name'                  => _x('Magazines', 'Post Type General Name', 'inside'),
        'singular_name'         => _x('Magazine', 'Post Type Singular Name', 'inside'),
        'menu_name'             => __('Magazines', 'inside'),
        'name_admin_bar'        => __('Magazine', 'inside'),
        'archives'              => __('Archives des magazines', 'inside'),
        'attributes'            => __('Attributs du magazine', 'inside'),
        'parent_item_colon'     => __('Magazine parent :', 'inside'),
        'all_items'             => __('Tous les magazines', 'inside'),
        'add_new_item'          => __('Ajouter un magazine', 'inside'),
        'add_new'               => __('Ajouter', 'inside'),
        'new_item'              => __('Nouveau magazine', 'inside'),
        'edit_item'             => __('Modifier le magazine', 'inside'),
        'update_item'           => __('Mettre à jour le magazine', 'inside'),
        'view_item'             => __('Voir le magazine', 'inside'),
        'view_items'            => __('Voir les magazines', 'inside'),
        'search_items'          => __('Rechercher un magazine', 'inside'),
        'not_found'             => __('Non trouvé', 'inside'),
        'not_found_in_trash'    => __('Non trouvé dans la corbeille', 'inside'),
        'featured_image'        => __('Image de couverture', 'inside'),
        'set_featured_image'    => __('Définir l\'image de couverture', 'inside'),
        'remove_featured_image' => __('Supprimer l\'image de couverture', 'inside'),
        'use_featured_image'    => __('Utiliser comme image de couverture', 'inside'),
        'insert_into_item'      => __('Insérer dans le magazine', 'inside'),
        'uploaded_to_this_item' => __('Téléversé dans ce magazine', 'inside'),
        'items_list'            => __('Liste des magazines', 'inside'),
        'items_list_navigation' => __('Navigation de la liste des magazines', 'inside'),
        'filter_items_list'     => __('Filtrer la liste des magazines', 'inside'),
    );

    $rewrite = array(
        'slug'       => '%edition%',
        'with_front' => false,
    );

    $args = array(
        'label'               => __('Magazine', 'inside'),
        'description'         => __('Publications du magazine eikon', 'inside'),
        'labels'              => $labels,
        'supports'            => array('title', 'thumbnail', 'revisions', 'author', 'slug'),
        'taxonomies'          => array('edition'),
        'hierarchical'        => false,
        'show_in_graphql'     => true,
        'graphql_single_name' => 'magazine',
        'graphql_plural_name' => 'magazines',
        'public'              => true,
        'show_ui'             => true,
        'show_in_rest'        => true,
        'show_in_menu'        => true,
        'menu_position'       => 6,
        'menu_icon'           => 'dashicons-book-alt',
        'show_in_admin_bar'   => true,
        'show_in_nav_menus'   => false,
        'can_export'          => true,
        'has_archive'         => false,
        'exclude_from_search' => false,
        'publicly_queryable'  => true,
        'rewrite'             => $rewrite,
        'capability_type'     => 'post',
    );

    register_post_type('magazine', $args);
}
add_action('init', 'magazine_post_type', 4);

/**
 * Filter the permalink for magazine posts to replace %edition% with the edition taxonomy slug.
 */
function magazine_post_link($post_link, $post)
{
    if (is_object($post) && $post->post_type === 'magazine') {
        $terms = wp_get_object_terms($post->ID, 'edition');
        if (!empty($terms) && !is_wp_error($terms)) {
            return str_replace('%edition%', $terms[0]->slug, $post_link);
        } else {
            return str_replace('%edition%', 'edition', $post_link);
        }
    }
    return $post_link;
}
add_filter('post_type_link', 'magazine_post_link', 10, 2);

/**
 * Ensure native category "magazine" exists for classifying posts.
 */
function eikon_ensure_magazine_category()
{
    if (!term_exists('magazine', 'category')) {
        wp_insert_term('Magazine', 'category', array('slug' => 'magazine'));
    }
}
add_action('init', 'eikon_ensure_magazine_category', 5);
