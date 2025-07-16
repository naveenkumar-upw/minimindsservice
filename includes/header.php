<?php
// Determine current page for active link highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
    .nav-header {
        background: #fff;
        padding: 10px 0;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
</style>
<div class="nav-header">
    <ul class="nav-links">
        <li><a href="stories.php" <?php echo $current_page == 'stories.php' ? 'class="active"' : ''; ?>>Stories</a></li>
        <li><a href="categories.php" <?php echo $current_page == 'categories.php' ? 'class="active"' : ''; ?>>Categories</a></li>
        <li><a href="devices.php" <?php echo $current_page == 'devices.php' ? 'class="active"' : ''; ?>>Devices</a></li>
    </ul>
</div>
