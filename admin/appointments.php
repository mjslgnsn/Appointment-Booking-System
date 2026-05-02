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
  <title>Manage Appointments — Appoint Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<button class="hamburger" onclick="toggleSidebar()"><span></span><span></span><span></span></button>
<div class="app-layout">
  <?php include '../includes/sidebar.php'; ?>
  <main class="main-content">
    <div class="topbar">
      <span class="topbar-title">Manage Appointments</span>
    </div>
    <div class="page-content">
      <!-- Filters -->
      <div class="flex items-center gap-3 mb-6" style="flex-wrap:wrap;">
        <input type="text" id="searchInput" placeholder="Search customer, service, ref..." style="padding:9px 14px;border:1.5px solid var(--border);border-radius:8px;font-family:var(--font-body);font-size:0.875rem;width:280px;background:var(--paper-card);" oninput="debounceSearch()">
        <select id="statusFilter" onchange="loadAppointments()" style="padding:9px 14px;border:1.5px solid var(--border);border-radius:8px;font-family:var(--font-body);font-size:0.875rem;background:var(--paper-card);">
          <option value="">All Statuses</option>
          <option value="pending">Pending</option>
          <option value="confirmed">Confirmed</option>
          <option value="completed">Completed</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>

      <div class="card">
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>Ref</th>
                <th>Customer</th>
                <th>Service</th>
                <th>Date & Time</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="apptBody">
              <tr><td colspan="7" class="text-mute" style="text-align:center;padding:40px;">Loading...</td></tr>
            </tbody>
          </table>
        </div>
        <div style="padding:16px 20px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
          <span class="text-mute" id="pageInfo"></span>
          <div class="flex gap-2" id="pagination"></div>
        </div>
      </div>
    </div>
  </main>
</div>

<!-- Status Update Modal -->
<div class="modal-overlay" id="statusModal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Update Appointment</span>
      <button class="modal-close" onclick="closeModal('statusModal')">×</button>
    </div>
    <div class="modal-body">
      <div id="apptDetail" style="background:var(--paper-warm);border-radius:8px;padding:16px;margin-bottom:20px;"></div>
      <div class="form-group">
        <label>Update Status</label>
        <select id="newStatus">
          <option value="pending">Pending</option>
          <option value="confirmed">Confirmed</option>
          <option value="completed">Completed</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>
      <input type="hidden" id="modalBookingId">
    </div>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="closeModal('statusModal')">Cancel</button>
      <button class="btn-primary" onclick="submitStatusUpdate()">Update Status</button>
    </div>
  </div>
</div>

<!-- Cancel Modal -->
<div class="modal-overlay" id="cancelModal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Cancel Appointment</span>
      <button class="modal-close" onclick="closeModal('cancelModal')">×</button>
    </div>
    <div class="modal-body">
      <p class="text-mute mb-4">Cancel this appointment on behalf of the customer?</p>
      <div class="form-group">
        <label>Reason</label>
        <textarea id="cancelReason" rows="3" placeholder="Reason for cancellation..."></textarea>
      </div>
      <input type="hidden" id="cancelBookingId">
    </div>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="closeModal('cancelModal')">Go Back</button>
      <button class="btn-danger" onclick="submitCancel()">Cancel Appointment</button>
    </div>
  </div>
</div>

<div class="toast-container" id="toastContainer"></div>
<script src="../js/app.js"></script>
<script>
let currentPage = 1;
let searchTimeout;

document.addEventListener('DOMContentLoaded', loadAppointments);

function debounceSearch() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => { currentPage = 1; loadAppointments(); }, 350);
}

async function loadAppointments() {
  const search = document.getElementById('searchInput').value;
  const status = document.getElementById('statusFilter').value;
  const body = document.getElementById('apptBody');
  body.innerHTML = `<tr><td colspan="7" class="text-mute" style="text-align:center;padding:40px;">Loading...</td></tr>`;

  const data = await apiGet(`../php/bookings.php?action=all&page=${currentPage}&status=${status}&search=${encodeURIComponent(search)}`);

  if (!data.success || !data.bookings.length) {
    body.innerHTML = `<tr><td colspan="7"><div class="empty-state"><h3>No appointments found</h3></div></td></tr>`;
    document.getElementById('pageInfo').textContent = '';
    document.getElementById('pagination').innerHTML = '';
    return;
  }

  body.innerHTML = data.bookings.map(b => `
    <tr>
      <td><code style="font-size:0.75rem;color:var(--gold-dark);background:var(--paper-warm);padding:2px 7px;border-radius:4px;">${b.booking_ref}</code></td>
      <td>
        <div style="font-weight:500;color:var(--ink);font-size:0.875rem;">${b.first_name} ${b.last_name}</div>
        <div style="font-size:0.73rem;color:var(--ink-mute);">${b.phone || b.email}</div>
      </td>
      <td>${b.icon} ${b.service_name}<br><span style="font-size:0.75rem;color:var(--ink-mute);">${b.duration_minutes}min</span></td>
      <td style="font-size:0.82rem;">${formatDate(b.booking_date)}<br>${formatTime(b.booking_time)}</td>
      <td style="font-weight:500;">${formatCurrency(b.total_amount)}</td>
      <td>${statusBadge(b.status)}</td>
      <td>
        <div class="flex gap-2">
          <button class="btn-secondary" style="font-size:0.78rem;padding:6px 10px;" onclick='openStatusModal(${b.id},"${b.status}","${b.first_name} ${b.last_name}","${b.service_name}","${b.booking_date}","${b.booking_time}")'>Edit</button>
          ${b.status !== 'cancelled' ? `<button class="btn-danger" style="font-size:0.78rem;padding:6px 10px;" onclick="openCancel(${b.id})">Cancel</button>` : ''}
        </div>
      </td>
    </tr>
  `).join('');

  const from = ((currentPage-1)*15)+1;
  const to = Math.min(currentPage*15, data.total);
  document.getElementById('pageInfo').textContent = `Showing ${from}–${to} of ${data.total}`;

  let pages = '';
  for (let i = 1; i <= data.pages; i++) {
    pages += `<button onclick="goPage(${i})" class="btn-secondary" style="padding:6px 12px;font-size:0.8rem;${i===currentPage?'background:var(--ink);color:#fff;border-color:var(--ink);':''}">${i}</button>`;
  }
  document.getElementById('pagination').innerHTML = pages;
}

function goPage(p) { currentPage = p; loadAppointments(); }

function openStatusModal(id, status, name, service, date, time) {
  document.getElementById('modalBookingId').value = id;
  document.getElementById('newStatus').value = status;
  document.getElementById('apptDetail').innerHTML = `
    <div style="font-size:0.82rem;line-height:1.8;">
      <strong>${name}</strong> — ${service}<br>
      ${formatDate(date)} at ${formatTime(time)}
    </div>
  `;
  openModal('statusModal');
}

async function submitStatusUpdate() {
  const id = document.getElementById('modalBookingId').value;
  const status = document.getElementById('newStatus').value;
  const data = await apiPost('../php/bookings.php', { action: 'update_status', booking_id: parseInt(id), status });
  if (data.success) {
    closeModal('statusModal');
    showToast('Status updated!', 'success');
    loadAppointments();
  } else {
    showToast(data.error || 'Failed.', 'error');
  }
}

function openCancel(id) {
  document.getElementById('cancelBookingId').value = id;
  document.getElementById('cancelReason').value = '';
  openModal('cancelModal');
}

async function submitCancel() {
  const id = document.getElementById('cancelBookingId').value;
  const reason = document.getElementById('cancelReason').value;
  const data = await apiPost('../php/bookings.php', { action: 'cancel', booking_id: parseInt(id), reason });
  if (data.success) {
    closeModal('cancelModal');
    showToast('Appointment cancelled.', 'success');
    loadAppointments();
  } else {
    showToast(data.error || 'Failed.', 'error');
  }
}
</script>
</body>
</html>
