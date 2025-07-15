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
<html>
<head>
    <title>Categories</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <h1>Categories</h1>
    <form method="POST" enctype="multipart/form-data">
        <input type="text" name="name" placeholder="Category Name" required>
        <input type="file" name="thumbnail" accept="image/*" required>
        <button type="submit" name="add_category">Add Category</button>
    </form>
    <table border="1" cellpadding="5">
        <tr><th>ID</th><th>Name</th><th>Thumbnail</th><th>Actions</th></tr>
        <?php while($row = $categories->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= $row['name'] ?></td>
            <td><?php if ($row['thumbnail']): ?><img src="<?= $row['thumbnail'] ?>" alt="Thumbnail" style="max-width:60px;max-height:60px;"><?php endif; ?></td>
            <td>
                <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Delete?')">Delete</a>
                <form method="POST" enctype="multipart/form-data" style="display:inline">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                    <input type="text" name="name" value="<?= $row['name'] ?>" required>
                    <input type="file" name="thumbnail" accept="image/*">
                    <button type="submit" name="edit_category">Edit</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    <a href="stories.php">Manage Stories</a>
</body>
</html>
