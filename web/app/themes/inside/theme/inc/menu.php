<?php

function custom_nav() {
  register_nav_menu('mainNav',__( 'Menu principal' ));
  register_nav_menu('2022MainNav',__( '2022 Menu principal' ));
  register_nav_menu('2022SecondaryNav',__('2022 Menu secondaire' ));
  register_nav_menu('2022FooterNav',__('2022 Menu footer' ));
}
add_action( 'init', 'custom_nav' );
