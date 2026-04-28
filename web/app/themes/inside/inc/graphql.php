<?php

add_filter(
  'graphql_PostObjectsConnectionOrderbyEnum_values',
  function ($values) {

    $values['RAND'] = [
      'value' => 'rand',
      'description' => __('Order randomly', 'wp-graphql'),
    ];

    return $values;
  }
);

/**
 * Make draft/pending/future "project" posts fully public in GraphQL.
 *
 * Note: WPGraphQL's Post model uses a restricted-capability for draft/pending posts.
 * Setting visibility to "public" ensures the full fields (content, ACF, etc.) are returned.
 */
add_filter('graphql_object_visibility', function ($visibility, $model_name, $data, $owner, $current_user) {
  if ('PostObject' !== $model_name || !($data instanceof \WP_Post)) {
    return $visibility;
  }

  // Make unpublished projects public.
  if (
    'project' === $data->post_type &&
    in_array($data->post_status, ['draft', 'pending', 'future'], true)
  ) {
    return 'public';
  }

  // Also make attachments of unpublished projects public (otherwise featuredImage can be null).
  if ('attachment' === $data->post_type && !empty($data->post_parent)) {
    $parent = get_post((int) $data->post_parent);
    if (
      $parent instanceof \WP_Post &&
      'project' === $parent->post_type &&
      in_array($parent->post_status, ['draft', 'pending', 'future'], true)
    ) {
      return 'public';
    }
  }

  return $visibility;
}, 10, 5);

/**
 * Make all users visible in GraphQL when referenced as project authors (ACF user field).
 *
 * By default WPGraphQL restricts user visibility: only users with published posts
 * are publicly queryable. This causes ACF "user" fields to return empty nodes
 * for students or teachers who haven't published yet.
 */
add_filter('graphql_object_visibility', function ($visibility, $model_name, $data) {
  if ('UserObject' === $model_name && $data instanceof \WP_User) {
    return 'public';
  }
  return $visibility;
}, 10, 3);

/**
 * Remove `has_published_posts` restriction from user connection queries.
 *
 * WPGraphQL sets this on WP_User_Query for unauthenticated requests, which
 * excludes users without published posts at the DB level — before the
 * graphql_object_visibility filter can make them public.
 */
add_filter('graphql_connection_query_args', function ($query_args, $connection_resolver) {
  if ($connection_resolver instanceof \WPGraphQL\Data\Connection\UserConnectionResolver) {
    unset($query_args['has_published_posts']);
  }
  return $query_args;
}, 10, 2);

/**
 * Allow resolving draft/pending/future projects when querying a specific project by slug (direct URL on Nuxt).
 *
 * We intentionally do NOT change list queries (connections), so unpublished projects won't appear in
 * general "projects" listings unless explicitly requested.
 */
add_action('pre_get_posts', function ($query) {
  // Only for GraphQL requests
  if (!defined('GRAPHQL_REQUEST') || !GRAPHQL_REQUEST) {
    return;
  }

  $post_type = $query->get('post_type');
  $is_project = $post_type === 'project' || (is_array($post_type) && in_array('project', $post_type, true));
  if (!$is_project) {
    return;
  }

  // Only widen statuses for single-item lookups (slug or ID).
  if (empty($query->get('name')) && empty($query->get('p'))) {
    return;
  }

  $query->set('post_status', ['publish', 'draft', 'pending', 'future']);
});

/**
 * Return plain-text media captions for GraphQL consumers.
 *
 * WPGraphQL's MediaItem.caption defaults to captionRendered, which runs WordPress
 * excerpt filters and can return HTML entities (for example &rsquo;).
 * Decode entities and remove HTML so Nuxt receives a clean string without custom JS.
 */
add_filter('graphql_resolve_field', function ($result, $source, $args, $context, $info, $type_name, $field_key) {
  if ('MediaItem' !== $type_name || 'caption' !== $field_key) {
    return $result;
  }

  if (!is_string($result) || '' === $result) {
    return $result;
  }

  $plain_text = wp_strip_all_tags($result, true);
  $plain_text = html_entity_decode($plain_text, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
  $plain_text = trim(preg_replace('/\s+/u', ' ', $plain_text));

  return '' === $plain_text ? null : $plain_text;
}, 20, 9);
