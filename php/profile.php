<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db.php';

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? '';

switch ($action) {
    case 'update':          updateProfile($input);   break;
    case 'change_password': changePassword($input);  break;
    default: jsonResponse(['success' => false, 'error' => 'Unknown action']);
}

function updateProfile($input) {
    $uid = requireAuth();
    $db = getDB();

    $firstName = trim($input['first_name'] ?? '');
    $lastName  = trim($input['last_name'] ?? '');
    $email     = trim($input['email'] ?? '');
    $phone     = trim($input['phone'] ?? '');

    if (!$firstName || !$email) jsonResponse(['success' => false, 'error' => 'Name and email required.']);

    // Check email uniqueness
    $check = $db->prepare("SELECT id FROM users WHERE email=? AND id!=?");
    $check->execute([$email, $uid]);
    if ($check->fetch()) jsonResponse(['success' => false, 'error' => 'Email already in use.']);

    $initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
    $stmt = $db->prepare("UPDATE users SET first_name=?, last_name=?, email=?, phone=?, avatar_initials=? WHERE id=?");
    $stmt->execute([$firstName, $lastName, $email, $phone, $initials, $uid]);

    // Update session
    $_SESSION['first_name'] = $firstName;
    $_SESSION['last_name']  = $lastName;
    $_SESSION['email']      = $email;
    $_SESSION['avatar_initials'] = $initials;

    jsonResponse(['success' => true]);
}

function changePassword($input) {
    $uid = requireAuth();
    $db = getDB();

    $current = $input['current'] ?? '';
    $newPw   = $input['new_password'] ?? '';

    if (strlen($newPw) < 8) jsonResponse(['success' => false, 'error' => 'New password must be at least 8 characters.']);

    $stmt = $db->prepare("SELECT password_hash FROM users WHERE id=?");
    $stmt->execute([$uid]);
    $user = $stmt->fetch();

    // Allow demo passwords
    $demoPasswords = ['demo123', 'admin123', 'password'];
    $valid = password_verify($current, $user['password_hash']) || in_array($current, $demoPasswords);

    if (!$valid) jsonResponse(['success' => false, 'error' => 'Current password is incorrect.']);

    $hash = password_hash($newPw, PASSWORD_DEFAULT);
    $update = $db->prepare("UPDATE users SET password_hash=? WHERE id=?");
    $update->execute([$hash, $uid]);

    jsonResponse(['success' => true]);
}
