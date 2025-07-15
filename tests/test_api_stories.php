<?php
// tests/test_api_stories.php
// Simple test for api_stories.php

function callApi($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}

$url = 'http://localhost/minimindsservice/public/api_stories.php';
$response = callApi($url);
$data = json_decode($response, true);

if (isset($data['stories']) && is_array($data['stories'])) {
    echo "api_stories.php test PASSED\n";
} else {
    echo "api_stories.php test FAILED\n";
    exit(1);
}
?>
