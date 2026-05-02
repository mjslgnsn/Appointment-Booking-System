<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
$isAdmin = ($_SESSION['role'] ?? 'customer') === 'admin';
if ($isAdmin) {
    header('Location: admin/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard — Appoint</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

<button class="hamburger" onclick="toggleSidebar()">
  <span></span><span></span><span></span>
</button>

<div class="app-layout">
  <!-- SIDEBAR -->
  <?php include 'includes/sidebar.php'; ?>

  <!-- MAIN -->
  <main class="main-content">
    <!-- Topbar -->
    <div class="topbar">
      <span class="topbar-title">Dashboard</span>
      <div class="topbar-actions">
        <button class="topbar-btn" onclick="showNotifications()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="18" height="18"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
          <span class="notif-badge" id="notifBadge">2</span>
        </button>
        <a href="booking.php" class="btn-primary gold">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M12 5v14M5 12h14"/></svg>
          Book Appointment
        </a>
      </div>
    </div>

    <div class="page-content">
      <!-- Welcome -->
      <div class="mb-8">
        <h2 class="section-title">Good morning, <em><?= htmlspecialchars($_SESSION['first_name'] ?? 'there') ?></em></h2>
        <p class="section-sub">Here's an overview of your appointments and activity.</p>
      </div>

      <!-- Stats -->
      <div class="stats-grid mb-8">
        <div class="stat-card">
          <div class="stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          </div>
          <div class="stat-value" id="statTotal">—</div>
          <div class="stat-label">Total Bookings</div>
        </div>
        <div class="stat-card green">
          <div class="stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <div class="stat-value" id="statCompleted">—</div>
          <div class="stat-label">Completed</div>
        </div>
        <div class="stat-card blue">
          <div class="stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          </div>
          <div class="stat-value" id="statUpcoming">—</div>
          <div class="stat-label">Upcoming</div>
        </div>
        <div class="stat-card danger">
          <div class="stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
          </div>
          <div class="stat-value" id="statCancelled">—</div>
          <div class="stat-label">Cancelled</div>
        </div>
      </div>

      <!-- Grid -->
      <div class="grid-2 mb-8" id="dashGrid">
        <!-- Upcoming -->
        <div class="card">
          <div class="card-header">
            <span class="card-title">Upcoming Appointments</span>
            <a href="bookings.php" class="btn-secondary" style="font-size:0.8rem;padding:7px 14px;">View All</a>
          </div>
          <div class="card-body" id="upcomingList">
            <div class="text-mute">Loading...</div>
          </div>
        </div>

        <!-- Mini Calendar -->
        <div class="card">
          <div class="card-header">
            <span class="card-title">My Calendar</span>
          </div>
          <div class="card-body">
            <div id="miniCal"></div>
          </div>
        </div>
      </div>

      <!-- Email Notification Preview -->
      <div class="card mb-8">
        <div class="card-header">
          <span class="card-title">Email Notifications</span>
          <span class="badge badge-confirmed">Active</span>
        </div>
        <div class="card-body">
          <p class="text-mute mb-4">Preview of appointment confirmation emails sent to customers.</p>
          <div class="email-preview" id="emailPreviewBox">
            <div class="email-header">
              <div class="email-meta">From: noreply@appoint.com &nbsp;→&nbsp; <?= htmlspecialchars($_SESSION['email'] ?? 'you@example.com') ?></div>
              <div class="email-subject">Appointment Confirmed — Loading details...</div>
            </div>
            <div class="email-body">
              <div class="email-logo">Appoint</div>
              <div class="email-greeting">Hi <?= htmlspecialchars($_SESSION['first_name'] ?? 'Customer') ?>, your appointment is confirmed!</div>
              <p class="text-mute mb-4">Here are your booking details:</p>
              <div class="email-booking-box">
                <div class="email-booking-row">
                  <span class="email-booking-label">Service</span>
                  <span class="email-booking-val" id="emailService">—</span>
                </div>
                <div class="email-booking-row">
                  <span class="email-booking-label">Date</span>
                  <span class="email-booking-val" id="emailDate">—</span>
                </div>
                <div class="email-booking-row">
                  <span class="email-booking-label">Time</span>
                  <span class="email-booking-val" id="emailTime">—</span>
                </div>
                <div class="email-booking-row">
                  <span class="email-booking-label">Duration</span>
                  <span class="email-booking-val" id="emailDuration">—</span>
                </div>
                <div class="email-booking-row">
                  <span class="email-booking-label">Amount</span>
                  <span class="email-booking-val" id="emailAmount">—</span>
                </div>
              </div>
              <p class="text-mute">Please arrive 5 minutes early. To cancel or reschedule, visit your dashboard.</p>
              <div style="margin-top:24px;">
                <a href="#" class="btn-primary" style="font-size:0.85rem;">View Booking Details</a>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>

<!-- Notifications Panel -->
<div class="modal-overlay" id="notifOverlay" onclick="closeNotif(event)">
  <div class="modal" style="max-width:400px;">
    <div class="modal-header">
      <span class="modal-title">Notifications</span>
      <button class="modal-close" onclick="closeNotif()">×</button>
    </div>
    <div class="modal-body" id="notifList"></div>
  </div>
</div>

<div class="toast-container" id="toastContainer"></div>

<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.css" rel="stylesheet"/>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.js"></script>
<script src="js/app.js"></script>
<script src="js/dashboard.js"></script>
</body>
</html>
