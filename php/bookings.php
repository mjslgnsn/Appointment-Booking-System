<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db.php';

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? $action;
}

switch ($action) {
    case 'stats':           getStats();           break;
    case 'upcoming':        getUpcoming();        break;
    case 'calendar_events': getCalendarEvents();  break;
    case 'history':         getHistory();         break;
    case 'all':             getAllBookings();      break;
    case 'services':        getServices();        break;
    case 'slots':           getAvailableSlots();  break;
    case 'create':          createBooking($input ?? []); break;
    case 'cancel':          cancelBooking($input ?? []); break;
    case 'reschedule':      rescheduleBooking($input ?? []); break;
    case 'update_status':   updateStatus($input ?? []); break;
    default: jsonResponse(['success' => false, 'error' => 'Unknown action']);
}

function getStats() {
    $uid = requireAuth();
    $role = $_SESSION['role'] ?? 'customer';
    $db = getDB();

    if ($role === 'admin') {
        $rows = $db->query("
            SELECT
              COUNT(*) as total,
              SUM(status='completed') as completed,
              SUM(status IN ('pending','confirmed') AND booking_date >= CURDATE()) as upcoming,
              SUM(status='cancelled') as cancelled,
              SUM(CASE WHEN status='completed' THEN total_amount ELSE 0 END) as revenue
            FROM bookings
        ")->fetch();
    } else {
        $stmt = $db->prepare("
            SELECT
              COUNT(*) as total,
              SUM(status='completed') as completed,
              SUM(status IN ('pending','confirmed') AND booking_date >= CURDATE()) as upcoming,
              SUM(status='cancelled') as cancelled,
              SUM(CASE WHEN status='completed' THEN total_amount ELSE 0 END) as revenue
            FROM bookings WHERE user_id=?
        ");
        $stmt->execute([$uid]);
        $rows = $stmt->fetch();
    }

    jsonResponse(['success' => true, 'stats' => $rows]);
}

function getUpcoming() {
    $uid = requireAuth();
    $limit = (int)($_GET['limit'] ?? 5);
    $db = getDB();

    $stmt = $db->prepare("
        SELECT b.*, s.name as service_name, s.icon, s.duration_minutes
        FROM bookings b
        JOIN services s ON b.service_id = s.id
        WHERE b.user_id = ? AND b.status IN ('pending','confirmed') AND b.booking_date >= CURDATE()
        ORDER BY b.booking_date ASC, b.booking_time ASC
        LIMIT ?
    ");
    $stmt->execute([$uid, $limit]);
    jsonResponse(['success' => true, 'bookings' => $stmt->fetchAll()]);
}

function getCalendarEvents() {
    $uid = requireAuth();
    $role = $_SESSION['role'] ?? 'customer';
    $db = getDB();

    if ($role === 'admin') {
        $stmt = $db->query("
            SELECT b.id, b.booking_date, b.booking_time, b.status,
                   s.name as service_name, u.first_name, u.last_name
            FROM bookings b JOIN services s ON b.service_id=s.id JOIN users u ON b.user_id=u.id
            WHERE b.status != 'cancelled'
        ");
    } else {
        $stmt = $db->prepare("
            SELECT b.id, b.booking_date, b.booking_time, b.status,
                   s.name as service_name, u.first_name, u.last_name
            FROM bookings b JOIN services s ON b.service_id=s.id JOIN users u ON b.user_id=u.id
            WHERE b.user_id=?
        ");
        $stmt->execute([$uid]);
    }

    $events = [];
    foreach ($stmt->fetchAll() as $row) {
        $events[] = [
            'id'     => $row['id'],
            'title'  => $row['service_name'],
            'start'  => $row['booking_date'] . 'T' . $row['booking_time'],
            'status' => $row['status'],
            'extendedProps' => ['status' => $row['status']]
        ];
    }
    jsonResponse(['success' => true, 'events' => $events]);
}

function getHistory() {
    $uid = requireAuth();
    $db = getDB();
    $page = max(1, (int)($_GET['page'] ?? 1));
    $per  = 10;
    $offset = ($page - 1) * $per;
    $status = $_GET['status'] ?? '';

    $where = "b.user_id = ?";
    $params = [$uid];
    if ($status && $status !== 'all') {
        $where .= " AND b.status = ?";
        $params[] = $status;
    }

    $count = $db->prepare("SELECT COUNT(*) FROM bookings b WHERE $where");
    $count->execute($params);
    $total = $count->fetchColumn();

    $stmt = $db->prepare("
        SELECT b.*, s.name as service_name, s.icon, s.duration_minutes
        FROM bookings b JOIN services s ON b.service_id=s.id
        WHERE $where
        ORDER BY b.booking_date DESC, b.booking_time DESC
        LIMIT ? OFFSET ?
    ");
    $params[] = $per;
    $params[] = $offset;
    $stmt->execute($params);

    jsonResponse([
        'success'  => true,
        'bookings' => $stmt->fetchAll(),
        'total'    => (int)$total,
        'pages'    => ceil($total / $per),
        'page'     => $page
    ]);
}

function getAllBookings() {
    requireAdmin();
    $db = getDB();
    $status = $_GET['status'] ?? '';
    $search = $_GET['search'] ?? '';
    $page = max(1, (int)($_GET['page'] ?? 1));
    $per = 15;
    $offset = ($page - 1) * $per;

    $where = "1=1";
    $params = [];
    if ($status && $status !== 'all') { $where .= " AND b.status=?"; $params[] = $status; }
    if ($search) {
        $where .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR b.booking_ref LIKE ? OR s.name LIKE ?)";
        $like = "%$search%";
        array_push($params, $like, $like, $like, $like);
    }

    $count = $db->prepare("SELECT COUNT(*) FROM bookings b JOIN users u ON b.user_id=u.id JOIN services s ON b.service_id=s.id WHERE $where");
    $count->execute($params);
    $total = $count->fetchColumn();

    $stmt = $db->prepare("
        SELECT b.*, s.name as service_name, s.icon, s.duration_minutes,
               u.first_name, u.last_name, u.email, u.phone
        FROM bookings b
        JOIN services s ON b.service_id=s.id
        JOIN users u ON b.user_id=u.id
        WHERE $where
        ORDER BY b.booking_date DESC, b.booking_time DESC
        LIMIT ? OFFSET ?
    ");
    array_push($params, $per, $offset);
    $stmt->execute($params);

    jsonResponse([
        'success'  => true,
        'bookings' => $stmt->fetchAll(),
        'total'    => (int)$total,
        'pages'    => ceil($total / $per),
        'page'     => $page
    ]);
}

function getServices() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM services WHERE is_active=1 ORDER BY name");
    jsonResponse(['success' => true, 'services' => $stmt->fetchAll()]);
}

function getAvailableSlots() {
    $date      = $_GET['date'] ?? '';
    $serviceId = (int)($_GET['service_id'] ?? 0);
    if (!$date) jsonResponse(['success' => false, 'error' => 'Date required']);

    $db = getDB();

    // Get duration
    $svc = $db->prepare("SELECT duration_minutes FROM services WHERE id=?");
    $svc->execute([$serviceId]);
    $service = $svc->fetch();

    // Get booked slots for the date
    $booked = $db->prepare("SELECT TIME_FORMAT(booking_time,'%H:%i') as t FROM bookings WHERE booking_date=? AND status NOT IN ('cancelled') AND service_id=?");
    $booked->execute([$date, $serviceId]);
    $bookedTimes = array_column($booked->fetchAll(), 't');

    // Generate slots 9am–5pm, every 30min
    $slots = [];
    $start = strtotime("09:00");
    $end   = strtotime("17:00");
    $now   = time();
    $isToday = ($date === date('Y-m-d'));

    for ($t = $start; $t < $end; $t += 30 * 60) {
        $timeStr = date('H:i', $t);
        $displayTime = date('g:i A', $t);
        $isBooked = in_array($timeStr, $bookedTimes);
        $isPast   = $isToday && $t < ($now + 3600);
        $slots[] = [
            'time'      => $timeStr,
            'display'   => $displayTime,
            'available' => !$isBooked && !$isPast
        ];
    }

    jsonResponse(['success' => true, 'slots' => $slots, 'date' => $date]);
}

function createBooking($input) {
    $uid = requireAuth();
    $db  = getDB();

    $serviceId = (int)($input['service_id'] ?? 0);
    $date      = $input['date'] ?? '';
    $time      = $input['time'] ?? '';
    $notes     = trim($input['notes'] ?? '');

    if (!$serviceId || !$date || !$time) {
        jsonResponse(['success' => false, 'error' => 'Service, date, and time are required.']);
    }

    // Validate date is not in past
    if ($date < date('Y-m-d')) {
        jsonResponse(['success' => false, 'error' => 'Cannot book appointments in the past.']);
    }

    // Check slot still available
    $check = $db->prepare("SELECT id FROM bookings WHERE service_id=? AND booking_date=? AND booking_time=? AND status NOT IN ('cancelled')");
    $check->execute([$serviceId, $date, $time]);
    if ($check->fetch()) {
        jsonResponse(['success' => false, 'error' => 'This slot was just taken. Please choose another time.']);
    }

    // Get price
    $svc = $db->prepare("SELECT price, name FROM services WHERE id=?");
    $svc->execute([$serviceId]);
    $service = $svc->fetch();
    if (!$service) jsonResponse(['success' => false, 'error' => 'Service not found.']);

    // Generate reference
    $ref = 'APT-' . date('Y') . '-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);

    $stmt = $db->prepare("INSERT INTO bookings (booking_ref, user_id, service_id, booking_date, booking_time, status, notes, total_amount) VALUES (?,?,?,?,?,'confirmed',?,?)");
    $stmt->execute([$ref, $uid, $serviceId, $date, $time, $notes, $service['price']]);
    $bookingId = $db->lastInsertId();

    // Create notification
    $notif = $db->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?,'booking_confirmed','Booking Confirmed','Your ".addslashes($service['name'])." appointment on ".date('M j, Y', strtotime($date))." at ".date('g:i A', strtotime($time))." is confirmed.')");
    $notif->execute([$uid]);

    jsonResponse([
        'success'     => true,
        'booking_id'  => $bookingId,
        'booking_ref' => $ref,
        'message'     => 'Appointment booked successfully!'
    ]);
}

function cancelBooking($input) {
    $uid = requireAuth();
    $role = $_SESSION['role'] ?? 'customer';
    $db  = getDB();

    $bookingId = (int)($input['booking_id'] ?? 0);
    $reason    = trim($input['reason'] ?? '');

    $where = $role === 'admin' ? "id=?" : "id=? AND user_id=?";
    $params = $role === 'admin' ? [$bookingId] : [$bookingId, $uid];

    $stmt = $db->prepare("SELECT * FROM bookings WHERE $where");
    $stmt->execute($params);
    $booking = $stmt->fetch();

    if (!$booking) jsonResponse(['success' => false, 'error' => 'Booking not found.']);
    if ($booking['status'] === 'cancelled') jsonResponse(['success' => false, 'error' => 'Already cancelled.']);

    $update = $db->prepare("UPDATE bookings SET status='cancelled', cancelled_reason=? WHERE id=?");
    $update->execute([$reason, $bookingId]);

    jsonResponse(['success' => true, 'message' => 'Booking cancelled successfully.']);
}

function rescheduleBooking($input) {
    $uid = requireAuth();
    $db  = getDB();

    $bookingId = (int)($input['booking_id'] ?? 0);
    $newDate   = $input['new_date'] ?? '';
    $newTime   = $input['new_time'] ?? '';

    if (!$newDate || !$newTime) jsonResponse(['success' => false, 'error' => 'New date and time required.']);

    $stmt = $db->prepare("SELECT * FROM bookings WHERE id=? AND user_id=?");
    $stmt->execute([$bookingId, $uid]);
    $booking = $stmt->fetch();
    if (!$booking) jsonResponse(['success' => false, 'error' => 'Booking not found.']);

    // Check new slot
    $check = $db->prepare("SELECT id FROM bookings WHERE service_id=? AND booking_date=? AND booking_time=? AND status NOT IN ('cancelled') AND id!=?");
    $check->execute([$booking['service_id'], $newDate, $newTime, $bookingId]);
    if ($check->fetch()) jsonResponse(['success' => false, 'error' => 'That slot is not available.']);

    $update = $db->prepare("UPDATE bookings SET booking_date=?, booking_time=?, status='confirmed' WHERE id=?");
    $update->execute([$newDate, $newTime, $bookingId]);

    jsonResponse(['success' => true, 'message' => 'Booking rescheduled successfully.']);
}

function updateStatus($input) {
    requireAdmin();
    $db = getDB();

    $bookingId = (int)($input['booking_id'] ?? 0);
    $status    = $input['status'] ?? '';
    $allowed   = ['pending','confirmed','cancelled','completed'];

    if (!in_array($status, $allowed)) jsonResponse(['success' => false, 'error' => 'Invalid status.']);

    $stmt = $db->prepare("UPDATE bookings SET status=? WHERE id=?");
    $stmt->execute([$status, $bookingId]);

    jsonResponse(['success' => true, 'message' => 'Status updated.']);
}
