<?php
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
