<?php

add_filter('allowed_block_types_all', 'eikon_allowed_block_types', 25, 2);

function eikon_allowed_block_types($allowed_blocks, $editor_context)
{

  return array(
    'eikonblocks/projects-dynamic',
    'eikonblocks/projects',
    'eikonblocks/departments-teaser',
    'eikonblocks/heading',
    'eikonblocks/marquee',
    'eikonblocks/numbers',
    'eikonblocks/buttons',
    'eikonblocks/mixedposts',
    'core/paragraph',
    'core/list',
    'core/list-item'
  );
}

function eikon_block_wrapper($block_content, $block)
{
  if ($block['blockName'] === 'core/paragraph' || $block['blockName'] === 'core/list') {
    $background_color_class = isset($block['attrs']['backgroundColor']) ? 'has-' . $block['attrs']['backgroundColor'] . '-background-color' : '';
    $text_color_class = isset($block['attrs']['textColor']) ? 'has-' . $block['attrs']['textColor'] . '-color' : '';

    $classes = $block['blockName'] === 'core/paragraph' ? 'wp-block-paragraph' : 'wp-block-list';
    if ($background_color_class) {
      $classes .= ' ' . $background_color_class;
    }
    if ($text_color_class) {
      $classes .= ' ' . $text_color_class;
    }

    $content = '<div class="' . esc_attr($classes) . '">';
    $content .= $block_content;
    $content .= '</div>';
    return $content;
  }
  return $block_content;
}

add_filter('render_block', 'eikon_block_wrapper', 10, 2);

function enqueue_custom_fonts()
{
  $theme_directory = get_template_directory_uri();
  $css = "@font-face {
            font-family: 'eikon';
            src: url('{$theme_directory}/assets/fonts/NoiGrotesk-Medium.woff2') format('woff2');
            font-style: normal;
            font-display: swap;
          }
          @font-face {
            font-family: 'eikon';
            src: url('{$theme_directory}/assets/fonts/HALTimezone-Italic.woff2') format('woff2');
            font-style: italic;
            font-display: swap;
          }
        ";
  wp_add_inline_style('wp-editor', $css);
}
add_action('enqueue_block_editor_assets', 'enqueue_custom_fonts');

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

/**
 * Registers support for color palette.
 */
add_theme_support('editor-color-palette', array(
  array(
    'name'  => __('Blue', 'eikon'),
    'slug'  => 'blue',
    'color'  => '#0000DE',
  ),
  array(
    'name'  => __('Black', 'eikon'),
    'slug'  => 'black',
    'color'  => '#000000',
  ),
  array(
    'name'  => __('White', 'eikon'),
    'slug'  => 'white',
    'color'  => '#FFFFFF',
  ),
  array(
    'name'  => __('Red', 'eikon'),
    'slug'  => 'red',
    'color'  => '#FF2C00',
  ),
  array(
    'name'  => __('Orange', 'eikon'),
    'slug'  => 'orange',
    'color'  => '#FF5F1C',
  ),
  array(
    'name'  => __('Fuchsia', 'eikon'),
    'slug'  => 'fuchsia',
    'color'  => '#FF3EAD',
  ),
  array(
    'name'  => __('Pink', 'eikon'),
    'slug'  => 'pink',
    'color'  => '#FFA1CE',
  ),
  array(
    'name'  => __('Violet', 'eikon'),
    'slug'  => 'violet',
    'color'  => '#A000FF',
  ),
));

add_theme_support('disable-custom-colors');
