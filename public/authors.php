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
        header('Location: authors.php');
        exit;
    }
    
    $name = $_POST['name'];
    $details = $_POST['details'];
    $photo = '';

    // Handle file upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
        $target_dir = "../assets/uploads/";
        $file_extension = pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION);
        $photo_name = 'author_' . uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $photo_name;

        if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
            $photo = $photo_name;
        }
    }

    // Insert author
    $stmt = $conn->prepare("INSERT INTO authors (name, photo, details) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $photo, $details);
    $stmt->execute();
    header("Location: authors.php");
    exit();
}

// Get all authors
$result = $conn->query("SELECT * FROM authors ORDER BY name");

// Get distinct languages from stories for filtering
$languages = $conn->query("SELECT DISTINCT language FROM stories ORDER BY language");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authors - MiniMinds Service</title>
<link rel="stylesheet" href="../assets/style.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
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
        .modal-content {
            background: var(--light);
            padding: 1rem;
            border-radius: 8px;
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
        .filters {
            background: #fff;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px var(--shadow);
        }
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            align-items: center;
        }
        .filters input, .filters select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: white;
        }
        .filters button {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            background: var(--primary);
            color: white;
            cursor: pointer;
        }
        .filters button:hover {
            background: var(--primary-dark);
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

<div class="container">
    <h1>Authors</h1>

    <div class="filters">
        <form id="filter-form" class="filters-grid">
            <div class="form-group">
                <input type="text" id="filter-name" placeholder="Search by name..." oninput="applyFilters()">
            </div>
            <div class="form-group">
                <select id="filter-language" onchange="applyFilters()">
                    <option value="">All Languages</option>
                    <?php while ($lang = $languages->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($lang['language']) ?>"><?= htmlspecialchars($lang['language']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <input type="text" id="filter-details" placeholder="Search in details..." oninput="applyFilters()">
            </div>
            <div class="form-group">
                <button type="button" onclick="resetFilters()">Reset Filters</button>
            </div>
        </form>
    </div>

<div class="form-container">
        <?php if ($isAdmin): ?>
        <form method="POST" enctype="multipart/form-data" class="form-grid">
                <div style="grid-column: 1 / -1;">
                    <input type="text" id="name" name="name" placeholder="Author Name" required>
                </div>
                <div style="grid-column: 1 / -1;">
                    <input type="file" id="photo" name="photo" accept="image/*">
                </div>
                <div style="grid-column: 1 / -1;">
                    <textarea id="details" name="details" placeholder="Author Details" rows="3"></textarea>
                </div>
                <div style="grid-column: 1 / -1;">
                    <button type="submit">Add Author</button>
                </div>
            </form>
        <?php endif; ?>
        </div>
    </div>

    <!-- Authors List -->
    <div class="table-responsive">
        <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Photo</th>
                            <th>Name</th>
                            <th>Details</th>
                            <?php if ($isAdmin): ?>
                            <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                <td>
                                    <?php if ($row['photo']): ?>
                                        <img src="../assets/uploads/<?php echo htmlspecialchars($row['photo']); ?>" 
                                             alt="Author photo" style="max-width: 50px;">
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo htmlspecialchars($row['details']); ?></td>
                                <?php if ($isAdmin): ?>
                                <td>
                                    <div class="actions">
                                        <button class="btn-edit edit-author" 
                                                data-id="<?php echo $row['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($row['name']); ?>"
                                                data-details="<?php echo htmlspecialchars($row['details']); ?>">
                                            Edit
                                        </button>
                                        <button class="btn-delete delete-author" 
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
<!-- Edit Author Modal -->
<div class="modal fade" id="editAuthorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Author</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editAuthorForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" id="edit_author_id" name="id">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_photo" class="form-label">Photo</label>
                        <input type="file" class="form-control" id="edit_photo" name="photo" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label for="edit_details" class="form-label">Details</label>
                        <textarea class="form-control" id="edit_details" name="details" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveAuthorEdit">Save changes</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function applyFilters() {
    const name = document.getElementById('filter-name').value.toLowerCase();
    const language = document.getElementById('filter-language').value.toLowerCase();
    const details = document.getElementById('filter-details').value.toLowerCase();

    document.querySelectorAll('tbody tr').forEach(row => {
        const rowName = row.children[2].textContent.toLowerCase();
        const rowDetails = row.children[3].textContent.toLowerCase();

        let showRow = true;

        if (name && !rowName.includes(name)) showRow = false;
        if (details && !rowDetails.includes(details)) showRow = false;

        row.style.display = showRow ? '' : 'none';
    });
}

function resetFilters() {
    document.getElementById('filter-name').value = '';
    document.getElementById('filter-language').value = '';
    document.getElementById('filter-details').value = '';
    document.querySelectorAll('tbody tr').forEach(row => row.style.display = '');
}

document.addEventListener('DOMContentLoaded', function() {
    // Edit Author
    document.querySelectorAll('.edit-author').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            const details = this.dataset.details;

            document.getElementById('edit_author_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_details').value = details;

            new bootstrap.Modal(document.getElementById('editAuthorModal')).show();
        });
    });

// Get API token from PHP session
    const getApiToken = () => {
        return '<?php echo $_SESSION["api_token"] ?? ""; ?>';
    };

    // Save Author Edit
    document.getElementById('saveAuthorEdit').addEventListener('click', async function() {
        const formData = new FormData(document.getElementById('editAuthorForm'));
        
        try {
            const response = await fetch('api_authors.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'Authorization': `Bearer ${getApiToken()}`
                }
            });
            
            if (response.ok) {
                window.location.reload();
            } else {
                alert('Error updating author');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error updating author');
        }
    });

    // Delete Author
    document.querySelectorAll('.delete-author').forEach(button => {
        button.addEventListener('click', async function() {
            if (confirm('Are you sure you want to delete this author?')) {
                const id = this.dataset.id;
                
                try {
                    const response = await fetch('api_authors.php', {
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
                        alert('Error deleting author');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error deleting author');
                }
            }
        });
    });
});
</script>
</body>
</html>
