<?php
/**
 * The template for displaying a single project
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
 */

$context = Timber::context();

$timber_post     = new Timber\Post();
$context['post'] = $timber_post;

if ($timber_post) {
  $first_term = get_the_terms( $timber_post->ID, 'section' )[0];
  $archive_page = get_field('archive_page', 'term_'.$first_term->term_id);

  $context['archive_page_name'] = $archive_page->post_title;
  $context['archive_page_url'] =  get_page_link($archive_page);
}

Timber::render( array( 'single-project.twig' ), $context );
