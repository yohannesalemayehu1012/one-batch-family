<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../helpers/session.php';
require_once __DIR__ . '/../../helpers/functions.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
require_once __DIR__ . '/../../includes/admin_nav.php';

if (!is_logged_in()) {
    redirect(BASE_URL . 'auth/login.php');
}

$user = current_user();
if (!$user || (int)$user['role_id'] !== 1) {
    redirect(BASE_URL . 'auth/login.php');
}
?>

<main class="container py-5">
    <h1>Events</h1>
    <p>Manage events from this section.</p>
</main>

<?php
require_once __DIR__ . '/../../includes/footer.php';
require_once __DIR__ . '/../../includes/scripts.php';
?>