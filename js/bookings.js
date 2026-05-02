// bookings.js

let currentStatus = 'all';
let currentPage = 1;

document.addEventListener('DOMContentLoaded', () => {
  const url = new URLSearchParams(window.location.search);
  loadBookings();

  // Watch reschedule date change
  document.getElementById('rescheduleDate').addEventListener('change', async (e) => {
    const svcId = document.getElementById('rescheduleServiceId').value;
    const data = await apiGet(`php/bookings.php?action=slots&date=${e.target.value}&service_id=${svcId}`);
    const sel = document.getElementById('rescheduleTime');
    sel.innerHTML = '<option value="">-- Select a time --</option>';
    if (data.success) {
      data.slots.filter(s => s.available).forEach(s => {
        sel.innerHTML += `<option value="${s.time}">${s.display}</option>`;
      });
    }
  });
});

function filterStatus(status, btn) {
  currentStatus = status;
  currentPage = 1;
  document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  loadBookings();
}

async function loadBookings() {
  const body = document.getElementById('bookingsBody');
  body.innerHTML = `<tr><td colspan="8" class="text-mute" style="text-align:center;padding:40px;">Loading...</td></tr>`;

  const data = await apiGet(`php/bookings.php?action=history&status=${currentStatus}&page=${currentPage}`);

  if (!data.success) {
    body.innerHTML = `<tr><td colspan="8" class="text-mute" style="text-align:center;padding:40px;">Failed to load bookings.</td></tr>`;
    return;
  }

  if (!data.bookings.length) {
    body.innerHTML = `<tr><td colspan="8">
      <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>
        <h3>No bookings found</h3>
        <p>You don't have any ${currentStatus !== 'all' ? currentStatus : ''} appointments.</p>
      </div>
    </td></tr>`;
    document.getElementById('pageInfo').textContent = '';
    document.getElementById('pagination').innerHTML = '';
    return;
  }

  body.innerHTML = data.bookings.map(b => {
    const canCancel    = ['pending','confirmed'].includes(b.status) && b.booking_date >= new Date().toISOString().split('T')[0];
    const canReschedule = canCancel;

    return `<tr>
      <td><code style="font-size:0.78rem;color:var(--gold-dark);background:var(--paper-warm);padding:3px 7px;border-radius:4px;">${b.booking_ref}</code></td>
      <td>
        <div style="display:flex;align-items:center;gap:8px;">
          <span style="font-size:1.2rem;">${b.icon}</span>
          <span style="font-weight:500;color:var(--ink);">${b.service_name}</span>
        </div>
      </td>
      <td>${formatDate(b.booking_date)}</td>
      <td>${formatTime(b.booking_time)}</td>
      <td>${b.duration_minutes} min</td>
      <td style="font-weight:500;">${formatCurrency(b.total_amount)}</td>
      <td>${statusBadge(b.status)}</td>
      <td>
        <div class="flex gap-2">
          ${canReschedule ? `<button class="btn-success" style="font-size:0.78rem;padding:6px 12px;" onclick="openReschedule(${b.id},${b.service_id})">Reschedule</button>` : ''}
          ${canCancel ? `<button class="btn-danger" style="font-size:0.78rem;padding:6px 12px;" onclick="openCancel(${b.id})">Cancel</button>` : ''}
          ${!canCancel && !canReschedule ? '<span class="text-mute" style="font-size:0.8rem;">—</span>' : ''}
        </div>
      </td>
    </tr>`;
  }).join('');

  // Pagination
  const from = ((currentPage - 1) * 10) + 1;
  const to   = Math.min(currentPage * 10, data.total);
  document.getElementById('pageInfo').textContent = `Showing ${from}–${to} of ${data.total}`;

  let pages = '';
  for (let i = 1; i <= data.pages; i++) {
    pages += `<button onclick="goPage(${i})" class="btn-secondary" style="padding:6px 12px;font-size:0.8rem;${i===currentPage?'background:var(--ink);color:#fff;border-color:var(--ink);':''}">${i}</button>`;
  }
  document.getElementById('pagination').innerHTML = pages;
}

function goPage(p) { currentPage = p; loadBookings(); }

// Cancel
function openCancel(id) {
  document.getElementById('cancelBookingId').value = id;
  document.getElementById('cancelReason').value = '';
  openModal('cancelModal');
}

async function submitCancel() {
  const id = document.getElementById('cancelBookingId').value;
  const reason = document.getElementById('cancelReason').value;
  const data = await apiPost('php/bookings.php', { action: 'cancel', booking_id: parseInt(id), reason });
  if (data.success) {
    closeModal('cancelModal');
    showToast('Booking cancelled.', 'success');
    loadBookings();
  } else {
    showToast(data.error || 'Failed to cancel.', 'error');
  }
}

// Reschedule
function openReschedule(id, serviceId) {
  document.getElementById('rescheduleBookingId').value = id;
  document.getElementById('rescheduleServiceId').value = serviceId;
  document.getElementById('rescheduleDate').value = '';
  document.getElementById('rescheduleTime').innerHTML = '<option value="">-- Select a date first --</option>';
  openModal('rescheduleModal');
}

async function submitReschedule() {
  const id      = document.getElementById('rescheduleBookingId').value;
  const newDate = document.getElementById('rescheduleDate').value;
  const newTime = document.getElementById('rescheduleTime').value;

  if (!newDate || !newTime) { showToast('Please select a new date and time.', 'error'); return; }

  const data = await apiPost('php/bookings.php', { action: 'reschedule', booking_id: parseInt(id), new_date: newDate, new_time: newTime });
  if (data.success) {
    closeModal('rescheduleModal');
    showToast('Booking rescheduled!', 'success');
    loadBookings();
  } else {
    showToast(data.error || 'Failed to reschedule.', 'error');
  }
}
