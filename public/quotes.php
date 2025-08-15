<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Initialize session and check authentication
initSession();
$currentUser = requireAuth();

// Check if user has admin privileges for write operations
$isAdmin = in_array($currentUser['role'], ['admin', 'super_admin']);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if user is admin
    if (!$isAdmin) {
        header('Location: quotes.php');
        exit;
    }
    $quote = $_POST['quote'];
    $author_id = $_POST['author_id'];
    $image = '';

    // Handle file upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $target_dir = "../assets/uploads/";
        $file_extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
        $image_name = 'quote_' . uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $image_name;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image = $image_name;
        }
    }

    // Insert quote
    $stmt = $conn->prepare("INSERT INTO quotes (quote, author_id, language, image) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("siss", $quote, $author_id, $_POST['language'], $image);
    $stmt->execute();
    header("Location: quotes.php");
    exit();
}

// Get all authors for dropdown
$authors = $conn->query("SELECT id, name FROM authors ORDER BY name");

// Get all quotes with author details
$quotes = $conn->query("
    SELECT q.*, a.name as author_name 
    FROM quotes q 
    LEFT JOIN authors a ON q.author_id = a.id 
    ORDER BY q.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotes - MiniMinds Service</title>
<link rel="stylesheet" href="../assets/style.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

</head>
<body>
    <?php include '../includes/header.php'; ?>

<div class="container">
    <h1>Quotes</h1>

    <div class="filters">
        <form id="filter-form" class="filters-grid">
            <div class="form-group">
                <select id="filter-author" onchange="applyFilters()">
                    <option value="">All Authors</option>
                    <?php 
                    $authors->data_seek(0);
                    while ($author = $authors->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($author['name']) ?>"><?= htmlspecialchars($author['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <select id="filter-language" onchange="applyFilters()">
                    <option value="">All Languages</option>
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
            <div class="form-group">
                <input type="text" id="filter-text" placeholder="Search quotes..." oninput="applyFilters()">
            </div>
            <div class="form-group">
                <button type="button" onclick="resetFilters()">Reset Filters</button>
            </div>
        </form>
    </div>

<div class="form-container">
        <?php if ($isAdmin): ?>
        <form method="POST" enctype="multipart/form-data" class="form-grid">
                <div class="grid-full">
                    <textarea id="quote" name="quote" placeholder="Enter Quote" required></textarea>
                </div>
                <div class="grid-full">
                    <select id="author_id" name="author_id" required>
                        <option value="">Select Author</option>
                        <?php while ($author = $authors->fetch_assoc()): ?>
                            <option value="<?php echo $author['id']; ?>">
                                <?php echo htmlspecialchars($author['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="grid-full">
                    <select id="language" name="language" required>
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
                    <input type="file" id="image" name="image" accept="image/*">
                </div>
                <div class="grid-full">
                    <button type="submit">Add Quote</button>
                </div>
            </form>
        <?php endif; ?>
        </div>
    </div>

    <!-- Quotes List -->
    <div class="table-responsive">
        <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Quote</th>
                            <th>Author</th>
                            <th>Language</th>
                            <th>Image</th>
                            <th>Created At</th>
                            <?php if ($isAdmin): ?>
                            <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $quotes->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                <td><?php echo htmlspecialchars($row['quote']); ?></td>
                                <td><?php echo htmlspecialchars($row['author_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['language']); ?></td>
                                <td>
                                    <?php if ($row['image']): ?>
                                        <img src="../assets/uploads/<?php echo htmlspecialchars($row['image']); ?>" 
                                             alt="Quote image" class="img-thumbnail">
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                <?php if ($isAdmin): ?>
                                <td>
                                    <div class="actions">
                                        <button class="btn-edit edit-quote" 
                                                data-id="<?php echo $row['id']; ?>"
                                                data-quote="<?php echo htmlspecialchars($row['quote']); ?>"
                                                data-author="<?php echo $row['author_id']; ?>"
                                                data-language="<?php echo htmlspecialchars($row['language']); ?>">
                                            Edit
                                        </button>
                                        <button class="btn-delete delete-quote" 
                                                data-id="<?php echo $row['id']; ?>">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if ($isAdmin): ?>
<!-- Edit Quote Modal -->
<div class="modal fade" id="editQuoteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Quote</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editQuoteForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" id="edit_quote_id" name="id">
                    <div class="mb-3">
                        <label for="edit_quote" class="form-label">Quote</label>
                        <textarea class="form-control" id="edit_quote" name="quote" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_author_id" class="form-label">Author</label>
                        <select class="form-control" id="edit_author_id" name="author_id" required>
                            <option value="">Select Author</option>
                            <?php 
                            $authors->data_seek(0);
                            while ($author = $authors->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $author['id']; ?>">
                                    <?php echo htmlspecialchars($author['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_language" class="form-label">Language</label>
                        <select class="form-control" id="edit_language" name="language" required>
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
                    <div class="mb-3">
                        <label for="edit_image" class="form-label">New Image (Optional)</label>
                        <input type="file" class="form-control" id="edit_image" name="image" accept="image/*">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveQuoteEdit">Save changes</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function applyFilters() {
    const author = document.getElementById('filter-author').value.toLowerCase();
    const language = document.getElementById('filter-language').value.toLowerCase();
    const text = document.getElementById('filter-text').value.toLowerCase();

    document.querySelectorAll('tbody tr').forEach(row => {
        const rowAuthor = row.children[2].textContent.toLowerCase();
        const rowLanguage = row.children[3].textContent.toLowerCase();
        const rowQuote = row.children[1].textContent.toLowerCase();

        let showRow = true;

        if (author && rowAuthor !== author) showRow = false;
        if (language && rowLanguage !== language) showRow = false;
        if (text && !rowQuote.includes(text)) showRow = false;

        row.style.display = showRow ? '' : 'none';
    });
}

function resetFilters() {
    document.getElementById('filter-author').value = '';
    document.getElementById('filter-language').value = '';
    document.getElementById('filter-text').value = '';
    document.querySelectorAll('tbody tr').forEach(row => row.style.display = '');
}

document.addEventListener('DOMContentLoaded', function() {
    // Edit Quote
    document.querySelectorAll('.edit-quote').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const quote = this.dataset.quote;
            const author = this.dataset.author;
            const language = this.dataset.language;

            document.getElementById('edit_quote_id').value = id;
            document.getElementById('edit_quote').value = quote;
            document.getElementById('edit_author_id').value = author;
            document.getElementById('edit_language').value = language;

            new bootstrap.Modal(document.getElementById('editQuoteModal')).show();
        });
    });

    // Get API token from PHP session
    const getApiToken = () => {
        return '<?php echo $_SESSION["api_token"] ?? ""; ?>';
    };

    // Save Quote Edit
    document.getElementById('saveQuoteEdit').addEventListener('click', async function() {
        const formData = new FormData(document.getElementById('editQuoteForm'));
        
        try {
            const response = await fetch('api_quotes.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'Authorization': `Bearer ${getApiToken()}`
                }
            });
            
            if (response.ok) {
                window.location.reload();
            } else {
                alert('Error updating quote');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error updating quote');
        }
    });

    // Delete Quote
    document.querySelectorAll('.delete-quote').forEach(button => {
        button.addEventListener('click', async function() {
            if (confirm('Are you sure you want to delete this quote?')) {
                const id = this.dataset.id;
                
                try {
                    const response = await fetch('api_quotes.php', {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${getApiToken()}`
                        },
                        body: JSON.stringify({ id: id })
                    });
                    
                    if (response.ok) {
                        window.location.reload();
                    } else {
                        alert('Error deleting quote');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error deleting quote');
                }
            }
        });
    });
});
</script>
</body>
</html>
