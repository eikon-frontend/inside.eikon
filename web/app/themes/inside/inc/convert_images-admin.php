<?php

// Add a button to the admin menu for converting images to WebP
function add_convert_images_button()
{
  add_management_page(
    'Convert Images to WebP',
    'Convert Images to WebP',
    'manage_options',
    'convert-images-to-webp',
    'convert_images_to_webp_page'
  );
}
add_action('admin_menu', 'add_convert_images_button');

// Display the conversion page
function convert_images_to_webp_page()
{
?>
  <div class="wrap">
    <h1>Convert Images to WebP</h1>
    <h2>Image Sizes and Dimensions</h2>
    <ul>
      <?php
      $sizes = list_image_sizes();
      foreach ($sizes as $size => $details) {
        echo '<li>' . $size . ': ' . $details['width'] . 'x' . $details['height'] . ' (Crop: ' . ($details['crop'] ? 'Yes' : 'No') . ')</li>';
      }
      ?>
    </ul>
    <form method="post" action="">
      <?php submit_button('Convert Now'); ?>
    </form>
  </div>
<?php

  // Handle form submission
  if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    convert_existing_images_to_webp();
    echo '<div class="updated"><p>Images have been converted to WebP format.</p></div>';
  }
}

// Convert existing images to WebP format
function convert_existing_images_to_webp()
{
  $upload_dir = wp_upload_dir();
  $base_dir = $upload_dir['basedir'];

  $images = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base_dir));
  foreach ($images as $image) {
    if ($image->isFile() && preg_match('/\.(jpg|jpeg|png)$/i', $image->getFilename()) && !preg_match('/\.webp$/i', $image->getFilename())) {
      $file = $image->getPathname();
      $webp_file = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $file);
      $image_editor = wp_get_image_editor($file);
      if (!is_wp_error($image_editor)) {
        $image_editor->save($webp_file, 'image/webp');
      }
    }
  }
}

// List all image sizes and their dimensions
function list_image_sizes()
{
  global $_wp_additional_image_sizes;

  $sizes = array();
  foreach (get_intermediate_image_sizes() as $size) {
    if (in_array($size, array('thumbnail', 'medium', 'large'))) {
      $sizes[$size]['width'] = get_option("{$size}_size_w");
      $sizes[$size]['height'] = get_option("{$size}_size_h");
      $sizes[$size]['crop'] = (bool) get_option("{$size}_crop");
    } elseif (isset($_wp_additional_image_sizes[$size])) {
      $sizes[$size] = array(
        'width' => $_wp_additional_image_sizes[$size]['width'],
        'height' => $_wp_additional_image_sizes[$size]['height'],
        'crop' => $_wp_additional_image_sizes[$size]['crop'],
      );
    }
  }
  return $sizes;
}
