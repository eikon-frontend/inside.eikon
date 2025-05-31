<?php

/**
 * Test script to debug poster API synchronization
 */

// Load environment variables
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$channelId = $_ENV['INFOMANIAK_CHANNEL_ID'] ?? '';
$apiToken = $_ENV['INFOMANIAK_TOKEN_API'] ?? '';

echo "=== Poster API Debug Test ===\n";
echo "Channel ID: " . $channelId . "\n";
echo "API Token: " . substr($apiToken, 0, 10) . "...\n\n";

// Test with a real media ID from the database
$mediaId = '1jijk03u2wh18'; // Real server code from database

if (empty($channelId) || empty($apiToken)) {
  echo "ERROR: Missing API credentials\n";
  exit(1);
}

$endpoint = "https://api.infomaniak.com/1/vod/channel/{$channelId}/media/{$mediaId}?with=poster";

echo "Testing endpoint: " . $endpoint . "\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  'Authorization: Bearer ' . $apiToken,
  'Content-Type: application/json',
  'Accept: application/json',
  'User-Agent: VOD-WordPress-Plugin-Debug/1.0'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: " . $httpCode . "\n";
if ($error) {
  echo "cURL Error: " . $error . "\n";
}

echo "\nResponse Body:\n";
echo $response . "\n\n";

if ($httpCode === 200) {
  $data = json_decode($response, true);
  if (json_last_error() === JSON_ERROR_NONE) {
    echo "JSON Response parsed successfully!\n";
    if (isset($data['result']) && $data['result'] === 'success') {
      if (isset($data['data']['poster']['url'])) {
        echo "✅ Poster URL found: " . $data['data']['poster']['url'] . "\n";
      } else {
        echo "⚠️  No poster URL in response\n";
        echo "Available data keys: " . implode(', ', array_keys($data['data'] ?? [])) . "\n";
      }
    } else {
      echo "❌ API returned error result\n";
    }
    echo "\nFull response structure:\n";
    print_r($data);
  } else {
    echo "❌ JSON parsing error: " . json_last_error_msg() . "\n";
  }
} else {
  echo "❌ HTTP request failed\n";
}
