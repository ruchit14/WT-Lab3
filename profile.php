<?php
require_once "includes/helpers.php";
require_student();

$uid = current_user_id();
$errors = [];
$allowed_years = ['1st Year', '2nd Year', '3rd Year', '4th Year'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_check();

    $name   = trim($_POST['name'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $year   = trim($_POST['year'] ?? '');
    $bio    = trim($_POST['bio'] ?? '');

    if ($name === '' || strlen($name) > 100)     $errors[] = "Enter your name (max 100 characters).";
    if ($course === '' || strlen($course) > 100) $errors[] = "Enter your course.";
    if (!in_array($year, $allowed_years, true))  $errors[] = "Select your year.";
    if (strlen($bio) > 500)                      $errors[] = "Bio can be at most 500 characters.";

    if (!$errors) {
        // The user ID comes from the session, never from the form
        $stmt = $conn->prepare("UPDATE users SET name = ?, course = ?, year = ?, bio = ? WHERE user_id = ?");
        $stmt->bind_param("ssssi", $name, $course, $year, $bio, $uid);
        $stmt->execute();
        $_SESSION['name'] = $name;
        flash('success', 'Profile updated.');
        redirect('profile.php');
    }
}

$stmt = $conn->prepare("SELECT name, email, course, year, bio FROM users WHERE user_id = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$me = $stmt->get_result()->fetch_assoc();

// Show what the user typed if validation failed
if ($errors) {
    $me['name'] = $name; $me['course'] = $course; $me['year'] = $year; $me['bio'] = $bio;
}

$page_title = "My Profile";
include "includes/header.php";
?>
<section class="form-card wide">
    <h2>My profile</h2>
    <p class="muted">Email: <?php echo e($me['email']); ?></p>

    <?php foreach ($errors as $err): ?>
        <div class="flash flash-error"><?php echo e($err); ?></div>
    <?php endforeach; ?>

    <form method="POST" action="profile.php">
        <?php echo csrf_field(); ?>

        <label>Full name</label>
        <input type="text" name="name" value="<?php echo e($me['name']); ?>" required>

        <label>Course</label>
        <input type="text" name="course" value="<?php echo e($me['course']); ?>" required>

        <label>Year</label>
        <select name="year" required>
            <option value="">Choose one</option>
            <?php foreach ($allowed_years as $y): ?>
                <option <?php echo $me['year'] === $y ? 'selected' : ''; ?>><?php echo e($y); ?></option>
            <?php endforeach; ?>
        </select>

        <label>Bio</label>
        <textarea name="bio" rows="4" maxlength="500"><?php echo e($me['bio']); ?></textarea>

        <button type="submit" class="btn-full">Save changes</button>
    </form>

    <p class="muted center"><a href="skills.php">Manage my skills →</a></p>
</section>
<?php include "includes/footer.php"; ?>
