<?php
/**
* Template Name: Sections
*/

$context = Timber::context();

$timber_post     = new Timber\Post();
$context['post'] = $timber_post;

$projects_section = get_field("projects_section", $post);
if ($projects_section) {
  $args = array(
    'post_type' => 'project',
    'posts_per_page' => -1,
    'tax_query' => array(
      'relation' => 'AND',
      array(
        'taxonomy' => 'section',
        'field'    => 'id',
        'terms' => array_values($projects_section)
      ),
      array(
        'taxonomy' => 'year',
        'field'    => 'name',
        'terms'    => "2021"
      ),
    ),
    'orderby' => array(
      'date' => 'DESC'
    )
  );

  $context['projects'] = Timber::get_posts( $args );
}
Timber::render( array( 'page-section.twig' ), $context );
