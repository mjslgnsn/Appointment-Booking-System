<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'monthly_revenue': monthlyRevenue(); break;
    case 'service_revenue': serviceRevenue(); break;
    default: jsonResponse(['success' => false, 'error' => 'Unknown action']);
}

function monthlyRevenue() {
    requireAdmin();
    $db   = getDB();
    $year = (int)($_GET['year'] ?? date('Y'));

    $stmt = $db->prepare("
        SELECT
          DATE_FORMAT(booking_date,'%b') as month,
          MONTH(booking_date) as month_num,
          COUNT(*) as count,
          SUM(CASE WHEN status='completed' THEN total_amount ELSE 0 END) as revenue
        FROM bookings
        WHERE YEAR(booking_date) = ? AND status != 'cancelled'
        GROUP BY MONTH(booking_date), DATE_FORMAT(booking_date,'%b')
        ORDER BY month_num
    ");
    $stmt->execute([$year]);
    $byMonth = [];
    foreach ($stmt->fetchAll() as $row) {
        $byMonth[(int)$row['month_num']] = $row;
    }

    $months = [];
    for ($m = 1; $m <= 12; $m++) {
        $months[] = [
            'month'   => date('M', mktime(0,0,0,$m,1)),
            'count'   => (int)($byMonth[$m]['count'] ?? 0),
            'revenue' => (float)($byMonth[$m]['revenue'] ?? 0),
        ];
    }

    // Totals
    $totals = $db->prepare("
        SELECT COUNT(*) as total_bookings,
               SUM(CASE WHEN status='completed' THEN total_amount ELSE 0 END) as total_revenue
        FROM bookings WHERE YEAR(booking_date)=? AND status!='cancelled'
    ");
    $totals->execute([$year]);
    $t = $totals->fetch();

    // Status breakdown
    $bk = $db->prepare("SELECT status, COUNT(*) as cnt FROM bookings WHERE YEAR(booking_date)=? GROUP BY status");
    $bk->execute([$year]);
    $breakdown = [];
    foreach ($bk->fetchAll() as $row) $breakdown[$row['status']] = (int)$row['cnt'];

    jsonResponse([
        'success'          => true,
        'months'           => $months,
        'total_bookings'   => (int)($t['total_bookings'] ?? 0),
        'total_revenue'    => (float)($t['total_revenue'] ?? 0),
        'status_breakdown' => $breakdown,
    ]);
}

function serviceRevenue() {
    requireAdmin();
    $db   = getDB();
    $year = (int)($_GET['year'] ?? date('Y'));

    $stmt = $db->prepare("
        SELECT s.name, s.icon,
               COUNT(b.id) as count,
               SUM(CASE WHEN b.status='completed' THEN b.total_amount ELSE 0 END) as revenue
        FROM services s
        LEFT JOIN bookings b ON b.service_id=s.id AND YEAR(b.booking_date)=? AND b.status!='cancelled'
        GROUP BY s.id, s.name, s.icon
        ORDER BY revenue DESC
    ");
    $stmt->execute([$year]);
    jsonResponse(['success' => true, 'services' => $stmt->fetchAll()]);
}
