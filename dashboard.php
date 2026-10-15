<?php
require_once "includes/helpers.php";
require_student();

$uid = current_user_id();

// Small helper: run a COUNT query with one integer parameter
function count_for(mysqli $conn, string $sql, int $uid): int {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    return (int)$stmt->get_result()->fetch_row()[0];
}

$offer_count    = count_for($conn, "SELECT COUNT(*) FROM user_skills WHERE user_id = ? AND type = 'OFFER'", $uid);
$want_count     = count_for($conn, "SELECT COUNT(*) FROM user_skills WHERE user_id = ? AND type = 'WANT'", $uid);
$pending_count  = count_for($conn, "SELECT COUNT(*) FROM exchange_requests WHERE receiver_id = ? AND status = 'PENDING'", $uid);
$accepted_count = count_for($conn, "SELECT COUNT(*) FROM exchange_requests WHERE ? IN (sender_id, receiver_id) AND status = 'ACCEPTED'", $uid);

// Up to 3 perfect matches (they teach what I want AND want what I teach)
$matches = fetch_students($conn, $uid, '', 3, true);

$page_title = "Dashboard";
include "includes/header.php";
?>
<section class="page-section">

    <div class="welcome">
        <div>
            <span class="section-number light">YOUR DASHBOARD</span>
            <h2>Hello, <em><?php echo e($_SESSION['name']); ?></em></h2>
            <p>Your skills, requests and matches.</p>
        </div>
        <div class="welcome-actions">
            <a href="students.php" class="primary-btn">Find students <span>↗</span></a>
            <a href="skills.php" class="secondary-btn">My skills</a>
        </div>
    </div>

    <div class="stat-grid">
        <div class="stat-card" style="--c:#6b61d8;--bg:#eeecff">
            <div class="stat-icon"><?php echo icon('lightbulb'); ?></div>
            <b><?php echo $offer_count; ?></b>
            <span>Skills I can teach</span>
        </div>
        <div class="stat-card" style="--c:#f08b55;--bg:#fdeee4">
            <div class="stat-icon"><?php echo icon('book'); ?></div>
            <b><?php echo $want_count; ?></b>
            <span>Skills I want to learn</span>
        </div>
        <div class="stat-card" style="--c:#e0a526;--bg:#fff1d6">
            <div class="stat-icon"><?php echo icon('clock'); ?></div>
            <b><?php echo $pending_count; ?></b>
            <span>Pending requests received</span>
        </div>
        <div class="stat-card" style="--c:#29845b;--bg:#e3f4ec">
            <div class="stat-icon"><?php echo icon('check'); ?></div>
            <b><?php echo $accepted_count; ?></b>
            <span>Accepted exchanges</span>
        </div>
    </div>

    <?php if ($pending_count > 0): ?>
        <div class="notice">
            You have <b><?php echo $pending_count; ?></b> pending request<?php echo $pending_count === 1 ? '' : 's'; ?> waiting.
            <a href="requests.php">Review now →</a>
        </div>
    <?php endif; ?>

    <div class="section-title">
        <h3>Your best matches</h3>
        <a href="students.php">Browse all →</a>
    </div>

    <?php if ($matches): ?>
        <div class="card-grid">
            <?php foreach ($matches as $s) { echo student_card($s); } ?>
        </div>
    <?php else: ?>
        <div class="empty">
            <p>No perfect matches yet. Add what you can teach and what you want to learn, and students who fit will show up here.</p>
            <a href="skills.php" class="btn-small">Update my skills</a>
        </div>
    <?php endif; ?>
</section>
<?php include "includes/footer.php"; ?>
