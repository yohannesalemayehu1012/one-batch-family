<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
require_once __DIR__ . '/../models/Member.php';

$slug = isset($_GET['member']) ? strtolower(trim($_GET['member'])) : '';
$member = Member::findBySlug($slug);

if (!$member) {
    header('Location: members.php');
    exit;
}
?>

<main class="container py-5">
    <div class="mb-4">
        <a href="members.php" class="btn btn-outline-primary">Back to all members</a>
    </div>

    <div class="row g-4 align-items-start">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center profile-spotlight-card">
                <img src="<?php echo $member['image']; ?>" class="img-fluid rounded-4 mb-4 member-profile-image" alt="<?php echo htmlspecialchars($member['name']); ?>">
                <h1 class="h3 mb-2"><?php echo htmlspecialchars($member['name']); ?></h1>
                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill mb-3"><?php echo htmlspecialchars($member['role']); ?></span>
                <p class="text-muted mb-0"><?php echo htmlspecialchars($member['bio']); ?></p>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 profile-details-card">
                <h2 class="h4 mb-4 text-primary fw-bold">Profile Details</h2>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="p-3 rounded-4 bg-light profile-detail-item">
                            <h3 class="h6 text-uppercase text-accent mb-2">University</h3>
                            <p class="mb-0 fw-semibold"><?php echo htmlspecialchars($member['university']); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 rounded-4 bg-light profile-detail-item">
                            <h3 class="h6 text-uppercase text-accent mb-2">Department</h3>
                            <p class="mb-0 fw-semibold"><?php echo htmlspecialchars($member['department']); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 rounded-4 bg-light profile-detail-item">
                            <h3 class="h6 text-uppercase text-accent mb-2">Age</h3>
                            <p class="mb-0 fw-semibold"><?php echo htmlspecialchars($member['age']); ?> years</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 rounded-4 bg-light profile-detail-item">
                            <h3 class="h6 text-uppercase text-accent mb-2">Year</h3>
                            <p class="mb-0 fw-semibold"><?php echo htmlspecialchars($member['year']); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 rounded-4 bg-light profile-detail-item">
                            <h3 class="h6 text-uppercase text-accent mb-2">Email</h3>
                            <p class="mb-0 fw-semibold"><?php echo htmlspecialchars($member['email']); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 rounded-4 bg-light profile-detail-item">
                            <h3 class="h6 text-uppercase text-accent mb-2">Role</h3>
                            <p class="mb-0 fw-semibold"><?php echo htmlspecialchars($member['role']); ?></p>
                        </div>
                    </div>
                </div>

                <div class="mt-4 social-media-panel rounded-4 p-4">
                    <h3 class="h5 mb-3 fw-bold text-primary">Social Media</h3>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($member['socials'] as $social): ?>
                            <a href="<?php echo htmlspecialchars($social['url']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-accent rounded-pill px-3">
                                <?php echo htmlspecialchars($social['label']); ?>
                            </a>
                        <?php endforeach; ?>
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