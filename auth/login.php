<?php
ob_start();
require_once __DIR__ . '/../helpers/session.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$loginError = '';
$email = '';
$loginNotice = trim($_GET['message'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $loginError = 'Please enter both email and password.';
    } else {
        try {
            $pdo = Database::connect();
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                login_user($user);

                $redirectAfterLogin = trim($_GET['redirect'] ?? '');
                if ($redirectAfterLogin !== '') {
                    redirect($redirectAfterLogin);
                }

                if ((int)$user['role_id'] === 1) {
                    redirect(BASE_URL . 'admin/index.php');
                }
                redirect(BASE_URL . 'user/dashboard.php');
            }

            $loginError = 'Invalid email or password. Please try again.';
        } catch (PDOException $e) {
            $loginError = 'Unable to log in right now. Please try again later.';
        }
    }
}
?>

<main class="auth-page container py-5">
    <div class="row justify-content-center align-items-center gx-5">
        <div class="col-lg-5">
            <div class="auth-sidebar rounded-4 p-5 mb-4 mb-lg-0 position-relative overflow-hidden text-white">
                <span class="auth-badge mb-3">Sign In</span>
                <h1 class="display-6 fw-bold mb-3">Welcome back to Dae Batch Family</h1>
                <p class="text-white-75 mb-4">Sign in to connect with family events, prayer requests, memories, and the batch community in one beautiful family space.</p>
                <ul class="list-unstyled auth-features mb-0">
                    <li><span class="feature-dot"></span>Quick access to family updates</li>
                    <li><span class="feature-dot"></span>Secure member-only area</li>
                    <li><span class="feature-dot"></span>Prayer and memory sharing</li>
                </ul>
                <div class="auth-circle auth-circle-1"></div>
                <div class="auth-circle auth-circle-2"></div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card auth-card border-0 shadow-lg">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <h2 class="h4 fw-bold mb-2">Sign in to your account</h2>
                        <p class="text-muted mb-0">Use your email and password to continue.</p>
                    </div>

                    <?php if ($loginNotice): ?>
                        <div class="alert alert-warning rounded-4 shadow-sm mb-4"><?php echo htmlspecialchars($loginNotice); ?></div>
                    <?php endif; ?>

                    <?php if ($loginError): ?>
                        <div class="alert alert-danger rounded-4 shadow-sm mb-4"><?php echo htmlspecialchars($loginError); ?></div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control auth-input" id="email" name="email" placeholder="name@example.com" value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label for="password" class="form-label mb-0">Password</label>
                                <a href="forgot-password.php" class="small text-decoration-none">Forgot password?</a>
                            </div>
                            <input type="password" class="form-control auth-input" id="password" name="password" placeholder="Enter your password" required>
                        </div>
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="remember_me" name="remember_me">
                            <label class="form-check-label" for="remember_me">Remember me</label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-3 rounded-pill">Sign In</button>
                    </form>

                    <p class="text-center text-muted small mt-4 mb-0">Don't have an account? <a href="register.php" class="text-decoration-none">Create one</a></p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
require_once __DIR__ . '/../includes/footer.php';
require_once __DIR__ . '/../includes/scripts.php';
?>