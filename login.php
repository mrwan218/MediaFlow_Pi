<?php
require_once 'config.php';
$errors = [];
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    if (empty($username) || empty($password)) {
        $errors[] = "Please enter username and password.";
    }

    if (empty($errors)) {
        $sql = "SELECT id, username, password_hash, role FROM users WHERE username = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows == 1) {
                $stmt->bind_result($id, $username, $hashed_password, $role);
                if ($stmt->fetch()) {
                    if (password_verify($password, $hashed_password)) {
                        session_regenerate_id();
                        $_SESSION['user_id'] = $id;
                        $_SESSION['username'] = $username;
                        $_SESSION['user_role'] = $role;
                        header('location: dashboard.php');
                        exit();
                    } else {
                        $errors[] = "Invalid password.";
                    }
                }
            } else {
                $errors[] = "Invalid username.";
            }
            $stmt->close();
        }
    }
}
require_once 'header.php';
?>
<div class="form-container">
    <h2>Login</h2>
    <?php if (!empty($errors)): ?>
        <div class="error-box">
            <?php foreach ($errors as $error): ?>
                <p><?php echo $error; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form action="login.php" method="post">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>
</div>
</main>
</body>
</html>
