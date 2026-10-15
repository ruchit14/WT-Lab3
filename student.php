<?php
require_once "includes/helpers.php";
require_student();

$uid = current_user_id();
$id  = (int)($_GET['id'] ?? 0);

if ($id === $uid) {
    redirect('profile.php');   // you cannot swap with yourself
}

$stmt = $conn->prepare("SELECT user_id, name, course, year, bio FROM users WHERE user_id = ? AND role = 'student'");
$stmt->bind_param("i", $id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    flash('error', 'Student not found.');
    redirect('students.php');
}

function get_skills(mysqli $conn, int $user_id, string $type): array {
    $stmt = $conn->prepare("
        SELECT s.skill_id, s.skill_name FROM user_skills us
        JOIN skills s ON s.skill_id = us.skill_id
        WHERE us.user_id = ? AND us.type = ? ORDER BY s.skill_name");
    $stmt->bind_param("is", $user_id, $type);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$their_offers = get_skills($conn, $id, 'OFFER');
$their_wants  = get_skills($conn, $id, 'WANT');
$my_offers    = get_skills($conn, $uid, 'OFFER');
$my_wants     = get_skills($conn, $uid, 'WANT');

$their_want_ids = array_column($their_wants, 'skill_id');
$my_want_ids    = array_column($my_wants, 'skill_id');

// Skills I can teach that this student wants (these make the best swap)
$useful_offers = array_filter($my_offers, fn($s) => in_array($s['skill_id'], $their_want_ids));
// Skills this student teaches that I want
$useful_wants  = array_filter($their_offers, fn($s) => in_array($s['skill_id'], $my_want_ids));

// Show the useful ones first in the dropdowns
usort($my_offers, fn($a, $b) => (int)in_array($b['skill_id'], $their_want_ids) <=> (int)in_array($a['skill_id'], $their_want_ids));
usort($their_offers, fn($a, $b) => (int)in_array($b['skill_id'], $my_want_ids) <=> (int)in_array($a['skill_id'], $my_want_ids));

// Skill names to highlight on this student's profile
$hl_teach = array_column($useful_wants, 'skill_name');   // they teach, I want
$hl_want  = array_column($useful_offers, 'skill_name');  // they want, I teach

$first = e($student['name']);
$page_title = $student['name'];
include "includes/header.php";
?>
<section class="page-section">
    <a href="students.php" class="back-link">← Back to students</a>

    <div class="profile-layout">

        <!-- LEFT: public profile -->
        <div class="profile-card">
            <div class="profile-head">
                <div class="avatar big <?php echo avatar_class($id); ?>"><?php echo e(initial($student['name'])); ?></div>
                <div>
                    <h2><?php echo $first; ?></h2>
                    <p class="muted"><?php echo e($student['course']); ?> · <?php echo e($student['year']); ?></p>
                </div>
            </div>

            <p class="bio"><?php echo $student['bio'] ? nl2br(e($student['bio'])) : '<span class="muted">No bio yet.</span>'; ?></p>

            <span class="sc-label teach">CAN TEACH</span>
            <?php echo chips(array_column($their_offers, 'skill_name'), '', $hl_teach); ?>

            <span class="sc-label want" style="margin-top:22px">WANTS TO LEARN</span>
            <?php echo chips(array_column($their_wants, 'skill_name'), 'want', $hl_want); ?>

            <?php if ($hl_teach || $hl_want): ?>
                <p class="legend"><span class="chip match">Highlighted</span> matches your skills</p>
            <?php endif; ?>
        </div>

        <!-- RIGHT: request form -->
        <div class="request-card">
            <h3>Propose a swap</h3>

            <?php if (!$my_offers): ?>
                <p class="hint warn">You haven't added any skill you can teach yet. <a href="skills.php">Add one in My Skills</a> to send a request.</p>
            <?php elseif (!$their_offers): ?>
                <p class="hint warn"><?php echo $first; ?> hasn't listed any skill they can teach, so there is nothing to ask for yet.</p>
            <?php else: ?>

                <?php if ($useful_offers): ?>
                    <div class="hint good">
                        <span class="hint-icon"><?php echo icon('swap'); ?></span>
                        <span><b>Match.</b> You can teach <b><?php echo e(implode(', ', array_column($useful_offers, 'skill_name'))); ?></b>, which <?php echo $first; ?> wants to learn.</span>
                    </div>
                <?php elseif ($their_wants): ?>
                    <div class="hint warn">
                        <span><?php echo $first; ?> wants to learn <b><?php echo e(implode(', ', array_column($their_wants, 'skill_name'))); ?></b>, but none of that is on your "can teach" list. If you know one of these, <a href="skills.php">add it to My Skills</a>. You can still offer something else below.</span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="request.php">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="receiver_id" value="<?php echo (int)$student['user_id']; ?>">

                    <label>I will teach <small>(from your skills)</small></label>
                    <select name="offered_skill_id" required>
                        <?php foreach ($my_offers as $s): ?>
                            <option value="<?php echo (int)$s['skill_id']; ?>">
                                <?php echo e($s['skill_name']); ?><?php echo in_array($s['skill_id'], $their_want_ids) ? e(' (match: ' . $student['name'] . ' wants this)') : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div class="swap-divider">⇅</div>

                    <label>In exchange, I want to learn <small>(from <?php echo $first; ?>)</small></label>
                    <select name="wanted_skill_id" required>
                        <?php foreach ($their_offers as $s): ?>
                            <option value="<?php echo (int)$s['skill_id']; ?>">
                                <?php echo e($s['skill_name']); ?><?php echo in_array($s['skill_id'], $my_want_ids) ? ' (match: on your wish list)' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label>Message <small>(optional)</small></label>
                    <textarea name="message" rows="3" maxlength="300" placeholder="Say hi and suggest a time..."></textarea>

                    <button type="submit" class="btn-full">Send request →</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php include "includes/footer.php"; ?>
