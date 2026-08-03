<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

function current_user(): ?array
{
    if (!is_logged_in()) {
        return null;
    }

    require_once __DIR__ . '/../models/User.php';
    return User::findById((int)$_SESSION['user_id']);
}

function login_user(array $user): void
{
    $_SESSION['user_id'] = $user['id'];
}

function logout_user(): void
{
    session_unset();
    session_destroy();
}
