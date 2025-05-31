<?php

/**
 * WordPress environment test for poster API variables
 */

// This needs to be run from within WordPress context
if (!defined('ABSPATH')) {
  // Load WordPress environment
  require_once __DIR__ . '/web/wp-config.php';
}

echo "=== WordPress Environment Test ===\n";
echo "WP_ENV: " . (defined('WP_ENV') ? WP_ENV : 'not defined') . "\n";

// Test environment variables
$channelId = getenv('INFOMANIAK_CHANNEL_ID');
$apiToken = getenv('INFOMANIAK_TOKEN_API');

echo "INFOMANIAK_CHANNEL_ID: " . ($channelId ? $channelId : 'NOT FOUND') . "\n";
echo "INFOMANIAK_TOKEN_API: " . ($apiToken ? substr($apiToken, 0, 10) . '...' : 'NOT FOUND') . "\n";

// Test $_ENV superglobal
echo "\nUsing \$_ENV:\n";
echo "INFOMANIAK_CHANNEL_ID: " . ($_ENV['INFOMANIAK_CHANNEL_ID'] ?? 'NOT FOUND') . "\n";
echo "INFOMANIAK_TOKEN_API: " . (isset($_ENV['INFOMANIAK_TOKEN_API']) ? substr($_ENV['INFOMANIAK_TOKEN_API'], 0, 10) . '...' : 'NOT FOUND') . "\n";

// Load the VOD plugin and test
if (class_exists('EasyVod')) {
  echo "\nVOD Plugin found, testing poster fetch...\n";

  global $oVod;
  if ($oVod && method_exists($oVod, 'fetchPosterUrlFromRestAPI')) {
    $posterUrl = $oVod->fetchPosterUrlFromRestAPI('14234', '1jijk03u2wh18');
    echo "Poster URL result: " . ($posterUrl ?: 'NULL') . "\n";
  } else {
    echo "VOD plugin not properly loaded\n";
  }
} else {
  echo "\nEasyVod class not found\n";
}
