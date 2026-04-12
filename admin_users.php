<?php
require_once 'config.php';
if (!is_admin()) {
    header('location: dashboard.php');
    exit();
}
require_once 'header.php';

$users = [];
$errors = [];
$success_msg = '';

if (isset($_POST['action']) && $_POST['action'] === 'change_role' && isset($_POST['user_id']) && isset($_POST['new_role'])) {
    $user_id_to_change = (int)$_POST['user_id']; $new_role = $_POST['new_role'];
    if ($user_id_to_change === $_SESSION['user_id']) { $errors[] = "You cannot change your own role."; }
    else {
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->bind_param("si", $new_role, $user_id_to_change);
        if ($stmt->execute()) { $success_msg = "User role updated successfully."; }
        else { $errors[] = "Failed to update user role."; }
        $stmt->close();
    }
}
if (isset($_POST['action']) && $_POST['action'] === 'change_rating' && isset($_POST['user_id']) && isset($_POST['new_rating'])) {
    $user_id_to_change = (int)$_POST['user_id']; $new_rating = $_POST['new_rating'];
    if ($user_id_to_change !== $_SESSION['user_id']) {
        $stmt = $conn->prepare("UPDATE users SET max_allowed_rating = ? WHERE id = ?");
        $stmt->bind_param("si", $new_rating, $user_id_to_change);
        $stmt->execute(); $stmt->close();
        $success_msg = "User rating updated successfully.";
    }
}
if (isset($_GET['delete'])) {
    $user_id_to_delete = (int)$_GET['delete'];
    if ($user_id_to_delete === $_SESSION['user_id']) { $errors[] = "You cannot delete your own account."; }
    else {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("DELETE FROM user_library_access WHERE user_id = ?");
            $stmt->bind_param("i", $user_id_to_delete); $stmt->execute(); $stmt->close();
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id_to_delete); $stmt->execute(); $stmt->close();
            $conn->commit();
            header('location: admin_users.php?msg=deleted'); exit();
        } catch (Exception $e) { $conn->rollback(); $errors[] = "Failed to delete user: " . $e->getMessage(); }
    }
}
if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') { $success_msg = "User deleted successfully."; }
$sql = "SELECT id, username, email, role, max_allowed_rating, created_at FROM users ORDER BY created_at DESC";
$result = $conn->query($sql);
if ($result) { $users = $result->fetch_all(MYSQLI_ASSOC); }
else { $errors[] = "Could not fetch users from the database."; }
?>

<div class="admin-container">
    <h1>User Management</h1>
    <p>View and manage all user accounts and their content permissions.</p>
    <?php if ($success_msg): ?><div class="success-box"><?php echo $success_msg; ?></div><?php endif; ?>
    <?php if (!empty($errors)): ?><div class="error-box"><?php foreach ($errors as $error) { echo "<p>$error</p>"; } ?></div><?php endif; ?>
    <div class="card">
        <h2>All Users</h2>
        <?php if (empty($users)): ?>
            <p>No users found in the database.</p>
        <?php else: ?>
            <table>
                <thead><tr><th>Username</th><th>Email</th><th>Role</th><th>Max Allowed Rating</th><th>Registered</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <form action="admin_users.php" method="post" style="display:inline;">
                                <input type="hidden" name="action" value="change_role"><input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <select name="new_role" onchange="this.form.submit()" <?php echo ($user['id'] == $_SESSION['user_id']) ? 'disabled' : ''; ?>>
                                    <option value="user" <?php echo ($user['role'] === 'user') ? 'selected' : ''; ?>>User</option>
                                    <option value="admin" <?php echo ($user['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </form>
                        </td>
                        <td>
                            <form action="admin_users.php" method="post" style="display:inline;">
                                <input type="hidden" name="action" value="change_rating"><input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <select name="new_rating" onchange="this.form.submit()" <?php echo ($user['id'] == $_SESSION['user_id']) ? 'disabled' : ''; ?>>
                                    <option value="G" <?php echo ($user['max_allowed_rating'] === 'G') ? 'selected' : ''; ?>>G</option>
                                    <option value="PG" <?php echo ($user['max_allowed_rating'] === 'PG') ? 'selected' : ''; ?>>PG</option>
                                    <option value="PG-13" <?php echo ($user['max_allowed_rating'] === 'PG-13') ? 'selected' : ''; ?>>PG-13</option>
                                    <option value="R" <?php echo ($user['max_allowed_rating'] === 'R') ? 'selected' : ''; ?>>R</option>
                                    <option value="NC-17" <?php echo ($user['max_allowed_rating'] === 'NC-17') ? 'selected' : ''; ?>>NC-17</option>
                                </select>
                            </form>
                        </td>
                        <td><?php echo date("M j, Y", strtotime($user['created_at'])); ?></td>
                        <td>
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <a href="admin_users.php?delete=<?php echo $user['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Are you sure?');">Delete</a>
                            <?php else: ?>
                                <span style="color: #888;">(You)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

</main>
</body>
</html>
