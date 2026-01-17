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

add_filter('graphql_object_visibility', function ($visibility, $model_name, $data, $owner, $current_user) {
  if ('Project' === $model_name) {
    return 'public';
  }
  return $visibility;
}, 10, 5);

add_action('pre_get_posts', function ($query) {
  // Ensure we are in a GraphQL request context
  if (defined('GRAPHQL_REQUEST') && GRAPHQL_REQUEST) {
    $post_type = $query->get('post_type');
    // Check if it's the 'project' post type
    if ($post_type === 'project' || (is_array($post_type) && in_array('project', $post_type))) {
      $query->set('post_status', ['publish', 'draft', 'pending', 'future']);
    }
  }
});
