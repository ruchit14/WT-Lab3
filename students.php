<?php
require_once "includes/helpers.php";
require_login();

$uid = current_user_id();
$q = trim($_GET['q'] ?? '');

$students = fetch_students($conn, $uid, $q);

// Most-used skills, shown as quick search chips
$popular = $conn->query("
    SELECT s.skill_name FROM skills s
    JOIN user_skills us ON us.skill_id = s.skill_id
    GROUP BY s.skill_id, s.skill_name
    ORDER BY COUNT(*) DESC, s.skill_name
    LIMIT 8")->fetch_all(MYSQLI_ASSOC);

$page_title = "Find Students";
include "includes/header.php";
?>
<section class="page-section">
    <div class="page-head">
        <span class="section-number">01 / EXPLORE</span>
        <h2>Find someone to <em>swap skills</em> with.</h2>
        <p>Search by skill. Students marked as a perfect match teach what you want and want what you teach.</p>
    </div>

    <form method="GET" action="students.php" class="search-bar">
        <input type="text" name="q" placeholder="Search skills, e.g. Photoshop" value="<?php echo e($q); ?>">
        <button type="submit" class="btn-small">Search</button>
    </form>

    <div class="chips quick-chips">
        <a href="students.php" class="chip link <?php echo $q === '' ? 'active' : ''; ?>">All</a>
        <?php foreach ($popular as $p): ?>
            <a href="students.php?q=<?php echo urlencode($p['skill_name']); ?>"
               class="chip link <?php echo strcasecmp($q, $p['skill_name']) === 0 ? 'active' : ''; ?>"><?php echo e($p['skill_name']); ?></a>
        <?php endforeach; ?>
    </div>

    <?php if (!$students): ?>
        <div class="empty">
            <p>No students found<?php echo $q !== '' ? ' for "' . e($q) . '"' : ''; ?>.</p>
        </div>
    <?php else: ?>
        <p class="result-count"><?php echo count($students); ?> student<?php echo count($students) === 1 ? '' : 's'; ?></p>
        <div class="card-grid">
            <?php foreach ($students as $s) { echo student_card($s); } ?>
        </div>
    <?php endif; ?>
</section>
<?php include "includes/footer.php"; ?>
