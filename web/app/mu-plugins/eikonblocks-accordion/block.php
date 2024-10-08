<?php

/**
 * Plugin Name:       eikonblocks: ACCORDIONS
 * Description:       Accordions block scaffolded with Create Block tool for eikon website.
 * Requires at least: 6.1
 * Requires PHP:      7.0
 * Version:           0.1.0
 * Author:            The WordPress Contributors
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       eikon
 *
 * @package           eikonblocks/accordion
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
function eikonblocks_accordion_init()
{
  register_block_type(__DIR__ . '/build');
}
add_action('init', 'eikonblocks_accordion_init');

/**
 * Adds the eikonblocks/accordion block to the allowed block types list.
 *
 * @param array|bool $allowed_block_types Array of allowed block types or boolean.
 * @return array|bool Modified array of allowed block types or boolean.
 */
function eikonblocks_add_accordion($allowed_block_types)
{
  if (is_array($allowed_block_types)) {
    $allowed_block_types[] = 'eikonblocks/accordion';
  }
  return $allowed_block_types;
}
add_filter('allowed_block_types_all', 'eikonblocks_add_accordion', 30, 2);
