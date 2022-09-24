<?php

function custom_nav() {
  register_nav_menu('mainNav',__( 'Menu principal' ));
}
add_action( 'init', 'custom_nav' );
