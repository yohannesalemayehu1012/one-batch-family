<?php
require_once __DIR__ . '/../helpers/session.php';
require_once __DIR__ . '/../helpers/functions.php';

if (!is_logged_in()) {
    redirect(BASE_URL . 'auth/login.php');
}

require_once __DIR__ . '/../models/Media.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$success = flash('success');
$error = flash('error');
$currentUserId = (int) ($_SESSION['user_id'] ?? 0);
$selectedType = $_GET['media_type'] ?? 'all';
$selectedYear = isset($_GET['year']) ? (int) $_GET['year'] : 0;
$memoryItems = [];

if ($currentUserId > 0 && method_exists(Media::class, 'getByUserId')) {
    $memoryItems = Media::getByUserId($currentUserId);
} elseif (method_exists(Media::class, 'getAll')) {
    $memoryItems = Media::getAll();
}

$memoryYearOptions = [2023, 2024, 2025, 2026, 2027];

if (!empty($memoryItems)) {
    foreach ($memoryItems as $item) {
        $year = (int) ($item['year'] ?? date('Y'));
        if (!in_array($year, $memoryYearOptions, true)) {
            $memoryYearOptions[] = $year;
        }
    }
}

sort($memoryYearOptions);

if ($selectedType !== 'all') {
    $memoryItems = array_values(array_filter($memoryItems, static fn($item): bool => ($item['type'] ?? 'photo') === $selectedType));
}

if ($selectedYear > 0) {
    $memoryItems = array_values(array_filter($memoryItems, static fn($item): bool => (int) ($item['year'] ?? 0) === $selectedYear));
}

$groupedMemoryItems = [];
foreach ($memoryItems as $item) {
    $year = (int) ($item['year'] ?? date('Y'));
    $type = $item['type'] ?? 'photo';
    $groupedMemoryItems[$year][$type][] = $item;
}

krsort($groupedMemoryItems);
?>

<main class="container py-5">
    <section class="memory-hero rounded-4 p-4 p-lg-5 mb-5 text-white shadow-lg" style="background: linear-gradient(135deg, rgba(15, 23, 42, 0.95), rgba(37, 99, 235, 0.9));">
        <div class="row align-items-center gy-4">
            <div class="col-lg-8">
                <p class="text-uppercase text-muted mb-2 letter-spacing">Family Memories</p>
                <h1 class="display-5 fw-bold mb-3">Capture Every Moment</h1>
                <p class="lead mb-0 section-description">Upload photos, videos, and audio memories to preserve your family timeline with a beautiful, easy-to-navigate collection.</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <div class="memory-hero-card rounded-4 p-4 bg-white text-dark shadow-sm">
                    <h2 class="h5 fw-bold mb-3">Quick Stats</h2>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Photos</span>
                        <strong><?php echo count(array_filter($memoryItems, fn($item) => $item['type'] === 'photo')); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Videos</span>
                        <strong><?php echo count(array_filter($memoryItems, fn($item) => $item['type'] === 'video')); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Audios</span>
                        <strong><?php echo count(array_filter($memoryItems, fn($item) => $item['type'] === 'audio')); ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php if ($success): ?>
        <div class="alert alert-success rounded-4 shadow-sm"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger rounded-4 shadow-sm"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="row g-4 mb-5">
        <div class="col-lg-4">
            <div class="upload-card rounded-4 shadow-sm h-100 p-4 bg-white">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h2 class="h5 fw-bold mb-1">Upload Photo</h2>
                        <p class="text-muted mb-0">Add favorite family photos by year.</p>
                    </div>
                    <span class="badge bg-primary text-white">Photo</span>
                </div>
                <form action="../api/upload_media.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="media_type" value="photo">
                    <div class="mb-3">
                        <label for="photo_title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="photo_title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="photo_year" class="form-label">Year</label>
                        <select class="form-select" id="photo_year" name="year" required>
                            <?php foreach ($memoryYearOptions as $year): ?>
                                <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="photo_orientation" class="form-label">Orientation</label>
                        <select class="form-select" id="photo_orientation" name="orientation" required>
                            <option value="landscape">Landscape</option>
                            <option value="portrait">Portrait</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="photo_file" class="form-label">Image(s)</label>
                        <input type="file" class="form-control" id="photo_file" name="media_file[]" accept="image/*" multiple required>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Upload Photo(s)</button>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="upload-card rounded-4 shadow-sm h-100 p-4 bg-white">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h2 class="h5 fw-bold mb-1">Upload Video</h2>
                        <p class="text-muted mb-0">Share video memories from family events.</p>
                    </div>
                    <span class="badge bg-success text-white">Video</span>
                </div>
                <form action="../api/upload_media.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="media_type" value="video">
                    <div class="mb-3">
                        <label for="video_title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="video_title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="video_year" class="form-label">Year</label>
                        <select class="form-select" id="video_year" name="year" required>
                            <?php foreach ($memoryYearOptions as $year): ?>
                                <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="video_orientation" class="form-label">Orientation</label>
                        <select class="form-select" id="video_orientation" name="orientation" required>
                            <option value="landscape">Landscape</option>
                            <option value="portrait">Portrait</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="video_file" class="form-label">Video(s)</label>
                        <input type="file" class="form-control" id="video_file" name="media_file[]" accept="video/*" multiple required>
                    </div>
                    <button class="btn btn-success w-100" type="submit">Upload Video(s)</button>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="upload-card rounded-4 shadow-sm h-100 p-4 bg-white">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h2 class="h5 fw-bold mb-1">Upload Audio</h2>
                        <p class="text-muted mb-0">Add audio messages, songs, and stories.</p>
                    </div>
                    <span class="badge bg-warning text-dark">Audio</span>
                </div>
                <form action="../api/upload_media.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="media_type" value="audio">
                    <div class="mb-3">
                        <label for="audio_title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="audio_title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="audio_year" class="form-label">Year</label>
                        <select class="form-select" id="audio_year" name="year" required>
                            <?php foreach ($memoryYearOptions as $year): ?>
                                <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="audio_orientation" class="form-label">Orientation</label>
                        <select class="form-select" id="audio_orientation" name="orientation" required>
                            <option value="landscape">Landscape</option>
                            <option value="portrait">Portrait</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="audio_file" class="form-label">Audio(s)</label>
                        <input type="file" class="form-control" id="audio_file" name="media_file[]" accept="audio/*" multiple required>
                    </div>
                    <button class="btn btn-warning w-100" type="submit">Upload Audio(s)</button>
                </form>
            </div>
        </div>
    </div>

    <?php if (!empty($groupedMemoryItems)): ?>
        <div class="mb-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h2 class="h4 mb-1">Family Timeline</h2>
                    <p class="text-muted mb-0">Browse your uploaded memories arranged by year and media type.</p>
                </div>
                <form method="get" class="d-flex gap-2 align-items-center flex-wrap" id="memory-filter-form">
                    <select name="media_type" class="form-select form-select-sm border-secondary" aria-label="Filter by type" onchange="document.getElementById('memory-filter-form').submit()">
                        <option value="all" <?php echo $selectedType === 'all' ? 'selected' : ''; ?>>All types</option>
                        <option value="photo" <?php echo $selectedType === 'photo' ? 'selected' : ''; ?>>Photos</option>
                        <option value="video" <?php echo $selectedType === 'video' ? 'selected' : ''; ?>>Videos</option>
                        <option value="audio" <?php echo $selectedType === 'audio' ? 'selected' : ''; ?>>Audio</option>
                    </select>
                    <select name="year" class="form-select form-select-sm border-secondary" aria-label="Filter by year" onchange="document.getElementById('memory-filter-form').submit()">
                        <option value="0" <?php echo $selectedYear === 0 ? 'selected' : ''; ?>>All years</option>
                        <?php foreach (array_reverse($memoryYearOptions) as $year): ?>
                            <option value="<?php echo $year; ?>" <?php echo $selectedYear === (int) $year ? 'selected' : ''; ?>><?php echo $year; ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </div>

        <?php foreach ($groupedMemoryItems as $year => $itemsByType): ?>
            <div class="mb-5 timeline-year-wrap">
                <div class="timeline-year-card rounded-4 p-3 p-md-4 mb-3 shadow-sm">
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                        <div>
                            <h3 class="h4 mb-1">Year <?php echo htmlspecialchars((string) $year); ?></h3>
                            <p class="text-muted mb-0">Separated by photo, video, and audio memories.</p>
                        </div>
                        <span class="badge timeline-year-badge rounded-pill px-3 py-2"><?php echo htmlspecialchars((string) $year); ?></span>
                    </div>
                </div>

                <?php foreach (['photo', 'video', 'audio'] as $type): ?>
                    <?php if (empty($itemsByType[$type])): ?>
                        <?php continue; ?>
                    <?php endif; ?>

                    <div class="mb-4 timeline-type-section timeline-type-section--<?php echo htmlspecialchars($type); ?> rounded-4 p-3 p-md-4">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <h4 class="h5 mb-0"><?php echo htmlspecialchars(ucfirst($type)); ?>s</h4>
                            <span class="badge rounded-pill <?php echo $type === 'photo' ? 'bg-primary' : ($type === 'video' ? 'bg-success' : 'bg-warning text-dark'); ?> px-3"><?php echo count($itemsByType[$type]); ?></span>
                        </div>

                        <div class="row g-4">
                            <?php foreach ($itemsByType[$type] as $item): ?>
                                <div class="col-md-6 col-xl-4">
                                    <div class="card memory-card rounded-4 shadow-sm h-100 overflow-hidden">
                                        <div class="card-body p-4 d-flex flex-column">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div>
                                                    <h5 class="mb-1 fw-bold"><?php echo htmlspecialchars($item['title']); ?></h5>
                                                    <p class="text-muted mb-1"><?php echo htmlspecialchars($item['year']); ?></p>
                                                </div>
                                                <span class="badge memory-badge <?php echo $item['type'] === 'photo' ? 'badge-photo' : ($item['type'] === 'video' ? 'badge-video' : 'badge-audio'); ?>"><?php echo htmlspecialchars(ucfirst($item['type'])); ?></span>
                                            </div>
                                            <div class="media-preview overflow-hidden rounded-4 mb-4">
                                                <?php if ($item['type'] === 'photo'): ?>
                                                    <img src="<?php echo htmlspecialchars(media_url($item['filename'], $item['type'])); ?>" class="media-orientation media-orientation--<?php echo htmlspecialchars($item['orientation'] ?? 'landscape'); ?> img-fluid w-100" alt="<?php echo htmlspecialchars($item['title']); ?>">
                                                <?php elseif ($item['type'] === 'video'): ?>
                                                    <video controls class="media-orientation media-orientation--<?php echo htmlspecialchars($item['orientation'] ?? 'landscape'); ?> w-100 rounded-4">
                                                        <source src="<?php echo htmlspecialchars(media_url($item['filename'], $item['type'])); ?>" type="video/mp4">
                                                        Your browser does not support video playback.
                                                    </video>
                                                <?php else: ?>
                                                    <div class="media-preview-audio p-4 bg-light rounded-4 w-100">
                                                        <audio controls class="w-100">
                                                            <source src="<?php echo htmlspecialchars(media_url($item['filename'], $item['type'])); ?>" type="audio/mpeg">
                                                            Your browser does not support audio playback.
                                                        </audio>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="mt-auto">
                                                <p class="text-muted small mb-1">Uploaded by: <?php echo htmlspecialchars($item['uploader_name'] ?? 'Unknown'); ?></p>
                                                <p class="text-muted small mb-2">Uploaded in <?php echo htmlspecialchars($item['year']); ?></p>
                                                <a href="<?php echo htmlspecialchars(media_url($item['filename'], $item['type'])); ?>" target="_blank" class="btn btn-outline-primary w-100">View Media</a>
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
        <div class="alert alert-info rounded-4">No memories found yet. Start by uploading a photo, video, or audio memory above.</div>
    <?php endif; ?>
</main>

<?php
require_once __DIR__ . '/../includes/footer.php';
require_once __DIR__ . '/../includes/scripts.php';
?>