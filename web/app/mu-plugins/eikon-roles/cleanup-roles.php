<?php

/**
 * One-time cleanup script for orphaned roles
 *
 * This file can be run once to clean up any roles that exist in the database
 * but should not. Delete this file after running.
 *
 * To use:
 * 1. Include this in your theme functions.php or another bootstrap file temporarily
 * 2. Load any admin page to trigger the cleanup
 * 3. Delete this file
 *
 * Or run via WP-CLI:
 * wp eval-file web/app/mu-plugins/eikon-roles/cleanup-roles.php
 */

if (!defined('ABSPATH')) {
  return;
}

global $wp_roles;

if (!$wp_roles) {
  $wp_roles = new WP_Roles();
}

// List of roles to completely remove from the database
$roles_to_remove = array(
  'supervisor',
  'subscriber',
  'responsable_de_branche',
  'responsable-de-branche',
  'branch_manager',
  'branch-manager',
);

foreach ($roles_to_remove as $role_name) {
  remove_role($role_name);
}
