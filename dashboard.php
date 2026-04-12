<?php require_once 'header.php'; ?>

<h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>

<?php
$config_file_path = __DIR__ . '/backend/config.json';
$accessible_libraries = [];
$config_error = false;

// 1. Get the current user's granted libraries from the database.
$granted_library_names = [];
$stmt = $conn->prepare("SELECT library_name FROM user_library_access WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $granted_library_names[] = $row['library_name'];
}
$stmt->close();

// 2. Fetch and validate the full library definitions from backend/config.json.
if (file_exists($config_file_path)) {
    $json_data = file_get_contents($config_file_path);
    $config = json_decode($json_data, true);

    // Check for JSON errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        $config_error = true;
        $error_message = "Error parsing configuration file. Please contact an administrator.";
    } else {
        $all_libraries = $config['libraries'] ?? [];

        // 3. Filter the libraries based on permissions (public or granted).
        foreach ($all_libraries as $library) {
            if ($library['public'] || in_array($library['name'], $granted_library_names)) {
                $accessible_libraries[] = $library;
            }
        }
    }
} else {
    $config_error = true;
    $error_message = "Configuration file not found. Please contact an administrator.";
}
?>

<div class="library-grid">
    <?php if ($config_error): ?>
        <div class="dashboard-card error-card">
            <h2>Configuration Error</h2>
            <p><?php echo $error_message; ?></p>
        </div>
    <?php elseif (empty($accessible_libraries)): ?>
        <div class="dashboard-card">
            <h2>No Libraries Available</h2>
            <p>You do not have access to any media libraries yet. Please contact your administrator.</p>
        </div>
    <?php else: ?>
        <?php foreach ($accessible_libraries as $library): ?>
            <a href="library_view.php?name=<?php echo urlencode($library['name']); ?>" class="dashboard-card library-card-link">
                <h3><?php echo htmlspecialchars($library['name']); ?></h3>
                <?php if ($library['public']): ?>
                    <span class="badge badge-public">Public</span>
                <?php endif; ?>
                <p><em>Click to browse media</em></p>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</main>
</body>
</html>
