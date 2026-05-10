<?php

/**
 * User Import from CSV
 *
 * Allows administrators to import users from a CSV file
 * CSV format: Nom, Prénom, E-Mail, Classe
 */

/**
 * Add User Import menu page to WordPress admin
 */
function eikon_add_user_import_menu()
{
  add_users_page(
    __('Importer', 'eikon'),
    __('Importer', 'eikon'),
    'manage_options',
    'eikon-user-import',
    'eikon_user_import_page'
  );
}
add_action('admin_menu', 'eikon_add_user_import_menu');
