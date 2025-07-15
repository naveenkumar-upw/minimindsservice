<?php
include '../includes/db.php';
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
    $conn->query("INSERT INTO stories (category_id, title, content, language, country, state, images) VALUES ($category_id, '$title', '$content', '$language', '$country', '$state', '$imagesStr')");
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
        $conn->query("UPDATE stories SET category_id=$category_id, title='$title', content='$content', language='$language', country='$country', state='$state', images='$imagesStr' WHERE id=$id");
    } else {
        $conn->query("UPDATE stories SET category_id=$category_id, title='$title', content='$content', language='$language', country='$country', state='$state' WHERE id=$id");
    }
}
$stories = $conn->query("SELECT stories.*, categories.name as category FROM stories JOIN categories ON stories.category_id=categories.id");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Stories</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <h1>Stories</h1>
    <form method="POST" enctype="multipart/form-data">
        <input type="text" name="title" placeholder="Story Title" required>
        <textarea name="content" placeholder="Content" required></textarea>
        <input type="text" name="language" placeholder="Language" required>
        <input type="text" name="country" placeholder="Country" required>
        <input type="text" name="state" placeholder="State" required>
        <select name="category_id" required>
            <option value="">Select Category</option>
            <?php foreach($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>"><?= $cat['name'] ?></option>
            <?php endforeach; ?>
        </select>
        <input type="file" name="images[]" accept="image/*" multiple required>
        <button type="submit" name="add_story">Add Story</button>
    </form>
    <table border="1" cellpadding="5">
        <tr><th>ID</th><th>Title</th><th>Category</th><th>Content</th><th>Language</th><th>Country</th><th>State</th><th>Images</th><th>Actions</th></tr>
        <?php while($row = $stories->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= $row['title'] ?></td>
            <td><?= $row['category'] ?></td>
            <td><?= $row['content'] ?></td>
            <td><?= $row['language'] ?></td>
            <td><?= $row['country'] ?></td>
            <td><?= $row['state'] ?></td>
            <td>
                <?php if (!empty($row['images'])): ?>
                    <?php foreach (explode(',', $row['images']) as $img): ?>
                        <img src="../<?= trim($img) ?>" alt="Story Image" style="max-width:40px;max-height:40px;">
                    <?php endforeach; ?>
                <?php endif; ?>
            </td>
            <td>
                <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Delete?')">Delete</a>
                <form method="POST" enctype="multipart/form-data" style="display:inline">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                    <input type="text" name="title" value="<?= $row['title'] ?>" required>
                    <textarea name="content" required><?= $row['content'] ?></textarea>
                    <input type="text" name="language" value="<?= $row['language'] ?>" required>
                    <input type="text" name="country" value="<?= $row['country'] ?>" required>
                    <input type="text" name="state" value="<?= $row['state'] ?>" required>
                    <select name="category_id" required>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $cat['id']==$row['category_id']?'selected':'' ?>><?= $cat['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="file" name="images[]" accept="image/*" multiple>
                    <button type="submit" name="edit_story">Edit</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    <a href="categories.php">Manage Categories</a>
</body>
</html>
