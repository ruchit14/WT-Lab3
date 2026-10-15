<?php
require_once "includes/helpers.php";

if (is_logged_in()) {
    redirect(is_admin() ? 'admin.php' : 'dashboard.php');
}

$errors = [];
$old = ['name' => '', 'email' => '', 'course' => '', 'year' => ''];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_check();

    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';
    $course   = trim($_POST['course'] ?? '');
    $year     = trim($_POST['year'] ?? '');
    $old = compact('name', 'email', 'course', 'year');

    $allowed_years = ['1st Year', '2nd Year', '3rd Year', '4th Year'];

    if ($name === '' || strlen($name) > 100)            $errors[] = "Enter your name (max 100 characters).";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))     $errors[] = "Enter a valid email address.";
    if (strlen($password) < 6)                          $errors[] = "Password must be at least 6 characters.";
    if ($password !== $confirm)                         $errors[] = "Passwords do not match.";
    if ($course === '' || strlen($course) > 100)        $errors[] = "Enter your course.";
    if (!in_array($year, $allowed_years, true))         $errors[] = "Select your year.";

    if (!$errors) {
        // Is the email already registered?
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            $errors[] = "This email is already registered.";
        }
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, course, year, role) VALUES (?, ?, ?, ?, ?, 'student')");
        $stmt->bind_param("sssss", $name, $email, $hash, $course, $year);
        if ($stmt->execute()) {
            flash('success', 'Account created! You can log in now.');
            redirect('login.php');
        }
        $errors[] = "Could not create the account. Please try again.";
    }
}

$page_title = "Register";
include "includes/header.php";
?>
<section class="form-card">
    <h2>Create your account</h2>
    <p class="muted">Join SkillSwap and start exchanging skills.</p>

    <?php foreach ($errors as $err): ?>
        <div class="flash flash-error"><?php echo e($err); ?></div>
    <?php endforeach; ?>

    <form method="POST" action="register.php">
        <?php echo csrf_field(); ?>

        <label>Full name</label>
        <input type="text" name="name" value="<?php echo e($old['name']); ?>" required>

        <label>Email</label>
        <input type="email" name="email" value="<?php echo e($old['email']); ?>" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <label>Confirm password</label>
        <input type="password" name="confirm" required>

        <label>Course</label>
        <input type="text" name="course" placeholder="e.g. BCA" value="<?php echo e($old['course']); ?>" required>

        <label>Year</label>
        <select name="year" required>
            <option value="">Choose one</option>
            <?php foreach (['1st Year', '2nd Year', '3rd Year', '4th Year'] as $y): ?>
                <option <?php echo $old['year'] === $y ? 'selected' : ''; ?>><?php echo e($y); ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn-full">Register</button>
    </form>

    <p class="muted center">Already have an account? <a href="login.php">Log in</a></p>
</section>
<?php include "includes/footer.php"; ?>
