<?php
require_once 'config.php';
if (!is_admin()) {
    header('location: dashboard.php');
    exit();
}
require_once 'header.php';

$stats = [];
$errors = [];

// 1. Total Users
$result = $conn->query("SELECT COUNT(*) as count FROM users");
$stats['total_users'] = $result->fetch_assoc()['count'];

// 2. Total Libraries (from config.json)
$config_file_path = __DIR__ . '/backend/config.json';
if (file_exists($config_file_path)) {
    $config = json_decode(file_get_contents($config_file_path), true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $stats['total_libraries'] = count($config['libraries'] ?? []);
    } else {
        $errors[] = "Could not parse config.json.";
        $stats['total_libraries'] = 'N/A';
    }
} else {
    $errors[] = "config.json not found.";
    $stats['total_libraries'] = 'N/A';
}

// 3. Total Media Files (from database)
$result = $conn->query("SELECT COUNT(*) as count FROM media_items");
$stats['total_media_files'] = $result->fetch_assoc()['count'];

// 4. Recent User Registrations
$result = $conn->query("SELECT username, created_at FROM users ORDER BY created_at DESC LIMIT 5");
$stats['recent_users'] = $result->fetch_all(MYSQLI_ASSOC);
?>

<div class="admin-dashboard">
    <h1>Admin Dashboard</h1>
    <p>System overview and management.</p>

    <?php if (!empty($errors)): ?>
        <div class="card error-card">
            <h2>⚠️ System Health Warnings</h2>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card"><h3>Total Users</h3><span class="stat-number"><?php echo $stats['total_users']; ?></span></div>
        <div class="stat-card"><h3>Total Libraries</h3><span class="stat-number"><?php echo $stats['total_libraries']; ?></span></div>
        <div class="stat-card"><h3>Total Media Files</h3><span class="stat-number"><?php echo $stats['total_media_files']; ?></span></div>
    </div>

    <div class="card">
        <h2>Management</h2>
        <div class="action-grid">
            <a href="admin_libraries.php" class="action-card"><h3>📁 Libraries</h3><p>Add, remove, or configure media libraries.</p></a>
            <a href="admin_permissions.php" class="action-card"><h3>👥 Permissions</h3><p>Control user access to private libraries and content ratings.</p></a>
            <a href="admin_users.php" class="action-card"><h3>🔐 Users</h3><p>Manage user accounts and roles.</p></a>
            <a href="settings.php" class="action-card"><h3>⚙️ Settings</h3><p>Configure admin preferences and theme style.</p></a>
            <a href="run_scan.php" class="action-card"><h3>🔄 Scan Now</h3><p>Run the media scanner to update the library.</p></a>
        </div>
    </div>

    <div class="card">
        <h2>Recent User Registrations</h2>
        <?php if (empty($stats['recent_users'])): ?>
            <p>No new users have registered yet.</p>
        <?php else: ?>
            <ul class="activity-list">
                <?php foreach ($stats['recent_users'] as $user): ?>
                    <li>
                        <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                        <span class="activity-date"><?php echo date("M j, Y", strtotime($user['created_at'])); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

</main>
</body>
</html>
