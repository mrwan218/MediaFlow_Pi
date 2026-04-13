<?php
require_once 'config.php';
if (!is_logged_in()) {
    http_response_code(401);
    exit('Unauthorized');
}
$file_id = $_GET['id'] ?? null;
if (!$file_id || !is_numeric($file_id)) {
    http_response_code(400);
    exit('Bad Request: Invalid file ID.');
}
$stmt = $conn->prepare("SELECT file_path, library_name FROM media_items WHERE id = ?");
$stmt->bind_param("i", $file_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    http_response_code(404);
    exit('File not found in database.');
}
$file_data = $result->fetch_assoc();
$stmt->close();
$library_name = $file_data['library_name'];
$file_path = $file_data['file_path'];

// --- Security Fix: Path Validation ---
$is_allowed = false;
$config = json_decode(file_get_contents(__DIR__ . '/backend/config.json'), true);
$target_library = null;
foreach ($config['libraries'] as $lib) {
    if ($lib['name'] === $library_name) {
        $target_library = $lib;
        if ($lib['public']) {
            $is_allowed = true;
        }
        break;
    }
}

if (!$is_allowed && $target_library) {
    $stmt = $conn->prepare("SELECT 1 FROM user_library_access WHERE user_id = ? AND library_name = ?");
    $stmt->bind_param("is", $_SESSION['user_id'], $library_name);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 1) {
        $is_allowed = true;
    }
    $stmt->close();
}

if (!$is_allowed) {
    http_response_code(403);
    exit('Forbidden: You do not have access to this file.');
}

// Verify file is within the library path to prevent directory traversal
$real_file_path = realpath($file_path);
$real_library_path = realpath($target_library['path']);

if (!$real_file_path || !$real_library_path || strpos($real_file_path, $real_library_path) !== 0) {
    http_response_code(403);
    exit('Forbidden: Invalid file path.');
}

if (!file_exists($file_path)) {
    http_response_code(404);
    exit('File not found on disk.');
}

// --- Streaming Optimization: Robust Range Handling ---
$file_size = filesize($file_path);
$file_mime = mime_content_type($file_path);
// Fallback for empty files or detection failures
if ($file_mime === 'application/x-empty' || !$file_mime) {
    $ext = pathinfo($file_path, PATHINFO_EXTENSION);
    $mimes = ['mp4' => 'video/mp4', 'mkv' => 'video/x-matroska', 'avi' => 'video/x-msvideo', 'mov' => 'video/quicktime', 'wmv' => 'video/x-ms-wmv'];
    $file_mime = $mimes[$ext] ?? 'video/mp4';
}

$etag = md5($file_path . $file_size . filemtime($file_path));

header('Content-Type: ' . $file_mime);
header('Accept-Ranges: bytes');
header('Cache-Control: no-cache');
header('ETag: "' . $etag . '"');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Range');
header('Access-Control-Expose-Headers: Content-Range, Accept-Ranges');

$start = 0;
$end = $file_size - 1;
$buffer_size = 8192; // 8KB buffer

if (isset($_SERVER['HTTP_RANGE'])) {
    $c_start = $start;
    $c_end = $end;

    list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
    if (strpos($range, ',') !== false) {
        header('HTTP/1.1 416 Requested Range Not Satisfiable');
        header("Content-Range: bytes */$file_size");
        exit;
    }
    if ($range == '-') {
        $c_start = $file_size - substr($range, 1);
    } else {
        $range = explode('-', $range);
        $c_start = $range[0];
        $c_end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $file_size - 1;
    }
    $c_end = ($c_end > $file_size - 1) ? $file_size - 1 : $c_end;
    if ($c_start > $c_end || $c_start > $file_size - 1) {
        header('HTTP/1.1 416 Requested Range Not Satisfiable');
        header("Content-Range: bytes */$file_size");
        exit;
    }
    $start = $c_start;
    $end = $c_end;
    $length = $end - $start + 1;
    header('HTTP/1.1 206 Partial Content');
    header("Content-Range: bytes $start-$end/$file_size");
    header("Content-Length: $length");
} else {
    header("Content-Length: $file_size");
}

$fp = fopen($file_path, 'rb');
fseek($fp, $start);

while (!feof($fp) && ($pos = ftell($fp)) <= $end) {
    if ($pos + $buffer_size > $end) {
        $buffer_size = $end - $pos + 1;
    }
    echo fread($fp, $buffer_size);
    flush();
    if (connection_aborted()) break;
}
fclose($fp);
exit;
?>
