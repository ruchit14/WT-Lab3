<?php
require_once "includes/helpers.php";
require_student();

$uid = current_user_id();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $skill_id = (int)($_POST['skill_id'] ?? 0);
        $type     = $_POST['type'] ?? '';

        if (!in_array($type, ['OFFER', 'WANT'], true) || $skill_id <= 0) {
            flash('error', 'Choose a skill and a type.');
            redirect('skills.php');
        }

        // The skill must exist in the skills table
        $stmt = $conn->prepare("SELECT skill_id FROM skills WHERE skill_id = ?");
        $stmt->bind_param("i", $skill_id);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) {
            flash('error', 'That skill does not exist.');
            redirect('skills.php');
        }

        // INSERT IGNORE + the UNIQUE key stops duplicates
        $stmt = $conn->prepare("INSERT IGNORE INTO user_skills (user_id, skill_id, type) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $uid, $skill_id, $type);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            flash('success', 'Skill added.');
        } else {
            flash('error', 'You already added this skill.');
        }
        redirect('skills.php');
    }

    if ($action === 'remove') {
        $user_skill_id = (int)($_POST['user_skill_id'] ?? 0);

        // "AND user_id = ?" guarantees you can only delete your own rows
        $stmt = $conn->prepare("DELETE FROM user_skills WHERE user_skill_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $user_skill_id, $uid);
        $stmt->execute();
        flash($stmt->affected_rows > 0 ? 'success' : 'error',
              $stmt->affected_rows > 0 ? 'Skill removed.' : 'Could not remove that skill.');
        redirect('skills.php');
    }

    redirect('skills.php');
}

// All available skills for the dropdown
$all_skills = $conn->query("SELECT skill_id, skill_name, category FROM skills ORDER BY category, skill_name")->fetch_all(MYSQLI_ASSOC);

// My skills
$stmt = $conn->prepare("
    SELECT us.user_skill_id, us.type, s.skill_name, s.category
    FROM user_skills us
    JOIN skills s ON s.skill_id = us.skill_id
    WHERE us.user_id = ?
    ORDER BY s.skill_name
");
$stmt->bind_param("i", $uid);
$stmt->execute();
$mine   = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$offers = array_values(array_filter($mine, fn($r) => $r['type'] === 'OFFER'));
$wants  = array_values(array_filter($mine, fn($r) => $r['type'] === 'WANT'));

$page_title = "My Skills";
include "includes/header.php";

function skill_pills(array $rows, string $cls): void {
    if (!$rows) {
        echo '<p class="muted">Nothing here yet. Add one above.</p>';
        return;
    }
    echo '<div class="skill-pills">';
    foreach ($rows as $r) {
        echo '<div class="skill-pill ' . $cls . '"><span>' . e($r['skill_name']) . ' <small>' . e($r['category']) . '</small></span>';
        echo '<form method="POST" action="skills.php">' . csrf_field();
        echo '<input type="hidden" name="action" value="remove">';
        echo '<input type="hidden" name="user_skill_id" value="' . (int)$r['user_skill_id'] . '">';
        echo '<button type="submit" title="Remove" aria-label="Remove ' . e($r['skill_name']) . '">×</button></form></div>';
    }
    echo '</div>';
}
?>
<section class="page-section">
    <div class="page-head">
        <span class="section-number">MY SKILLS</span>
        <h2>What you <em>know</em> and what you <em class="orange">want.</em></h2>
        <p>Add skills you can teach and skills you'd like to learn. Others find you through these.</p>
    </div>

    <div class="add-card">
        <h3>Add a skill</h3>
        <form method="POST" action="skills.php" class="add-form">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="add">

            <select name="skill_id" required>
                <option value="">Choose a skill</option>
                <?php foreach ($all_skills as $s): ?>
                    <option value="<?php echo (int)$s['skill_id']; ?>"><?php echo e($s['category'] . ' – ' . $s['skill_name']); ?></option>
                <?php endforeach; ?>
            </select>

            <div class="type-toggle">
                <input type="radio" name="type" id="t-offer" value="OFFER" checked>
                <label for="t-offer">I can teach</label>
                <input type="radio" name="type" id="t-want" value="WANT">
                <label for="t-want">I want to learn</label>
            </div>

            <button type="submit" class="btn-add">Add</button>
        </form>
    </div>

    <div class="two-col">
        <div class="skill-col offer">
            <h3>I can teach <span class="count-pill"><?php echo count($offers); ?></span></h3>
            <?php skill_pills($offers, 'offer'); ?>
        </div>
        <div class="skill-col want">
            <h3>I want to learn <span class="count-pill"><?php echo count($wants); ?></span></h3>
            <?php skill_pills($wants, 'want'); ?>
        </div>
    </div>
</section>
<?php include "includes/footer.php"; ?>
