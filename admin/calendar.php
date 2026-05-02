<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../index.php'); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Calendar — Appoint Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<button class="hamburger" onclick="toggleSidebar()"><span></span><span></span><span></span></button>
<div class="app-layout">
  <?php include '../includes/sidebar.php'; ?>
  <main class="main-content">
    <div class="topbar">
      <span class="topbar-title">Calendar View</span>
      <div class="topbar-actions">
        <div class="flex gap-2 items-center">
          <span style="display:flex;align-items:center;gap:6px;font-size:0.78rem;"><span style="width:10px;height:10px;border-radius:50%;background:var(--success);display:inline-block;"></span>Confirmed</span>
          <span style="display:flex;align-items:center;gap:6px;font-size:0.78rem;"><span style="width:10px;height:10px;border-radius:50%;background:var(--gold);display:inline-block;"></span>Pending</span>
          <span style="display:flex;align-items:center;gap:6px;font-size:0.78rem;"><span style="width:10px;height:10px;border-radius:50%;background:var(--danger);display:inline-block;"></span>Cancelled</span>
        </div>
      </div>
    </div>
    <div class="page-content">
      <div class="card">
        <div class="card-body" style="padding:0;">
          <div id="fullAdminCal" style="padding:20px;"></div>
        </div>
      </div>
    </div>
  </main>
</div>

<!-- Event Detail Modal -->
<div class="modal-overlay" id="eventModal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Appointment Details</span>
      <button class="modal-close" onclick="closeModal('eventModal')">×</button>
    </div>
    <div class="modal-body" id="eventModalBody"></div>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="closeModal('eventModal')">Close</button>
      <a href="appointments.php" class="btn-primary">Manage Appointments</a>
    </div>
  </div>
</div>

<div class="toast-container" id="toastContainer"></div>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.js"></script>
<script src="../js/app.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  fetch('../php/bookings.php?action=calendar_events')
    .then(r => r.json())
    .then(data => {
      const cal = new FullCalendar.Calendar(document.getElementById('fullAdminCal'), {
        initialView: 'dayGridMonth',
        headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,listWeek' },
        height: 'auto',
        events: (data.events || []).map(e => ({
          ...e,
          backgroundColor: e.extendedProps.status === 'confirmed' ? 'var(--success)' :
                            e.extendedProps.status === 'cancelled' ? 'var(--danger)' : 'var(--gold)',
          borderColor: 'transparent',
          textColor: e.extendedProps.status === 'pending' ? 'var(--ink)' : '#fff',
        })),
        eventClick: (info) => {
          const e = info.event;
          document.getElementById('eventModalBody').innerHTML = `
            <div class="email-booking-box">
              <div class="email-booking-row"><span class="email-booking-label">Service</span><span class="email-booking-val">${e.title}</span></div>
              <div class="email-booking-row"><span class="email-booking-label">Date</span><span class="email-booking-val">${formatDate(e.startStr.split('T')[0])}</span></div>
              <div class="email-booking-row"><span class="email-booking-label">Time</span><span class="email-booking-val">${formatTime(e.startStr.split('T')[1]?.slice(0,5))}</span></div>
              <div class="email-booking-row"><span class="email-booking-label">Status</span><span class="email-booking-val">${statusBadge(e.extendedProps.status)}</span></div>
            </div>
          `;
          openModal('eventModal');
        }
      });
      cal.render();
    });
});
</script>
</body>
</html>
