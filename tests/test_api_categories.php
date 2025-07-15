<?php
// tests/test_api_categories.php
// Simple test for api_categories.php

function callApi($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}

$url = 'http://localhost/minimindsservice/public/api_categories.php';
$response = callApi($url);
$data = json_decode($response, true);

if (isset($data['categories']) && is_array($data['categories'])) {
    echo "api_categories.php test PASSED\n";
} else {
    echo "api_categories.php test FAILED\n";
    exit(1);
}
?>
