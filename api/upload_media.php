<?php
require_once __DIR__ . '/../helpers/session.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../helpers/upload.php';
require_once __DIR__ . '/../models/Media.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../pages/memories.php');
}

if (!is_logged_in()) {
    redirect('../auth/login.php');
}

$mediaType = $_POST['media_type'] ?? '';
$title = trim($_POST['title'] ?? '');
$year = trim($_POST['year'] ?? '');
$orientation = trim($_POST['orientation'] ?? 'landscape');
$file = $_FILES['media_file'] ?? null;

if (!$title || !$year || !$file) {
    flash('error', 'All fields are required.');
    redirect('../pages/memories.php');
}

$uploadedFiles = [];
if (is_array($file['name'] ?? null)) {
    foreach ($file['name'] as $index => $fileName) {
        if (($file['error'][$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            continue;
        }

        $uploadedFiles[] = [
            'name' => $fileName,
            'type' => $file['type'][$index] ?? '',
            'tmp_name' => $file['tmp_name'][$index] ?? '',
            'error' => $file['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            'size' => $file['size'][$index] ?? 0,
        ];
    }
} elseif (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
    $uploadedFiles[] = $file;
}

if (empty($uploadedFiles)) {
    flash('error', 'Please select at least one file to upload.');
    redirect('../pages/memories.php');
}

if (!in_array($orientation, ['landscape', 'portrait'], true)) {
    $orientation = 'landscape';
}

$allowedTypes = [];
$destinationDir = '';
$maxSize = 10 * 1024 * 1024;

switch ($mediaType) {
    case 'photo':
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $destinationDir = __DIR__ . '/../assets/uploads/memories/photos';
        $maxSize = 10 * 1024 * 1024;
        break;
    case 'video':
        $allowedTypes = ['mp4', 'mov', 'avi', 'mkv', 'webm'];
        $destinationDir = __DIR__ . '/../assets/uploads/memories/videos';
        $maxSize = 50 * 1024 * 1024;
        break;
    case 'audio':
        $allowedTypes = ['mp3', 'wav', 'ogg', 'm4a'];
        $destinationDir = __DIR__ . '/../assets/uploads/memories/audio';
        $maxSize = 15 * 1024 * 1024;
        break;
    default:
        flash('error', 'Invalid media type.');
        redirect('../pages/memories.php');
}

$uploadedCount = 0;
$failedCount = 0;
$uploadedFilenames = [];

foreach ($uploadedFiles as $uploadFile) {
    $filename = upload_file($uploadFile, $destinationDir, $allowedTypes, $maxSize);

    if (!$filename) {
        $failedCount++;
        continue;
    }

    $inserted = Media::create([
        'title' => $title,
        'year' => (int) $year,
        'type' => $mediaType,
        'orientation' => $orientation,
        'filename' => $filename,
        'user_id' => $_SESSION['user_id'] ?? null,
    ]);

    if (!$inserted) {
        @unlink($destinationDir . DIRECTORY_SEPARATOR . $filename);
        $failedCount++;
        continue;
    }

    $uploadedCount++;
    $uploadedFilenames[] = $filename;
}

if ($uploadedCount === 0) {
    flash('error', 'No files could be uploaded. Please use valid file types and sizes.');
    redirect('../pages/memories.php');
}

if ($failedCount > 0) {
    flash('success', ucfirst($mediaType) . ' uploaded successfully. ' . $uploadedCount . ' file(s) saved, ' . $failedCount . ' skipped due to invalid type or size.');
} else {
    flash('success', ucfirst($mediaType) . ' uploaded successfully. ' . $uploadedCount . ' file(s) saved.');
}
redirect('../pages/memories.php');
