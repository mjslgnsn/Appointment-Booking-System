// dashboard.js

document.addEventListener('DOMContentLoaded', () => {
  loadStats();
  loadUpcoming();
  initMiniCalendar();
});

async function loadStats() {
  const data = await apiGet('php/bookings.php?action=stats');
  if (!data.success) return;
  const s = data.stats;
  animateCount('statTotal', s.total);
  animateCount('statCompleted', s.completed);
  animateCount('statUpcoming', s.upcoming);
  animateCount('statCancelled', s.cancelled);
}

function animateCount(id, target) {
  const el = document.getElementById(id);
  if (!el) return;
  let current = 0;
  const step = Math.ceil(target / 20);
  const timer = setInterval(() => {
    current = Math.min(current + step, target);
    el.textContent = current;
    if (current >= target) clearInterval(timer);
  }, 40);
}

async function loadUpcoming() {
  const data = await apiGet('php/bookings.php?action=upcoming&limit=3');
  const el = document.getElementById('upcomingList');
  if (!data.success || !data.bookings.length) {
    el.innerHTML = `
      <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        <h3>No upcoming appointments</h3>
        <p>Book your next appointment to get started.</p>
      </div>`;
    return;
  }

  el.innerHTML = data.bookings.map(b => `
    <div style="padding:14px 0;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:14px;">
      <div style="font-size:1.6rem;width:44px;text-align:center;">${b.icon}</div>
      <div style="flex:1;">
        <div style="font-weight:500;color:var(--ink);font-size:0.9rem;">${b.service_name}</div>
        <div style="font-size:0.8rem;color:var(--ink-mute);margin-top:3px;">${formatDate(b.booking_date)} · ${formatTime(b.booking_time)}</div>
      </div>
      <div>${statusBadge(b.status)}</div>
    </div>
  `).join('') + (data.bookings.length ? `<div style="padding-top:12px;"><a href="bookings.php" class="text-mute" style="font-size:0.82rem;">View all bookings →</a></div>` : '');

  // Update email preview with first upcoming
  const first = data.bookings[0];
  if (first) {
    document.querySelector('.email-subject').textContent = `Appointment Confirmed — ${first.service_name}`;
    document.getElementById('emailService').textContent = first.service_name;
    document.getElementById('emailDate').textContent = formatDate(first.booking_date);
    document.getElementById('emailTime').textContent = formatTime(first.booking_time);
    document.getElementById('emailDuration').textContent = first.duration_minutes + ' minutes';
    document.getElementById('emailAmount').textContent = formatCurrency(first.total_amount);
  }
}

function initMiniCalendar() {
  const el = document.getElementById('miniCal');
  if (!el || typeof FullCalendar === 'undefined') return;

  fetch('php/bookings.php?action=calendar_events')
    .then(r => r.json())
    .then(data => {
      const cal = new FullCalendar.Calendar(el, {
        initialView: 'dayGridMonth',
        headerToolbar: { left: 'prev', center: 'title', right: 'next' },
        height: 320,
        events: data.events || [],
        eventClick: (info) => {
          window.location.href = `bookings.php?highlight=${info.event.id}`;
        },
        eventClassNames: (arg) => {
          const s = arg.event.extendedProps.status;
          return s === 'confirmed' ? ['fc-event-green'] : s === 'cancelled' ? ['fc-event-red'] : ['fc-event-gold'];
        }
      });
      cal.render();
    });
}

// Notifications panel
async function showNotifications() {
  openModal('notifOverlay');
  const data = await apiGet('php/notifications.php?action=list');
  const el = document.getElementById('notifList');
  if (!data.success || !data.notifications.length) {
    el.innerHTML = '<p class="text-mute">No notifications.</p>';
    return;
  }
  el.innerHTML = data.notifications.map(n => `
    <div style="padding:14px 0;border-bottom:1px solid var(--border);">
      <div style="font-weight:500;font-size:0.875rem;margin-bottom:4px;">${n.title}</div>
      <div style="font-size:0.8rem;color:var(--ink-mute);">${n.message}</div>
    </div>
  `).join('');
  document.getElementById('notifBadge').style.display = 'none';
  apiPost('php/notifications.php', { action: 'mark_read' });
}

function closeNotif(e) {
  if (!e || e.target === document.getElementById('notifOverlay')) {
    closeModal('notifOverlay');
  }
}
