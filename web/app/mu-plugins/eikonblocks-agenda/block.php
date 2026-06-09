<?php

/**
 * Plugin Name:       eikonblocks: Agenda
 * Description:       Agenda block for displaying a list of events with date, title, and optional link.
 * Requires at least: 6.1
 * Requires PHP:      7.0
 * Version:           0.1.0
 * Author:            The WordPress Contributors
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       eikon
 *
 * @package           eikonblocks/agenda
 */

if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly.
}

function eikonblocks_agenda_init()
{
  register_block_type(__DIR__ . '/build');
}
add_action('init', 'eikonblocks_agenda_init');
