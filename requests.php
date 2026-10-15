<?php
require_once "includes/helpers.php";
require_student();

$uid = current_user_id();

// Accept / reject an incoming request
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_check();

    $request_id = (int)($_POST['request_id'] ?? 0);
    $action     = $_POST['action'] ?? '';
    $map = ['accept' => 'ACCEPTED', 'reject' => 'REJECTED'];

    if (!isset($map[$action])) {
        flash('error', 'Invalid action.');
        redirect('requests.php');
    }
    $new_status = $map[$action];

    // One query checks everything: it must be MY received request AND still pending.
    $stmt = $conn->prepare("UPDATE exchange_requests SET status = ?
                            WHERE request_id = ? AND receiver_id = ? AND status = 'PENDING'");
    $stmt->bind_param("sii", $new_status, $request_id, $uid);
    $stmt->execute();

    if ($stmt->affected_rows === 1) {
        flash('success', 'Request ' . strtolower($new_status) . '.');
    } else {
        flash('error', 'That request cannot be updated.');
    }
    redirect('requests.php');
}

$base = "SELECT r.request_id, r.message, r.status, r.created_at,
                u.name AS other_name, u.user_id AS other_id,
                so.skill_name AS offered, sw.skill_name AS wanted
         FROM exchange_requests r
         JOIN users u   ON u.user_id = %s
         JOIN skills so ON so.skill_id = r.offered_skill_id
         JOIN skills sw ON sw.skill_id = r.wanted_skill_id
         WHERE %s = ?
         ORDER BY r.created_at DESC";

$stmt = $conn->prepare(sprintf($base, 'r.receiver_id', 'r.sender_id'));
$stmt->bind_param("i", $uid);
$stmt->execute();
$sent = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt = $conn->prepare(sprintf($base, 'r.sender_id', 'r.receiver_id'));
$stmt->bind_param("i", $uid);
$stmt->execute();
$received = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

/*
 * One request card.
 * $dir = 'in'  -> a request I received: offered = what THEY teach, wanted = what they want FROM ME
 * $dir = 'out' -> a request I sent:     offered = what I teach,    wanted = what I want to learn
 */
function request_card(array $r, string $dir): void {
    $in = ($dir === 'in');
    $status = strtolower($r['status']);
    ?>
    <article class="req-card status-<?php echo e($status); ?>">
        <div class="req-head">
            <div class="avatar <?php echo avatar_class((int)$r['other_id']); ?>"><?php echo e(initial($r['other_name'])); ?></div>
            <div class="req-who">
                <small><?php echo $in ? 'FROM' : 'TO'; ?></small>
                <a href="student.php?id=<?php echo (int)$r['other_id']; ?>"><?php echo e($r['other_name']); ?></a>
            </div>
            <span class="badge badge-<?php echo e($status); ?>"><?php echo e($r['status']); ?></span>
        </div>

        <div class="swap-row">
            <div class="swap-box">
                <small><?php echo $in ? 'THEY TEACH' : 'YOU TEACH'; ?></small>
                <b><?php echo e($r['offered']); ?></b>
            </div>
            <div class="swap-icon">⇄</div>
            <div class="swap-box alt">
                <small><?php echo $in ? 'THEY WANT FROM YOU' : 'YOU WANT TO LEARN'; ?></small>
                <b><?php echo e($r['wanted']); ?></b>
            </div>
        </div>

        <?php if ($r['message'] !== null && $r['message'] !== ''): ?>
            <p class="req-message">“<?php echo e($r['message']); ?>”</p>
        <?php endif; ?>

        <div class="req-foot">
            <span class="muted small"><?php echo e(date('d M Y', strtotime($r['created_at']))); ?></span>
            <?php if ($in && $r['status'] === 'PENDING'): ?>
                <form method="POST" action="requests.php" class="req-actions">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="request_id" value="<?php echo (int)$r['request_id']; ?>">
                    <button name="action" value="reject" class="btn-small btn-light">Reject</button>
                    <button name="action" value="accept" class="btn-small btn-accept">Accept</button>
                </form>
            <?php endif; ?>
        </div>
    </article>
    <?php
}

$page_title = "My Requests";
include "includes/header.php";
?>
<section class="page-section">
    <div class="page-head">
        <span class="section-number">MY REQUESTS</span>
        <h2>Your <em>swaps,</em> at a glance.</h2>
        <p>Accept or reject requests you received, and track the ones you sent.</p>
    </div>

    <div class="section-title"><h3>Received <span class="count-pill"><?php echo count($received); ?></span></h3></div>
    <?php if (!$received): ?>
        <div class="empty"><p>No requests received yet.</p></div>
    <?php else: ?>
        <div class="req-grid">
            <?php foreach ($received as $r) { request_card($r, 'in'); } ?>
        </div>
    <?php endif; ?>

    <div class="section-title" style="margin-top:45px"><h3>Sent <span class="count-pill"><?php echo count($sent); ?></span></h3></div>
    <?php if (!$sent): ?>
        <div class="empty">
            <p>You haven't sent any requests yet.</p>
            <a href="students.php" class="btn-small">Find a student</a>
        </div>
    <?php else: ?>
        <div class="req-grid">
            <?php foreach ($sent as $r) { request_card($r, 'out'); } ?>
        </div>
    <?php endif; ?>
</section>
<?php include "includes/footer.php"; ?>
