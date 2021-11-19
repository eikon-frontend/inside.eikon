<?php
/**
* Template Name: Sections
*/

$context = Timber::context();

$timber_post     = new Timber\Post();
$context['post'] = $timber_post;

$projects_years = get_field("projects_years", $post);
if ($projects_years) {
  $args = array(
    'post_type' => 'project',
    'posts_per_page' => -1,
    'tax_query' => array(
      array(
        'taxonomy' => 'years',
        'field'    => 'id',
        'terms' => array_values($projects_years)
      ),
    ),
    'orderby' => array(
      'date' => 'DESC'
    )
  );

  $context['projects'] = Timber::get_posts( $args );
}
Timber::render( array( 'page-section.twig' ), $context );
