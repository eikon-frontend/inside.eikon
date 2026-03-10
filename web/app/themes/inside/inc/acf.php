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

// Simplify WordPress editor for project post type (bold and italic only)
add_filter('tiny_mce_before_init', function ($settings) {
  global $post;
  if ($post && $post->post_type === 'project') {
    // Only show bold and italic buttons
    $settings['toolbar1'] = 'bold,italic';
    $settings['toolbar2'] = '';
  }
  return $settings;
});

// Custom WYSIWYG toolbar for portfolio text layout
add_filter('acf/fields/wysiwyg/toolbars', function ($toolbars) {
  $toolbars['Portfolio Layout'] = [];
  $toolbars['Portfolio Layout'][1] = ['formatselect', 'italic', 'link', 'bullist', 'numlist', 'undo', 'redo', 'fullscreen'];
  return $toolbars;
});

// Restrict format dropdown to only paragraph and h3
add_filter('acf/fields/wysiwyg/toolbars', function ($toolbars) {
  return $toolbars;
}, 20);

add_filter('tiny_mce_before_init', function ($settings) {
  $settings['block_formats'] = 'Paragraph=p;Heading 3=h3';
  return $settings;
}, 20);
