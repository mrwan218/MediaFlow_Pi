<?php
require_once 'config.php';

// If the user is already logged in, redirect them to the dashboard
if (is_logged_in()) {
    header('location: dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediaFlow - Welcome</title>
    <link rel="stylesheet" href="assets/css/style-dark.css">
    <style>
        body { display: flex; justify-content: center; align-items: center; height: 100vh; text-align: center; }
        .landing-card { background: var(--card-bg-color); padding: 3rem; border-radius: 12px; border: 1px solid var(--border-color); }
        .landing-card h1 { font-size: 3rem; color: var(--primary-color); margin-bottom: 1rem; }
        .landing-card p { font-size: 1.2rem; margin-bottom: 2rem; }
        .landing-card a { display: inline-block; margin: 0 10px; padding: 10px 20px; background: var(--primary-color); color: white; text-decoration: none; border-radius: 5px; }
        .landing-card a:hover { background: #303f9f; }
    </style>
</head>
<body>
    <div class="landing-card">
        <h1>MediaFlow</h1>
        <p>Your self-hosted media streaming solution.</p>
        <div class="button-group">
            <a href="login.php" class="button-primary">Login</a>
            <a href="register.php" class="button-secondary">Register</a>
        </div>
    </div>
</body>
</html>
