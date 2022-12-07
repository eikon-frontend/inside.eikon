<?php

function custom_nav() {
  register_nav_menu('2022MainNav',__( 'Menu principal' ));
  register_nav_menu('2022SecondaryNav',__('Menu secondaire' ));
  register_nav_menu('2022FooterNav',__('Menu footer' ));
}
add_action( 'init', 'custom_nav' );
