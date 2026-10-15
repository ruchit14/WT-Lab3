<?php
// Shared top of every page. Set $page_title before including this file.
require_once __DIR__ . "/helpers.php";
$page_title = $page_title ?? "SkillSwap";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($page_title); ?> | SkillSwap</title>
    <link rel="stylesheet" href="styles.css">
   <!-- <link rel="stylesheet" href="extra.css"> -->
</head>
<body>
<header class="navbar">
    <a href="index.php" class="logo">skill<span>swap</span><b>•</b></a>

    <nav>
        <a href="index.php">Home</a>
        <a href="students.php">Explore</a>
        <?php if (is_logged_in()): ?>
            <?php if (is_admin()): ?>
                <a href="admin.php">Admin</a>
            <?php else: ?>
                <a href="dashboard.php">Dashboard</a>
                <a href="skills.php">My Skills</a>
                <a href="requests.php">My Requests</a>
                <a href="profile.php">My Profile</a>
            <?php endif; ?>
        <?php endif; ?>
    </nav>

    <?php if (is_logged_in()): ?>
        <a href="logout.php" class="nav-btn">Logout</a>
    <?php else: ?>
        <a href="login.php" class="nav-btn">Join Exchange</a>
    <?php endif; ?>
</header>

<main class="page">
<?php show_flash(); ?>
