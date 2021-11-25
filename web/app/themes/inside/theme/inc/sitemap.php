<?php

add_filter( 'wp_sitemaps_add_provider', function ($provider, $name) {
  return ( $name == 'users' ) ? false : $provider;
}, 10, 2);

function remove_tax_from_sitemap( $taxonomies ) {
     unset( $taxonomies['years'] );
     unset( $taxonomies['category'] );
     unset( $taxonomies['subjects'] );
     return $taxonomies;
}

add_filter( 'wp_sitemaps_taxonomies', 'remove_tax_from_sitemap' );
