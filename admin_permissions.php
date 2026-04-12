<?php
require_once 'config.php';
if (!is_admin()) {
    header('location: dashboard.php');
    exit();
}
require_once 'header.php';

$config_file_path = __DIR__ . '/backend/config.json';
$users = [];
$libraries = [];
$errors = [];
$success_msg = '';
$rating_hierarchy = ['G' => 1, 'PG' => 2, 'PG-13' => 3, 'R' => 4, 'NC-17' => 5];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn->begin_transaction();
    try {
        if (isset($_POST['ratings'])) {
            $stmt_update_rating = $conn->prepare("UPDATE users SET max_allowed_rating = ? WHERE id = ?");
            foreach ($_POST['ratings'] as $user_id => $new_rating) {
                $stmt_update_rating->bind_param("si", $new_rating, $user_id);
                $stmt_update_rating->execute();
            }
            $stmt_update_rating->close();
        }
        if (isset($_POST['permissions'])) {
            $stmt_delete = $conn->prepare("DELETE FROM user_library_access");
            $stmt_delete->execute();
            $stmt_delete->close();
            $stmt_insert = $conn->prepare("INSERT INTO user_library_access (user_id, library_name) VALUES (?, ?)");
            foreach ($_POST['permissions'] as $user_id => $library_names) {
                foreach ($library_names as $library_name) {
                    $stmt_insert->bind_param("is", $user_id, $library_name);
                    $stmt_insert->execute();
                }
            }
            $stmt_insert->close();
        }
        $conn->commit();
        $success_msg = "All user permissions updated successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $errors[] = "Failed to update permissions: " . $e->getMessage();
    }
}
$sql = "SELECT id, username, max_allowed_rating FROM users WHERE role = 'user'";
$result = $conn->query($sql);
if ($result) { $users = $result->fetch_all(MYSQLI_ASSOC); }
if (file_exists($config_file_path)) {
    $config = json_decode(file_get_contents($config_file_path), true);
    $all_libraries = $config['libraries'] ?? [];
    $libraries = array_filter($all_libraries, function($lib) { return !$lib['public']; });
} else { $errors[] = "Configuration file not found."; }
$current_permissions = [];
$stmt = $conn->prepare("SELECT user_id, library_name FROM user_library_access");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $current_permissions[$row['user_id']][] = $row['library_name'];
}
$stmt->close();
?>

<div class="admin-container">
    <h1>User Permissions</h1>
    <p>Manage library access and content ratings for all users.</p>
    <?php if (!empty($errors)): ?><div class="error-box"><?php foreach ($errors as $error) { echo "<p>$error</p>"; } ?></div><?php endif; ?>
    <?php if ($success_msg): ?><div class="success-box"><?php echo $success_msg; ?></div><?php endif; ?>

    <?php if (empty($users)): ?>
        <div class="card"><p>No non-admin users found.</p></div>
    <?php else: ?>
        <form action="admin_permissions.php" method="post">
            <?php foreach ($users as $user): ?>
                <div class="card user-permission-card">
                    <h3><?php echo htmlspecialchars($user['username']); ?></h3>
                    <div class="permission-section">
                        <h4>Content Rating</h4>
                        <p>Set the maximum rating this user can view.</p>
                        <select name="ratings[<?php echo $user['id']; ?>]">
                            <?php foreach ($rating_hierarchy as $rating => $level): ?>
                                <option value="<?php echo $rating; ?>" <?php echo ($user['max_allowed_rating'] === $rating) ? 'selected' : ''; ?>><?php echo $rating; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="permission-section">
                        <h4>Library Access</h4>
                        <p>Select which private libraries this user can access.</p>
                        <?php if (empty($libraries)): ?>
                             <p><em>No private libraries have been created yet.</em></p>
                        <?php else: ?>
                            <?php foreach ($libraries as $lib): ?>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="permissions[<?php echo $user['id']; ?>][]" value="<?php echo htmlspecialchars($lib['name']); ?>" <?php echo (isset($current_permissions[$user['id']]) && in_array($lib['name'], $current_permissions[$user['id']])) ? 'checked' : ''; ?>>
                                    <?php echo htmlspecialchars($lib['name']); ?>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="form-actions">
                <button type="submit" class="btn-primary">Save All Permissions</button>
            </div>
        </form>
    <?php endif; ?>
</div>

</main>
</body>
</html>
