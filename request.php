<?php
require_once "includes/helpers.php";
require_student();

// This page only handles the form from student.php
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirect('students.php');
}
csrf_check();

$sender_id   = current_user_id();                       // from the session, not the form
$receiver_id = (int)($_POST['receiver_id'] ?? 0);
$offered_id  = (int)($_POST['offered_skill_id'] ?? 0);
$wanted_id   = (int)($_POST['wanted_skill_id'] ?? 0);
$message     = trim($_POST['message'] ?? '');

$back = "student.php?id=" . $receiver_id;

function fail(string $msg, string $back): void {
    flash('error', $msg);
    redirect($back);
}

if ($receiver_id === $sender_id) {
    fail('You cannot send a request to yourself.', 'students.php');
}
if (strlen($message) > 300) {
    fail('Message is too long (max 300 characters).', $back);
}

// 1. Receiver must be a real student
$stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = ? AND role = 'student'");
$stmt->bind_param("i", $receiver_id);
$stmt->execute();
if (!$stmt->get_result()->fetch_assoc()) {
    fail('Student not found.', 'students.php');
}

// 2. The offered skill must be in MY OFFER list
$stmt = $conn->prepare("SELECT 1 FROM user_skills WHERE user_id = ? AND skill_id = ? AND type = 'OFFER'");
$stmt->bind_param("ii", $sender_id, $offered_id);
$stmt->execute();
if (!$stmt->get_result()->fetch_assoc()) {
    fail('You can only offer a skill from your own "can teach" list.', $back);
}

// 3. The skill I want to learn must be one THEY teach (their OFFER list)
$stmt = $conn->prepare("SELECT 1 FROM user_skills WHERE user_id = ? AND skill_id = ? AND type = 'OFFER'");
$stmt->bind_param("ii", $receiver_id, $wanted_id);
$stmt->execute();
if (!$stmt->get_result()->fetch_assoc()) {
    fail('That student does not teach the selected skill.', $back);
}

// 4. No duplicate pending request for the same swap
$stmt = $conn->prepare("SELECT 1 FROM exchange_requests
    WHERE sender_id = ? AND receiver_id = ? AND offered_skill_id = ? AND wanted_skill_id = ? AND status = 'PENDING'");
$stmt->bind_param("iiii", $sender_id, $receiver_id, $offered_id, $wanted_id);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()) {
    fail('You already have a pending request for this swap.', $back);
}

// 5. Save it (status defaults to PENDING)
$stmt = $conn->prepare("INSERT INTO exchange_requests (sender_id, receiver_id, offered_skill_id, wanted_skill_id, message, status)
                        VALUES (?, ?, ?, ?, ?, 'PENDING')");
$stmt->bind_param("iiiis", $sender_id, $receiver_id, $offered_id, $wanted_id, $message);
if ($stmt->execute()) {
    flash('success', 'Exchange request sent!');
    redirect('requests.php');
}
fail('Could not send the request. Please try again.', $back);
