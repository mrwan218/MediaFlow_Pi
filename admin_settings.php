<?php
require_once 'config.php';
if (!is_admin()) {
    header('location: dashboard.php');
    exit();
}

$errors = [];
$success_message = '';

$current_theme = $_SESSION['theme'] ?? 'dark';

// Load current TMDB API key from config.json
$config_path = __DIR__ . '/backend/config.json';
$current_tmdb_key = '';
if (file_exists($config_path)) {
    $config_data = json_decode(file_get_contents($config_path), true);
    $current_tmdb_key = $config_data['tmdb_api_key'] ?? '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_theme = $_POST['theme'] ?? 'dark';
    $tmdb_api_key = trim($_POST['tmdb_api_key'] ?? '');

    if (!in_array($selected_theme, ['dark', 'light'])) {
        $errors[] = 'Invalid theme selection.';
    }

    if (empty($tmdb_api_key)) {
        $errors[] = 'TMDB API key cannot be empty.';
    }

    if (empty($errors)) {
        // Update theme in database
        $stmt = $conn->prepare('UPDATE users SET theme = ? WHERE id = ?');
        $stmt->bind_param('si', $selected_theme, $_SESSION['user_id']);

        if ($stmt->execute()) {
            $_SESSION['theme'] = $selected_theme;
            $current_theme = $selected_theme;
        } else {
            $errors[] = 'Failed to update theme: ' . $stmt->error;
        }

        $stmt->close();

        // Update TMDB API key in config.json
        if (file_exists($config_path)) {
            $config_data['tmdb_api_key'] = $tmdb_api_key;
            if (file_put_contents($config_path, json_encode($config_data, JSON_PRETTY_PRINT)) === false) {
                $errors[] = 'Failed to update TMDB API key.';
            } else {
                $current_tmdb_key = $tmdb_api_key;
                $success_message = 'Admin settings updated successfully.';
            }
        } else {
            $errors[] = 'Config file not found.';
        }
    }
}

require_once 'header.php';
?>

<div class="form-container">
    <h2>Admin Settings</h2>

    <?php if (!empty($errors)): ?>
        <div class="error-box">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success_message)): ?>
        <div class="success-box">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>

    <form method="post" action="admin_settings.php">
        <label for="theme">Theme Style</label>
        <select name="theme" id="theme" class="form-select">
            <option value="dark" <?php echo ($current_theme === 'dark') ? 'selected' : ''; ?>>Dark</option>
            <option value="light" <?php echo ($current_theme === 'light') ? 'selected' : ''; ?>>Light</option>
        </select>

        <label for="tmdb_api_key">TMDB API Key</label>
        <input type="text" name="tmdb_api_key" id="tmdb_api_key" value="<?php echo htmlspecialchars($current_tmdb_key); ?>" required class="form-input">

        <button type="submit">Save Settings</button>
    </form>
</div>

</main>
</body>
</html>
