<?php
include '../includes/db.php';
include '../includes/firebase/config.php';

// Fetch categories for dropdown
$cat_result = $conn->query("SELECT * FROM categories");
$categories = [];
while($row = $cat_result->fetch_assoc()) $categories[] = $row;

// Handle Add Story
if (isset($_POST['add_story'])) {
    $title = $conn->real_escape_string($_POST['title']);
    $content = $conn->real_escape_string($_POST['content']);
    $category_id = (int)$_POST['category_id'];
    $language = $conn->real_escape_string($_POST['language']);
    $country = $conn->real_escape_string($_POST['country']);
    $state = $conn->real_escape_string($_POST['state']);
    $read_time = (int)$_POST['read_time'];
    $images = [];
    if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
        $uploadDir = '../assets/uploads/';
        foreach ($_FILES['images']['name'] as $key => $name) {
            if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                $fileType = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (in_array($fileType, $allowedTypes)) {
                    $fileName = uniqid('story_', true) . '_' . basename($name);
                    $targetFile = $uploadDir . $fileName;
                    if (move_uploaded_file($_FILES['images']['tmp_name'][$key], $targetFile)) {
                        $images[] = 'assets/uploads/' . $fileName;
                    }
                }
            }
        }
    }
    $imagesStr = $conn->real_escape_string(implode(',', $images));
    if($conn->query("INSERT INTO stories (category_id, title, content, language, country, state, images, read_time) VALUES ($category_id, '$title', '$content', '$language', '$country', '$state', '$imagesStr', $read_time)")) {
        // Send push notification for new story
        notifyNewStory($title);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Handle Delete Story
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM stories WHERE id=$id");
}

// Handle Edit Story
if (isset($_POST['edit_story'])) {
    $id = (int)$_POST['id'];
    $title = $conn->real_escape_string($_POST['title']);
    $content = $conn->real_escape_string($_POST['content']);
    $category_id = (int)$_POST['category_id'];
    $language = $conn->real_escape_string($_POST['language']);
    $country = $conn->real_escape_string($_POST['country']);
    $state = $conn->real_escape_string($_POST['state']);
    $read_time = (int)$_POST['read_time'];
    $images = [];
    if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
        $uploadDir = '../assets/uploads/';
        foreach ($_FILES['images']['name'] as $key => $name) {
            if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                $fileType = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (in_array($fileType, $allowedTypes)) {
                    $fileName = uniqid('story_', true) . '_' . basename($name);
                    $targetFile = $uploadDir . $fileName;
                    if (move_uploaded_file($_FILES['images']['tmp_name'][$key], $targetFile)) {
                        $images[] = 'assets/uploads/' . $fileName;
                    }
                }
            }
        }
    }
    $imagesStr = '';
    if (count($images) > 0) {
        $imagesStr = $conn->real_escape_string(implode(',', $images));
        $conn->query("UPDATE stories SET category_id=$category_id, title='$title', content='$content', language='$language', country='$country', state='$state', images='$imagesStr', read_time=$read_time WHERE id=$id");
    } else {
        $conn->query("UPDATE stories SET category_id=$category_id, title='$title', content='$content', language='$language', country='$country', state='$state', read_time=$read_time WHERE id=$id");
    }
}

$stories = $conn->query("SELECT stories.*, categories.name as category FROM stories JOIN categories ON stories.category_id=categories.id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stories - MiniMinds Service</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .form-container {
            background: #fff;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px var(--shadow);
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            align-items: start;
        }
        textarea {
            min-height: 100px;
            resize: vertical;
        }
        .image-preview {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .image-preview img {
            object-fit: cover;
            border-radius: 4px;
            box-shadow: 0 1px 3px var(--shadow);
        }
        .edit-form {
            background: var(--light);
            padding: 1rem;
            margin-top: 1rem;
            border-radius: 4px;
        }
        .edit-form button[type="submit"] {
            margin-top: 1rem;
        }
        .actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .btn-delete {
            padding: 4px 8px;
            background: var(--danger);
            color: white;
            border-radius: 4px;
            font-size: 0.875rem;
        }
        .btn-delete:hover {
            background-color: #c82333;
            color: white;
        }
        .btn-edit {
            padding: 4px 8px;
            background: var(--secondary);
            color: white;
            border-radius: 4px;
            font-size: 0.875rem;
        }
        .btn-edit:hover {
            background-color: #545b62;
            color: white;
        }
        .collapse {
            display: none;
        }
        .collapse.show {
            display: block;
        }
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h1>Stories</h1>

        <div class="filters">
            <form id="filter-form" class="filters-grid">
                <div class="form-group">
                    <select id="filter-category" onchange="applyFilters()">
                        <option value="">All Categories</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['name']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <select id="filter-language" onchange="applyFilters()">
                        <option value="">All Languages</option>
                        <?php
                        $languages = [];
                        $result = $conn->query("SELECT DISTINCT language FROM stories ORDER BY language");
                        while($row = $result->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($row['language']) . "'>" . htmlspecialchars($row['language']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <select id="filter-country" onchange="applyFilters()">
                        <option value="">All Countries</option>
                        <?php
                        $result = $conn->query("SELECT DISTINCT country FROM stories ORDER BY country");
                        while($row = $result->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($row['country']) . "'>" . htmlspecialchars($row['country']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <select id="filter-readtime" onchange="applyFilters()">
                        <option value="">All Read Times</option>
                        <option value="0-5">0-5 minutes</option>
                        <option value="5-10">5-10 minutes</option>
                        <option value="10+">10+ minutes</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="button" onclick="resetFilters()">Reset Filters</button>
                </div>
            </form>
        </div>

    <div class="form-container">
        <form method="POST" enctype="multipart/form-data" class="form-grid">
            <input type="text" name="title" placeholder="Story Title" required>
            <input type="number" name="read_time" placeholder="Read Time (minutes)" required min="1">
            <input type="text" name="language" placeholder="Language" required>
            <input type="text" name="country" placeholder="Country" required>
            <input type="text" name="state" placeholder="State" required>
            <select name="category_id" required>
                <option value="">Select Category</option>
                <?php foreach($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat['id']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <div style="grid-column: 1 / -1;">
                <textarea name="content" placeholder="Content" required></textarea>
            </div>
            <div style="grid-column: 1 / -1;">
                <input type="file" name="images[]" accept="image/*" multiple required>
            </div>
            <div style="grid-column: 1 / -1;">
                <button type="submit" name="add_story">Add Story</button>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Category</th>
                <th>Content</th>
                <th>Read Time</th>
                <th>Language</th>
                <th>Country</th>
                <th>State</th>
                <th>Images</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $stories->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['id']) ?></td>
                <td><?= htmlspecialchars($row['title']) ?></td>
                <td><?= htmlspecialchars($row['category']) ?></td>
                <td><div class="text-truncate"><?= htmlspecialchars($row['content']) ?></div></td>
                <td><?= htmlspecialchars($row['read_time']) ?> min</td>
                <td><?= htmlspecialchars($row['language']) ?></td>
                <td><?= htmlspecialchars($row['country']) ?></td>
                <td><?= htmlspecialchars($row['state']) ?></td>
                <td class="image-preview">
                    <?php if (!empty($row['images'])): ?>
                        <?php foreach (explode(',', $row['images']) as $img): ?>
                            <img src="../<?= htmlspecialchars(trim($img)) ?>" alt="Story Image" style="max-width:40px;max-height:40px;">
                        <?php endforeach; ?>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="actions">
                        <a href="?delete=<?= urlencode($row['id']) ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this story?')">Delete</a>
                        <a href="#" class="btn-edit" onclick="toggleEdit(<?= $row['id'] ?>)">Edit</a>
                    </div>
                    <form method="POST" enctype="multipart/form-data" class="edit-form collapse" id="edit-form-<?= $row['id'] ?>">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']) ?>">
                        <div class="form-grid">
                            <input type="text" name="title" value="<?= htmlspecialchars($row['title']) ?>" required>
                            <input type="number" name="read_time" value="<?= htmlspecialchars($row['read_time']) ?>" required min="1">
                            <input type="text" name="language" value="<?= htmlspecialchars($row['language']) ?>" required>
                            <input type="text" name="country" value="<?= htmlspecialchars($row['country']) ?>" required>
                            <input type="text" name="state" value="<?= htmlspecialchars($row['state']) ?>" required>
                            <select name="category_id" required>
                                <?php foreach($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat['id']) ?>" <?= $cat['id']==$row['category_id']?'selected':'' ?>><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div style="grid-column: 1 / -1;">
                                <textarea name="content" required><?= htmlspecialchars($row['content']) ?></textarea>
                            </div>
                            <div style="grid-column: 1 / -1;">
                                <input type="file" name="images[]" accept="image/*" multiple>
                            </div>
                            <div style="grid-column: 1 / -1;">
                                <button type="submit" name="edit_story">Update</button>
                            </div>
                        </div>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    </div>
    </div>

    <script>
    function toggleEdit(id) {
        const form = document.getElementById(`edit-form-${id}`);
        form.classList.toggle('show');
    }

    function applyFilters() {
        const category = document.getElementById('filter-category').value.toLowerCase();
        const language = document.getElementById('filter-language').value.toLowerCase();
        const country = document.getElementById('filter-country').value.toLowerCase();
        const readtime = document.getElementById('filter-readtime').value;

        document.querySelectorAll('tbody tr').forEach(row => {
            const rowCategory = row.children[2].textContent.toLowerCase();
            const rowLanguage = row.children[5].textContent.toLowerCase();
            const rowCountry = row.children[6].textContent.toLowerCase();
            const rowReadTime = parseInt(row.children[4].textContent);

            let showRow = true;

            if (category && rowCategory !== category) showRow = false;
            if (language && rowLanguage !== language) showRow = false;
            if (country && rowCountry !== country) showRow = false;
            
            if (readtime) {
                if (readtime === '0-5' && rowReadTime > 5) showRow = false;
                else if (readtime === '5-10' && (rowReadTime <= 5 || rowReadTime > 10)) showRow = false;
                else if (readtime === '10+' && rowReadTime <= 10) showRow = false;
            }

            row.style.display = showRow ? '' : 'none';
        });
    }

    function resetFilters() {
        document.getElementById('filter-category').value = '';
        document.getElementById('filter-language').value = '';
        document.getElementById('filter-country').value = '';
        document.getElementById('filter-readtime').value = '';
        document.querySelectorAll('tbody tr').forEach(row => row.style.display = '');
    }

    // Add loading state to forms
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            this.classList.add('loading');
        });
    });
    </script>
</body>
</html>
