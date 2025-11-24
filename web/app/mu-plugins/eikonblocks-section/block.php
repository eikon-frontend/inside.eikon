<?php

/**
 * Plugin Name:       eikonblocks: SECTION
 * Description:       Maruqee block scaffolded with Create Block tool for eikon website.
 * Requires at least: 6.1
 * Requires PHP:      7.0
 * Version:           0.1.0
 * Author:            The WordPress Contributors
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       eikon
 *
 * @package           eikonblocks/section
 */

if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly.
}

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function eikonblocks_section_init()
{
  register_block_type(__DIR__ . '/build');
}
add_action('init', 'eikonblocks_section_init');

/**
 * Filter to hide the block if it is inactive.
 */
function eikonblocks_section_render_filter($block_content, $block)
{
  if ($block['blockName'] === 'eikonblocks/section') {
    if (isset($block['attrs']['isActive']) && $block['attrs']['isActive'] === false) {
      return '';
    }
  }
  return $block_content;
}
add_filter('render_block', 'eikonblocks_section_render_filter', 10, 2);
