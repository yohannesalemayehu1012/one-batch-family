<?php
ob_start();
require_once __DIR__ . '/../helpers/session.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../helpers/upload.php';
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Member.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$registerError = '';
$name = '';
$email = '';
$familyMembers = '';
$university = '';
$department = '';
$age = '';
$year = '';
$role = '';
$socialTelegram = '';
$socialFacebook = '';
$socialLinkedIn = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $familyMembers = trim($_POST['family_members'] ?? '');
    $university = trim($_POST['university'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $age = trim($_POST['age'] ?? '');
    $year = trim($_POST['year'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $socialTelegram = trim($_POST['social_telegram'] ?? '');
    $socialFacebook = trim($_POST['social_facebook'] ?? '');
    $socialLinkedIn = trim($_POST['social_linkedin'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $photoFile = $_FILES['profile_photo'] ?? null;

    if ($name === '' || $email === '' || $password === '' || $confirmPassword === '' || $familyMembers === '' || $university === '' || $department === '' || $year === '' || $role === '' || $socialTelegram === '' || !$photoFile) {
        $registerError = 'Please complete every required field to create your account.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $registerError = 'Please enter a valid email address.';
    } elseif ($password !== $confirmPassword) {
        $registerError = 'Passwords do not match. Please try again.';
    } elseif ($age !== '' && (!is_numeric($age) || (int)$age <= 0)) {
        $registerError = 'Please enter a valid age.';
    } else {
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $destinationDir = __DIR__ . '/../assets/uploads/profile';
        $photoFilename = upload_file($photoFile, $destinationDir, $allowedTypes, 5 * 1024 * 1024);

        if (!$photoFilename) {
            $registerError = 'Please upload a valid profile photo (image files only, max 5MB).';
        } else {
            try {
                $pdo = Database::connect();
                $check = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :email');
                $check->execute(['email' => $email]);

                if ((int)$check->fetchColumn() > 0) {
                    $registerError = 'This email is already registered. Please sign in or use a different email.';
                } else {
                    $pdo->beginTransaction();
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare('INSERT INTO users (fullname, email, password, role_id, created_at) VALUES (:fullname, :email, :password, 2, NOW())');
                    $stmt->execute([
                        'fullname' => $name,
                        'email' => $email,
                        'password' => $hash,
                    ]);

                    $userId = (int)$pdo->lastInsertId();
                    $telegramHandle = trim($socialTelegram);
                    if ($telegramHandle !== '') {
                        $telegramHandle = ltrim($telegramHandle, '@');
                        $telegramUrl = preg_match('/^https?:\/\//i', $telegramHandle)
                            ? $telegramHandle
                            : 'https://t.me/' . $telegramHandle;
                    } else {
                        $telegramUrl = '';
                    }

                    $memberData = [
                        'user_id' => $userId,
                        'slug' => Member::generateSlug($name),
                        'name' => $name,
                        'email' => $email,
                        'role' => $role,
                        'photo' => $photoFilename,
                        'university' => $university,
                        'department' => $department,
                        'age' => $age,
                        'year' => $year,
                        'family_members' => $familyMembers,
                        'social_links' => json_encode(array_filter([
                            ['label' => 'Telegram', 'url' => $telegramUrl],
                            ['label' => 'Facebook', 'url' => $socialFacebook],
                            ['label' => 'LinkedIn', 'url' => $socialLinkedIn],
                        ], fn($link) => !empty($link['url']))),
                        'bio' => 'A dedicated new member of the family hub.',
                    ];

                    if (!Member::create($memberData)) {
                        $pdo->rollBack();
                        $registerError = 'Unable to save your member profile right now. Please try again later.';
                    } else {
                        $pdo->commit();
                        login_user([
                            'id' => $userId,
                            'fullname' => $name,
                            'email' => $email,
                            'role_id' => 2,
                        ]);
                        redirect(BASE_URL . 'user/dashboard.php');
                    }
                }
            } catch (PDOException $e) {
                if ($pdo && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $registerError = 'Unable to create your account right now. Please try again later.';
            }
        }
    }
}
?>

<main class="auth-page container py-5">
    <div class="row justify-content-center align-items-center gx-5">
        <div class="col-lg-5 order-lg-2">
            <div class="auth-sidebar rounded-4 p-5 mb-4 mb-lg-0 position-relative overflow-hidden text-white">
                <span class="auth-badge mb-3">Register</span>
                <h1 class="display-6 fw-bold mb-3"><span class="text-accent">Create</span> your family account</h1>
                <p class="text-white-75 mb-4 section-description">Join the Dae Batch Family hub to share memories, prayer updates, and stay connected with batch events.</p>
                <ul class="list-unstyled auth-features mb-0">
                    <li><span class="feature-dot"></span>Safe private membership</li>
                    <li><span class="feature-dot"></span>Share stories and media</li>
                    <li><span class="feature-dot"></span>Support and prayer together</li>
                </ul>
                <div class="auth-circle auth-circle-1"></div>
                <div class="auth-circle auth-circle-2"></div>
            </div>
        </div>
        <div class="col-lg-5 order-lg-1">
            <div class="card auth-card border-0 shadow-lg">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <h2 class="h4 fw-bold mb-2">Register a new account</h2>
                        <p class="text-muted mb-0">Create your profile and start connecting with the family.</p>
                    </div>

                    <?php if ($registerError): ?>
                        <div class="alert alert-danger rounded-4 shadow-sm mb-4"><?php echo htmlspecialchars($registerError); ?></div>
                    <?php endif; ?>

                    <form method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control auth-input" id="name" name="name" placeholder="Your full name" value="<?php echo htmlspecialchars($name); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control auth-input" id="email" name="email" placeholder="name@example.com" value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="family_members" class="form-label">Family Members</label>
                            <input type="text" class="form-control auth-input" id="family_members" name="family_members" placeholder="Spouse, children, parents, etc." value="<?php echo htmlspecialchars($familyMembers); ?>" required>
                        </div>
                        <div class="row gx-3">
                            <div class="col-md-6 mb-3">
                                <label for="university" class="form-label">University</label>
                                <input type="text" class="form-control auth-input" id="university" name="university" placeholder="University name" value="<?php echo htmlspecialchars($university); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="department" class="form-label">Department</label>
                                <input type="text" class="form-control auth-input" id="department" name="department" placeholder="Department or major" value="<?php echo htmlspecialchars($department); ?>" required>
                            </div>
                        </div>
                        <div class="row gx-3">
                            <div class="col-md-4 mb-3">
                                <label for="age" class="form-label">Age (Optional)</label>
                                <input type="number" class="form-control auth-input" id="age" name="age" placeholder="Age" value="<?php echo htmlspecialchars($age); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="year" class="form-label">Year</label>
                                <input type="text" class="form-control auth-input" id="year" name="year" placeholder="4th Year, Alumni, etc." value="<?php echo htmlspecialchars($year); ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="role" class="form-label">Role</label>
                                <input type="text" class="form-control auth-input" id="role" name="role" placeholder="Family role or responsibility" value="<?php echo htmlspecialchars($role); ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="profile_photo" class="form-label">Profile Photo</label>
                            <input type="file" class="form-control auth-input" id="profile_photo" name="profile_photo" accept="image/*" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Social Media</label>
                            <div class="row gx-3">
                                <div class="col-md-4 mb-3">
                                    <input type="text" class="form-control auth-input" id="social_telegram" name="social_telegram" placeholder="Telegram username or name" value="<?php echo htmlspecialchars($socialTelegram); ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <input type="text" class="form-control auth-input" id="social_facebook" name="social_facebook" placeholder="Facebook URL or username" value="<?php echo htmlspecialchars($socialFacebook); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <input type="text" class="form-control auth-input" id="social_linkedin" name="social_linkedin" placeholder="LinkedIn URL or username" value="<?php echo htmlspecialchars($socialLinkedIn); ?>">
                                </div>
                            </div>
                            <small class="text-muted">Telegram is required. Facebook and LinkedIn are optional.</small>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control auth-input" id="password" name="password" placeholder="Create a password" required>
                        </div>
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control auth-input" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                        </div>
                        <button type="submit" class="btn btn-accent w-100 py-3 rounded-pill">Register Now</button>
                    </form>

                    <p class="text-center text-muted small mt-4 mb-0">Already have an account? <a href="login.php" class="text-decoration-none">Sign In</a></p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
require_once __DIR__ . '/../includes/footer.php';
require_once __DIR__ . '/../includes/scripts.php';
?>