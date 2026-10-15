<?php
require_once "includes/helpers.php";

if (is_logged_in()) {
    redirect(is_admin() ? 'admin.php' : 'dashboard.php');
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_check();

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $conn->prepare("SELECT user_id, name, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);   // new session ID after login (stops session fixation)
            $_SESSION['user_id'] = (int)$user['user_id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['role']    = $user['role'];

            redirect($user['role'] === 'admin' ? 'admin.php' : 'dashboard.php');
        }
        // One generic message, so attackers cannot find out which emails exist
        $error = "Invalid email or password.";
    }
}

$page_title = "Login";
include "includes/header.php";
?>
<section class="form-card">
    <h2>Welcome back</h2>
    <p class="muted">Log in to manage your skill exchanges.</p>

    <?php if ($error): ?>
        <div class="flash flash-error"><?php echo e($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <?php echo csrf_field(); ?>

        <label>Email address</label>
        <input type="email" name="email" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <button type="submit" class="btn-full">Log In</button>
    </form>

    <p class="muted center">Don't have an account? <a href="register.php">Register here</a></p>
</section>
<?php include "includes/footer.php"; ?>
