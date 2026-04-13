<?php
// Start the session
session_start();

// Database configuration
define('DB_SERVER', getenv('DB_SERVER') ?: 'localhost');
define('DB_USERNAME', getenv('DB_USERNAME') ?: 'mediaflow_user'); 
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: 'change_this_password'); 
define('DB_NAME', getenv('DB_NAME') ?: 'mediaflow_db');

// External API Keys
define('TMDB_API_KEY', getenv('TMDB_API_KEY') ?: '6bdd792021e955dd99fd0a21c25397e3');

// Validate required environment variables
$required_env_vars = ['DB_SERVER', 'DB_USERNAME', 'DB_PASSWORD', 'DB_NAME', 'TMDB_API_KEY'];
foreach ($required_env_vars as $var) {
    if (!getenv($var) && constant($var) === ($var === 'TMDB_API_KEY' ? '6bdd792021e955dd99fd0a21c25397e3' : 'change_this_password')) {
        die("Error: Required environment variable '$var' is not set. Please check your .env file.");
    }
}

// Attempt to connect to MySQL database
$conn = @new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    define('DB_CONNECTION_ERROR', $conn->connect_error);
}

// Helper function to check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Helper function to check if user is admin
function is_admin() {
    return is_logged_in() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}
?>
