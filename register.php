<?php
require_once 'config.php';
$errors = [];
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    $confirm_password = trim($_POST["confirm_password"]);

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $errors[] = "All fields are required.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    if (empty($errors)) {
        $sql = "SELECT id FROM users WHERE username = ? OR email = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows == 1) {
                $errors[] = "Username or email already exists.";
            }
            $stmt->close();
        }
    }

    if (empty($errors)) {
        $sql = "INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)";
        if ($stmt = $conn->prepare($sql)) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt->bind_param("sss", $username, $email, $password_hash);
            if ($stmt->execute()) {
                header('location: login.php');
                exit();
            } else {
                $errors[] = "Something went wrong. Please try again.";
            }
            $stmt->close();
        }
    }
}
require_once 'header.php';
?>
<div class="form-container">
    <h2>Register</h2>
    <?php if (!empty($errors)): ?>
        <div class="error-box">
            <?php foreach ($errors as $error): ?>
                <p><?php echo $error; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form action="register.php" method="post">
        <input type="text" name="username" placeholder="Username" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required>
        <button type="submit">Register</button>
    </form>
</div>
</main>
</body>
</html>
