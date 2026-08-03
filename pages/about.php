<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$settingsMap = [];
try {
    $pdo = Database::connect();
    $settingsStmt = $pdo->query('SELECT setting_key, setting_value FROM site_settings ORDER BY setting_key ASC');
    $settings = $settingsStmt->fetchAll();
    foreach ($settings as $setting) {
        $settingsMap[$setting['setting_key']] = $setting['setting_value'];
    }
} catch (PDOException $e) {
    $settingsMap = [];
}

$downloadTitle = $settingsMap['about_download_title'] ?? 'Dambii Ittin Bulmaataa';
$downloadSubtitle = $settingsMap['about_download_subtitle'] ?? 'Group 2014 Batch';
$downloadDescription = $settingsMap['about_download_description'] ?? 'Dambii fi qajeelcha group keenya PDF, PowerPoint yookaan txt keessatti argachuu dandeessu.';

$downloadFilesStmt = $pdo->query('SELECT id, display_name, file_type, stored_filename FROM about_download_files ORDER BY file_type ASC, display_name ASC');
$downloadFiles = $downloadFilesStmt->fetchAll();
?>

<main class="container py-5">
    <section class="rounded-4 p-5 mb-5 text-white shadow-lg about-hero">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <p class="text-uppercase fw-bold mb-3 letter-spacing text-accent">Our Family Story</p>
                <h1 class="display-5 fw-bold mb-3">One Batch Family</h1>
                <p class="lead mb-0 section-description" style="max-width:720px;">Connecting memories, prayers, members, and events in one warm space. Our goal is to preserve family history and make every moment easy to revisit.</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <div class="about-download-card rounded-4 px-4 py-4 shadow-sm border-0 text-start text-white">
                    <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                        <div>
                            <div class="fw-bold fs-5"><?php echo htmlspecialchars($downloadTitle); ?></div>
                            <div class="small text-white-50"><?php echo htmlspecialchars($downloadSubtitle); ?></div>
                        </div>
                        <div class="about-download-icon rounded-circle d-inline-flex align-items-center justify-content-center">
                            <i class="fa-solid fa-download"></i>
                        </div>
                    </div>
                    <p class="mb-3 small text-white-75"><?php echo htmlspecialchars($downloadDescription); ?></p>
                    <div class="d-flex flex-wrap gap-2">
                        <?php if (!empty($downloadFiles)): ?>
                            <?php foreach ($downloadFiles as $downloadFile): ?>
                                <a href="<?php echo rtrim(BASE_URL, '/') . '/assets/downloads/' . htmlspecialchars($downloadFile['stored_filename']); ?>" class="btn btn-light btn-sm rounded-pill px-3" download="<?php echo htmlspecialchars($downloadFile['display_name']); ?>"><?php echo htmlspecialchars(strtoupper($downloadFile['file_type'])); ?></a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="text-white-75 small">No downloadable files have been added yet.</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="row g-4 mb-5">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100 rounded-4 overflow-hidden content-panel">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle bg-warning bg-opacity-25 p-3 me-3">
                            <i class="fa-solid fa-heart text-accent"></i>
                        </div>
                        <h2 class="h5 mb-0">What We Do</h2>
                    </div>
                    <p class="text-muted">We share photos, videos, and audio memories from family milestones. We also keep a calendar of events and prayer activities to stay connected.</p>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><i class="fa-solid fa-check text-accent me-2"></i>Curate family stories and memories</li>
                        <li class="mb-2"><i class="fa-solid fa-check text-accent me-2"></i>Share events and celebrations</li>
                        <li><i class="fa-solid fa-check text-accent me-2"></i>Support prayer requests across the family</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100 rounded-4 overflow-hidden content-panel">
                <img src="https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=900&q=80" class="card-img-top" alt="Family gathering" style="height: 260px; object-fit: cover;">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                        <h2 class="h5 mb-0">A Place for Every Memory</h2>
                        <span class="badge bg-warning text-dark rounded-pill px-3">Shared Story</span>
                    </div>
                    <p class="text-dark mb-0">From first steps to major celebrations, this site keeps the family's memories organized and easy to revisit for everyone.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="feature-box p-4 rounded-4 shadow-sm bg-white h-100 border border-light">
                <h3 class="h6 text-uppercase text-secondary">Mission</h3>
                <p class="mb-0 text-muted">Preserve the family’s history while making it simple to share stories, media, and support.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-box p-4 rounded-4 shadow-sm bg-white h-100 border border-light">
                <h3 class="h6 text-uppercase text-secondary">Vision</h3>
                <p class="mb-0 text-muted">Create a trusted online family hub where members can gather, remember, and pray together.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-box p-4 rounded-4 shadow-sm bg-white h-100 border border-light">
                <h3 class="h6 text-uppercase text-secondary">Values</h3>
                <p class="mb-0 text-muted">Faith, family, unity, and celebration are at the heart of every memory and every event we share.</p>
            </div>
        </div>
    </div>
</main>

<?php
require_once __DIR__ . '/../includes/footer.php';
require_once __DIR__ . '/../includes/scripts.php';
?>