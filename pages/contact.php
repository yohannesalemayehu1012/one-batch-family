<?php
ob_start();
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$success = flash('success');
$error = '';
$contactName = '';
$contactEmail = '';
$contactMessage = '';

$pdo = Database::connect();
$settingsStmt = $pdo->query('SELECT setting_key, setting_value FROM site_settings ORDER BY setting_key ASC');
$settings = $settingsStmt->fetchAll();
$settingsMap = [];
foreach ($settings as $setting) {
    $settingsMap[$setting['setting_key']] = $setting['setting_value'];
}

$leadersStmt = $pdo->query('SELECT id, name, role, telegram, phone, photo FROM contact_leaders ORDER BY id ASC');
$contactLeaders = $leadersStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contactName = trim($_POST['contact_name'] ?? '');
    $contactEmail = trim($_POST['contact_email'] ?? '');
    $contactMessage = trim($_POST['contact_message'] ?? '');

    if ($contactName === '' || $contactEmail === '' || $contactMessage === '') {
        $error = 'Please fill in all contact fields before sending your message.';
    } elseif (!filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            $pdo = Database::connect();
            $stmt = $pdo->prepare('INSERT INTO contact_messages (name, email, message, created_at) VALUES (:name, :email, :message, NOW())');
            $stmt->execute([
                'name' => $contactName,
                'email' => $contactEmail,
                'message' => $contactMessage,
            ]);

            flash('success', 'Your message has been sent. We will get back to you soon.');
            redirect(BASE_URL . 'pages/contact.php');
        } catch (PDOException $e) {
            $error = 'Unable to submit your message right now. Please try again later.';
        }
    }
}
?>

<main class="container py-5">
    <section class="row align-items-center g-4 mb-5">
        <div class="col-lg-6">
            <div class="contact-hero p-5 h-100">
                <p class="text-uppercase text-accent mb-2 small fw-semibold">Get in Touch</p>
                <h1 class="display-6 fw-bold mb-3">Contact the One Batch Family Team</h1>
                <p class="lead text-white-75 mb-4 section-description">Questions, support, or updates? Send a message and we’ll respond as soon as possible.</p>
                <ul class="list-unstyled contact-info mb-0">
                    <li class="mb-3"><i class="fa-solid fa-location-dot text-accent me-2"></i><?php echo htmlspecialchars($settingsMap['contact_address'] ?? '123 Family Lane, Home City'); ?></li>
                    <li class="mb-3"><i class="fa-solid fa-envelope text-accent me-2"></i><?php echo htmlspecialchars($settingsMap['contact_email'] ?? 'family@onebatch.org'); ?></li>
                    <li><i class="fa-solid fa-phone text-accent me-2"></i><?php echo htmlspecialchars($settingsMap['contact_phone'] ?? '+1 (555) 123-4567'); ?></li>
                </ul>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card contact-card border-0 shadow-sm">
                <div class="card-body p-5">
                    <?php if ($success): ?>
                        <div class="alert alert-success rounded-4 shadow-sm mb-4"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger rounded-4 shadow-sm mb-4"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <div class="mb-4 text-center">
                        <h2 class="h4 fw-bold mb-2">Send a message</h2>
                        <p class="text-muted mb-0">We’re here to help with member support and family updates.</p>
                    </div>
                    <form method="post">
                        <div class="mb-3">
                            <label for="contact_name" class="form-label">Your Name</label>
                            <input type="text" class="form-control auth-input" id="contact_name" name="contact_name" placeholder="Enter your name" value="<?php echo htmlspecialchars($contactName); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="contact_email" class="form-label">Email Address</label>
                            <input type="email" class="form-control auth-input" id="contact_email" name="contact_email" placeholder="name@example.com" value="<?php echo htmlspecialchars($contactEmail); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="contact_message" class="form-label">Message</label>
                            <textarea class="form-control auth-input" id="contact_message" name="contact_message" rows="5" placeholder="Write your message"><?php echo htmlspecialchars($contactMessage); ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-accent w-100 py-3 rounded-pill">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <p class="text-uppercase text-accent mb-1 small fw-semibold">Family Leaders</p>
                <h2 class="h3 fw-bold mb-0">The Leaders of One Batch Family</h2>
            </div>
        </div>

        <?php if (empty($contactLeaders)): ?>
            <div class="alert alert-info rounded-4">No leaders have been added yet by the admin.</div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($contactLeaders as $leader): ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="card border-0 shadow-sm h-100 overflow-hidden">
                            <?php if (!empty($leader['photo'])): ?>
                                <img src="<?php echo htmlspecialchars(BASE_URL . 'assets/uploads/leaders/' . $leader['photo']); ?>" alt="<?php echo htmlspecialchars($leader['name']); ?>" class="leader-card-image img-fluid w-100">
                            <?php endif; ?>
                            <div class="card-body p-4">
                                <h3 class="h5 fw-bold mb-1"><?php echo htmlspecialchars($leader['name']); ?></h3>
                                <div class="text-accent fw-semibold mb-3"><?php echo htmlspecialchars($leader['role']); ?></div>
                                <ul class="list-unstyled mb-0 contact-info">
                                    <?php if (!empty($leader['telegram'])): ?>
                                        <li class="mb-2"><i class="fa-brands fa-telegram text-accent me-2"></i><?php echo htmlspecialchars($leader['telegram']); ?></li>
                                    <?php endif; ?>
                                    <?php if (!empty($leader['phone'])): ?>
                                        <li><i class="fa-solid fa-phone text-accent me-2"></i><?php echo htmlspecialchars($leader['phone']); ?></li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php
require_once __DIR__ . '/../includes/footer.php';
require_once __DIR__ . '/../includes/scripts.php';
?>