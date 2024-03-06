<?php

add_image_size('medium', 400);

update_option('thumbnail_size_w', 505);
update_option('thumbnail_size_h', 445);

add_action('rest_api_init', 'add_thumbnail_to_JSON');
function add_thumbnail_to_JSON()
{
  //Add featured image
  register_rest_field(
    ['project'],
    'featured_image_src',
    array(
      'get_callback'    => 'get_image_src',
      'update_callback' => null,
      'schema'          => null,
    )
  );
}

function get_image_src($object, $field_name, $request)
{
  $feat_img_array = wp_get_attachment_image_src(
    $object['featured_media'], // Image attachment ID
    'thumbnail',  // Size.  Ex. "thumbnail", "large", "full", etc..
    true // Whether the image should be treated as an icon.
  );
  return $feat_img_array[0];
}
