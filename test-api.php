<?php

/**
 * Quick API test script to check Infomaniak VOD API response structure
 */

$channel_id = '14234';
$api_token = '3-E1aGo8aTIzlIqpmqOVWofdJQmJLXuhIKYmq433pO8c_N_2c9UES4rt6rXI2D9ySz-0B7q8wWDTlAw8';

$api_url = "https://api.infomaniak.com/1/vod/channel/{$channel_id}/media";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
  'Authorization: Bearer ' . $api_token,
  'Accept: application/json'
));

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: " . $http_code . "\n\n";
echo "Response:\n";
echo $response . "\n\n";

if ($response) {
  $data = json_decode($response, true);
  if ($data && isset($data['data']) && !empty($data['data'])) {
    echo "First video structure:\n";
    print_r($data['data'][0]);
  }
}
