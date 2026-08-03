<?php
ob_start();
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$success = flash('success');
$error = '';
$prayerRequest = '';

try {
    $pdo = Database::connect();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $prayerRequest = trim($_POST['prayer_request'] ?? '');

        if ($prayerRequest === '') {
            $error = 'Please enter your prayer request.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO prayer_requests (name, request, status, created_at) VALUES (:name, :request, :status, NOW())');
            $stmt->execute([
                'name' => 'Anonymous',
                'request' => $prayerRequest,
                'status' => 'accepted',
            ]);

            flash('success', 'Your prayer request has been added and is now visible to the family.');
            redirect(BASE_URL . 'pages/prayer.php');
        }
    }

    $today = date('Y-m-d');
    $verseStmt = $pdo->prepare('SELECT verse_text, reference FROM daily_verses WHERE verse_date = :verse_date ORDER BY created_at DESC LIMIT 1');
    $verseStmt->execute(['verse_date' => $today]);
    $verseOfDay = $verseStmt->fetch(PDO::FETCH_ASSOC);

    if (!$verseOfDay) {
        $latestVerseStmt = $pdo->query('SELECT verse_text, reference FROM daily_verses ORDER BY verse_date DESC, created_at DESC LIMIT 1');
        $verseOfDay = $latestVerseStmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$verseOfDay) {
        $verseOfDay = [
            'verse_text' => 'For where two or three are gathered in my name, there am I among them.',
            'reference' => 'Matthew 18:20',
        ];
    }

    $requestsStmt = $pdo->prepare('SELECT request, created_at FROM prayer_requests WHERE status = :status ORDER BY created_at DESC');
    $requestsStmt->execute(['status' => 'accepted']);
    $acceptedPrayerRequests = $requestsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Unable to load the prayer page right now. Please try again later.';
    $verseOfDay = [
        'verse_text' => 'For where two or three are gathered in my name, there am I among them.',
        'reference' => 'Matthew 18:20',
    ];
    $acceptedPrayerRequests = [];
}
?>

<main class="container py-5">
    <section class="prayer-hero rounded-4 p-5 mb-5 position-relative overflow-hidden text-white">
        <div class="hero-glow"></div>
        <div class="row align-items-center">
            <div class="col-lg-7">
                <p class="text-uppercase text-white-50 mb-3 small fw-bold">Prayer & encouragement</p>
                <h1 class="display-5 fw-bold mb-3">Lift every prayer, together.</h1>
                <p class="lead mb-4 section-description" style="opacity:.92; max-width:680px;">Share your needs, support family requests, and receive a fresh Scripture encouragement each day. This page is your space for faith, healing, and unity.</p>
                <div class="d-flex flex-column flex-sm-row gap-2 gap-sm-3">
                    <span class="badge bg-warning text-dark rounded-pill px-3 py-2">Daily verse</span>
                    <span class="badge bg-white text-dark rounded-pill px-3 py-2">Family requests</span>
                    <span class="badge bg-white text-dark rounded-pill px-3 py-2">Shared support</span>
                </div>
            </div>
            <div class="col-lg-5 text-lg-end mt-4 mt-lg-0">
                <div class="prayer-hero-panel p-4 rounded-4 bg-white bg-opacity-10 border border-white border-opacity-20 text-start text-white">
                    <h2 class="h5 mb-2">How it works</h2>
                    <ul class="list-unstyled mb-0 prayer-hero-list">
                        <li>1. Add your prayer request below.</li>
                        <li>2. Check requests others have shared.</li>
                        <li>3. Pray daily with the family’s verse.</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <?php if ($success): ?>
        <div class="alert alert-success rounded-4 shadow-sm mb-4"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger rounded-4 shadow-sm mb-4"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="row g-4 align-items-stretch">
        <div class="col-lg-5">
            <div class="card prayer-card shadow-sm h-100 border-0">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between mb-4">
                        <div>
                            <h2 class="h5 mb-1">Current Requests</h2>
                            <p class="text-muted mb-0">Prayer needs shared by the family.</p>
                        </div>
                        <span class="badge bg-primary">Active</span>
                    </div>

                    <?php if (empty($acceptedPrayerRequests)): ?>
                        <div class="request-item rounded-4 p-4">
                            <p class="mb-0 text-muted">No prayer requests have been shared yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($acceptedPrayerRequests as $acceptedPrayer): ?>
                            <div class="request-item rounded-4 p-4 mb-3">
                                <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap mb-2">
                                    <h3 class="h6 mb-0">Family Prayer Request</h3>
                                    <span class="badge bg-warning text-dark rounded-pill">Accepted</span>
                                </div>
                                <p class="mb-2 text-muted"><?php echo htmlspecialchars($acceptedPrayer['request']); ?></p>
                                <span class="badge prayer-status bg-soft-primary text-primary"><?php echo htmlspecialchars(date('M d, Y', strtotime($acceptedPrayer['created_at']))); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card prayer-card shadow-sm h-100 border-0">
                <div class="card-body">
                    <div class="verse-card mb-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h2 class="h5 mb-2">Verse of the Day</h2>
                                <p class="text-muted mb-0">Automatically refreshed every 24 hours.</p>
                            </div>
                            <span class="badge bg-primary px-3 py-2">Daily</span>
                        </div>
                        <p class="verse-text mb-3">"<?php echo htmlspecialchars($verseOfDay['verse_text']); ?>"</p>
                        <p class="verse-source mb-0"><?php echo htmlspecialchars($verseOfDay['reference']); ?></p>
                    </div>

                    <h2 class="h5 mb-4">Submit a Prayer Request</h2>
                    <form class="prayer-form" method="post">
                        <div class="mb-3">
                            <label for="prayer_request" class="form-label">Prayer Request</label>
                            <textarea class="form-control" id="prayer_request" name="prayer_request" rows="5" placeholder="Share your prayer need"><?php echo htmlspecialchars($prayerRequest); ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill">Submit Request</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
require_once __DIR__ . '/../includes/footer.php';
require_once __DIR__ . '/../includes/scripts.php';
?>