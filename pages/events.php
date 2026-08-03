<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$events = [];

try {
    $pdo = Database::connect();
    $eventsStmt = $pdo->query('SELECT id, title, description, event_date, media_type, media_filename FROM events ORDER BY event_date DESC, id DESC');
    $events = $eventsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $events = [];
}
?>

<main class="container py-5">
    <section class="text-center mb-5 text-surface events-hero rounded-4 p-4 p-lg-5 border-0 shadow-sm">
        <p class="text-uppercase text-accent mb-2 fw-bold">Upcoming Events</p>
        <h1 class="section-heading fw-bold">Family Celebrations & Gatherings</h1>
        <p class="mx-auto lead section-description" style="max-width:720px;">See what’s next for the family, from reunions to special milestones and community events.</p>
    </section>

    <div class="row g-4">
        <?php if (empty($events)): ?>
            <div class="col-12">
                <div class="alert alert-info rounded-4 mb-0">No events have been added yet. Admins can publish one from the dashboard.</div>
            </div>
        <?php else: ?>
            <?php foreach ($events as $event): ?>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100 event-card">
                        <?php if (!empty($event['media_filename'])): ?>
                            <?php if (($event['media_type'] ?? 'photo') === 'video'): ?>
                                <video class="card-img-top" controls src="<?php echo htmlspecialchars(rtrim(BASE_URL, '/') . '/assets/uploads/events/' . $event['media_filename']); ?>"></video>
                            <?php elseif (($event['media_type'] ?? 'photo') === 'audio'): ?>
                                <div class="p-3 bg-light">
                                    <audio class="w-100" controls src="<?php echo htmlspecialchars(rtrim(BASE_URL, '/') . '/assets/uploads/events/' . $event['media_filename']); ?>"></audio>
                                </div>
                            <?php else: ?>
                                <img src="<?php echo htmlspecialchars(rtrim(BASE_URL, '/') . '/assets/uploads/events/' . $event['media_filename']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($event['title']); ?>">
                            <?php endif; ?>
                        <?php endif; ?>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2 flex-wrap">
                                <h3 class="h5 mb-0"><?php echo htmlspecialchars($event['title']); ?></h3>
                                <span class="badge bg-warning text-dark rounded-pill"><?php echo htmlspecialchars(ucfirst($event['media_type'] ?? 'photo')); ?></span>
                            </div>
                            <p class="text-muted mb-2"><?php echo htmlspecialchars(date('l, F j', strtotime($event['event_date']))); ?></p>
                            <?php if (!empty($event['description'])): ?>
                                <p><?php echo htmlspecialchars($event['description']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<?php
require_once __DIR__ . '/../includes/footer.php';
require_once __DIR__ . '/../includes/scripts.php';
?>