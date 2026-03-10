<?php

/**
 * Role and Capability Definitions
 *
 * Custom roles are now managed by the Eikon Roles plugin (mu-plugins/eikon-roles/)
 * This file removes legacy roles from other plugins
 */

$wp_roles = new WP_Roles();
// Remove legacy roles from other plugins
$wp_roles->remove_role("matomo_write_role");
$wp_roles->remove_role("matomo_view_role");
$wp_roles->remove_role("matomo_superuser_role");
$wp_roles->remove_role("matomo_admin_role");
