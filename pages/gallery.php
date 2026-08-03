<?php
require_once __DIR__ . '/../helpers/session.php';
require_once __DIR__ . '/../helpers/functions.php';

if (!is_logged_in()) {
    redirect(BASE_URL . 'auth/login.php');
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
require_once __DIR__ . '/../models/Media.php';

$selectedType = $_GET['media_type'] ?? 'all';
$selectedYear = isset($_GET['year']) ? (int) $_GET['year'] : 0;
$searchQuery = trim($_GET['q'] ?? '');
$mediaItems = Media::getAll();
$galleryYearOptions = [2023, 2024, 2025, 2026, 2027];

if (!empty($mediaItems)) {
    foreach ($mediaItems as $item) {
        $year = (int) ($item['year'] ?? date('Y'));
        if (!in_array($year, $galleryYearOptions, true)) {
            $galleryYearOptions[] = $year;
        }
    }
}

sort($galleryYearOptions);

if ($selectedType !== 'all') {
    $mediaItems = array_values(array_filter($mediaItems, static fn($item): bool => ($item['type'] ?? 'photo') === $selectedType));
}

if ($selectedYear > 0) {
    $mediaItems = array_values(array_filter($mediaItems, static fn($item): bool => (int) ($item['year'] ?? 0) === $selectedYear));
}

if ($searchQuery !== '') {
    $searchTerm = strtolower($searchQuery);
    $mediaItems = array_values(array_filter($mediaItems, static function ($item) use ($searchTerm): bool {
        $haystack = strtolower(trim((($item['title'] ?? '') . ' ' . ($item['year'] ?? '') . ' ' . ($item['type'] ?? '') . ' ' . ($item['uploader_name'] ?? '') . ' ' . ($item['filename'] ?? ''))));
        return strpos($haystack, $searchTerm) !== false;
    }));
}

$groupedGalleryItems = [];
foreach ($mediaItems as $item) {
    $year = (int) ($item['year'] ?? date('Y'));
    $type = $item['type'] ?? 'photo';
    $groupedGalleryItems[$year][$type][] = $item;
}

krsort($groupedGalleryItems);
?>

<main class="container py-5">
    <section class="gallery-hero rounded-4 p-4 p-lg-5 mb-5 text-white shadow-lg" style="background: linear-gradient(135deg, rgba(15, 23, 42, 0.95), rgba(20, 184, 166, 0.88));">
        <div class="row align-items-center gy-4">
            <div class="col-lg-8">
                <p class="text-uppercase text-white-50 mb-2 letter-spacing">Family Gallery</p>
                <h1 class="display-5 fw-bold mb-3">Browse the Shared Timeline</h1>
                <p class="lead mb-0">Explore photos, videos, and audio memories by year so every family moment stays easy to revisit.</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <div class="gallery-hero-card rounded-4 p-4 bg-white text-dark shadow-sm">
                    <h2 class="h5 fw-bold mb-3">Quick Snapshot</h2>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Photos</span>
                        <strong><?php echo count(array_filter($mediaItems, fn($item) => ($item['type'] ?? 'photo') === 'photo')); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Videos</span>
                        <strong><?php echo count(array_filter($mediaItems, fn($item) => ($item['type'] ?? 'photo') === 'video')); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Audio</span>
                        <strong><?php echo count(array_filter($mediaItems, fn($item) => ($item['type'] ?? 'photo') === 'audio')); ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php if (!empty($groupedGalleryItems)): ?>
        <div class="mb-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h2 class="h4 mb-1">Family Timeline</h2>
                    <p class="text-muted mb-0">Browse the shared family uploads arranged by year and media type.</p>
                </div>
                <form method="get" class="gallery-filter-form d-flex gap-2 align-items-center flex-wrap" id="gallery-filter-form">
                    <input type="search" name="q" id="gallery-search" class="form-control form-control-sm border-secondary" placeholder="Search title, year, type..." value="<?php echo htmlspecialchars($searchQuery); ?>" oninput="filterGalleryCards()">
                    <button type="submit" class="btn btn-sm btn-primary">Search</button>
                    <select name="media_type" class="form-select form-select-sm border-secondary" aria-label="Filter by type" onchange="document.getElementById('gallery-filter-form').submit()">
                        <option value="all" <?php echo $selectedType === 'all' ? 'selected' : ''; ?>>All types</option>
                        <option value="photo" <?php echo $selectedType === 'photo' ? 'selected' : ''; ?>>Photos</option>
                        <option value="video" <?php echo $selectedType === 'video' ? 'selected' : ''; ?>>Videos</option>
                        <option value="audio" <?php echo $selectedType === 'audio' ? 'selected' : ''; ?>>Audio</option>
                    </select>
                    <select name="year" class="form-select form-select-sm border-secondary" aria-label="Filter by year" onchange="document.getElementById('gallery-filter-form').submit()">
                        <option value="0" <?php echo $selectedYear === 0 ? 'selected' : ''; ?>>All years</option>
                        <?php foreach (array_reverse($galleryYearOptions) as $year): ?>
                            <option value="<?php echo $year; ?>" <?php echo $selectedYear === (int) $year ? 'selected' : ''; ?>><?php echo $year; ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </div>

        <?php foreach ($groupedGalleryItems as $year => $itemsByType): ?>
            <div class="mb-5 gallery-year-section">
                <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                    <div>
                        <h3 class="h4 mb-1">Year <?php echo htmlspecialchars((string) $year); ?></h3>
                        <p class="text-muted mb-0">Separated by photo, video, and audio memories.</p>
                    </div>
                    <span class="badge bg-dark rounded-pill px-3 py-2"><?php echo htmlspecialchars((string) $year); ?></span>
                </div>

                <?php foreach (['photo', 'video', 'audio'] as $type): ?>
                    <?php if (empty($itemsByType[$type])): ?>
                        <?php continue; ?>
                    <?php endif; ?>

                    <div class="mb-4">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <h4 class="h5 mb-0"><?php echo htmlspecialchars(ucfirst($type)); ?>s</h4>
                            <span class="badge rounded-pill <?php echo $type === 'photo' ? 'bg-primary' : ($type === 'video' ? 'bg-success' : 'bg-warning text-dark'); ?> px-3"><?php echo count($itemsByType[$type]); ?></span>
                        </div>

                        <div class="row g-4">
                            <?php foreach ($itemsByType[$type] as $item): ?>
                                <div class="col-md-6 col-xl-4 gallery-item-card" data-search="<?php echo htmlspecialchars(strtolower(($item['title'] ?? '') . ' ' . ($item['year'] ?? '') . ' ' . ($item['type'] ?? '') . ' ' . ($item['uploader_name'] ?? ''))); ?>">
                                    <div class="card gallery-card h-100 shadow-sm">
                                        <div class="card-body d-flex flex-column">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div>
                                                    <h5 class="card-title mb-1"><?php echo htmlspecialchars($item['title']); ?></h5>
                                                    <p class="card-text mb-1">Year: <?php echo htmlspecialchars($item['year']); ?></p>
                                                    <p class="text-muted small mb-0">Uploaded by: <?php echo htmlspecialchars($item['uploader_name'] ?? 'Unknown'); ?></p>
                                                </div>
                                                <span class="badge rounded-pill <?php echo $item['type'] === 'photo' ? 'bg-primary' : ($item['type'] === 'video' ? 'bg-success' : 'bg-warning text-dark'); ?>"><?php echo htmlspecialchars(ucfirst($item['type'])); ?></span>
                                            </div>
                                            <div class="gallery-media-frame gallery-media-frame--<?php echo htmlspecialchars($item['type'] ?? 'photo'); ?>">
                                                <?php if ($item['type'] === 'photo'): ?>
                                                    <img src="<?php echo htmlspecialchars(media_url($item['filename'], $item['type'])); ?>" class="gallery-media gallery-media--image media-orientation media-orientation--<?php echo htmlspecialchars($item['orientation'] ?? 'landscape'); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                                                <?php elseif ($item['type'] === 'video'): ?>
                                                    <video controls class="gallery-media gallery-media--video media-orientation media-orientation--<?php echo htmlspecialchars($item['orientation'] ?? 'landscape'); ?>">
                                                        <source src="<?php echo htmlspecialchars(media_url($item['filename'], $item['type'])); ?>" type="video/mp4">
                                                        Your browser does not support video playback.
                                                    </video>
                                                <?php else: ?>
                                                    <audio controls class="gallery-media gallery-media--audio media-orientation media-orientation--<?php echo htmlspecialchars($item['orientation'] ?? 'landscape'); ?>">
                                                        <source src="<?php echo htmlspecialchars(media_url($item['filename'], $item['type'])); ?>" type="audio/mpeg">
                                                        Your browser does not support audio playback.
                                                    </audio>
                                                <?php endif; ?>
                                            </div>
                                            <div class="mt-auto">
                                                <a href="<?php echo htmlspecialchars(media_url($item['filename'], $item['type'])); ?>" target="_blank" class="btn btn-outline-primary w-100 gallery-view-btn">View Media</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="alert alert-info">No media uploaded yet. Upload memories from the Memories page.</div>
    <?php endif; ?>
</main>

<script>
    function filterGalleryCards() {
        const searchField = document.getElementById('gallery-search');
        const query = (searchField ? searchField.value : '').toLowerCase().trim();
        const itemCards = document.querySelectorAll('.gallery-item-card');
        const yearSections = document.querySelectorAll('.gallery-year-section');

        itemCards.forEach((card) => {
            const text = (card.dataset.search || '').toLowerCase();
            const isMatch = query === '' || text.includes(query);
            card.classList.toggle('d-none', !isMatch);
        });

        yearSections.forEach((section) => {
            const visibleCards = section.querySelectorAll('.gallery-item-card:not(.d-none)');
            section.classList.toggle('d-none', visibleCards.length === 0);
        });
    }
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
require_once __DIR__ . '/../includes/scripts.php';
?>