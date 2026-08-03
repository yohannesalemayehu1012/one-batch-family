<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/session.php';
$isLoggedIn = is_logged_in();
$user = current_user();
$currentPage = basename($_SERVER['PHP_SELF']);
$baseUrl = rtrim(BASE_URL, '/') . '/';
$isLoginPage = $currentPage === 'login.php';
$isRegisterPage = $currentPage === 'register.php';
$navItems = [
    ['label' => 'Home', 'url' => 'index.php', 'file' => 'index.php'],
    ['label' => 'About', 'url' => 'pages/about.php', 'file' => 'about.php'],
    ['label' => 'Members', 'url' => 'pages/members.php', 'file' => 'members.php'],
    ['label' => 'Prayer', 'url' => 'pages/prayer.php', 'file' => 'prayer.php'],
    ['label' => 'Events', 'url' => 'pages/events.php', 'file' => 'events.php'],
    ['label' => 'Contact', 'url' => 'pages/contact.php', 'file' => 'contact.php'],
];

if ($isLoggedIn) {
    $navItems[] = ['label' => 'Gallery', 'url' => 'pages/gallery.php', 'file' => 'gallery.php'];
    $navItems[] = ['label' => 'Memories', 'url' => 'pages/memories.php', 'file' => 'memories.php'];
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm py-3">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="<?php echo $baseUrl; ?>index.php">
            <img src="<?php echo BASE_URL; ?>assets/images/logo/logo.svg" alt="Dae Batch Family logo" class="brand-icon me-2">
            <span class="fw-bold">DBF</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <?php foreach ($navItems as $item): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === $item['file'] ? 'active' : ''; ?>" href="<?php echo $baseUrl . $item['url']; ?>">
                            <?php echo htmlspecialchars($item['label']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="d-flex align-items-center gap-2">
                <?php if ($isLoggedIn): ?>
                    <?php $dashboardUrl = isset($user['role_id']) && (int)$user['role_id'] === 1 ? 'admin/index.php' : 'user/dashboard.php'; ?>
                    <a href="<?php echo $baseUrl . $dashboardUrl; ?>" class="btn btn-primary btn-sm">Dashboard</a>
                    <a href="<?php echo $baseUrl; ?>auth/logout.php" class="btn btn-outline-light btn-sm">Sign Out</a>
                <?php else: ?>
                    <a href="<?php echo $baseUrl; ?>auth/login.php" class="btn <?php echo $isLoginPage ? 'btn-primary active' : 'btn-outline-light'; ?> btn-sm" <?php echo $isLoginPage ? 'aria-current="page"' : ''; ?>>Sign In</a>
                    <a href="<?php echo $baseUrl; ?>auth/register.php" class="btn <?php echo $isRegisterPage ? 'btn-primary active' : 'btn-outline-light'; ?> btn-sm" <?php echo $isRegisterPage ? 'aria-current="page"' : ''; ?>>Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>