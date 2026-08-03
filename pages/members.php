<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
require_once __DIR__ . '/../models/Member.php';

$members = Member::getAll();
?>

<main class="container py-5">
    <section class="rounded-4 p-4 p-lg-5 mb-5 text-white shadow-lg members-hero">
        <div class="row align-items-center g-3">
            <div class="col-lg-8">
                <p class="text-uppercase fw-bold mb-2 text-accent">Family Members</p>
                <h1 class="display-6 fw-bold mb-3">Meet Our Family</h1>
                <p class="mx-auto lead mb-0 text-white-75" style="max-width:720px;">Explore member profiles, family roles, and the people who keep the Dae Batch Family connected.</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <span class="badge bg-warning text-dark rounded-pill px-3 py-2">Profiles • Roles • Stories</span>
            </div>
        </div>
    </section>

    <div class="row g-4">
        <?php if (empty($members)): ?>
            <div class="col-12">
                <div class="alert alert-info rounded-4">No members found yet. A new member will appear here after signup.</div>
            </div>
        <?php else: ?>
            <?php foreach ($members as $member): ?>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm h-100 content-panel member-profile-card">
                        <img src="<?php echo htmlspecialchars($member['image']); ?>" class="card-img-top member-card-image img-fluid" alt="<?php echo htmlspecialchars($member['name']); ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <h3 class="h5 mb-0"><?php echo htmlspecialchars($member['name']); ?></h3>
                                <span class="badge bg-warning text-dark rounded-pill">Member</span>
                            </div>
                            <p class="text-muted mb-3"><?php echo htmlspecialchars($member['role'] ?? 'Family Member'); ?></p>
                            <p>Committed to strengthening family ties and capturing every special moment.</p>
                            <a href="member.php?member=<?php echo urlencode($member['slug']); ?>" class="btn btn-primary w-100">View Profile</a>
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