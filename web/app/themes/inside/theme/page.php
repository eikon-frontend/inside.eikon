<?php
/**
 * The template for displaying all pages.
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site will use a
 * different template.
 *
 * To generate specific templates for your pages you can use:
 * /mytheme/templates/page-mypage.twig
 * (which will still route through this PHP file)
 * OR
 * /mytheme/page-mypage.php
 * (in which case you'll want to duplicate this file and save to the above path)
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
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
Timber::render( array( 'page-' . $timber_post->post_name . '.twig', 'page.twig' ), $context );
