<?php
require_once "includes/helpers.php";
require_admin();   // server-side role check (re-reads the role from the database)

$uid = current_user_id();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'delete_user') {
        $target = (int)($_POST['user_id'] ?? 0);
        if ($target === $uid) {
            flash('error', 'You cannot delete your own account.');
        } else {
            // Only students can be deleted. ON DELETE CASCADE removes their skills and requests.
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ? AND role = 'student'");
            $stmt->bind_param("i", $target);
            $stmt->execute();
            flash($stmt->affected_rows > 0 ? 'success' : 'error',
                  $stmt->affected_rows > 0 ? 'User deleted.' : 'User could not be deleted.');
        }
        redirect('admin.php?tab=users');
    }

    if ($action === 'add_skill') {
        $name = trim($_POST['skill_name'] ?? '');
        $cat  = $_POST['category'] ?? '';
        $cats = ['Technology', 'Creative', 'Academic', 'Lifestyle'];

        if ($name === '' || strlen($name) > 100 || !in_array($cat, $cats, true)) {
            flash('error', 'Enter a skill name and choose a category.');
        } else {
            $stmt = $conn->prepare("INSERT IGNORE INTO skills (skill_name, category) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $cat);
            $stmt->execute();
            flash($stmt->affected_rows > 0 ? 'success' : 'error',
                  $stmt->affected_rows > 0 ? 'Skill added.' : 'That skill already exists.');
        }
        redirect('admin.php?tab=skills');
    }

    if ($action === 'delete_skill') {
        $sid = (int)($_POST['skill_id'] ?? 0);
        // Foreign keys cascade: user links and requests using this skill are removed too
        $stmt = $conn->prepare("DELETE FROM skills WHERE skill_id = ?");
        $stmt->bind_param("i", $sid);
        $stmt->execute();
        flash($stmt->affected_rows > 0 ? 'success' : 'error',
              $stmt->affected_rows > 0 ? 'Skill deleted.' : 'Skill not found.');
        redirect('admin.php?tab=skills');
    }

    redirect('admin.php');
}

$tab = $_GET['tab'] ?? 'overview';
if (!in_array($tab, ['overview', 'users', 'skills', 'requests'], true)) {
    $tab = 'overview';
}

// Statistics
$stats = [
    'Students'         => $conn->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetch_row()[0],
    'Skills'           => $conn->query("SELECT COUNT(*) FROM skills")->fetch_row()[0],
    'Exchange requests'=> $conn->query("SELECT COUNT(*) FROM exchange_requests")->fetch_row()[0],
    'Pending requests' => $conn->query("SELECT COUNT(*) FROM exchange_requests WHERE status = 'PENDING'")->fetch_row()[0],
];

$page_title = "Admin";
include "includes/header.php";
?>
<section class="page-section">
    <div class="page-head">
        <span class="section-number">ADMIN</span>
        <h2>Site <em>administration.</em></h2>
        <p>Manage students, skills and exchange requests.</p>
    </div>

    <div class="tabs">
        <?php foreach (['overview' => 'Overview', 'users' => 'Users', 'skills' => 'Skills', 'requests' => 'Requests'] as $k => $label): ?>
            <a href="admin.php?tab=<?php echo $k; ?>" class="<?php echo $tab === $k ? 'active' : ''; ?>"><?php echo $label; ?></a>
        <?php endforeach; ?>
    </div>

<?php if ($tab === 'overview'): ?>
    <div class="stat-grid">
        <?php foreach ($stats as $label => $num): ?>
            <div class="stat-card"><b><?php echo (int)$num; ?></b><span><?php echo e($label); ?></span></div>
        <?php endforeach; ?>
    </div>

<?php elseif ($tab === 'users'):
    $users = $conn->query("SELECT user_id, name, email, course, year, role FROM users ORDER BY user_id")->fetch_all(MYSQLI_ASSOC); ?>
    <div class="table-wrap"><table class="data-table">
        <tr><th>ID</th><th>Name</th><th>Email</th><th>Course</th><th>Year</th><th>Role</th><th></th></tr>
        <?php foreach ($users as $u): ?>
        <tr>
            <td><?php echo (int)$u['user_id']; ?></td>
            <td><?php echo e($u['name']); ?></td>
            <td><?php echo e($u['email']); ?></td>
            <td><?php echo e($u['course'] ?: '—'); ?></td>
            <td><?php echo e($u['year'] ?: '—'); ?></td>
            <td><?php echo e($u['role']); ?></td>
            <td class="actions">
                <?php if ($u['role'] === 'student'): ?>
                <form method="POST" action="admin.php" onsubmit="return confirm('Delete this user and all their skills and requests?');">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" value="<?php echo (int)$u['user_id']; ?>">
                    <button class="btn-small btn-danger">Delete</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table></div>

<?php elseif ($tab === 'skills'):
    $skills = $conn->query("SELECT skill_id, skill_name, category FROM skills ORDER BY category, skill_name")->fetch_all(MYSQLI_ASSOC); ?>
    <form method="POST" action="admin.php" class="inline-form">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="add_skill">
        <input type="text" name="skill_name" placeholder="New skill name" required maxlength="100">
        <select name="category" required>
            <?php foreach (['Technology', 'Creative', 'Academic', 'Lifestyle'] as $c): ?>
                <option><?php echo e($c); ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn-small">Add skill</button>
    </form>

    <div class="table-wrap"><table class="data-table">
        <tr><th>ID</th><th>Skill</th><th>Category</th><th></th></tr>
        <?php foreach ($skills as $s): ?>
        <tr>
            <td><?php echo (int)$s['skill_id']; ?></td>
            <td><?php echo e($s['skill_name']); ?></td>
            <td><?php echo e($s['category']); ?></td>
            <td class="actions">
                <form method="POST" action="admin.php" onsubmit="return confirm('Delete this skill? It will also be removed from users and requests.');">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="delete_skill">
                    <input type="hidden" name="skill_id" value="<?php echo (int)$s['skill_id']; ?>">
                    <button class="btn-small btn-danger">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table></div>

<?php else:
    $reqs = $conn->query("
        SELECT r.request_id, r.status, r.created_at,
               s.name AS sender, rc.name AS receiver,
               so.skill_name AS offered, sw.skill_name AS wanted
        FROM exchange_requests r
        JOIN users s   ON s.user_id  = r.sender_id
        JOIN users rc  ON rc.user_id = r.receiver_id
        JOIN skills so ON so.skill_id = r.offered_skill_id
        JOIN skills sw ON sw.skill_id = r.wanted_skill_id
        ORDER BY r.created_at DESC")->fetch_all(MYSQLI_ASSOC); ?>
    <?php if (!$reqs): ?>
        <p class="muted">No exchange requests yet.</p>
    <?php else: ?>
    <div class="table-wrap"><table class="data-table">
        <tr><th>ID</th><th>Sender</th><th>Receiver</th><th>Offered</th><th>Wanted</th><th>Date</th><th>Status</th></tr>
        <?php foreach ($reqs as $r): ?>
        <tr>
            <td><?php echo (int)$r['request_id']; ?></td>
            <td><?php echo e($r['sender']); ?></td>
            <td><?php echo e($r['receiver']); ?></td>
            <td><?php echo e($r['offered']); ?></td>
            <td><?php echo e($r['wanted']); ?></td>
            <td><?php echo e(date('d M Y', strtotime($r['created_at']))); ?></td>
            <td><span class="badge badge-<?php echo e(strtolower($r['status'])); ?>"><?php echo e($r['status']); ?></span></td>
        </tr>
        <?php endforeach; ?>
    </table></div>
    <?php endif; ?>
<?php endif; ?>
</section>
<?php include "includes/footer.php"; ?>
