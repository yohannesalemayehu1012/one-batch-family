<?php
$baseUrl = rtrim(BASE_URL, '/') . '/';
$requestUri = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');

$sections = [
    ['label' => 'Admin', 'url' => '#control-center', 'key' => 'admin'],
    ['label' => 'Events', 'url' => '#events', 'key' => 'events'],
    ['label' => 'Gallery', 'url' => '#gallery', 'key' => 'gallery'],
    ['label' => 'Members', 'url' => '#members', 'key' => 'members'],
    ['label' => 'Memories', 'url' => '#memories', 'key' => 'memories'],
    ['label' => 'Prayers', 'url' => '#prayers', 'key' => 'prayers'],
    ['label' => 'Settings', 'url' => '#settings', 'key' => 'settings'],
    ['label' => 'Users', 'url' => '#users', 'key' => 'users'],
];

function is_admin_active(string $requestUri, string $key): bool
{
    $adminBase = rtrim(BASE_URL, '/');

    if ($key === 'admin') {
        return str_contains($requestUri, $adminBase . '/admin/index.php') || $requestUri === $adminBase . '/admin/' || $requestUri === $adminBase . '/admin';
    }

    return false;
}
?>
<div class="admin-sidebar-card rounded-4 shadow-sm mb-4">
    <div class="p-3 d-flex flex-column flex-lg-row flex-wrap gap-2">
        <?php foreach ($sections as $section): ?>
            <a href="<?php echo htmlspecialchars($section['url']); ?>"
                class="admin-menu-link <?php echo is_admin_active($requestUri, $section['key']) ? 'active' : ''; ?>"
                data-admin-target="<?php echo htmlspecialchars(ltrim($section['url'], '#')); ?>">
                <span><?php echo htmlspecialchars($section['label']); ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</div>