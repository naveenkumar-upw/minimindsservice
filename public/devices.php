<?php
include '../includes/db.php';

// Fetch all device tokens
$result = $conn->query("SELECT device_token, created_at FROM device_tokens ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Device Tokens - MiniMinds Service</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .status-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            color: var(--secondary);
            font-size: 0.875rem;
        }
        .auto-refresh {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .refresh-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--success);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .refresh-indicator.active {
            opacity: 1;
        }
        .table-empty {
            text-align: center;
            padding: 2rem;
            color: var(--secondary);
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px var(--shadow);
        }
        .datetime {
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <h1>Device Tokens</h1>

        <div class="status-bar">
            <div class="auto-refresh">
                <div id="refresh-indicator" class="refresh-indicator"></div>
                Auto-refreshing every 30 seconds
            </div>
            <div>Last refreshed: <span id="last-refresh">just now</span></div>
        </div>

    <?php if ($result->num_rows > 0): ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Device Token</th>
                    <th>Registration Date</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['device_token']); ?></td>
                        <td class="datetime"><?php echo $row['created_at'] ? date('Y-m-d H:i:s', strtotime($row['created_at'])) : 'N/A'; ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <div class="table-empty">
            <p>No device tokens registered</p>
        </div>
    <?php endif; ?>
    </div>

    <script>
        // Auto-refresh the page every 30 seconds to show new registrations
        setTimeout(() => window.location.reload(), 30000);
    </script>
</body>
</html>
