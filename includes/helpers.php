<?php
// Shared helpers: session, escaping, flash messages, CSRF, login/admin checks.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../db.php";

// Short name for htmlspecialchars (prevents XSS when printing user data)
function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void {
    header("Location: " . $url);
    exit();
}

// ---- Flash messages (shown once after a redirect) ----
function flash(string $type, string $text): void {
    $_SESSION['flash'] = ['type' => $type, 'text' => $text];
}

function show_flash(): void {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        echo '<div class="flash flash-' . e($f['type']) . '">' . e($f['text']) . '</div>';
    }
}

// ---- CSRF protection ----
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

// Call at the top of any POST handler. Stops the request if the token is wrong.
function csrf_check(): void {
    $sent = $_POST['csrf'] ?? '';
    if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $sent)) {
        http_response_code(403);
        die("Invalid request (CSRF check failed). Go back, refresh the page and try again.");
    }
}

// ---- Access control ----
function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

function require_login(): void {
    if (!is_logged_in()) {
        flash('error', 'Please log in first.');
        redirect('login.php');
    }
}

// The role is re-read from the database so a stale session cannot keep admin rights.
function require_admin(): void {
    global $conn;
    require_login();
    $stmt = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row || $row['role'] !== 'admin') {
        http_response_code(403);
        die("Access denied. Administrators only.");
    }
}

// Student-only pages: admins are sent to the admin panel instead.
function require_student(): void {
    require_login();
    if (is_admin()) {
        redirect('admin.php');
    }
}

function current_user_id(): int {
    return (int)($_SESSION['user_id'] ?? 0);
}

function is_admin(): bool {
    return ($_SESSION['role'] ?? '') === 'admin';
}

require_once __DIR__ . "/cards.php";
