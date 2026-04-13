<?php
// Shared utility functions for MediaFlow

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

    foreach ($iterator as $fileinfo) {
        if ($fileinfo->isDot()) {
            continue;
        }

        if ($fileinfo->isDir()) {
            $fullPath = $fileinfo->getPathname();
            if (strtolower($fileinfo->getFilename()) === strtolower($name)) {
                return $fullPath;
            }

            $nested = recursive_find_directory($fullPath, $name, $maxDepth, $currentDepth + 1);
            if ($nested !== null) {
                return $nested;
            }
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
?>