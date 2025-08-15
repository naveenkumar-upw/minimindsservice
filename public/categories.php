<?php
include '../includes/db.php';
require_once '../includes/auth.php';

// Initialize session and check authentication
initSession();
$currentUser = requireAuth();

// Check if user has admin privileges for write operations
$isAdmin = in_array($currentUser['role'], ['admin', 'super_admin']);

// Handle Add Category
if (isset($_POST['add_category'])) {
    // Check if user is admin
    if (!$isAdmin) {
        header('Location: categories.php');
        exit;
    }
    $name = $conn->real_escape_string($_POST['name']);
    $thumbnailPath = '';
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../assets/uploads/';
        $fileName = uniqid('cat_', true) . '_' . basename($_FILES['thumbnail']['name']);
        $targetFile = $uploadDir . $fileName;
        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $targetFile)) {
                $thumbnailPath = 'assets/uploads/' . $fileName;
            }
        }
    }
    $language = $conn->real_escape_string($_POST['language']);
    $conn->query("INSERT INTO categories (name, language, thumbnail) VALUES ('$name', '$language', '$thumbnailPath')");
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Handle Delete Category
if (isset($_GET['delete'])) {
    // Check if user is admin
    if (!$isAdmin) {
        header('Location: categories.php');
        exit;
    }
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM categories WHERE id=$id");
}

// Handle Edit Category
if (isset($_POST['edit_category'])) {
    // Check if user is admin
    if (!$isAdmin) {
        header('Location: categories.php');
        exit;
    }
    $id = (int)$_POST['id'];
    $name = $conn->real_escape_string($_POST['name']);
    $thumbnailPath = '';
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../assets/uploads/';
        $fileName = uniqid('cat_', true) . '_' . basename($_FILES['thumbnail']['name']);
        $targetFile = $uploadDir . $fileName;
        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $targetFile)) {
                $thumbnailPath = 'assets/uploads/' . $fileName;
            }
        }
    }
    if ($thumbnailPath) {
        $language = $conn->real_escape_string($_POST['language']);
        $conn->query("UPDATE categories SET name='$name', language='$language', thumbnail='$thumbnailPath' WHERE id=$id");
    } else {
        $language = $conn->real_escape_string($_POST['language']);
        $conn->query("UPDATE categories SET name='$name', language='$language' WHERE id=$id");
    }
}

$categories = $conn->query("SELECT * FROM categories");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - MiniMinds Service</title>
    <link rel="stylesheet" href="../assets/style.css">

</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <h1>Categories</h1>

    <div class="form-container">
        <?php if ($isAdmin): ?>
        <form method="POST" enctype="multipart/form-data" class="form-grid">
            <div class="grid-full">
                <input type="text" name="name" placeholder="Category Name" required>
            </div>
            <div class="grid-full">
                <select name="language" required>
                    <option value="">Select Language</option>
                    <option value="English">English</option>
                    <option value="Hindi">Hindi</option>
                    <option value="Tamil">Tamil</option>
                    <option value="Telugu">Telugu</option>
                    <option value="Malayalam">Malayalam</option>
                    <option value="Kannada">Kannada</option>
                    <option value="Marathi">Marathi</option>
                    <option value="Bengali">Bengali</option>
                    <option value="Gujarati">Gujarati</option>
                </select>
            </div>
            <div class="grid-full">
                <input type="file" name="thumbnail" accept="image/*" required>
            </div>
            <div class="grid-full">
                <button type="submit" name="add_category">Add Category</button>
            </div>
            </form>
        <?php endif; ?>
    </div>

    <div class="table-responsive">
        <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Language</th>
                <th>Thumbnail</th>
                <?php if ($isAdmin): ?>
                <th>Actions</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $categories->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['id']) ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['language']) ?></td>
                <td>
                    <?php if ($row['thumbnail']): ?>
                        <img src="../<?= htmlspecialchars($row['thumbnail']) ?>" alt="Thumbnail" class="thumbnail-sm">
                    <?php endif; ?>
                </td>
                <?php if ($isAdmin): ?>
                <td>
                    <div class="actions">
                        <a href="?delete=<?= urlencode($row['id']) ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this category?')">Delete</a>
                        <a href="#" class="btn-edit" onclick="toggleEdit(<?= $row['id'] ?>)">Edit</a>
                    </div>
                    <form method="POST" enctype="multipart/form-data" class="edit-form" id="edit-form-<?= $row['id'] ?>">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']) ?>">
                        <input type="text" name="name" value="<?= htmlspecialchars($row['name']) ?>" required>
                        <select name="language" required>
                            <option value="">Select Language</option>
                            <option value="English" <?= $row['language'] === 'English' ? 'selected' : '' ?>>English</option>
                            <option value="Hindi" <?= $row['language'] === 'Hindi' ? 'selected' : '' ?>>Hindi</option>
                            <option value="Tamil" <?= $row['language'] === 'Tamil' ? 'selected' : '' ?>>Tamil</option>
                            <option value="Telugu" <?= $row['language'] === 'Telugu' ? 'selected' : '' ?>>Telugu</option>
                            <option value="Malayalam" <?= $row['language'] === 'Malayalam' ? 'selected' : '' ?>>Malayalam</option>
                            <option value="Kannada" <?= $row['language'] === 'Kannada' ? 'selected' : '' ?>>Kannada</option>
                            <option value="Marathi" <?= $row['language'] === 'Marathi' ? 'selected' : '' ?>>Marathi</option>
                            <option value="Bengali" <?= $row['language'] === 'Bengali' ? 'selected' : '' ?>>Bengali</option>
                            <option value="Gujarati" <?= $row['language'] === 'Gujarati' ? 'selected' : '' ?>>Gujarati</option>
                        </select>
                        <input type="file" name="thumbnail" accept="image/*">
                        <button type="submit" name="edit_category">Update</button>
                    </form>
                </td>
                <?php endif; ?>
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

    // Add loading state to forms
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            this.classList.add('loading');
        });
    });
    </script>
</body>
</html>
