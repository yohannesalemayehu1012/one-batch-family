<?php
function dd($value)
{
    echo '<pre>' . print_r($value, true) . '</pre>';
    exit;
}

function redirect(string $path)
{
    header('Location: ' . $path);
    exit;
}

function flash(string $key, $message = null)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if ($message === null) {
        $value = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $value;
    }

    $_SESSION['flash'][$key] = $message;
}

function old(string $key, $default = '')
{
    if (isset($_POST[$key])) {
        return htmlspecialchars($_POST[$key]);
    }
    return $default;
}

function asset(string $path)
{
    return $path;
}

function media_url(string $filename, string $type): string
{
    if (filter_var($filename, FILTER_VALIDATE_URL)) {
        return $filename;
    }

    $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/' : '/';
    $folder = match ($type) {
        'photo' => 'photos',
        'video' => 'videos',
        'audio' => 'audio',
        default => '',
    };

    $relativePath = $folder !== '' ? 'assets/uploads/memories/' . $folder . '/' . $filename : 'assets/uploads/memories/' . $filename;

    return $baseUrl . $relativePath;
}

function member_photo_url(string $filename): string
{
    if (filter_var($filename, FILTER_VALIDATE_URL)) {
        return $filename;
    }

    $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/' : '/';
    return $baseUrl . 'assets/uploads/profile/' . ltrim($filename, '/');
}
