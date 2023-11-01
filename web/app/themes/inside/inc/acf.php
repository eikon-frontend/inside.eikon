<?php

if (function_exists('acf_add_options_page') && current_user_can('manage_options')) {
  acf_add_options_page(array(
    'page_title'   => 'Paramètres du thème',
    'menu_title'  => 'Paramètres du thème',
    'menu_slug'   => 'theme-settings',
    'capability'  => 'edit_posts',
    'redirect'    => false,
    'icon_url' => 'dashicons-block-default'
  ));
}
