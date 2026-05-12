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
    'supports'              => array('title', 'editor', 'thumbnail', 'author', 'revisions'),
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

/**
 * Build a clean URL-safe author prefix for project slugs.
 *
 * Uses "prenom-nom" from first_name + last_name meta if both are set,
 * otherwise falls back to user_nicename (which can be ugly when derived from an email).
 *
 * @param  WP_User|false $author
 * @return string  e.g. "julien-minguely-" or ""
 */
function eikon_project_author_prefix($author): string
{
  if (!$author) {
    return '';
  }

  $first = trim($author->first_name);
  $last  = trim($author->last_name);

  if ($first !== '' && $last !== '') {
    return sanitize_title($first . '-' . $last) . '-';
  }

  // Fallback: user_nicename (safe slug, but may be email-derived)
  return !empty($author->user_nicename) ? $author->user_nicename . '-' : '';
}

/**
 * Ensure projects have a slug (post_name) even when saved as draft/pending.
 *
 * Nuxt fetches projects by GraphQL idType: SLUG. WordPress sometimes keeps post_name empty
 * until the first publish, which makes unpublished projects unreachable by slug.
 */
function eikon_ensure_project_slug_on_save($post_id, $post, $update)
{
  static $running = false;
  if ($running) {
    return;
  }

  if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
    return;
  }

  if (empty($post) || $post->post_type !== 'project') {
    return;
  }

  // Don't generate slugs for placeholder posts.
  if ($post->post_status === 'auto-draft') {
    return;
  }

  // Only set if empty; don't override manually edited slugs.
  if (!empty($post->post_name) || empty($post->post_title)) {
    return;
  }

  $running = true;

  $author        = get_userdata($post->post_author);
  $is_student    = $author && in_array('student', (array) $author->roles, true);
  $author_prefix = $is_student ? eikon_project_author_prefix($author) : '';
  $slug          = $author_prefix . sanitize_title($post->post_title);
  $slug = wp_unique_post_slug($slug, $post_id, $post->post_status, $post->post_type, $post->post_parent);

  // Use direct database update to avoid infinite hook loops with wp_update_post()
  global $wpdb;
  $wpdb->update(
    $wpdb->posts,
    ['post_name' => $slug],
    ['ID' => $post_id],
    ['%s'],
    ['%d']
  );

  $running = false;
}
add_action('save_post_project', 'eikon_ensure_project_slug_on_save', 10, 3);

/**
 * Enforce unique slugs for projects across ALL statuses (draft, pending, publish, etc.).
 *
 * WordPress core only deduplicates slugs for published posts on non-hierarchical types.
 * Since this is a headless setup where projects are fetched by slug via GraphQL regardless
 * of status, we must prevent duplicates across all statuses.
 */
function eikon_unique_project_slug($slug, $post_id, $post_status, $post_type, $post_parent, $original_slug)
{
  if ($post_type !== 'project') {
    return $slug;
  }

  global $wpdb;

  $check_sql = "SELECT post_name FROM $wpdb->posts WHERE post_name = %s AND post_type = %s AND ID != %d LIMIT 1";

  if ($wpdb->get_var($wpdb->prepare($check_sql, $slug, $post_type, $post_id))) {
    $suffix = 2;
    do {
      $alt_slug = "$slug-$suffix";
      $suffix++;
    } while ($wpdb->get_var($wpdb->prepare($check_sql, $alt_slug, $post_type, $post_id)));
    $slug = $alt_slug;
  }

  return $slug;
}
add_filter('wp_unique_post_slug', 'eikon_unique_project_slug', 10, 6);

/**
 * Ensure project slugs are generated and unique BEFORE save.
 *
 * WordPress skips slug generation for drafts/pending. Since projects are fetched
 * by slug via GraphQL regardless of status, we must:
 * 1. Generate a slug from the title if none exists (even for drafts)
 * 2. Enforce uniqueness across ALL statuses
 */
function eikon_enforce_unique_project_slug_on_save($data, $postarr)
{
  if ($data['post_type'] !== 'project' || $data['post_status'] === 'auto-draft') {
    return $data;
  }

  // Ensure the slug is prefixed with the author's nicename.
  // We apply the prefix when:
  //   (a) post_name is empty (drafts: WordPress skips slug generation), or
  //   (b) post_name was just auto-generated from the title without the prefix
  //       (published posts: WordPress regenerates the slug before this filter runs
  //        when the user clears the slug field in the admin).
  $author_id     = !empty($postarr['post_author']) ? (int) $postarr['post_author'] : get_current_user_id();
  $author        = get_userdata($author_id);
  $is_student    = $author && in_array('student', (array) $author->roles, true);
  $author_prefix = $is_student ? eikon_project_author_prefix($author) : '';

  if ($is_student && !empty($data['post_title'])) {
    $title_slug = sanitize_title($data['post_title']);
    $needs_prefix = empty($data['post_name'])                              // (a) draft: no slug yet
      || $data['post_name'] === $title_slug                                // (b) bare title slug
      || (str_starts_with($data['post_name'], $title_slug));               // (b) bare title slug + -2, -3…

    if ($needs_prefix && !empty($author_prefix) && !str_starts_with($data['post_name'], $author_prefix)) {
      $base = empty($data['post_name']) ? $title_slug : $data['post_name'];
      $data['post_name'] = $author_prefix . $base;
    }
  }

  if (empty($data['post_name'])) {
    return $data;
  }

  $post_id = $postarr['ID'] ?? 0;

  global $wpdb;

  $check_sql = "SELECT post_name FROM $wpdb->posts WHERE post_name = %s AND post_type = 'project' AND ID != %d LIMIT 1";
  $slug = $data['post_name'];

  if ($wpdb->get_var($wpdb->prepare($check_sql, $slug, $post_id))) {
    $suffix = 2;
    do {
      $alt_slug = "$slug-$suffix";
      $suffix++;
    } while ($wpdb->get_var($wpdb->prepare($check_sql, $alt_slug, $post_id)));
    $data['post_name'] = $alt_slug;
  }

  return $data;
}
add_filter('wp_insert_post_data', 'eikon_enforce_unique_project_slug_on_save', 10, 2);

/**
 * AJAX endpoint to check if a project slug already exists.
 * Used by the permalink editor in the admin to warn users before saving.
 */
function eikon_check_project_slug_ajax()
{
  check_ajax_referer('eikon-check-slug', 'nonce');

  if (!current_user_can('edit_posts')) {
    wp_send_json_error('Unauthorized', 403);
  }

  $slug = sanitize_title($_POST['slug'] ?? '');
  $post_id = absint($_POST['post_id'] ?? 0);

  if (empty($slug)) {
    wp_send_json_error('Empty slug');
  }

  global $wpdb;
  $existing = $wpdb->get_var($wpdb->prepare(
    "SELECT ID FROM $wpdb->posts WHERE post_name = %s AND post_type = 'project' AND ID != %d LIMIT 1",
    $slug,
    $post_id
  ));

  wp_send_json_success(['exists' => !empty($existing)]);
}
add_action('wp_ajax_eikon_check_project_slug', 'eikon_check_project_slug_ajax');

/**
 * Enqueue slug validation script on project edit screens.
 */
function eikon_enqueue_slug_validation($hook)
{
  if (!in_array($hook, ['post.php', 'post-new.php'])) {
    return;
  }

  $screen = get_current_screen();
  if (!$screen || $screen->post_type !== 'project') {
    return;
  }

  wp_enqueue_script(
    'eikon-slug-validation',
    get_template_directory_uri() . '/js/slug-validation.js',
    ['jquery'],
    filemtime(get_template_directory() . '/js/slug-validation.js'),
    true
  );

  wp_localize_script('eikon-slug-validation', 'eikonSlug', [
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce'   => wp_create_nonce('eikon-check-slug'),
    'postId'  => get_the_ID(),
  ]);
}
add_action('admin_enqueue_scripts', 'eikon_enqueue_slug_validation');


register_taxonomy('year', array('project', 'mandat'), array(
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

register_taxonomy('section', array('project', 'mandat'), array(
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

register_taxonomy('subjects', array('project', 'mandat'), array(
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
