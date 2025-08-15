<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// GET: Fetch all stories or single story
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $story_id = $_GET['id'] ?? null;
    function get_story_sections($conn, $story_id) {
        $sections = [];
        $stmt = $conn->prepare("SELECT id, sequence_number, content, image FROM story_sections WHERE story_id = ? ORDER BY sequence_number ASC");
        $stmt->bind_param("i", $story_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $sections[] = $row;
        }
        return $sections;
    }

    if ($story_id) {
        $stmt = $conn->prepare("
            SELECT s.*, c.name as category_name
            FROM stories s
            LEFT JOIN categories c ON s.category_id = c.id
            WHERE s.id = ?
        ");
        $stmt->bind_param("i", $story_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $story = $result->fetch_assoc();
        if ($story) {
            // Decode distractors JSON
            $story['distractors'] = $story['distractors'] ? json_decode($story['distractors'], true) : [];
            // Add story sections
            $story['sections'] = get_story_sections($conn, $story['id']);
        }
        echo json_encode($story);
    } else {
        $result = $conn->query("
            SELECT s.*, c.name as category_name
            FROM stories s
            LEFT JOIN categories c ON s.category_id = c.id
            ORDER BY s.created DESC
        ");
        $stories = [];
        while ($row = $result->fetch_assoc()) {
            $row['distractors'] = $row['distractors'] ? json_decode($row['distractors'], true) : [];
            $row['sections'] = get_story_sections($conn, $row['id']);
            $stories[] = $row;
        }
        echo json_encode(['stories' => $stories]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. This API is read-only.'
    ]);
}
