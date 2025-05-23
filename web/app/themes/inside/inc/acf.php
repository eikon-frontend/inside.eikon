<?php

add_filter('acf/load_field/name=galerie', function ($field) {
  if (!current_user_can('administrator')) {
    // Filter out both video-embed and twitch layouts by name
    $field['layouts'] = array_filter($field['layouts'], function ($layout) {
      return !in_array($layout['name'], ['video-embed', 'twitch']);
    });
  }
  return $field;
});
