<?php
require_once 'config.php';
if (!is_admin()) {
    header('location: dashboard.php');
    exit();
}

function read_config_safely($path, $lock_path) {
    $lock_handle = fopen($lock_path, 'w');
    if (flock($lock_handle, LOCK_EX)) {
        if (!file_exists($path)) {
            flock($lock_handle, LOCK_UN); fclose($lock_handle);
            return ['libraries' => [], 'supported_video_formats' => []];
        }
        $json_data = file_get_contents($path);
        $config = json_decode($json_data, true);
        flock($lock_handle, LOCK_UN); fclose($lock_handle);
        return $config ?: ['libraries' => [], 'supported_video_formats' => []];
    }
    fclose($lock_handle);
    return null;
}

function write_config_safely($path, $data) {
    $temp_path = $path . '.tmp.' . uniqid();
    if (file_put_contents($temp_path, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX) === false) {
        @unlink($temp_path); return false;
    }
    if (!rename($temp_path, $path)) {
        @unlink($temp_path); return false;
    }
    return true;
}

$errors = [];
$success_msg = '';
$config_file_path = __DIR__ . '/backend/config.json';
$lock_file_path = $config_file_path . '.lock';

if (isset($_GET['delete'])) {
    $lib_to_delete = urldecode($_GET['delete']);
    $config = read_config_safely($config_file_path, $lock_file_path);
    if ($config !== null) {
        $config['libraries'] = array_values(array_filter($config['libraries'], function($lib) use ($lib_to_delete) { return $lib['name'] !== $lib_to_delete; }));
        if (write_config_safely($config_file_path, $config)) {
            // Also delete the symlink if it exists
            $link_path = __DIR__ . '/backend/symlinks/' . $lib_to_delete;
            if (is_link($link_path)) {
                unlink($link_path);
            }
            header('location: admin_libraries.php?msg=deleted');
            exit();
        } else {
            $errors[] = "Failed to delete library. Could not write to config file.";
        }
    } else {
        $errors[] = "Could not acquire lock to delete library.";
    }
}

require_once 'header.php';

function normalize_windows_path($path) {
    if (preg_match('/^[A-Za-z]:[\\\/]/', $path)) {
        $drive = strtolower($path[0]);
        $rest = substr($path, 2);
        $rest = str_replace('\\', '/', $rest);
        return "/$drive/$rest";
    }
    return $path;
}

function recursive_find_directory($base, $name, $maxDepth = 4, $currentDepth = 0) {
    if ($currentDepth > $maxDepth) {
        return null;
    }

    try {
        $iterator = new DirectoryIterator($base);
    } catch (UnexpectedValueException $e) {
        return null;
    }

    foreach ($iterator as $item) {
        if ($item->isDot() || !$item->isDir()) {
            continue;
        }

        if (strcasecmp($item->getFilename(), $name) === 0) {
            return $item->getPathname();
        }

        $found = recursive_find_directory($item->getPathname(), $name, $maxDepth, $currentDepth + 1);
        if ($found) {
            return $found;
        }
    }

    return null;
}

function resolve_library_path($target) {
    $target = trim($target);
    if (empty($target)) {
        return null;
    }

    $normalized = normalize_windows_path($target);
    if (is_dir($normalized)) {
        return $normalized;
    }

    if (is_dir($target)) {
        return $target;
    }

    $folder = basename(str_replace('\\', '/', $target));
    $searchRoots = ['/c', '/d', '/e', '/f', '/g', '/h'];
    foreach ($searchRoots as $root) {
        if (!is_dir($root)) {
            continue;
        }
        $candidate = recursive_find_directory($root, $folder);
        if ($candidate !== null) {
            return $candidate;
        }
    }

    return null;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_library'])) {
        $lib_name = trim($_POST['lib_name']);
        $target = trim($_POST['target_path']);
        $is_public = isset($_POST['is_public']);
        if (empty($lib_name) || empty($target)) {
            $errors[] = "Library name and target path are required.";
        } else {
            $resolved_target = resolve_library_path($target);
            if ($resolved_target === null) {
                $errors[] = "The specified target folder could not be found. Please enter a valid absolute path or select the correct folder.";
            } else {
                $config = read_config_safely($config_file_path, $lock_file_path);
                if ($config !== null) {
                    foreach ($config['libraries'] as $lib) {
                        if ($lib['name'] === $lib_name) {
                            $errors[] = "A library with this name already exists.";
                            break;
                        }
                    }
                    if (empty($errors)) {
                        $config['libraries'][] = ['name' => $lib_name, 'path' => $resolved_target, 'public' => $is_public];
                        if (write_config_safely($config_file_path, $config)) {
                            $success_msg = "Library added successfully!";
                        } else {
                            $errors[] = "Could not write to configuration file.";
                        }
                    }
                } else {
                    $errors[] = "Could not acquire lock to add library.";
                }
            }
        }
    }
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'deleted') {
        $success_msg = "Library deleted successfully!";
    }
}

$config = read_config_safely($config_file_path, $lock_file_path);
$libraries = $config['libraries'] ?? [];
if ($config === null) {
    $errors[] = "Warning: Could not get a stable lock on the config file.";
}
?>

<div class="admin-container">
    <h1>Library Management</h1>
    <p>Configure media library sources from this page.</p>
    <?php if ($success_msg): ?><div class="success-box"><?php echo $success_msg; ?></div><?php endif; ?>
    <?php if (!empty($errors)): ?><div class="error-box"><?php foreach ($errors as $error) { echo "<p>$error</p>"; } ?></div><?php endif; ?>

    <div class="card">
        <h2>Add New Library</h2>
        <form action="admin_libraries.php" method="post">
            <label for="lib_name">Library Name</label>
            <input type="text" id="lib_name" name="lib_name" placeholder="e.g., Movies" required>
            <label for="target_path">Target Path</label>
            <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 1rem;">
                <input type="text" id="target_path" name="target_path" placeholder="e.g., C:\Users\HP\Videos" required style="flex: 1;">
                <button type="button" class="btn-action" onclick="document.getElementById('library_folder_picker').click();">Browse</button>
            </div>
            <p class="info-text">Use a full Windows absolute path. If browser folder selection only returns a folder name, the server will try to resolve it on the mounted drives.</p>
            <input type="file" id="library_folder_picker" webkitdirectory directory mozdirectory style="display:none;" />
            <label style="display: flex; align-items: center; margin-bottom: 1rem;">
                <input type="checkbox" name="is_public" style="width: auto; margin-right: 10px;">
                <span>Make this library public (visible to all users)</span>
            </label>
            <button type="submit" name="add_library">Add Library</button>
        </form>
    </div>

    <div class="card">
        <h2>Existing Libraries</h2>
        <?php if (empty($libraries)): ?>
            <p>No libraries have been configured yet.</p>
        <?php else: ?>
            <table>
                <thead><tr><th>Name</th><th>Path</th><th>Access</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($libraries as $lib): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($lib['name']); ?></td>
                        <td><?php echo htmlspecialchars($lib['path']); ?></td>
                        <td><?php echo $lib['public'] ? '<span class="badge badge-public">Public</span>' : '<span class="badge badge-private">Private</span>'; ?></td>
                        <td>
                            <a href="admin_permissions.php" class="btn-action">Manage Permissions</a>
                            <a href="admin_libraries.php?delete=<?php echo urlencode($lib['name']); ?>" class="btn-action btn-delete" onclick="return confirm('Are you sure?');">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</div>

<script>
document.getElementById('library_folder_picker').addEventListener('change', function(e) {
    if (!e.target.files.length) {
        return;
    }

    const file = e.target.files[0];
    const targetInput = document.getElementById('target_path');

    if (file.path) {
        targetInput.value = file.path;
        return;
    }

    const relativePath = file.webkitRelativePath || '';
    const folderName = relativePath.split('/')[0] || relativePath.split('\\')[0];
    if (folderName) {
        targetInput.value = folderName;
        return;
    }

    targetInput.value = file.name;
});
</script>

</main>
