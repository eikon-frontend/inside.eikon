<?php

function custom_nav()
{
  register_nav_menu('EcoleNav', __('Ecole'));
  register_nav_menu('StagesNav', __('Stages'));
  register_nav_menu('InformationsNav', __('Informations'));
}
add_action('init', 'custom_nav');
