<?php

if( function_exists('acf_add_options_page') ) {

	acf_add_options_page(array(
		'page_title' 	=> 'Paramètres du thème',
		'menu_title'	=> 'Paramètres du thème',
		'menu_slug' 	=> 'theme-settings',
		'capability'	=> 'edit_posts',
		'redirect'		=> false,
    'icon_url' => 'dashicons-block-default'
	));

	acf_add_options_sub_page(array(
		'page_title' 	=> 'Footer',
		'menu_title'	=> 'Footer',
		'parent_slug'	=> 'theme-settings',
    'icon_url' => 'dashicons-block-default'
	));

}
