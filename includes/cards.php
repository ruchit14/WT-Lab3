<?php
// Shared pieces for listing students (used by students.php and dashboard.php).

function avatar_class(int $id): string {
    $colors = ['purple', 'yellow', 'blue', 'green'];
    return $colors[$id % 4];
}

function initial(string $name): string {
    return strtoupper(mb_substr($name, 0, 1));
}

function split_list(?string $value): array {
    return ($value === null || $value === '') ? [] : explode('||', $value);
}

// Small line icons (inline SVG, colour comes from CSS via currentColor)
function icon(string $name): string {
    $paths = [
        'lightbulb' => '<path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"/><path d="M9 18h6"/><path d="M10 22h4"/>',
        'book'      => '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>',
        'clock'     => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        'check'     => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
        'swap'      => '<path d="M17 1l4 4-4 4"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><path d="M7 23l-4-4 4-4"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/>',
    ];
    return '<svg viewBox="0 0 24 24" aria-hidden="true">' . ($paths[$name] ?? '') . '</svg>';
}

// $highlight = skill names to show as "match" chips
function chips(array $items, string $cls = '', array $highlight = []): string {
    $html = '';
    foreach ($items as $item) {
        $extra = in_array($item, $highlight, true) ? ' match' : '';
        $html .= '<span class="chip ' . $cls . $extra . '">' . e($item) . '</span>';
    }
    if ($html === '') {
        $html = '<span class="chip empty">Nothing yet</span>';
    }
    return '<div class="chips">' . $html . '</div>';
}

/*
 * Returns the other students with their offered/wanted skills and two match counts:
 *   they_teach_me = skills THEY offer that I want
 *   i_teach_them  = skills I offer that THEY want
 * A "perfect match" is when both are above zero.
 */
function fetch_students(mysqli $conn, int $uid, string $q = '', int $limit = 0, bool $matches_only = false): array {
    $inner = "
        SELECT u.user_id, u.name, u.course, u.year,
            (SELECT GROUP_CONCAT(s.skill_name ORDER BY s.skill_name SEPARATOR '||')
               FROM user_skills us JOIN skills s ON s.skill_id = us.skill_id
              WHERE us.user_id = u.user_id AND us.type = 'OFFER') AS offers,
            (SELECT GROUP_CONCAT(s.skill_name ORDER BY s.skill_name SEPARATOR '||')
               FROM user_skills us JOIN skills s ON s.skill_id = us.skill_id
              WHERE us.user_id = u.user_id AND us.type = 'WANT') AS wants,
            (SELECT COUNT(*) FROM user_skills a JOIN user_skills b ON b.skill_id = a.skill_id
              WHERE a.user_id = u.user_id AND a.type = 'OFFER' AND b.user_id = ? AND b.type = 'WANT') AS they_teach_me,
            (SELECT COUNT(*) FROM user_skills a JOIN user_skills b ON b.skill_id = a.skill_id
              WHERE a.user_id = u.user_id AND a.type = 'WANT' AND b.user_id = ? AND b.type = 'OFFER') AS i_teach_them
        FROM users u
        WHERE u.role = 'student' AND u.user_id <> ?";

    $types  = "iii";
    $params = [$uid, $uid, $uid];

    if ($q !== '') {
        $inner .= " AND EXISTS (
                    SELECT 1 FROM user_skills us2 JOIN skills s2 ON s2.skill_id = us2.skill_id
                     WHERE us2.user_id = u.user_id AND s2.skill_name LIKE ?)";
        $types   .= "s";
        $params[] = "%" . $q . "%";
    }

    $sql = "SELECT * FROM ($inner) t";
    if ($matches_only) {
        $sql .= " WHERE they_teach_me > 0 AND i_teach_them > 0";
    }
    $sql .= " ORDER BY (they_teach_me > 0 AND i_teach_them > 0) DESC, (they_teach_me > 0 OR i_teach_them > 0) DESC, name";
    if ($limit > 0) {
        $sql .= " LIMIT " . (int)$limit;
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function student_card(array $s): string {
    $a = (int)$s['they_teach_me'] > 0;
    $b = (int)$s['i_teach_them'] > 0;

    $badge = '';
    if ($a && $b)  $badge = '<span class="match-badge">⇄ PERFECT MATCH</span>';
    elseif ($a)    $badge = '<span class="match-badge soft">TEACHES WHAT YOU WANT</span>';
    elseif ($b)    $badge = '<span class="match-badge soft">WANTS WHAT YOU TEACH</span>';

    $id = (int)$s['user_id'];
    $h  = '<a class="student-card' . ($a && $b ? ' is-match' : '') . '" href="student.php?id=' . $id . '">';
    $h .= '<div class="sc-top"><div class="avatar ' . avatar_class($id) . '">' . e(initial($s['name'])) . '</div>';
    $h .= '<div><h3>' . e($s['name']) . '</h3><p class="muted">' . e($s['course']) . ' · ' . e($s['year']) . '</p></div></div>';
    $h .= $badge;
    $h .= '<div><span class="sc-label teach">TEACHES</span>' . chips(split_list($s['offers'])) . '</div>';
    $h .= '<div><span class="sc-label want">WANTS TO LEARN</span>' . chips(split_list($s['wants']), 'want') . '</div>';
    $h .= '<span class="sc-link">View profile →</span></a>';
    return $h;
}
