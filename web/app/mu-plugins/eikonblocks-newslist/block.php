<?php

/**
 * Plugin Name:       eikonblocks: NEWS LIST
 * Description:       News list block scaffolded with Create Block tool for eikon website.
 * Requires at least: 6.1
 * Requires PHP:      7.0
 * Version:           0.1.0
 * Author:            The WordPress Contributors
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       eikon
 *
 * @package           eikonblocks/newslist
 */

if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly.
}

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function eikonblocks_newslist_init()
{
  register_block_type(__DIR__ . '/build', [
    'render_callback' => 'eikonblocks_newslist_render',
  ]);
}
add_action('init', 'eikonblocks_newslist_init');

/**
 * Render callback for the news list block.
 *
 * @param array $attributes Block attributes.
 * @param string $content Block content.
 * @return string Rendered block output.
 */
function eikonblocks_newslist_render($attributes, $content)
{
  $selected_taxonomies = isset($attributes['selectedTaxonomies']) ? $attributes['selectedTaxonomies'] : [];
  $posts_per_page = isset($attributes['postsPerPage']) ? (int)$attributes['postsPerPage'] : 10;

  // Build query args
  $args = [
    'post_type' => 'post',
    'post_status' => 'publish',
    'posts_per_page' => $posts_per_page,
    'orderby' => 'date',
    'order' => 'DESC',
  ];

  // Add taxonomy filtering if terms are selected
  if (!empty($selected_taxonomies) && is_array($selected_taxonomies)) {
    $tax_query = [];

    foreach ($selected_taxonomies as $taxonomy_slug => $term_names) {
      if (is_array($term_names) && !empty($term_names)) {
        foreach ($term_names as $term_name) {
          // Find the term by name within the taxonomy
          $term = get_term_by('name', $term_name, $taxonomy_slug);

          if (!$term) {
            // Fallback to slug if name doesn't work
            $term = get_term_by('slug', $term_name, $taxonomy_slug);
          }

          if ($term) {
            $tax_query[] = [
              'taxonomy' => $taxonomy_slug,
              'field' => 'id',
              'terms' => $term->term_id,
            ];
          }
        }
      }
    }

    if (!empty($tax_query)) {
      if (count($tax_query) > 1) {
        $tax_query['relation'] = 'OR';
      }
      $args['tax_query'] = $tax_query;
    }
  }

  $query = new WP_Query($args);

  ob_start();
?>
  <div class="wp-block-eikonblocks-newslist">
    <?php
    if ($query->have_posts()) {
    ?>
      <div class="newslist-items">
        <?php
        while ($query->have_posts()) {
          $query->the_post();
        ?>
          <article class="newslist-item">
            <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
            <div class="newslist-meta">
              <span class="newslist-date"><?php echo get_the_date('j F Y'); ?></span>
            </div>
            <div class="newslist-excerpt">
              <?php the_excerpt(); ?>
            </div>
          </article>
        <?php
        }
        ?>
      </div>
    <?php
    } else {
    ?>
      <p><?php esc_html_e('No news found.', 'eikon'); ?></p>
    <?php
    }
    ?>
  </div>
<?php
  wp_reset_postdata();

  return ob_get_clean();
}
