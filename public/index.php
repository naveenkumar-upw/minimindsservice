<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Initialize session and check authentication
initSession();
$currentUser = requireAuth();

// Get some basic statistics for the dashboard
$stats = [
    'stories' => $conn->query("SELECT COUNT(*) as count FROM stories")->fetch_assoc()['count'],
    'categories' => $conn->query("SELECT COUNT(*) as count FROM categories")->fetch_assoc()['count'],
    'authors' => $conn->query("SELECT COUNT(*) as count FROM authors")->fetch_assoc()['count'],
    'quotes' => $conn->query("SELECT COUNT(*) as count FROM quotes")->fetch_assoc()['count'],
    'devices' => $conn->query("SELECT COUNT(*) as count FROM device_tokens")->fetch_assoc()['count']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - MiniMinds Service</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .welcome-section {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px var(--shadow);
            margin-bottom: 2rem;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px var(--shadow);
            text-align: center;
        }
        .stat-card h3 {
            color: var(--secondary);
            margin: 0 0 0.5rem 0;
        }
        .stat-card .number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary);
        }
        .quick-links {
            margin-top: 2rem;
        }
        .quick-links h2 {
            margin-bottom: 1rem;
        }
        .links-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }
        .quick-link {
            display: block;
            padding: 1rem;
            background: var(--light);
            border-radius: 8px;
            text-align: center;
            text-decoration: none;
            color: var(--dark);
            transition: all 0.3s ease;
        }
        .quick-link:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }
        .admin-section {
            margin-top: 2rem;
            padding: 1rem;
            background: #f8d7da;
            border-radius: 8px;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <div class="welcome-section">
            <h1>Welcome, <?php echo htmlspecialchars($currentUser['username']); ?>!</h1>
            <p>Welcome to the MiniMinds Service management dashboard. Here you can manage all aspects of your content.</p>
            <?php if ($currentUser['role'] === 'admin'): ?>
                <div class="admin-section">
                    <h3>Admin Access</h3>
                    <p>You have administrative privileges. You can create, edit, and delete all content.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Stories</h3>
                <div class="number"><?php echo $stats['stories']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Categories</h3>
                <div class="number"><?php echo $stats['categories']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Authors</h3>
                <div class="number"><?php echo $stats['authors']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Quotes</h3>
                <div class="number"><?php echo $stats['quotes']; ?></div>
            </div>
            <?php if ($currentUser['role'] === 'admin'): ?>
            <div class="stat-card">
                <h3>Registered Devices</h3>
                <div class="number"><?php echo $stats['devices']; ?></div>
            </div>
            <?php endif; ?>
        </div>

        <div class="quick-links">
            <h2>Quick Links</h2>
            <div class="links-grid">
                <a href="stories.php" class="quick-link">Stories</a>
                <a href="categories.php" class="quick-link">Categories</a>
                <a href="authors.php" class="quick-link">Authors</a>
                <a href="quotes.php" class="quick-link">Quotes</a>
                <?php if ($currentUser['role'] === 'super_admin'): ?>
                    <a href="devices.php" class="quick-link">Device Tokens</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
