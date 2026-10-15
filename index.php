<?php
$page_title = "Student Skill Exchange";
include "includes/header.php";

// Live number of students
$row = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role = 'student'")->fetch_assoc();
$student_count = (int)$row['c'];

// Two real students shown as a sample match (offer/want come from the database)
$sample = $conn->query("
    SELECT u.user_id, u.name,
        (SELECT GROUP_CONCAT(s.skill_name SEPARATOR ' • ') FROM user_skills us JOIN skills s ON s.skill_id = us.skill_id WHERE us.user_id = u.user_id AND us.type = 'OFFER') AS offers,
        (SELECT GROUP_CONCAT(s.skill_name SEPARATOR ', ')  FROM user_skills us JOIN skills s ON s.skill_id = us.skill_id WHERE us.user_id = u.user_id AND us.type = 'WANT')  AS wants
    FROM users u
    WHERE u.role = 'student'
    ORDER BY u.user_id DESC
    LIMIT 2
")->fetch_all(MYSQLI_ASSOC);
?>
<section class="hero" id="home">
    <div class="hero-content">
        <div class="mini-badge"><span></span> STUDENTS HELPING STUDENTS</div>

        <h1>Don't just <em>learn.</em><br><strong>Exchange.</strong></h1>

        <p>Share what you know, discover what others can teach, and build useful skills together.</p>

        <div class="hero-buttons">
            <a href="students.php" class="primary-btn">Find a Skill <span>↗</span></a>
            <?php if (is_logged_in()): ?>
                <a href="skills.php" class="secondary-btn">Offer Your Skill</a>
            <?php else: ?>
                <a href="register.php" class="secondary-btn">Offer Your Skill</a>
            <?php endif; ?>
        </div>

        <div class="student-count">
            <div class="avatars"><span>A</span><span>M</span><span>R</span><span>+</span></div>
            <p><strong><?php echo $student_count; ?></strong> students already sharing skills</p>
        </div>
    </div>

    <div class="hero-art">
        <?php if (count($sample) === 2): ?>
        <div class="simple-match-preview">
            <?php foreach ($sample as $i => $s): ?>
                <?php if ($i === 1): ?><div class="swap-icon">⇄</div><?php endif; ?>
                <div class="preview-card <?php echo $i === 1 ? 'highlight-card' : ''; ?>">
                    <div class="preview-badge">TEACHES</div>
                    <h3><?php echo e($s['name']); ?></h3>
                    <p class="skill-tag"><?php echo e($s['offers'] ?: '—'); ?></p>
                    <small>Wants: <?php echo e($s['wants'] ?: '—'); ?></small>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php include "includes/footer.php"; ?>
