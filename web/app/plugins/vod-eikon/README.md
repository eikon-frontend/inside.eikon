# VOD Eikon Plugin

A WordPress plugin that integrates with the Infomaniak VOD API to manage and display videos directly from WordPress.

## Features

- **Real-time callback system** for instant video processing updates
- **Upload videos directly to Infomaniak VOD via WordPress admin**
- Synchronize videos from Infomaniak VOD API
- Store video metadata in WordPress database
- Tabbed admin interface for managing and uploading videos
- Helper functions for displaying videos in themes
- DashJS integration for MPD video playback
- Automatic daily synchronization (fallback)
- Video grid display with modal player
- Real-time upload progress tracking
- Event-driven video updates (media_deleted)

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

### Callback System Setup

**Important:** Configure Infomaniak VOD webhook for real-time updates:

1. **Callback URL:** `https://your-site.com/vod-callback/`
2. **Events to enable:**
   - `encoding_finished` - When video encoding is complete
   - `thumbnail_finished` - When thumbnail generation is complete
   - `media_deleted` - When video is deleted
3. **HTTP Method:** POST
4. **Content-Type:** application/json

The callback system replaces the old polling mechanism and provides instant updates when videos are processed or deleted on Infomaniak's servers.

### Video Publication Workflow

Videos go through a simplified processing workflow:

1. **Upload**: Video is uploaded to Infomaniak VOD
2. **Processing**: Video encoding and thumbnail generation occur in parallel
3. **Auto-Publishing**: Videos are automatically marked as published when both MPD URL and poster are available
4. **Published**: Only published videos are shown by default in helper functions and ACF fields

The plugin includes automatic publishing logic that:

- Checks during video sync for videos with both poster and MPD URL
- Automatically marks them as published (`published = 1`)
- Ensures no videos are missed for publishing, even if they were processed while the plugin was inactive
- Runs both during manual sync operations and automatic update checks

## Database Structure

The plugin creates a table `wp_vod_eikon_videos` with the following fields:

- `id` - Auto-incremented primary key
- `vod_id` - Unique VOD ID from Infomaniak
- `name` - Video title/name
- `poster` - URL to video poster/thumbnail image
- `mpd_url` - MPD URL for DashJS integration
- `published` - Boolean flag indicating if video is fully processed and ready (default: 0)
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

### `vod_eikon_get_videos($published_only)`

Get all videos from the database.

```php
// Get only published videos (default)
$videos = vod_eikon_get_videos();
foreach ($videos as $video) {
    echo '<h3>' . $video->name . '</h3>';
    echo '<img src="' . $video->poster . '" alt="' . $video->name . '">';
}

// Get all videos including unpublished ones
$all_videos = vod_eikon_get_videos(false);
```

**Parameters:**

- `$published_only` (bool, optional) - Whether to only return published videos. Default: `true`

### `vod_eikon_get_video($vod_id, $published_only)`

Get a specific video by its VOD ID.

```php
// Get a published video (default)
$video = vod_eikon_get_video('your_vod_id');
if ($video) {
    echo '<h3>' . $video->name . '</h3>';
    echo '<p>MPD URL: ' . $video->mpd_url . '</p>';
}

// Get any video regardless of publication status
$any_video = vod_eikon_get_video('your_vod_id', false);
```

**Parameters:**

- `$vod_id` (string) - The VOD ID of the video to retrieve
- `$published_only` (bool, optional) - Whether to only return published videos. Default: `true`

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

### `vod_eikon_video_grid($options, $published_only)`

Display a grid of video thumbnails with modal player.

```php
// Basic grid (published videos only)
echo vod_eikon_video_grid();

// Custom grid with published videos only
echo vod_eikon_video_grid(array(
    'columns' => 4,
    'show_title' => true,
    'link_to_player' => true
));

// Include all videos regardless of status
echo vod_eikon_video_grid(array(
    'columns' => 3,
    'show_title' => true,
    'link_to_player' => true
), false);
```

**Parameters:**

- `$options` (array, optional) - Display options for the grid
- `$published_only` (bool, optional) - Whether to only show published videos. Default: `true`

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

4. **Videos missing poster images or playback URLs**
   - This is normal for newly uploaded videos that are still processing
   - Processing typically takes 5-30 minutes depending on video size and quality
   - The plugin automatically checks for updates at regular intervals
   - Use the "Update Incomplete Videos" button to manually check for processing completion
   - Videos still processing will show a clock icon and "Processing" label in the admin table

### Video Processing Flow

When a video is uploaded:

1. **Upload**: Video is uploaded to Infomaniak's servers
2. **Initial Sync**: Video is added to WordPress database (may be missing poster/MPD URL)
3. **Processing**: Infomaniak processes the video (encoding, thumbnail generation)
4. **Callback Updates**: Infomaniak sends `encoding_finished` callback when processing is complete
5. **Automatic Database Update**: Plugin receives callback and updates video data instantly
6. **Fallback Sync**: Daily synchronization runs as a fallback for any missed updates

### Callback System Troubleshooting

1. **Callback endpoint not accessible**

   - Test endpoint: `curl -I https://your-site.com/vod-callback/`
   - Should return HTTP 200 or 405 (method not allowed for GET)
   - Ensure WordPress rewrite rules are flushed (deactivate/reactivate plugin)

2. **Callbacks not being received**

   - Check Infomaniak VOD webhook configuration
   - Verify callback URL is correctly set: `https://your-site.com/vod-callback/`
   - Ensure your server is accessible from external sources (no firewall blocking)
   - Check WordPress debug.log for callback processing messages

3. **Testing callbacks manually**
   - Use the test script: `php test-callback.php` (in plugin directory)
   - Send test POST requests with JSON payload to callback endpoint
   - Look for log entries: "VOD Callback: Processing encoding_finished event for video: ..."

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
