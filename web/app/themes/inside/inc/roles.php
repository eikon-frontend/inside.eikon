<?php

$wp_roles = new WP_Roles();
$wp_roles->remove_role("matomo_write_role");
$wp_roles->remove_role("matomo_view_role");
$wp_roles->remove_role("matomo_superuser_role");
$wp_roles->remove_role("matomo_admin_role");

$wp_roles->add_role("supervisor", "Enseignant / enseignante");
