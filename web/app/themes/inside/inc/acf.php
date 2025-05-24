<?php

// Only filter layouts from the ACF UI for non-admin users
add_filter('acf/load_field/name=galerie', function ($field) {
  // Only apply the filter in the admin interface
  if (is_admin() && !current_user_can('administrator')) {
    // Filter out both video and twitch layouts by name, but only from the UI
    $field['layouts'] = array_filter($field['layouts'], function ($layout) {
      return !in_array($layout['name'], ['video', 'twitch']);
    });
  }
  return $field;
});
