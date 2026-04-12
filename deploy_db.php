<?php
/**
 * MediaFlow Database Deployment Script (Docker Edition)
 * This script automates the creation of the database and tables.
 */

// --- Configuration ---
$host = 'localhost'; 
$root_user = 'root';
$root_pass = ''; // As per user's environment
$db_name = 'mediaflow_db';
$app_user = 'mediaflow_user';
$app_pass = 'change_this_password';

echo "<html><body style='font-family:sans-serif; background:#121212; color:#eee; padding:2rem;'>";
echo "<h1>MediaFlow Database Deployment (Docker)</h1>";

// 1. Diagnostic Information
echo "<h3>System Diagnostics</h3>";
echo "<ul>";
echo "<li>PHP Version: " . PHP_VERSION . "</li>";
echo "<li>MySQLi Extension: " . (extension_loaded('mysqli') ? 'Loaded' : 'NOT LOADED') . "</li>";
echo "<li>Target Docker Host: $host</li>";
echo "</ul>";

// 2. Connect to MySQL as Root
echo "<h3>Connecting to Docker MySQL...</h3>";
mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli($host, $root_user, $root_pass);

if ($conn->connect_error) {
    echo "<div style='background:#b71c1c; padding:1rem; border-radius:4px;'>";
    echo "<strong>Connection failed:</strong> " . $conn->connect_error . "<br>";
    echo "Error Code: " . $conn->connect_errno;
    echo "</div>";
    
    echo "<h4>Troubleshooting Tips for Docker:</h4>";
    echo "<ul>";
    echo "<li>Ensure the MySQL container at <code>$host</code> is running and reachable.</li>";
    echo "<li>Check if your Docker network allows connections from the web server container to the MySQL container.</li>";
    echo "<li>Verify that the root user is allowed to connect from your web server's IP.</li>";
    echo "</ul>";
    die("</body></html>");
}

echo "<p style='color:#4caf50;'>✓ Connected to Docker MySQL successfully.</p>";

// 3. Create Database
if ($conn->query("CREATE DATABASE IF NOT EXISTS `$db_name`")) {
    echo "<p>✓ Database `$db_name` ready.</p>";
} else {
    die("<p style='color:#f44336;'>Error creating database: " . $conn->error . "</p>");
}

$conn->select_db($db_name);

// 4. Create Tables
$tables = [
    "users" => "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('admin', 'user') DEFAULT 'user',
        theme ENUM('dark', 'light') DEFAULT 'dark',
        max_allowed_rating ENUM('G', 'PG', 'PG-13', 'R', 'NC-17') DEFAULT 'R',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;",
    
    "media_items" => "CREATE TABLE IF NOT EXISTS media_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        file_path TEXT NOT NULL,
        file_hash VARCHAR(32) NOT NULL,
        library_name VARCHAR(100) NOT NULL,
        title VARCHAR(255) NOT NULL,
        year INT,
        overview TEXT,
        poster_path VARCHAR(255),
        backdrop_path VARCHAR(255),
        rating ENUM('G', 'PG', 'PG-13', 'R', 'NC-17') DEFAULT 'R',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (library_name),
        INDEX (rating)
    ) ENGINE=InnoDB;",
    
    "user_library_access" => "CREATE TABLE IF NOT EXISTS user_library_access (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        library_name VARCHAR(100) NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;"
];

echo "<h3>Creating Tables...</h3>";
foreach ($tables as $name => $sql) {
    if ($conn->query($sql)) {
        echo "<p>✓ Table `$name` ready.</p>";
    } else {
        echo "<p style='color:#f44336;'>Error creating table `$name`: " . $conn->error . "</p>";
    }
}

// 5. Create App User and Grant Privileges
echo "<h3>Configuring User Privileges...</h3>";
// In Docker, we use '%' for host to allow connections from other containers
$conn->query("CREATE USER IF NOT EXISTS '$app_user'@'%' IDENTIFIED BY '$app_pass'");
$conn->query("ALTER USER '$app_user'@'%' IDENTIFIED BY '$app_pass'");

if ($conn->query("GRANT ALL PRIVILEGES ON `$db_name`.* TO '$app_user'@'%'")) {
    echo "<p>✓ Privileges granted to `$app_user`@`%`.</p>";
} else {
    echo "<p style='color:#ff9800;'>Warning: Could not grant privileges: " . $conn->error . "</p>";
}

$conn->query("FLUSH PRIVILEGES");

echo "<hr><h2 style='color:#4caf50;'>Docker Deployment Complete!</h2>";
echo "<p>You can now <a href='index.php' style='color:#e50914; font-weight:bold;'>Go to the Homepage</a>.</p>";
echo "</body></html>";

$conn->close();
?>
