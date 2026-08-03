<?php
function upload_file(array $file, string $destinationDir, array $allowedTypes, int $maxSize = 5242880): ?string
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    if ($file['size'] > $maxSize) {
        return null;
    }

    $fileInfo = pathinfo($file['name']);
    $extension = strtolower($fileInfo['extension'] ?? '');
    if (!in_array($extension, $allowedTypes, true)) {
        return null;
    }

    $filename = uniqid('upload_', true) . '.' . $extension;
    $destination = rtrim($destinationDir, '/\\') . DIRECTORY_SEPARATOR . $filename;

    if (!is_dir($destinationDir)) {
        mkdir($destinationDir, 0755, true);
    }

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return $filename;
    }

    return null;
}
