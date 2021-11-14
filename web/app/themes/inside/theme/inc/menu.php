<?php

function custom_nav() {
  register_nav_menu('main-nav',__( 'Menu principal' ));
}
add_action( 'init', 'custom_nav' );
