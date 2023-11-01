<?php

function custom_nav()
{
  register_nav_menu('MainNav', __('Menu principal'));
  register_nav_menu('SecondaryNav', __('Menu secondaire'));
}
add_action('init', 'custom_nav');
