<?php
require_once __DIR__ . '/../helpers/session.php';
require_once __DIR__ . '/../helpers/functions.php';

if (!is_logged_in()) {
    redirect(BASE_URL . 'auth/login.php');
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$user = current_user();
?>

<main class="container py-5">
    <section class="overflow-hidden rounded-4 shadow-lg mb-5" style="background: linear-gradient(155deg, rgba(11,47,87,0.98), rgba(8,27,52,0.96));">
        <div class="row g-0 align-items-center">
            <div class="col-lg-7 p-5 text-white">
                <span class="badge bg-warning text-dark rounded-pill py-2 px-3 mb-3">Member Dashboard</span>
                <h1 class="display-5 fw-bold mb-3">Welcome back, <?php echo htmlspecialchars($user['fullname']); ?></h1>
                <p class="lead text-white-75 mb-4">Your family hub for memories, events, prayers, and shared moments. Keep the story alive with beautiful updates and easy access to everything that matters.</p>
                <div class="d-flex flex-column flex-sm-row gap-2">
                    <a href="<?php echo BASE_URL; ?>pages/memories.php" class="btn btn-warning btn-lg">Browse Memories</a>
                    <a href="<?php echo BASE_URL; ?>pages/events.php" class="btn btn-outline-light btn-lg">View Events</a>
                </div>
            </div>
            <div class="col-lg-5 position-relative">
                <div class="ratio ratio-4x3">
                    <img src="https://images.unsplash.com/photo-1529156069898-49953e39b3ac?auto=format&fit=crop&w=900&q=80" alt="Family memories" class="img-fluid rounded-4 object-fit-cover shadow-lg">
                </div>
            </div>
        </div>
        <div class="position-absolute top-0 end-0 opacity-25" style="width: 40%; height: 100%; background: radial-gradient(circle at top right, rgba(212,175,55,0.32), transparent 55%);"></div>
    </section>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card rounded-4 shadow-sm border-0 p-4 h-100 bg-white">
                <h2 class="h5 text-primary mb-3">Your profile</h2>
                <p class="mb-2 text-muted small text-uppercase">Name</p>
                <p class="fw-semibold mb-3"><?php echo htmlspecialchars($user['fullname']); ?></p>
                <p class="mb-2 text-muted small text-uppercase">Email</p>
                <p class="fw-semibold mb-3"><?php echo htmlspecialchars($user['email']); ?></p>
                <p class="mb-0 text-muted">Signed in as a cherished family member.</p>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card rounded-4 shadow-sm border-0 p-4 h-100 bg-white">
                <h2 class="h5 text-primary mb-3">Quick actions</h2>
                <div class="list-group list-group-flush">
                    <a href="<?php echo BASE_URL; ?>pages/memories.php" class="list-group-item list-group-item-action rounded-4 mb-2">Browse memories</a>
                    <a href="<?php echo BASE_URL; ?>pages/gallery.php" class="list-group-item list-group-item-action rounded-4 mb-2">Open gallery</a>
                    <a href="<?php echo BASE_URL; ?>pages/prayer.php" class="list-group-item list-group-item-action rounded-4 mb-2">Share a prayer</a>
                    <a href="<?php echo BASE_URL; ?>pages/events.php" class="list-group-item list-group-item-action rounded-4">See upcoming events</a>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card rounded-4 shadow-sm border-0 p-4 h-100 bg-white">
                <h2 class="h5 text-primary mb-3">Dashboard snapshot</h2>
                <div class="d-flex flex-column gap-3">
                    <div class="p-3 rounded-4 border border-primary border-opacity-10 bg-light">
                        <p class="mb-1 text-muted small text-uppercase">Memories</p>
                        <p class="mb-0 fw-semibold">Add photos, videos, or audio keepsakes.</p>
                    </div>
                    <div class="p-3 rounded-4 border border-primary border-opacity-10 bg-light">
                        <p class="mb-1 text-muted small text-uppercase">Prayers</p>
                        <p class="mb-0 fw-semibold">Submit and support family prayer requests.</p>
                    </div>
                    <div class="p-3 rounded-4 border border-primary border-opacity-10 bg-light">
                        <p class="mb-1 text-muted small text-uppercase">Events</p>
                        <p class="mb-0 fw-semibold">Stay connected with family gatherings.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
require_once __DIR__ . '/../includes/footer.php';
require_once __DIR__ . '/../includes/scripts.php';
?>