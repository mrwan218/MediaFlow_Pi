<?php
require_once 'config.php';

// Check if there was a database connection error
if (defined('DB_CONNECTION_ERROR')) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Database Connection Error</title>
        <style>
            body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background: #121212; color: #fff; margin: 0; }
            .error-card { background: #1e1e1e; padding: 2rem; border-radius: 8px; border: 1px solid #cf6679; max-width: 500px; text-align: center; }
            h1 { color: #cf6679; }
            code { background: #333; padding: 0.2rem 0.4rem; border-radius: 4px; }
        </style>
    </head>
    <body>
        <div class="error-card">
            <h1>Database Connection Error</h1>
            <p>MediaFlow could not connect to the database.</p>
            <p>Error: <code><?php echo htmlspecialchars(DB_CONNECTION_ERROR); ?></code></p>
            <p>Please check your <code>config.php</code> settings and ensure your database server is running.</p>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// --- Session & Authentication Check ---
$public_pages = ['login.php', 'register.php'];
if (!is_logged_in() && !in_array(basename($_SERVER['PHP_SELF']), $public_pages)) {
    header('location: login.php');
    exit();
}

// If the user IS logged in, refresh their session data and set theme
if (is_logged_in()) {
    $stmt = $conn->prepare("SELECT username, role, theme, max_allowed_rating FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user_data = $result->fetch_assoc();
        $_SESSION['username'] = $user_data['username'];
        $_SESSION['user_role'] = $user_data['role'];
        $_SESSION['theme'] = $user_data['theme'];
        $_SESSION['max_allowed_rating'] = $user_data['max_allowed_rating'];
    } else {
        session_destroy();
        header('location: login.php?error=invalid_session');
        exit();
    }
    $stmt->close();
}

$theme = $_SESSION['theme'] ?? 'dark';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediaFlow</title>
    <link rel="stylesheet" href="assets/css/style-dark.css">
    <?php if ($theme === 'light'): ?>
        <link rel="stylesheet" href="assets/css/style-light.css">
    <?php endif; ?>
    <script src="assets/js/script.js" defer></script>
</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="nav-brand">MediaFlow</a>
        <div class="nav-links">
            <?php if (is_logged_in()): ?>
                <a href="<?php echo is_admin() ? 'admin_dashboard.php' : 'dashboard.php'; ?>">Dashboard</a>
                <?php if (is_admin()): ?>
                    <a href="admin_settings.php">Settings</a>
                <?php endif; ?>
                <a href="profile.php">Profile</a>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>
            <?php endif; ?>
        </div>
    </nav>
    <main class="container">
