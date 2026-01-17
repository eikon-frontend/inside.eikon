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
