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

// Restrict file uploads to only images and PDFs
add_filter('upload_mimes', function ($mimes) {
  // Remove all existing MIME types
  $mimes = array();

  // Add only image and PDF MIME types
  $mimes['jpg|jpeg|jpe'] = 'image/jpeg';
  $mimes['png'] = 'image/png';
  $mimes['webp'] = 'image/webp';
  $mimes['pdf'] = 'application/pdf';
  $mimes['zip'] = 'application/zip';

  return $mimes;
});

// Additional security check for file uploads
add_filter('wp_check_filetype_and_ext', function ($data, $file, $filename, $mimes) {
  $wp_filetype = wp_check_filetype($filename, $mimes);
  $ext = $wp_filetype['ext'];
  $type = $wp_filetype['type'];

  // List of allowed extensions
  $allowed_extensions = array('jpg', 'jpeg', 'jpe', 'gif', 'png', 'bmp', 'tiff', 'tif', 'webp', 'ico', 'heic', 'svg', 'pdf');

  if (!in_array($ext, $allowed_extensions)) {
    return array(
      'ext' => false,
      'type' => false,
      'proper_filename' => false
    );
  }

  return $data;
}, 10, 4);

// Custom error message for rejected file types
add_filter('upload_error_messages', function ($messages) {
  $messages[false] = __('Sorry! This file type is not allowed. Only images (JPG, PNG, GIF, WebP, SVG, etc.) and PDF files are permitted.');
  return $messages;
});

// Hide the "Add New Media" page from WordPress admin
add_action('admin_menu', function () {
  remove_submenu_page('upload.php', 'media-new.php');
});

// Redirect users if they try to access media-new.php directly
add_action('admin_init', function () {
  global $pagenow;
  if ($pagenow === 'media-new.php') {
    wp_redirect(admin_url('upload.php'));
    exit;
  }
});

// Rename "Médiathèque" submenu to "Images"
add_action('admin_menu', function () {
  global $submenu;

  // Rename the "Médiathèque" (Library) submenu item to "Images"
  if (isset($submenu['upload.php'])) {
    foreach ($submenu['upload.php'] as $key => $item) {
      if ($item[2] === 'upload.php') {
        $submenu['upload.php'][$key][0] = 'Images';
        break;
      }
    }
  }
}, 999); // High priority to ensure it runs after other menu modifications
