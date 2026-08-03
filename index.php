<?php
require_once __DIR__ . '/helpers/session.php';
require_once __DIR__ . '/helpers/functions.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/models/Member.php';
require_once __DIR__ . '/models/Media.php';

$members = Member::getAll();
$allMemories = Media::getAll();
$featuredMemories = [];
foreach (['video', 'audio', 'photo'] as $type) {
    $latestTypeMemory = array_values(array_filter($allMemories, static fn($item): bool => ($item['type'] ?? 'photo') === $type))[0] ?? null;
    if ($latestTypeMemory) {
        $featuredMemories[$type] = $latestTypeMemory;
    }
}
$memoryYearSections = [2023, 2024, 2025, 2026, 2027];
$mediaWarningMessage = 'To Get Upoded Video, Audio and Image First Log In To The Website';

$memorialPhotos = [];
$memorialAudio = null;
$memorialPdf = null;
$memorialPpt = null;
$memorialName = 'Hanna Abu';
$memorialJourney = 'Hanna Abu was born in 1996 E.C. in Ada Qoshe, Munesa Woreda, to Ato Abu Boki and W/ro Ayyu Gemechu. She completed her primary and secondary education before graduating in Laboratory Technology from Chilalo College.

Despite facing many hardships, including her father\'s long illness, she remained determined and worked hard to achieve her educational goals. Sadly, while preparing for her Certificate of Competency (COC), she died by suicide on July 25, 2018 E.C., at the age of 22.

Hanna is remembered for her resilience, dedication to education, and the strength she showed throughout her life.';

try {
    $resourceDb = Database::connect();
    $resourceStmt = $resourceDb->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('memorial_photo_1_filename', 'memorial_photo_2_filename', 'memorial_photo_3_filename', 'memorial_audio_filename', 'memorial_pdf_filename', 'memorial_ppt_filename')");
    $resourceRows = $resourceStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($resourceRows as $resourceRow) {
        $resourceKey = $resourceRow['setting_key'];
        $resourceValue = $resourceRow['setting_value'];

        if (str_starts_with($resourceKey, 'memorial_photo_') && $resourceValue) {
            $memorialPhotos[] = rtrim(BASE_URL, '/') . '/assets/uploads/memories/photos/' . $resourceValue;
        }
        if ($resourceKey === 'memorial_audio_filename' && $resourceValue) {
            $memorialAudio = rtrim(BASE_URL, '/') . '/assets/uploads/memories/audio/' . $resourceValue;
        }
        if ($resourceKey === 'memorial_pdf_filename' && $resourceValue) {
            $memorialPdf = rtrim(BASE_URL, '/') . '/assets/downloads/' . $resourceValue;
        }
        if ($resourceKey === 'memorial_ppt_filename' && $resourceValue) {
            $memorialPpt = rtrim(BASE_URL, '/') . '/assets/downloads/' . $resourceValue;
        }
    }
} catch (PDOException $e) {
    $memorialPhotos = [];
    $memorialAudio = null;
    $memorialPdf = null;
    $memorialPpt = null;
}

$yearPreviewMap = [];
foreach ($memoryYearSections as $year) {
    $yearMemories = array_values(array_filter($allMemories, static fn($item): bool => (int) ($item['year'] ?? 0) === $year));
    $yearPreviewMap[$year] = [
        'photo' => array_values(array_filter($yearMemories, static fn($item): bool => ($item['type'] ?? 'photo') === 'photo'))[0] ?? null,
        'video' => array_values(array_filter($yearMemories, static fn($item): bool => ($item['type'] ?? 'photo') === 'video'))[0] ?? null,
        'audio' => array_values(array_filter($yearMemories, static fn($item): bool => ($item['type'] ?? 'photo') === 'audio'))[0] ?? null,
    ];
}
$mediaLoginUrl = BASE_URL . 'auth/login.php?redirect=' . urlencode(BASE_URL . 'pages/memories.php') . '&message=' . urlencode($mediaWarningMessage);
$mediaAccessUrl = is_logged_in() ? BASE_URL . 'pages/memories.php' : $mediaLoginUrl;
?>

<main>
    <section class="hero-section py-5 text-white">
        <div class="container-fluid px-0">
            <div class="row align-items-center g-5 justify-content-center mx-0">
                <div class="col-12">
                    <div class="hero-copy text-center text-lg-start px-4 px-lg-5">
                        <p class="text-uppercase text-secondary mb-3 letter-spacing">Welcome To</p>
                        <h1 class="display-4 fw-bold mb-4">One Batch Family</h1>
                        <p class="lead mb-4">United by love, connected by faith, stronger as one family. Explore memories, members, events, and prayer support in a warm family space.</p>
                        <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-lg-start">
                            <a href="pages/about.php" class="btn btn-primary px-4 py-3 rounded-pill">Explore More</a>
                            <a href="<?php echo htmlspecialchars($mediaAccessUrl); ?>" class="btn btn-secondary px-4 py-3 rounded-pill">Watch Video</a>
                        </div>
                    </div>
                </div>
                <div class="col-12 px-4 px-lg-5">
                    <div class="hero-card p-4 rounded-4 shadow-lg bg-white text-dark w-100">
                        <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
                            <div>
                                <h5 class="mb-1">Group Members</h5>
                                <p class="text-muted mb-0">A warm circle of family members and supporters.</p>
                            </div>
                            <span class="badge bg-accent text-dark py-2 px-3"><?php echo count($members); ?> Members</span>
                        </div>

                        <?php if (empty($members)): ?>
                            <div class="alert alert-info rounded-4">No members available yet. New members will appear here after signup.</div>
                        <?php endif; ?>

                        <div class="row row-cols-2 g-3 mb-4">
                            <?php foreach ($members as $member): ?>
                                <div class="col">
                                    <a href="pages/member.php?member=<?php echo urlencode($member['slug']); ?>" class="text-decoration-none text-dark">
                                        <div class="member-card rounded-4 p-3 text-center h-100">
                                            <img src="<?php echo $member['image']; ?>" class="member-avatar-img mb-3" alt="<?php echo htmlspecialchars($member['name']); ?>">
                                            <h6 class="mb-1 fw-semibold"><?php echo htmlspecialchars($member['name']); ?></h6>
                                            <p class="small text-secondary mb-0">Family Member</p>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="member-extra rounded-4 bg-light p-3">
                            <p class="small text-uppercase text-secondary mb-3">Featured members in motion</p>
                            <div class="member-marquee overflow-hidden">
                                <div class="member-track slide-left">
                                    <?php foreach (array_merge($members, $members) as $member): ?>
                                        <div class="member-slide">
                                            <img src="<?php echo $member['image']; ?>" class="member-avatar-img mb-2" alt="<?php echo htmlspecialchars($member['name']); ?>">
                                            <h6 class="mb-1 fw-semibold"><?php echo htmlspecialchars($member['name']); ?></h6>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="container py-5">
        <div class="memorial-section rounded-4 p-4 p-md-5">
            <div class="row g-4 align-items-start">
                <div class="col-lg-5">
                    <div class="memorial-copy">
                        <span class="badge memorial-badge rounded-pill px-3 py-2 mb-3">In Loving Memory</span>
                        <h2 class="section-heading mb-3 text-surface"><?php echo htmlspecialchars($memorialName); ?></h2>
                        <p class="section-description mb-4">Short Biography of Hanna Abu</p>
                        <div class="memorial-journey rounded-4 p-4">
                            <h3 class="h6 text-uppercase mb-3">Life Journey</h3>
                            <p class="mb-0"><?php echo htmlspecialchars($memorialJourney); ?></p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="row g-3">
                        <?php if (empty($memorialPhotos)): ?>
                            <div class="col-12">
                                <div class="alert alert-light rounded-4 border">No memorial photos have been uploaded yet. Admins can add or replace the three Hanna photo slots from the dashboard.</div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($memorialPhotos as $memorialPhoto): ?>
                                <div class="col-md-4">
                                    <div class="memorial-photo-card rounded-4 overflow-hidden h-100">
                                        <img src="<?php echo htmlspecialchars($memorialPhoto); ?>" class="img-fluid w-100 h-100" alt="<?php echo htmlspecialchars($memorialName); ?>">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php if ($memorialAudio): ?>
                            <div class="col-12">
                                <div class="memorial-audio-card rounded-4 p-3">
                                    <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-2">
                                        <h3 class="h6 mb-0">Memorial Audio</h3>
                                        <span class="badge bg-warning text-dark rounded-pill px-3 py-2">Admin Added</span>
                                    </div>
                                    <audio controls class="w-100">
                                        <source src="<?php echo htmlspecialchars($memorialAudio); ?>" type="audio/mpeg">
                                        Your browser does not support audio playback.
                                    </audio>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($memorialPdf || $memorialPpt): ?>
                            <div class="col-12">
                                <div class="memorial-resource-card rounded-4 p-3">
                                    <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
                                        <h3 class="h6 mb-0">Memorial Downloads</h3>
                                        <span class="badge bg-primary rounded-pill px-3 py-2">Admin Managed</span>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php if ($memorialPdf): ?>
                                            <a href="<?php echo htmlspecialchars($memorialPdf); ?>" class="btn btn-primary btn-sm rounded-pill" download>Download PDF</a>
                                        <?php endif; ?>
                                        <?php if ($memorialPpt): ?>
                                            <a href="<?php echo htmlspecialchars($memorialPpt); ?>" class="btn btn-secondary btn-sm rounded-pill" download>Download PowerPoint</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h2 class="section-heading text-surface">Our Family Memories</h2>
                <p class="section-description mb-0">Cherishing our journey through the years and preserving every milestone with love.</p>
            </div>
            <div>
                <select class="form-select form-select-sm border-primary rounded-pill px-3">
                    <option selected>When I Click Year</option>
                    <option>2024</option>
                    <option>2023</option>
                    <option>2022</option>
                </select>
            </div>
        </div>
        <div class="row g-4">
            <?php foreach (['video' => 'Latest Video', 'audio' => 'Latest Audio', 'photo' => 'Latest Photo'] as $type => $label): ?>
                <?php $card = $featuredMemories[$type] ?? null; ?>
                <div class="col-lg-4">
                    <div class="card h-100 shadow-sm border-0 rounded-4">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <span class="badge py-2 px-3 <?php echo $type === 'photo' ? 'bg-primary' : ($type === 'video' ? 'bg-success' : 'bg-warning text-dark'); ?>"><?php echo htmlspecialchars($label); ?></span>
                                <span class="text-muted small"><?php echo htmlspecialchars((string) ($card['year'] ?? '2024')); ?></span>
                            </div>
                            <?php if ($card): ?>
                                <h5><?php echo htmlspecialchars($card['title']); ?></h5>
                                <p class="text-muted mb-4">A family memory shared with the community.</p>
                                <div class="media-preview rounded-4 bg-light p-3 mb-3">
                                    <?php if ($type === 'photo'): ?>
                                        <img src="<?php echo htmlspecialchars(media_url($card['filename'], $card['type'])); ?>" class="img-fluid rounded" alt="<?php echo htmlspecialchars($card['title']); ?>">
                                    <?php elseif ($type === 'video'): ?>
                                        <video controls class="w-100 rounded">
                                            <source src="<?php echo htmlspecialchars(media_url($card['filename'], $card['type'])); ?>" type="video/mp4">
                                            Your browser does not support video playback.
                                        </video>
                                    <?php else: ?>
                                        <audio controls class="w-100">
                                            <source src="<?php echo htmlspecialchars(media_url($card['filename'], $card['type'])); ?>" type="audio/mpeg">
                                            Your browser does not support audio playback.
                                        </audio>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <h5><?php echo htmlspecialchars($label); ?></h5>
                                <p class="text-muted mb-4">No <?php echo htmlspecialchars($type); ?> memory has been uploaded yet.</p>
                                <div class="media-preview rounded-4 bg-light p-3 mb-3 text-center text-muted small">
                                    Reserved for a future upload.
                                </div>
                            <?php endif; ?>
                            <div class="mt-auto">
                                <a href="<?php echo htmlspecialchars($mediaAccessUrl); ?>" class="btn btn-primary w-100">View Memory</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php foreach ($memoryYearSections as $year): ?>
            <?php $yearPreviewCards = $yearPreviewMap[$year] ?? []; ?>
            <div class="pt-5">
                <h3 class="mb-4 section-heading text-surface"><?php echo htmlspecialchars((string) $year); ?> Family Memories</h3>
                <div class="row g-4">
                    <?php foreach (['photo', 'video', 'audio'] as $type): ?>
                        <?php $card = $yearPreviewCards[$type] ?? null; ?>
                        <div class="col-lg-4">
                            <div class="card h-100 shadow-sm border-0 rounded-4">
                                <div class="card-body d-flex flex-column">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <span class="badge bg-secondary py-2 px-3"><?php echo strtoupper($type === 'audio' ? 'music' : $type); ?></span>
                                        <span class="text-muted small"><?php echo htmlspecialchars((string) $year); ?></span>
                                    </div>
                                    <?php if ($card): ?>
                                        <h5><?php echo htmlspecialchars($card['title']); ?></h5>
                                        <p class="text-muted mb-4">A family memory captured for the year <?php echo htmlspecialchars((string) $year); ?>.</p>
                                        <div class="media-preview rounded-4 bg-light p-3 mb-3">
                                            <?php if ($type === 'photo'): ?>
                                                <img src="<?php echo htmlspecialchars(media_url($card['filename'], $card['type'])); ?>" class="img-fluid rounded" alt="<?php echo htmlspecialchars($card['title']); ?>">
                                            <?php elseif ($type === 'video'): ?>
                                                <video controls class="w-100 rounded">
                                                    <source src="<?php echo htmlspecialchars(media_url($card['filename'], $card['type'])); ?>" type="video/mp4">
                                                    Your browser does not support video playback.
                                                </video>
                                            <?php else: ?>
                                                <audio controls class="w-100">
                                                    <source src="<?php echo htmlspecialchars(media_url($card['filename'], $card['type'])); ?>" type="audio/mpeg">
                                                    Your browser does not support audio playback.
                                                </audio>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <h5><?php echo htmlspecialchars(ucfirst($type)); ?> reserved for the future</h5>
                                        <p class="text-muted mb-4">No <?php echo htmlspecialchars($type); ?> memory has been uploaded for <?php echo htmlspecialchars((string) $year); ?> yet.</p>
                                        <div class="media-preview rounded-4 bg-light p-3 mb-3 text-center text-muted small">
                                            Reserved for a future upload.
                                        </div>
                                    <?php endif; ?>
                                    <div class="mt-auto">
                                        <a href="<?php echo htmlspecialchars($mediaAccessUrl); ?>" class="btn btn-outline-primary w-100">Explore <?php echo htmlspecialchars((string) $year); ?></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </section>
</main>

<?php
require_once __DIR__ . '/includes/footer.php';
require_once __DIR__ . '/includes/scripts.php';
?>