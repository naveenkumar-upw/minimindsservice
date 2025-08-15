<?php
include '../includes/db.php';
require_once '../includes/auth.php';

// Initialize session and check authentication
initSession();
$currentUser = requireAuth();

// Check if user has admin privileges
$isAdmin = in_array($currentUser['role'], ['admin', 'super_admin']);
if (!$isAdmin) {
    header('Location: stories.php');
    exit;
}

// Set upload directory
$uploadDir = '../assets/uploads/';
$allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

// Fetch categories for dropdown
$cat_result = $conn->query("SELECT id, name, language FROM categories ORDER BY name");
$categories = [];
while($row = $cat_result->fetch_assoc()) $categories[] = $row;

// Initialize error and success message arrays
$errors = [];
$success = [];

// Handle Add Story
if (isset($_POST['add_story'])) {
    // Validate required fields
    $required_fields = ['title', 'category_id', 'language', 'country', 'state', 'read_time', 
                       'min_age', 'max_age', 'moral', 'moralExplanation'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
        }
    }

    // Validate distractors
    $distractors_array = [];
    for($i = 1; $i <= 3; $i++) {
        if (empty($_POST['distractor'.$i])) {
            $errors[] = "Distractor $i is required.";
        } else {
            $distractors_array[] = $_POST['distractor'.$i];
        }
    }

    // Validate age range
    if ((int)$_POST['min_age'] > (int)$_POST['max_age']) {
        $errors[] = 'Minimum age cannot be greater than maximum age.';
    }

    // Only proceed if there are no errors
    if (empty($errors)) {
        // Basic story details
        $title = $conn->real_escape_string($_POST['title']);
        $category_id = (int)$_POST['category_id'];
        $language = $conn->real_escape_string($_POST['language']);
        $country = $conn->real_escape_string($_POST['country']);
        $state = $conn->real_escape_string($_POST['state']);
        $read_time = (int)$_POST['read_time'];
        $min_age = (int)$_POST['min_age'];
        $max_age = (int)$_POST['max_age'];
        $moral = $conn->real_escape_string($_POST['moral']);
        $moralExplanation = $conn->real_escape_string($_POST['moralExplanation']);
        $distractors = json_encode($distractors_array);
        
        // Default values
        $likes = 0;
        $shares = 0;
        $created = date('Y-m-d H:i:s');
        $lastUpdated = date('Y-m-d H:i:s');
        
        // Handle cover image upload
        $coverImageUrl = '';
        if (isset($_FILES['coverImageUrl']) && $_FILES['coverImageUrl']['error'] === UPLOAD_ERR_OK) {
            $fileName = uniqid('story_cover_', true) . '_' . basename($_FILES['coverImageUrl']['name']);
            $targetFile = $uploadDir . $fileName;
            $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
            
            if (!in_array($fileType, $allowedTypes)) {
                $errors[] = 'Invalid cover image type. Allowed types: ' . implode(', ', $allowedTypes);
            } else if (!move_uploaded_file($_FILES['coverImageUrl']['tmp_name'], $targetFile)) {
                $errors[] = 'Failed to upload cover image.';
            } else {
                $coverImageUrl = 'assets/uploads/' . $fileName;
            }
        }

        if (empty($errors)) {
            // Start transaction
            $conn->begin_transaction();
            try {
                // Insert story
                $coverImageUrl = $conn->real_escape_string($coverImageUrl);
                $query = "INSERT INTO stories (category_id, title, language, country, state, read_time, coverImageUrl, 
                                         min_age, max_age, moral, moralExplanation, distractors, likes, shares, 
                                         created, lastUpdated) 
                      VALUES ($category_id, '$title', '$language', '$country', '$state', $read_time, '$coverImageUrl', 
                              $min_age, $max_age, '$moral', '$moralExplanation', '$distractors', $likes, $shares, 
                              '$created', '$lastUpdated')";
                
                if($conn->query($query)) {
                    $story_id = $conn->insert_id;
                    
                    // Process sections
                    $sequences = $_POST['sequence_number'] ?? [];
                    $contents = $_POST['section_content'] ?? [];

                    // Validate sections have unique sequence numbers
                    $usedSequences = [];
                    foreach($sequences as $sequence) {
                        if (in_array($sequence, $usedSequences)) {
                            throw new Exception("Each section must have a unique sequence number.");
                        }
                        $usedSequences[] = $sequence;
                    }

                    foreach($sequences as $key => $sequence) {
                        $sequence_number = (int)$sequence;
                        $content = isset($contents[$key]) ? $conn->real_escape_string($contents[$key]) : '';
                        $image = '';

                        if (isset($_FILES['section_image']['name'][$key]) && $_FILES['section_image']['error'][$key] === UPLOAD_ERR_OK) {
                            $fileName = uniqid('story_', true) . '_' . basename($_FILES['section_image']['name'][$key]);
                            $targetFile = $uploadDir . $fileName;
                            $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
                            if (in_array($fileType, $allowedTypes) && move_uploaded_file($_FILES['section_image']['tmp_name'][$key], $targetFile)) {
                                $image = 'assets/uploads/' . $fileName;
                            }
                        }

                        // Insert section
                        $image = $conn->real_escape_string($image);
                        $sectionQuery = "INSERT INTO story_sections (story_id, sequence_number, content, image) 
                                   VALUES ($story_id, $sequence_number, '$content', '$image')";
                        if (!$conn->query($sectionQuery)) {
                            throw new Exception("Failed to insert section");
                        }
                    }

                    $conn->commit();
                    $_SESSION['success_message'] = "Story added successfully!";
                    header('Location: stories.php');
                    exit();
                }
            } catch (Exception $e) {
                $conn->rollback();
                $errors[] = "Error: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Story - MiniMinds Service</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h1>Add New Story</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul style="margin: 0;">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="POST" enctype="multipart/form-data" class="form-grid" onsubmit="return validateForm()">
                <div class="form-section full-width">
                    <h3>Basic Information</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="title">Story Title</label>
                            <input type="text" id="title" name="title" required>
                        </div>
                        <div class="form-group">
                            <label for="category">Category</label>
                            <select id="category" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat['id']) ?>" data-language="<?= htmlspecialchars($cat['language']) ?>">
                                        <?= htmlspecialchars($cat['name']) ?> (<?= htmlspecialchars($cat['language']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="language">Language</label>
                            <select id="language" name="language" required>
                                <option value="">Select Language</option>
                                <?php 
                                $languages = ['English', 'Hindi', 'Tamil', 'Telugu', 'Malayalam', 'Kannada', 'Marathi', 'Bengali', 'Gujarati'];
                                foreach($languages as $lang): 
                                ?>
                                    <option value="<?= $lang ?>"><?= $lang ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="read_time">Read Time (minutes)</label>
                            <input type="number" id="read_time" name="read_time" min="1" required>
                        </div>
                        <div class="form-group">
                            <label for="country">Country</label>
                            <input type="text" id="country" name="country" required>
                        </div>
                        <div class="form-group">
                            <label for="state">State</label>
                            <input type="text" id="state" name="state" required>
                        </div>
                    </div>
                </div>

                <div class="form-section full-width">
                    <h3>Age Range and Cover Image</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="min_age">Minimum Age</label>
                            <input type="number" id="min_age" name="min_age" min="0" required>
                        </div>
                        <div class="form-group">
                            <label for="max_age">Maximum Age</label>
                            <input type="number" id="max_age" name="max_age" min="0" required>
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="coverImage">Cover Image</label>
                            <input type="file" id="coverImage" name="coverImageUrl" accept="image/*">
                        </div>
                    </div>
                </div>

                <div class="form-section full-width">
                    <h3>Story's Moral and Learning</h3>
                    <div class="form-grid">
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="moral">Story's Moral</label>
                            <textarea id="moral" name="moral" required style="height: 100px;"></textarea>
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="moralExplanation">Moral Explanation</label>
                            <textarea id="moralExplanation" name="moralExplanation" required style="height: 150px;"></textarea>
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>Quiz Distractors</label>
                            <div class="help-text" style="margin-bottom: 0.5rem;">These will be used as alternative choices in quizzes.</div>
                            <?php for($i = 1; $i <= 3; $i++): ?>
                                <div class="form-group">
                                    <label for="distractor<?= $i ?>">Distractor <?= $i ?></label>
                                    <input type="text" id="distractor<?= $i ?>" name="distractor<?= $i ?>" required>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>

                <div class="form-section full-width">
                    <h3>Story Sections</h3>
                    <div id="sections-container">
                        <div class="story-section">
                            <button type="button" class="btn-remove-section" onclick="this.parentElement.remove()">&times;</button>
                            <div class="form-group">
                                <label>Sequence Number</label>
                                <input type="number" name="sequence_number[]" min="1" required>
                            </div>
                            <div class="form-group">
                                <label>Section Content</label>
                                <textarea name="section_content[]" required style="height: 120px;"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Section Image</label>
                                <input type="file" name="section_image[]" accept="image/*">
                            </div>
                        </div>
                    </div>
                    <div style="text-align: center; margin-top: 1.5rem;">
                        <button type="button" onclick="addSection()" class="btn btn-secondary">Add Section</button>
                        <button type="submit" name="add_story" class="btn btn-primary">Save Story</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
    function validateForm() {
        const minAge = parseInt(document.getElementById('min_age').value);
        const maxAge = parseInt(document.getElementById('max_age').value);
        
        if (minAge > maxAge) {
            alert('Minimum age cannot be greater than maximum age.');
            return false;
        }

        const sections = document.querySelectorAll('.story-section');
        const usedSequences = new Set();
        
        for (let section of sections) {
            const sequence = section.querySelector('input[name="sequence_number[]"]').value;
            if (usedSequences.has(sequence)) {
                alert('Each section must have a unique sequence number.');
                return false;
            }
            usedSequences.add(sequence);
        }

        return true;
    }

    function addSection() {
        const container = document.getElementById('sections-container');
        const newSection = document.createElement('div');
        newSection.className = 'story-section';
        newSection.innerHTML = `
            <button type="button" class="btn-remove-section" onclick="this.parentElement.remove()">&times;</button>
            <div class="form-group">
                <label>Sequence Number</label>
                <input type="number" name="sequence_number[]" min="1" required>
            </div>
            <div class="form-group">
                <label>Section Content</label>
                <textarea name="section_content[]" required style="height: 120px;"></textarea>
            </div>
            <div class="form-group">
                <label>Section Image</label>
                <input type="file" name="section_image[]" accept="image/*">
            </div>
        `;
        container.appendChild(newSection);
    }

    // Auto-populate language based on category selection
    document.querySelector('select[name="category_id"]').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const language = selectedOption.getAttribute('data-language');
        if (language) {
            document.querySelector('select[name="language"]').value = language;
        }
    });

    // Add loading state to forms
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            if (validateForm()) {
                this.classList.add('loading');
            }
        });
    });
    </script>
</body>
</html>
