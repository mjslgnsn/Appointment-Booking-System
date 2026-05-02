<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Bookings — Appoint</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<button class="hamburger" onclick="toggleSidebar()"><span></span><span></span><span></span></button>
<div class="app-layout">
  <?php include 'includes/sidebar.php'; ?>
  <main class="main-content">
    <div class="topbar">
      <span class="topbar-title">Booking History</span>
      <div class="topbar-actions">
        <a href="booking.php" class="btn-primary gold">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><path d="M12 5v14M5 12h14"/></svg>
          New Booking
        </a>
      </div>
    </div>

    <div class="page-content">
      <!-- Filter Bar -->
      <div class="flex items-center gap-3 mb-6" style="flex-wrap:wrap;">
        <button class="filter-btn active" onclick="filterStatus('all', this)">All</button>
        <button class="filter-btn" onclick="filterStatus('confirmed', this)">Confirmed</button>
        <button class="filter-btn" onclick="filterStatus('pending', this)">Pending</button>
        <button class="filter-btn" onclick="filterStatus('completed', this)">Completed</button>
        <button class="filter-btn" onclick="filterStatus('cancelled', this)">Cancelled</button>
      </div>

      <div class="card">
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>Reference</th>
                <th>Service</th>
                <th>Date</th>
                <th>Time</th>
                <th>Duration</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="bookingsBody">
              <tr><td colspan="8" class="text-mute" style="text-align:center;padding:40px;">Loading...</td></tr>
            </tbody>
          </table>
        </div>
        <!-- Pagination -->
        <div style="padding:16px 20px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
          <span class="text-mute" id="pageInfo"></span>
          <div class="flex gap-2" id="pagination"></div>
        </div>
      </div>
    </div>
  </main>
</div>

<!-- Cancel Modal -->
<div class="modal-overlay" id="cancelModal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Cancel Booking</span>
      <button class="modal-close" onclick="closeModal('cancelModal')">×</button>
    </div>
    <div class="modal-body">
      <p class="text-mute mb-4">Are you sure you want to cancel this appointment? This action cannot be undone.</p>
      <div class="form-group">
        <label>Reason (Optional)</label>
        <textarea id="cancelReason" rows="3" placeholder="Let us know why you're cancelling..."></textarea>
      </div>
      <input type="hidden" id="cancelBookingId">
    </div>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="closeModal('cancelModal')">Keep Booking</button>
      <button class="btn-danger" onclick="submitCancel()">Cancel Appointment</button>
    </div>
  </div>
</div>

<!-- Reschedule Modal -->
<div class="modal-overlay" id="rescheduleModal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Reschedule Booking</span>
      <button class="modal-close" onclick="closeModal('rescheduleModal')">×</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label>New Date</label>
        <input type="date" id="rescheduleDate" min="<?= date('Y-m-d') ?>">
      </div>
      <div class="form-group">
        <label>New Time</label>
        <select id="rescheduleTime">
          <option value="">-- Select a time --</option>
        </select>
      </div>
      <input type="hidden" id="rescheduleBookingId">
      <input type="hidden" id="rescheduleServiceId">
    </div>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="closeModal('rescheduleModal')">Cancel</button>
      <button class="btn-primary" onclick="submitReschedule()">Confirm Reschedule</button>
    </div>
  </div>
</div>

<div class="toast-container" id="toastContainer"></div>

<style>
.filter-btn {
  padding: 8px 18px;
  border: 1.5px solid var(--border);
  border-radius: 20px;
  background: transparent;
  font-family: var(--font-body);
  font-size: 0.82rem;
  font-weight: 500;
  color: var(--ink-mute);
  cursor: pointer;
  transition: var(--transition);
}
.filter-btn:hover { border-color: var(--gold); color: var(--gold-dark); }
.filter-btn.active { border-color: var(--ink); background: var(--ink); color: #fff; }
</style>

<script src="js/app.js"></script>
<script src="js/bookings.js"></script>
</body>
</html>
