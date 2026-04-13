<?php
require_once 'config.php';

if (!is_logged_in()) {
    header('location: login.php');
    exit();
}

$errors = [];
$success_message = '';
$user_data = [];

$stmt = $conn->prepare("SELECT username, email, theme, role FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();

if (!$user_data) {
    $errors[] = 'User data not found.';
    $user_data = ['username' => '', 'email' => '', 'theme' => 'dark', 'role' => 'user'];
}

$current_theme = $user_data['theme'] ?? 'dark';
$is_admin = ($user_data['role'] === 'admin');

$config_path = __DIR__ . '/backend/config.json';
$current_tmdb_key = '';
if ($is_admin && file_exists($config_path)) {
    $config_content = file_get_contents($config_path);
    $config_data = json_decode($config_content, true);
    if ($config_data === null) {
        $errors[] = 'Invalid config file format.';
    } else {
        $current_tmdb_key = $config_data['tmdb_api_key'] ?? '';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_email = trim($_POST['email'] ?? ($user_data['email'] ?? ''));
    $selected_theme = $_POST['theme'] ?? $current_theme;

    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format.';
    }

    if (!in_array($selected_theme, ['dark', 'light'])) {
        $errors[] = 'Invalid theme selection.';
    }

    if (empty($errors)) {
        $stmt = $conn->prepare('UPDATE users SET email = ?, theme = ? WHERE id = ?');
        if ($stmt) {
            $stmt->bind_param('ssi', $new_email, $selected_theme, $_SESSION['user_id']);
            if ($stmt->execute()) {
                $_SESSION['theme'] = $selected_theme;
                $success_message = 'Settings updated successfully.';
                $user_data['email'] = $new_email;
                $user_data['theme'] = $selected_theme;
                $current_theme = $selected_theme;
            } else {
                $errors[] = 'Failed to update account settings: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $errors[] = 'Database error: ' . $conn->error;
        }
    }

    if ($is_admin && isset($_POST['tmdb_api_key'])) {
        $tmdb_api_key = trim($_POST['tmdb_api_key']);
        if ($tmdb_api_key === '') {
            $errors[] = 'TMDB API key cannot be empty.';
        } elseif ($config_data === null) {
            $errors[] = 'Config file is invalid.';
        } else {
            $config_data['tmdb_api_key'] = $tmdb_api_key;
            if (file_put_contents($config_path, json_encode($config_data, JSON_PRETTY_PRINT)) === false) {
                $errors[] = 'Failed to update TMDB API key.';
            } else {
                $current_tmdb_key = $tmdb_api_key;
                if (empty($success_message)) {
                    $success_message = 'Settings updated successfully.';
                }
            }
        }
    }
}

require_once 'header.php';
?>

<div class="form-container">
    <h2><?php echo $is_admin ? 'Settings' : 'Profile Settings'; ?></h2>
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

    <form method="post" action="settings.php">
        <p class="profile-username">Username: <strong><?php echo htmlspecialchars($user_data['username'] ?? ''); ?></strong></p>

        <label for="email">Email:</label>
        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required>

        <label for="theme">Theme:</label>
        <select name="theme" id="theme" class="form-select">
            <option value="dark" <?php echo ($current_theme === 'dark') ? 'selected' : ''; ?>>Dark</option>
            <option value="light" <?php echo ($current_theme === 'light') ? 'selected' : ''; ?>>Light</option>
        </select>

        <?php if ($is_admin): ?>
            <label for="tmdb_api_key">TMDB API Key</label>
            <input type="text" name="tmdb_api_key" id="tmdb_api_key" value="<?php echo htmlspecialchars($current_tmdb_key); ?>" required class="form-input">
        <?php endif; ?>

        <button type="submit">Save Changes</button>
    </form>
</div>

</main>
</body>
</html>
