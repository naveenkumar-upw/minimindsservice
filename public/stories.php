<?php
include '../includes/db.php';
require_once '../includes/auth.php';

// Initialize session and check authentication
initSession();
$currentUser = requireAuth();

// Check if user has admin privileges
$isAdmin = in_array($currentUser['role'], ['admin', 'super_admin']);
if (!$isAdmin) {
    header('Location: index.php');
    exit;
}

// Get filter parameters
$filters = [
    'category_id' => isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0,
    'language' => isset($_GET['language']) ? $_GET['language'] : '',
    'min_age' => isset($_GET['min_age']) ? (int)$_GET['min_age'] : 0,
    'max_age' => isset($_GET['max_age']) ? (int)$_GET['max_age'] : 100,
    'read_time_min' => isset($_GET['read_time_min']) ? (int)$_GET['read_time_min'] : 0,
    'read_time_max' => isset($_GET['read_time_max']) ? (int)$_GET['read_time_max'] : 1000,
    'country' => isset($_GET['country']) ? $_GET['country'] : '',
    'state' => isset($_GET['state']) ? $_GET['state'] : '',
    'date_from' => isset($_GET['date_from']) ? $_GET['date_from'] : '',
    'date_to' => isset($_GET['date_to']) ? $_GET['date_to'] : ''
];

// Get success message if any
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
unset($_SESSION['success_message']);

// Build WHERE clause based on filters
$where_clauses = [];
$params = [];

if ($filters['category_id']) {
    $where_clauses[] = "stories.category_id = ?";
    $params[] = $filters['category_id'];
}

if ($filters['language']) {
    $where_clauses[] = "stories.language = ?";
    $params[] = $filters['language'];
}

if ($filters['min_age'] > 0) {
    $where_clauses[] = "stories.min_age >= ?";
    $params[] = $filters['min_age'];
}

if ($filters['max_age'] < 100) {
    $where_clauses[] = "stories.max_age <= ?";
    $params[] = $filters['max_age'];
}

if ($filters['read_time_min'] > 0) {
    $where_clauses[] = "stories.read_time >= ?";
    $params[] = $filters['read_time_min'];
}

if ($filters['read_time_max'] < 1000) {
    $where_clauses[] = "stories.read_time <= ?";
    $params[] = $filters['read_time_max'];
}

if ($filters['country']) {
    $where_clauses[] = "stories.country LIKE ?";
    $params[] = "%" . $filters['country'] . "%";
}

if ($filters['state']) {
    $where_clauses[] = "stories.state LIKE ?";
    $params[] = "%" . $filters['state'] . "%";
}

if ($filters['date_from']) {
    $where_clauses[] = "stories.created >= ?";
    $params[] = $filters['date_from'] . " 00:00:00";
}

if ($filters['date_to']) {
    $where_clauses[] = "stories.created <= ?";
    $params[] = $filters['date_to'] . " 23:59:59";
}

// Construct final query
$where_sql = $where_clauses ? "WHERE " . implode(" AND ", $where_clauses) : "";
$query = "SELECT stories.*, categories.name as category 
          FROM stories 
          JOIN categories ON stories.category_id=categories.id 
          $where_sql 
          ORDER BY stories.lastUpdated DESC";

// Prepare and execute query
$stmt = $conn->prepare($query);
if ($params) {
    $stmt->bind_param(str_repeat('s', count($params)), ...$params);
}
$stmt->execute();
$stories = $stmt->get_result();

// Fetch all categories for filter dropdown
$categories = $conn->query("SELECT * FROM categories ORDER BY name");

// Fetch all languages
$languages_query = $conn->query("SELECT DISTINCT language FROM stories WHERE language != '' ORDER BY language");
$languages = [];
while ($lang = $languages_query->fetch_assoc()) {
    $languages[] = $lang['language'];
}

// Fetch sections for each story
$story_sections = [];
$sections_result = $conn->query("SELECT * FROM story_sections ORDER BY story_id, sequence_number");
while ($section = $sections_result->fetch_assoc()) {
    $story_sections[$section['story_id']][] = $section;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stories - MiniMinds Service</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <div class="header-actions">
            <h1>Stories</h1>
            <?php if ($isAdmin): ?>
                <a href="add_story.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Story
                </a>
            <?php endif; ?>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Cover Image</th>
                        <th>Content</th>
                        <th>Read Time</th>
                        <th>Language</th>
                        <th>Country</th>
                        <th>State</th>
                        <th>Age Range</th>
                        <th>Moral</th>
                        <th>Moral Explanation</th>
                        <th>Distractors</th>
                        <th>Likes</th>
                        <th>Shares</th>
                        <th>Updated</th>
                        <th>Created</th>
                        <?php if ($isAdmin): ?>
                        <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                    <tr>
                        <form id="filterForm" method="GET">
                        <td></td>
                        <td></td>
                        <td>
                            <select name="category_id" class="form-control" style="min-width: 120px;">
                                <option value="">All</option>
                                <?php $catRes = $conn->query("SELECT * FROM categories ORDER BY name"); while($category = $catRes->fetch_assoc()): ?>
                                    <option value="<?= $category['id'] ?>" <?= $filters['category_id'] == $category['id'] ? 'selected' : '' ?>><?= htmlspecialchars($category['name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </td>
                        <td></td>
                        <td></td>
                        <td>
                            <input type="number" name="read_time_min" class="form-control" placeholder="Min" value="<?= $filters['read_time_min'] ?>" min="0" style="width: 60px; display:inline-block;"> -
                            <input type="number" name="read_time_max" class="form-control" placeholder="Max" value="<?= $filters['read_time_max'] ?>" min="0" style="width: 60px; display:inline-block;">
                        </td>
                        <td>
                            <select name="language" class="form-control" style="min-width: 100px;">
                                <option value="">All</option>
                                <?php foreach($languages as $lang): ?>
                                    <option value="<?= $lang ?>" <?= $filters['language'] === $lang ? 'selected' : '' ?>><?= htmlspecialchars($lang) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input type="text" name="country" class="form-control" value="<?= htmlspecialchars($filters['country']) ?>" placeholder="Country" style="width: 90px;"></td>
                        <td><input type="text" name="state" class="form-control" value="<?= htmlspecialchars($filters['state']) ?>" placeholder="State" style="width: 90px;"></td>
                        <td>
                            <input type="number" name="min_age" class="form-control" placeholder="Min" value="<?= $filters['min_age'] ?>" min="0" max="100" style="width: 50px; display:inline-block;"> -
                            <input type="number" name="max_age" class="form-control" placeholder="Max" value="<?= $filters['max_age'] ?>" min="0" max="100" style="width: 50px; display:inline-block;">
                        </td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <?php if ($isAdmin): ?><td>
                            <button type="submit" class="btn btn-primary" style="padding: 0.2rem 0.7rem; font-size: 0.95rem;"><i class="fas fa-filter"></i></button>
                            <button type="button" class="btn btn-secondary" style="padding: 0.2rem 0.7rem; font-size: 0.95rem;" onclick="resetFilters()"><i class="fas fa-undo"></i></button>
                        </td><?php endif; ?>
                        </form>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($stories->num_rows === 0): ?>
                        <tr>
                            <td colspan="<?= $isAdmin ? '14' : '13' ?>" style="text-align: center;">
                                No stories found matching the selected filters.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php while($row = $stories->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['id']) ?></td>
                                <td><?= htmlspecialchars($row['title']) ?></td>
                                <td><?= htmlspecialchars($row['category']) ?></td>
                                <td>
                                    <?php if ($row['coverImageUrl']): ?>
                                        <img src="../<?= htmlspecialchars($row['coverImageUrl']) ?>" alt="Cover Image" style="max-width:100px;max-height:100px;">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (isset($story_sections[$row['id']])): ?>
                                        <?php foreach ($story_sections[$row['id']] as $section): ?>
                                            <div class="section-preview">
                                                <strong>Section <?= htmlspecialchars($section['sequence_number']) ?>:</strong>
                                                <div class="text-truncate"><?= htmlspecialchars($section['content'] ?: '(No text)') ?></div>
                                                <?php if ($section['image']): ?>
                                                    <img src="../<?= htmlspecialchars($section['image']) ?>" alt="Section Image" style="max-width:40px;max-height:40px;">
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($row['read_time']) ?> min</td>
                                <td><?= htmlspecialchars($row['language']) ?></td>
                                <td><?= htmlspecialchars($row['country']) ?></td>
                                <td><?= htmlspecialchars($row['state']) ?></td>
                                <td><?= htmlspecialchars($row['min_age']) ?>-<?= htmlspecialchars($row['max_age']) ?></td>
                                <td><?= htmlspecialchars($row['moral']) ?></td>
                                <td><?= htmlspecialchars($row['moralExplanation']) ?></td>
                                <td><?= htmlspecialchars(implode(", ", json_decode($row['distractors'] ?? '[]', true) ?: [])) ?></td>
                                <td><?= htmlspecialchars($row['likes']) ?></td>
                                <td><?= htmlspecialchars($row['shares']) ?></td>
                                <td><?= date('M j, Y', strtotime($row['lastUpdated'])) ?></td>
                                <td><?= date('M j, Y', strtotime($row['created'])) ?></td>
                                <?php if ($isAdmin): ?>
                                <td>
                                    <div class="actions">
                                        <a href="edit_story.php?id=<?= urlencode($row['id']) ?>" class="btn btn-secondary">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="?delete=<?= urlencode($row['id']) ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this story?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Toggle filter panel
        const filterToggle = document.getElementById('filterToggle');
        const filterForm = document.getElementById('filterForm');
        
        filterToggle.addEventListener('click', function() {
            filterForm.style.display = filterForm.style.display === 'none' ? 'block' : 'none';
            this.classList.toggle('collapsed');
        });

        // Reset filters
        function resetFilters() {
            const form = document.getElementById('filterForm');
            const inputs = form.querySelectorAll('input, select');
            inputs.forEach(input => {
                if (input.type === 'number') {
                    input.value = input.min || '0';
                } else {
                    input.value = '';
                }
            });
            form.submit();
        }

        // Remove individual filter
        function removeFilter(key) {
            const input = document.querySelector(`[name="${key}"]`);
            if (input) {
                if (input.type === 'number') {
                    input.value = input.min || '0';
                } else {
                    input.value = '';
                }
                document.getElementById('filterForm').submit();
            }
        }

        // Add loading state to form
        document.getElementById('filterForm').addEventListener('submit', function() {
            this.classList.add('loading');
        });
    </script>
</body>
</html>
