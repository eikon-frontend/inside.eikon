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
    // Disable code view tab (always visual mode)
    $settings['quicktags'] = false;
  }
  return $settings;
});

// Remove media upload button from project post type editor - higher priority
add_filter('media_buttons', function ($html) {
  global $post;
  if ($post && $post->post_type === 'project') {
    return ''; // Remove media button HTML entirely
  }
  return $html;
}, 1, 1);

// Remove media buttons from ACF WYSIWYG fields on project posts
add_filter('acf/fields/wysiwyg/media_buttons', function () {
  global $post;
  if ($post && $post->post_type === 'project') {
    return false; // Disable media buttons in ACF WYSIWYG
  }
  return true;
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

// JavaScript to remove media buttons and hide visual/code tabs from project post editor
add_action('admin_head', function () {
  global $post;
  if ($post && $post->post_type === 'project') {
    echo '<script>
      document.addEventListener("DOMContentLoaded", function() {
        const mediaButtons = document.getElementById("wp-content-media-buttons");
        if (mediaButtons) {
          mediaButtons.remove();
        }

        // Hide visual/code tabs
        const tabs = document.querySelectorAll(".wp-switch-editor, .wp-switch-html");
        tabs.forEach(tab => {
          tab.style.display = "none";
        });
      });
    </script>';
  }
});

// Pre-populate project_authors repeater with the post author when empty
add_filter('acf/load_value/name=project_authors', function ($value, $post_id, $field) {
  if (!empty($value)) {
    return $value;
  }

  $post = get_post($post_id);
  if (!$post || $post->post_type !== 'project' || !$post->post_author) {
    return $value;
  }

  return [
    ['field_68dc5a01b7e11' => $post->post_author]
  ];
}, 10, 3);
