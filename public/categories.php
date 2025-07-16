<?php
include '../includes/db.php';

// Handle Add Category
if (isset($_POST['add_category'])) {
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
    $conn->query("INSERT INTO categories (name, thumbnail) VALUES ('$name', '$thumbnailPath')");
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Handle Delete Category
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM categories WHERE id=$id");
}

// Handle Edit Category
if (isset($_POST['edit_category'])) {
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
        $conn->query("UPDATE categories SET name='$name', thumbnail='$thumbnailPath' WHERE id=$id");
    } else {
        $conn->query("UPDATE categories SET name='$name' WHERE id=$id");
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
        img {
            object-fit: cover;
            border-radius: 4px;
            box-shadow: 0 1px 3px var(--shadow);
        }
        .edit-form {
            background: var(--light);
            padding: 1rem;
            margin-top: 1rem;
            border-radius: 4px;
            display: none;
        }
        .edit-form.show {
            display: block;
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
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }
        .thumbnail-preview {
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <h1>Categories</h1>

    <div class="form-container">
        <form method="POST" enctype="multipart/form-data" class="form-grid">
            <div style="grid-column: 1 / -1;">
                <input type="text" name="name" placeholder="Category Name" required>
            </div>
            <div style="grid-column: 1 / -1;">
                <input type="file" name="thumbnail" accept="image/*" required>
            </div>
            <div style="grid-column: 1 / -1;">
                <button type="submit" name="add_category">Add Category</button>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Thumbnail</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $categories->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['id']) ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td>
                    <?php if ($row['thumbnail']): ?>
                        <img src="../<?= htmlspecialchars($row['thumbnail']) ?>" alt="Thumbnail" style="max-width:60px;max-height:60px;">
                    <?php endif; ?>
                </td>
                <td>
                    <div class="actions">
                        <a href="?delete=<?= urlencode($row['id']) ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this category?')">Delete</a>
                        <a href="#" class="btn-edit" onclick="toggleEdit(<?= $row['id'] ?>)">Edit</a>
                    </div>
                    <form method="POST" enctype="multipart/form-data" class="edit-form" id="edit-form-<?= $row['id'] ?>">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']) ?>">
                        <input type="text" name="name" value="<?= htmlspecialchars($row['name']) ?>" required>
                        <input type="file" name="thumbnail" accept="image/*">
                        <button type="submit" name="edit_category">Update</button>
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

    // Add loading state to forms
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            this.classList.add('loading');
        });
    });
    </script>
</body>
</html>
