# VOD Eikon Plugin

A WordPress plugin that integrates with the Infomaniak VOD API to manage and display videos directly from WordPress.

## Features

- Synchronize videos from Infomaniak VOD API
- **Upload videos directly to Infomaniak VOD via WordPress admin**
- Store video metadata in WordPress database
- Tabbed admin interface for managing and uploading videos
- Helper functions for displaying videos in themes
- DashJS integration for MPD video playback
- Automatic daily synchronization
- Video grid display with modal player
- Real-time upload progress tracking

## Installation

1. Upload the `vod-eikon` folder to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Set up environment variables (see Configuration section)

## Configuration

You need to set two environment variables in your `.env` file or server configuration:

```
INFOMANIAK_CHANNEL_ID=your_channel_id
INFOMANIAK_TOKEN_API=your_api_token
```

## Database Structure

The plugin creates a table `wp_vod_eikon_videos` with the following fields:

- `id` - Auto-incremented primary key
- `vod_id` - Unique VOD ID from Infomaniak
- `name` - Video title/name
- `poster` - URL to video poster/thumbnail image
- `mpd_url` - MPD URL for DashJS integration
- `created_at` - Timestamp when record was created
- `updated_at` - Timestamp when record was last updated

## Admin Interface

Access the admin interface through **Media > VOD Videos** in your WordPress admin.

### Video Library Tab

- View all synchronized videos in a table
- Synchronize videos manually with the "Synchronize Videos" button
- Delete videos from local database
- Copy MPD URLs to clipboard by clicking on them
- View video posters/thumbnails

### Upload Video Tab

- Upload video files directly to Infomaniak VOD
- Supported formats: MP4, MOV, AVI, MKV
- Maximum file size: 2GB
- Real-time upload progress tracking
- Automatic video title and description
- Cancel upload functionality
- Automatic sync after successful upload
- View video posters/thumbnails

## Helper Functions

The plugin provides several helper functions for use in themes and other plugins:

### `vod_eikon_get_videos()`

Get all videos from the database.

```php
$videos = vod_eikon_get_videos();
foreach ($videos as $video) {
    echo '<h3>' . $video->name . '</h3>';
    echo '<img src="' . $video->poster . '" alt="' . $video->name . '">';
}
```

### `vod_eikon_get_video($vod_id)`

Get a specific video by its VOD ID.

```php
$video = vod_eikon_get_video('your_vod_id');
if ($video) {
    echo '<h3>' . $video->name . '</h3>';
    echo '<p>MPD URL: ' . $video->mpd_url . '</p>';
}
```

### `vod_eikon_player($vod_id, $options)`

Generate a DashJS video player for a specific video.

```php
// Basic player
echo vod_eikon_player('your_vod_id');

// Custom player with options
echo vod_eikon_player('your_vod_id', array(
    'width' => '800px',
    'height' => '450px',
    'autoplay' => true,
    'controls' => true
));
```

#### Player Options:

- `width` - Player width (default: '100%')
- `height` - Player height (default: '400px')
- `autoplay` - Auto-play video (default: false)
- `controls` - Show player controls (default: true)
- `poster` - Custom poster URL (default: uses video poster)

### `vod_eikon_video_grid($options)`

Display a grid of video thumbnails with modal player.

```php
// Basic grid
echo vod_eikon_video_grid();

// Custom grid
echo vod_eikon_video_grid(array(
    'columns' => 4,
    'show_title' => true,
    'link_to_player' => true
));
```

#### Grid Options:

- `columns` - Number of columns (default: 3)
- `show_title` - Show video titles (default: true)
- `link_to_player` - Enable modal player (default: true)

## Usage Examples

### Display Videos in a Template

```php
// In your theme template file
$videos = vod_eikon_get_videos();

if (!empty($videos)) {
    echo '<div class="video-gallery">';
    foreach ($videos as $video) {
        echo '<div class="video-item">';
        echo '<img src="' . esc_url($video->poster) . '" alt="' . esc_attr($video->name) . '">';
        echo '<h4>' . esc_html($video->name) . '</h4>';
        echo vod_eikon_player($video->vod_id, array('height' => '300px'));
        echo '</div>';
    }
    echo '</div>';
}
```

### Shortcode Implementation

You can create a shortcode to display videos:

```php
// Add to your theme's functions.php
function vod_eikon_shortcode($atts) {
    $atts = shortcode_atts(array(
        'id' => '',
        'columns' => 3,
        'grid' => false
    ), $atts);

    if ($atts['grid']) {
        return vod_eikon_video_grid(array('columns' => $atts['columns']));
    } elseif ($atts['id']) {
        return vod_eikon_player($atts['id']);
    }

    return '';
}
add_shortcode('vod_eikon', 'vod_eikon_shortcode');
```

Usage:

```
[vod_eikon id="your_vod_id"]
[vod_eikon grid="true" columns="4"]
```

## API Integration

The plugin uses the Infomaniak VOD API endpoint:

```
GET https://api.infomaniak.com/1/vod/channel/{channel}/media
```

### Authentication

The API requires a Bearer token passed in the Authorization header:

```
Authorization: Bearer your_api_token
```

## Automatic Synchronization

The plugin automatically synchronizes videos daily using WordPress cron. You can also trigger manual synchronization through the admin interface.

## Troubleshooting

### Common Issues

1. **Videos not synchronizing**

   - Check that environment variables are set correctly
   - Verify API token has proper permissions
   - Check error logs for API response issues

2. **Video player not loading**

   - Ensure MPD URL is valid
   - Check that DashJS library is loading properly
   - Verify video is accessible from your domain

3. **Missing videos**
   - Run manual synchronization from admin interface
   - Check API response format matches expected structure

### Debug Mode

Enable WordPress debug mode to see detailed error messages:

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check `/wp-content/debug.log` for error messages.

## Requirements

- WordPress 5.0+
- PHP 7.4+
- cURL extension
- Valid Infomaniak VOD API credentials

## License

This plugin is proprietary software developed by EIKON. All rights reserved.

## Support

For support and questions, contact EIKON at [https://eikon.ch](https://eikon.ch).
