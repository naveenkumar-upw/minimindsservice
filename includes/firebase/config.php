<?php
// Firebase Configuration
define('FIREBASE_CREDENTIALS_PATH', __DIR__ . '/firebase-credentials.json');
define('FIREBASE_PROJECT_ID', 'test-notifications-f43a8'); // Replace with your Firebase project ID

// Initialize Firebase Admin SDK
require __DIR__ . '/../../vendor/autoload.php';

use Google\Cloud\Core\ServiceBuilder;
use Google\Cloud\Firestore\FirestoreClient;
use Google\Cloud\Storage\StorageClient;

// Function to initialize Firebase
function initializeFirebase() {
    putenv('GOOGLE_APPLICATION_CREDENTIALS=' . FIREBASE_CREDENTIALS_PATH);
    $cloud = new ServiceBuilder([
        'projectId' => FIREBASE_PROJECT_ID
    ]);
    return $cloud;
}

// Function to send notification to a single device
function sendPushNotification($token, $title, $body, $data = []) {
    $url = 'https://fcm.googleapis.com/v1/projects/' . FIREBASE_PROJECT_ID . '/messages:send';
    $key = json_decode(file_get_contents(FIREBASE_CREDENTIALS_PATH), true)['private_key'];
    
    $message = [
        'message' => [
            'token' => $token,
            'notification' => [
                'title' => $title,
                'body' => $body
            ],
            'data' => $data
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $key,
        'Content-Type: application/json'
    ]);

    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

// Function to send notification to multiple devices
function sendBulkPushNotifications($tokens, $title, $body, $data = []) {
    $results = [];
    foreach ($tokens as $token) {
        $results[] = sendPushNotification($token, $title, $body, $data);
    }
    return $results;
}

// Function to notify all registered devices about new story
function notifyNewStory($storyTitle) {
    global $conn;
    
    // Get all registered device tokens
    $result = $conn->query("SELECT device_token FROM device_tokens");
    $tokens = [];
    while ($row = $result->fetch_assoc()) {
        $tokens[] = $row['device_token'];
    }
    
    if (!empty($tokens)) {
        $title = "New Story Available!";
        $body = "Check out our new story: " . $storyTitle;
        $data = [
            'type' => 'new_story',
            'story_title' => $storyTitle
        ];
        
        return sendBulkPushNotifications($tokens, $title, $body, $data);
    }
    
    return false;
}
?>
