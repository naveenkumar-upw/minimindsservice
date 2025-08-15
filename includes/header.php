<?php
require_once __DIR__ . '/auth.php';

// Initialize session if not already started
if (session_status() === PHP_SESSION_NONE) {
    initSession();
}

// Determine current page for active link highlighting
$current_page = basename($_SERVER['PHP_SELF']);

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Get current user if authenticated
$currentUser = getCurrentUser();
?>
    <style>
        .nav-header {
            background: #fff;
            padding: 10px 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
    }
    .nav-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        max-width: 1200px;
        margin: 0 auto;
    }
    .nav-links {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        gap: 20px;
    }
    .nav-links li a {
        text-decoration: none;
        color: #333;
        padding: 5px 10px;
    }
    .nav-links li a.active {
        color: #007bff;
        font-weight: bold;
    }
    .nav-links li a:hover {
        color: #0056b3;
    }
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .username {
            font-weight: 500;
            color: #444;
        }
        .badge {
            background: #6c757d;
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.75em;
            margin-left: 5px;
        }
        .btn-logout {
            background: #dc3545;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9em;
            transition: background-color 0.2s ease;
        }
        .btn-logout:hover {
            background: #c82333;
            color: white;
            text-decoration: none;
        }
</style>
<div class="nav-header">
    <div class="nav-container">
        <ul class="nav-links">
            <li><a href="index.php" <?php echo $current_page == 'index.php' ? 'class="active"' : ''; ?>>Dashboard</a></li>
            <li><a href="stories.php" <?php echo $current_page == 'stories.php' ? 'class="active"' : ''; ?>>Stories</a></li>
            <li><a href="categories.php" <?php echo $current_page == 'categories.php' ? 'class="active"' : ''; ?>>Categories</a></li>
            <li><a href="quotes.php" <?php echo $current_page == 'quotes.php' ? 'class="active"' : ''; ?>>Quotes</a></li>
            <li><a href="authors.php" <?php echo $current_page == 'authors.php' ? 'class="active"' : ''; ?>>Authors</a></li>
            <?php if ($currentUser['role'] === 'super_admin'): ?>
                <li><a href="devices.php" <?php echo $current_page == 'devices.php' ? 'class="active"' : ''; ?>>Devices</a></li>
                <li><a href="users.php" <?php echo $current_page == 'users.php' ? 'class="active"' : ''; ?>>Users</a></li>
            <?php endif; ?>
        </ul>
        <div class="user-info">
            <span class="username">
                <?php echo htmlspecialchars($currentUser['username']); ?>
                <?php if ($currentUser['role'] === 'admin'): ?>
                    <span class="badge">(Admin)</span>
                <?php elseif ($currentUser['role'] === 'super_admin'): ?>
                    <span class="badge" style="background: #28a745;">(Super Admin)</span>
                <?php endif; ?>
            </span>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </div>
</div>
