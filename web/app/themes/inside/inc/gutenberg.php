<?php

add_filter('allowed_block_types_all', 'eikon_allowed_block_types', 25, 2);

function eikon_allowed_block_types($allowed_blocks, $editor_context)
{

  return array(
    'eikonblocks/projects',
    'eikonblocks/test',
    'eikonblocks/heading',
    'core/paragraph',
    'core/list',
    'core/list-item'
  );
}

function enqueue_custom_fonts()
{
  $theme_directory = get_template_directory_uri();
  $css = "@font-face {
            font-family: 'NoiGrotesk';
            src: url('{$theme_directory}/assets/fonts/NoiGrotesk-Medium.woff2') format('woff2');
            font-weight: 500;
            font-style: normal;
            font-display: swap;
          }
          @font-face {
            font-family: 'NoiGrotesk';
            src: url('{$theme_directory}/assets/fonts/NoiGrotesk-MediumItalic.woff2') format('woff2');
            font-weight: 500;
            font-style: italic;
            font-display: swap;
          }
        ";
  wp_add_inline_style('wp-editor', $css);
}
add_action('enqueue_block_editor_assets', 'enqueue_custom_fonts');

function remove_h1_from_editor()
{
  wp_enqueue_script(
    'remove-h1',
    get_template_directory_uri() . '/js/block-filters.js',
    array('wp-blocks', 'wp-dom-ready', 'wp-edit-post')
  );
}
add_action('enqueue_block_editor_assets', 'remove_h1_from_editor');

/**
 * Registers support for editor styles & Enqueue it.
 */
function ghub_child_setup()
{
  // Add support for editor styles.
  add_theme_support('editor-styles');
  // Enqueue editor styles.
  add_editor_style(get_template_directory_uri() . '/css/editor.css');
}
add_action('after_setup_theme', 'ghub_child_setup');
