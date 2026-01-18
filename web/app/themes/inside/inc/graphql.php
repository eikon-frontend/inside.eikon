<?php

// Disable display_errors to prevent PHP warnings from breaking JSON response
@ini_set('display_errors', 0);

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
