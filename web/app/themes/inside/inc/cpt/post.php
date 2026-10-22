<?php

/**
 * Customizations for native 'post' post type:
 * - Single/unique category selection (radio buttons in admin)
 * - Server-side single category enforcement
 * - Disambiguate 2-segment URLs (/edition/magazine/ vs /category/post/)
 * - Quick edit single category radio behavior
 */

// Replace default category checklist metabox with a single-choice radio metabox
function eikon_replace_post_category_metabox()
{
    remove_meta_box('categorydiv', 'post', 'side');
    add_meta_box(
        'eikon_single_categorydiv',
        __('Catégorie', 'inside'),
        'eikon_render_single_category_metabox',
        'post',
        'side',
        'core'
    );
}
add_action('admin_menu', 'eikon_replace_post_category_metabox');

// Render single category radio buttons
function eikon_render_single_category_metabox($post)
{
    $categories = get_categories(array(
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ));

    $current_cats = wp_get_post_categories($post->ID);
    $default_cat  = (int) get_option('default_category');
    $selected_cat = !empty($current_cats) ? (int) $current_cats[0] : $default_cat;
    ?>
    <div id="taxonomy-category" class="categorydiv" style="max-height: 220px; overflow: auto; padding: 4px 0;">
        <ul class="categorychecklist form-no-clear" style="margin: 0; padding: 0; list-style: none;">
            <?php foreach ($categories as $cat) : ?>
                <li id="category-<?php echo esc_attr((string) $cat->term_id); ?>" style="margin-bottom: 8px;">
                    <label class="selectit" style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px;">
                        <input
                            type="radio"
                            name="post_category[]"
                            value="<?php echo esc_attr((string) $cat->term_id); ?>"
                            id="in-category-<?php echo esc_attr((string) $cat->term_id); ?>"
                            <?php checked($selected_cat, $cat->term_id); ?>
                            style="margin: 0;"
                        />
                        <span><?php echo esc_html($cat->name); ?></span>
                    </label>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php
}

// Server-side enforcement: ensure only one category is assigned on save
function eikon_enforce_single_category_on_save($post_id, $post, $update)
{
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }

    if ($post->post_type !== 'post') {
        return;
    }

    remove_action('save_post_post', 'eikon_enforce_single_category_on_save', 25);

    $cats = wp_get_post_categories($post_id);
    if (count($cats) > 1) {
        wp_set_post_categories($post_id, array((int) $cats[0]));
    } elseif (empty($cats)) {
        $default_cat = (int) get_option('default_category');
        if ($default_cat) {
            wp_set_post_categories($post_id, array($default_cat));
        }
    }

    add_action('save_post_post', 'eikon_enforce_single_category_on_save', 25, 3);
}
add_action('save_post_post', 'eikon_enforce_single_category_on_save', 25, 3);

// In Quick Edit on edit.php, force category checkboxes to act like radio buttons
function eikon_quick_edit_single_category_script()
{
    global $post_type;
    if ($post_type !== 'post') {
        return;
    }
    ?>
    <script>
    jQuery(function($) {
        $(document).on('change', '.cat-checklist input[type="checkbox"]', function() {
            if ($(this).is(':checked')) {
                $(this).closest('.cat-checklist').find('input[type="checkbox"]').not(this).prop('checked', false);
            }
        });
    });
    </script>
    <?php
}
add_action('admin_footer-edit.php', 'eikon_quick_edit_single_category_script');

// Disambiguate 2-segment URLs (/edition/magazine/ vs /category/post/)
function eikon_disambiguate_two_segment_requests($query_vars)
{
    if (!empty($query_vars['magazine']) && !empty($query_vars['edition'])) {
        $edition_slug = $query_vars['edition'];
        $is_edition   = term_exists($edition_slug, 'edition');

        if (!$is_edition) {
            if (term_exists($edition_slug, 'category')) {
                $post_name = $query_vars['magazine'];
                unset($query_vars['edition'], $query_vars['magazine'], $query_vars['post_type']);
                $query_vars['category_name'] = $edition_slug;
                $query_vars['name']          = $post_name;
            }
        }
    }

    return $query_vars;
}
add_filter('request', 'eikon_disambiguate_two_segment_requests', 10, 1);
