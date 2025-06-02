# ACF VOD Video Field

A custom Advanced Custom Fields (ACF) field type that allows you to select videos from the Infomaniak VOD system.

## Description

This plugin adds a new field type to Advanced Custom Fields that enables you to browse and select videos from your Infomaniak VOD library directly from the WordPress admin interface. It provides a user-friendly modal interface for searching and selecting videos, and displays a preview of the selected video in the edit screen.

### Features

- Browse and search videos from your Infomaniak VOD library
- Filter videos by publication status (only show fully processed videos by default)
- Preview selected videos with thumbnails
- Select videos via a modal interface
- Various return formats (array, ID, or URL)
- Fully responsive design
- Compatible with ACF repeaters and flexible content fields

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- Advanced Custom Fields 5.7.0 or higher
- Active Infomaniak VOD account

## Installation

1. Upload the `vod-video-field` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. The field type will now be available in ACF when creating or editing a field group

## Usage

### Adding the Field

1. Create a new field group or edit an existing one in ACF
2. Add a new field and select "VOD Video" as the field type
3. Configure the field settings as needed
4. Save the field group

### Using in Templates

When you retrieve the field value in your templates, the format will depend on your "Return Format" setting:

#### Array Return Format (default)

```php
$video = get_field('your_field_name');
if ($video) {
    echo '<h3>' . esc_html($video['title']) . '</h3>';
    echo '<div class="video-embed">';
    echo '<img src="' . esc_url($video['thumbnail']) . '" alt="' . esc_attr($video['title']) . '">';
    echo '<a href="' . esc_url($video['url']) . '">Watch Video</a>';
    echo '</div>';
}
```

#### ID Return Format

```php
$video_id = get_field('your_field_name');
if ($video_id) {
    echo 'Video ID: ' . esc_html($video_id);
    // Use the ID to fetch video details as needed
}
```

#### URL Return Format

```php
$video_url = get_field('your_field_name');
if ($video_url) {
    echo '<a href="' . esc_url($video_url) . '">Watch Video</a>';
}
```

## Field Settings

### Return Format

Specify how the field value should be returned in templates:

- **Video Array**: Returns an array of video data including ID, title, thumbnail URL, video URL, etc.
- **Video ID**: Returns only the video ID
- **Video URL**: Returns only the video URL

### Show Published Videos Only

Controls whether the field should only display videos that have completed both encoding and thumbnail generation:

- **Enabled** (default): Only shows videos where both encoding and thumbnail generation are complete (published = 1)
- **Disabled**: Shows all videos regardless of processing status

This setting is useful for ensuring that only fully processed videos are available for selection in production environments, while allowing access to all videos during development or administrative tasks.

## Developer Hooks

### Filters

#### acf/fields/vod_video/api_results

Modify the video results returned from the API.

```php
add_filter('acf/fields/vod_video/api_results', 'my_custom_video_results', 10, 3);
function my_custom_video_results($results, $search_term, $field) {
    // Modify $results
    return $results;
}
```

#### acf/fields/vod_video/value

Modify the field value before it's returned to the template.

```php
add_filter('acf/fields/vod_video/value', 'my_custom_video_value', 10, 3);
function my_custom_video_value($value, $post_id, $field) {
    // Modify $value
    return $value;
}
```

### Actions

#### acf/fields/vod_video/selected

Triggered when a video is selected.

```php
add_action('acf/fields/vod_video/selected', 'my_video_selected_callback', 10, 3);
function my_video_selected_callback($video_id, $field, $post_id) {
    // Do something when a video is selected
}
```

## Troubleshooting

### No Videos Appear in the Selection Modal

- Ensure your Infomaniak VOD credentials are correctly configured
- Check that you have videos in your VOD library
- Verify that your server can connect to the Infomaniak API

### Video Preview Not Loading

- Check that the video thumbnail URL is accessible
- Ensure the browser can load the image from the URL

## Support

For support, please create an issue on the [GitHub repository](#) or contact EIKON directly.

## Credits

Developed by [EIKON](https://eikon.ch).

## License

This plugin is licensed under the GPL v2 or later.
