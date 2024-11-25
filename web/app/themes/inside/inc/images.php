<?php

add_theme_support('post-thumbnails');

add_filter('intermediate_image_sizes', function ($sizes) {
  return array_filter($sizes, function ($val) {
    return !in_array($val, ['medium_large', '1536x1536', '2048x2048']); // Filter out 'medium_large', '1536x1536', and '2048x2048'
  });
});

// Filter to convert images to WebP format upon upload
add_filter('wp_generate_attachment_metadata', function ($metadata) {
  $upload_dir = wp_upload_dir();

  // Convert original image to WebP format if not already WebP
  $original_file = $upload_dir['basedir'] . '/' . $metadata['file'];
  if (!preg_match('/\.webp$/i', $original_file)) {
    $original_webp_file = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $original_file);
    $original_image = wp_get_image_editor($original_file);
    if (!is_wp_error($original_image)) {
      $original_image->save($original_webp_file, 'image/webp');
    }
  }

  // Convert each image size variation to WebP format
  foreach ($metadata['sizes'] as $size => $sizeInfo) {
    $file = $upload_dir['basedir'] . '/' . dirname($metadata['file']) . '/' . $sizeInfo['file'];
    if (!preg_match('/\.webp$/i', $file)) {
      $webp_file = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $file);
      $image = wp_get_image_editor($file);
      if (!is_wp_error($image)) {
        $image->save($webp_file, 'image/webp');
        unlink($file); // Remove the original size variation
        $metadata['sizes'][$size]['file'] = basename($webp_file);
      }
    }
  }
  return $metadata;
});

// Function to remove WebP version of an image when the image is deleted
add_action('delete_attachment', function ($post_id) {
  $file = get_attached_file($post_id);
  $webp_file = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $file);

  if (file_exists($webp_file)) {
    unlink($webp_file);
  }

  $metadata = wp_get_attachment_metadata($post_id);
  if (isset($metadata['sizes'])) {
    $upload_dir = wp_upload_dir();
    foreach ($metadata['sizes'] as $size => $sizeInfo) {
      $size_file = $upload_dir['basedir'] . '/' . dirname($metadata['file']) . '/' . $sizeInfo['file'];
      $size_webp_file = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $size_file);
      if (file_exists($size_webp_file)) {
        unlink($size_webp_file);
      }
    }
  }
});

// Filter to serve WebP images if they exist
add_filter('wp_get_attachment_url', function ($url, $post_id) {
  $upload_dir = wp_upload_dir();
  $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $url);
  $webp_file_path = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $file_path);

  if (file_exists($webp_file_path)) {
    $url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $webp_file_path);
  }

  return $url;
}, 10, 2);
