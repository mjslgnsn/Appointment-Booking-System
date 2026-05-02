<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db.php';

$action = $_GET['action'] ?? '';
$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? $action;

switch ($action) {
    case 'list':      listNotifications(); break;
    case 'mark_read': markRead();          break;
    default: jsonResponse(['success' => false, 'error' => 'Unknown action']);
}

function listNotifications() {
    $uid = requireAuth();
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 20");
    $stmt->execute([$uid]);
    jsonResponse(['success' => true, 'notifications' => $stmt->fetchAll()]);
}

function markRead() {
    $uid = requireAuth();
    $db = getDB();
    $stmt = $db->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?");
    $stmt->execute([$uid]);
    jsonResponse(['success' => true]);
}
