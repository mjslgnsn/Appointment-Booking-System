<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db.php';

$action = $_GET['action'] ?? '';
$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? $action;

switch ($action) {
    case 'customer_count':   customerCount();   break;
    case 'monthly_stats':    monthlyStats();    break;
    case 'service_stats':    serviceStats();    break;
    case 'customers':        getCustomers();    break;
    case 'get_services':     getServices();     break;
    case 'save_service':     saveService($input); break;
    case 'toggle_service':   toggleService($input); break;
    case 'delete_service':   deleteService($input); break;
    default: jsonResponse(['success' => false, 'error' => 'Unknown action']);
}

function customerCount() {
    requireAdmin();
    $db = getDB();
    $count = $db->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();
    jsonResponse(['success' => true, 'count' => (int)$count]);
}

function monthlyStats() {
    requireAdmin();
    $db = getDB();

    // Monthly bookings for current year
    $stmt = $db->query("
        SELECT DATE_FORMAT(booking_date,'%b') as month,
               MONTH(booking_date) as month_num,
               COUNT(*) as count
        FROM bookings
        WHERE YEAR(booking_date) = YEAR(CURDATE())
        GROUP BY MONTH(booking_date), DATE_FORMAT(booking_date,'%b')
        ORDER BY month_num
    ");
    $monthData = [];
    $byMonth = [];
    foreach ($stmt->fetchAll() as $row) {
        $byMonth[$row['month_num']] = $row;
    }

    for ($m = 1; $m <= 12; $m++) {
        $monthData[] = [
            'month' => date('M', mktime(0,0,0,$m,1)),
            'count' => (int)($byMonth[$m]['count'] ?? 0)
        ];
    }

    // Status breakdown
    $breakdown = [];
    $bkStat = $db->query("SELECT status, COUNT(*) as cnt FROM bookings GROUP BY status");
    foreach ($bkStat->fetchAll() as $row) {
        $breakdown[$row['status']] = (int)$row['cnt'];
    }

    jsonResponse(['success' => true, 'months' => $monthData, 'status_breakdown' => $breakdown]);
}

function serviceStats() {
    requireAdmin();
    $db = getDB();
    $stmt = $db->query("
        SELECT s.name, s.icon, COUNT(b.id) as count
        FROM services s
        LEFT JOIN bookings b ON b.service_id=s.id AND b.status != 'cancelled'
        GROUP BY s.id, s.name, s.icon
        ORDER BY count DESC
    ");
    jsonResponse(['success' => true, 'services' => $stmt->fetchAll()]);
}

function getCustomers() {
    requireAdmin();
    $db = getDB();
    $search = $_GET['search'] ?? '';
    $page = max(1, (int)($_GET['page'] ?? 1));
    $per = 15;
    $offset = ($page-1) * $per;

    $where = "role='customer'";
    $params = [];
    if ($search) {
        $where .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
        $like = "%$search%";
        array_push($params, $like, $like, $like);
    }

    $total = $db->prepare("SELECT COUNT(*) FROM users WHERE $where");
    $total->execute($params);
    $totalCount = $total->fetchColumn();

    $stmt = $db->prepare("
        SELECT u.*, COUNT(b.id) as booking_count,
               MAX(b.booking_date) as last_booking
        FROM users u
        LEFT JOIN bookings b ON b.user_id=u.id
        WHERE $where
        GROUP BY u.id
        ORDER BY u.created_at DESC
        LIMIT ? OFFSET ?
    ");
    array_push($params, $per, $offset);
    $stmt->execute($params);

    jsonResponse([
        'success'   => true,
        'customers' => $stmt->fetchAll(),
        'total'     => (int)$totalCount,
        'pages'     => ceil($totalCount / $per),
        'page'      => $page
    ]);
}

function getServices() {
    requireAdmin();
    $db = getDB();
    $stmt = $db->query("SELECT * FROM services ORDER BY name");
    jsonResponse(['success' => true, 'services' => $stmt->fetchAll()]);
}

function saveService($input) {
    requireAdmin();
    $db = getDB();

    $id    = (int)($input['id'] ?? 0);
    $name  = trim($input['name'] ?? '');
    $desc  = trim($input['description'] ?? '');
    $dur   = (int)($input['duration_minutes'] ?? 60);
    $price = floatval($input['price'] ?? 0);
    $icon  = trim($input['icon'] ?? '🗓️');

    if (!$name) jsonResponse(['success' => false, 'error' => 'Service name required.']);

    if ($id) {
        $stmt = $db->prepare("UPDATE services SET name=?,description=?,duration_minutes=?,price=?,icon=? WHERE id=?");
        $stmt->execute([$name, $desc, $dur, $price, $icon, $id]);
    } else {
        $stmt = $db->prepare("INSERT INTO services (name,description,duration_minutes,price,icon) VALUES (?,?,?,?,?)");
        $stmt->execute([$name, $desc, $dur, $price, $icon]);
    }

    jsonResponse(['success' => true]);
}

function toggleService($input) {
    requireAdmin();
    $db = getDB();
    $id = (int)($input['id'] ?? 0);
    $stmt = $db->prepare("UPDATE services SET is_active = !is_active WHERE id=?");
    $stmt->execute([$id]);
    jsonResponse(['success' => true]);
}

function deleteService($input) {
    requireAdmin();
    $db = getDB();
    $id = (int)($input['id'] ?? 0);
    $stmt = $db->prepare("DELETE FROM services WHERE id=?");
    $stmt->execute([$id]);
    jsonResponse(['success' => true]);
}
