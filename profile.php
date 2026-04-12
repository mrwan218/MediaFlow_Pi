<?php require_once 'header.php'; ?>

<?php
$user_data = [];
$errors = [];
$success_msg = '';

// 1. Fetch current user data
$stmt = $conn->prepare("SELECT username, email, theme FROM users WHERE id = ?");
if (!$stmt) {
    die("Database error: " . $conn->error);
}
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();

// 2. Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_email = trim($_POST["email"]);
    $new_theme = $_POST["theme"];

    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    if (empty($errors)) {
        $sql = "UPDATE users SET email = ?, theme = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ssi", $new_email, $new_theme, $_SESSION['user_id']);
            if ($stmt->execute()) {
                $success_msg = "Profile updated successfully!";
                $_SESSION['theme'] = $new_theme;
                // Update local user_data to reflect changes
                $user_data['email'] = $new_email;
                $user_data['theme'] = $new_theme;
            } else {
                $errors[] = "Failed to update profile: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $errors[] = "Database error: " . $conn->error;
        }
    }
}
?>

<div class="form-container">
    <h2>User Profile</h2>
    <p class="profile-username">Username: <strong><?php echo htmlspecialchars($user_data['username']); ?></strong></p>
    
    <?php if (!empty($errors)): ?>
        <div class="error-box">
            <?php foreach ($errors as $error): ?>
                <p><?php echo $error; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if ($success_msg): ?>
        <div class="success-box" style="background: #388e3c; color: white; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
            <?php echo $success_msg; ?>
        </div>
    <?php endif; ?>

    <form action="profile.php" method="post">
        <label for="email">Email:</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
        
        <label for="theme">Theme:</label>
        <select name="theme" id="theme" class="form-select">
            <option value="dark" <?php echo ($user_data['theme'] === 'dark') ? 'selected' : ''; ?>>Dark</option>
            <option value="light" <?php echo ($user_data['theme'] === 'light') ? 'selected' : ''; ?>>Light</option>
        </select>
        
        <button type="submit">Save Changes</button>
    </form>
</div>

</main>
</body>
</html>
