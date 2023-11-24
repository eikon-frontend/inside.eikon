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
