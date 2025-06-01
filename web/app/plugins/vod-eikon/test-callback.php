#!/usr/bin/env php
<?php
/**
 * VOD Callback Test Script
 *
 * This script simulates Infomaniak VOD callbacks to test the callback implementation.
 * Run this script to verify that the callback system is working correctly.
 */

// Simulate callback data for testing
$callback_tests = [
  'media_ready' => [
    'event' => 'media_ready',
    'data' => [
      'id' => 'test-video-123',
      'title' => 'Test Video Title',
      'name' => 'Test Video Name',
      'poster' => 'https://example.com/poster.jpg',
      'encoded_medias' => [
        [
          'id' => 'media-1',
          'size' => 1024000,
          'url' => 'https://example.com/video.mp4'
        ],
        [
          'id' => 'media-2',
          'size' => 512000,
          'url' => 'https://example.com/video_low.mp4'
        ]
      ],
      'streams' => [
        [
          'type' => 'dash',
          'url' => 'https://play.vod2.infomaniak.com/dash/test-video-123/test-video-123/,media-1,media-2,.urlset/manifest.mpd'
        ]
      ]
    ]
  ],
  'media_deleted' => [
    'event' => 'media_deleted',
    'data' => [
      'id' => 'test-video-456'
    ]
  ]
];

echo "VOD Eikon Callback Test Script\n";
echo "==============================\n\n";

foreach ($callback_tests as $test_name => $callback_data) {
  echo "Testing {$test_name} callback...\n";
  echo "Callback data:\n";
  echo json_encode($callback_data, JSON_PRETTY_PRINT) . "\n\n";

  // You can use this data to test your callback endpoint manually
  echo "To test this callback, send a POST request to:\n";
  echo "URL: https://your-site.com/vod-callback/\n";
  echo "Method: POST\n";
  echo "Content-Type: application/json\n";
  echo "Body: " . json_encode($callback_data) . "\n\n";

  echo "Example curl command:\n";
  echo "curl -X POST \\\n";
  echo "  https://your-site.com/vod-callback/ \\\n";
  echo "  -H 'Content-Type: application/json' \\\n";
  echo "  -d '" . json_encode($callback_data) . "'\n\n";

  echo "Expected behavior:\n";
  if ($test_name === 'media_ready') {
    echo "- Video should be added/updated in WordPress database\n";
    echo "- Poster URL should be extracted and stored\n";
    echo "- MPD URL should be constructed from encoded_medias\n";
    echo "- Log entry should show 'VOD Callback: Processing media_ready event for video: test-video-123'\n";
  } else if ($test_name === 'media_deleted') {
    echo "- Video should be removed from WordPress database\n";
    echo "- Log entry should show 'VOD Callback: Processing media_deleted event for video: test-video-456'\n";
  }
  echo "\n" . str_repeat("-", 50) . "\n\n";
}

echo "Callback System Summary:\n";
echo "========================\n";
echo "1. Callback URL: https://your-site.com/vod-callback/\n";
echo "2. Supported events: media_ready, media_deleted, vod.media.processed (legacy)\n";
echo "3. HTTP method: POST\n";
echo "4. Content-Type: application/json\n";
echo "5. Response: HTTP 200 OK\n";
echo "6. Logging: Check WordPress debug.log for callback processing messages\n\n";

echo "Configuration Steps:\n";
echo "====================\n";
echo "1. Configure Infomaniak VOD webhook URL: https://your-site.com/vod-callback/\n";
echo "2. Enable the following events in Infomaniak VOD dashboard:\n";
echo "   - media_ready (when video processing is complete)\n";
echo "   - media_deleted (when video is deleted)\n";
echo "3. Ensure WordPress rewrite rules are flushed (plugin activation does this)\n";
echo "4. Enable WordPress debug logging to monitor callback processing\n\n";

echo "Troubleshooting:\n";
echo "================\n";
echo "1. Check WordPress debug.log for callback processing messages\n";
echo "2. Verify callback endpoint is accessible: curl -I https://your-site.com/vod-callback/\n";
echo "3. Test with example callback data above\n";
echo "4. Ensure Infomaniak webhook is configured correctly\n";
echo "5. Check that environment variables INFOMANIAK_CHANNEL_ID and INFOMANIAK_TOKEN_API are set\n\n";
