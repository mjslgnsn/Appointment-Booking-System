// admin.js

document.addEventListener('DOMContentLoaded', () => {
  loadAdminStats();
  loadRecentBookings();
  initAdminCalendar();
  loadCharts();
});

async function loadAdminStats() {
  const data = await apiGet('../php/bookings.php?action=stats');
  if (!data.success) return;
  const s = data.stats;
  animateCount('statTotal', s.total || 0);
  animateCount('statUpcoming', s.upcoming || 0);

  // Revenue
  const revEl = document.getElementById('statRevenue');
  if (revEl) revEl.textContent = '$' + parseFloat(s.revenue || 0).toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2});

  // Customers
  const custData = await apiGet('../php/admin.php?action=customer_count');
  animateCount('statCustomers', custData.count || 0);
}

function animateCount(id, target) {
  const el = document.getElementById(id);
  if (!el) return;
  let current = 0;
  const step = Math.max(1, Math.ceil(target / 25));
  const timer = setInterval(() => {
    current = Math.min(current + step, target);
    el.textContent = current;
    if (current >= target) clearInterval(timer);
  }, 35);
}

async function loadRecentBookings() {
  const data = await apiGet('../php/bookings.php?action=all&page=1');
  const body = document.getElementById('recentBody');
  if (!body) return;

  if (!data.success || !data.bookings.length) {
    body.innerHTML = `<tr><td colspan="4" class="text-mute" style="text-align:center;padding:24px;">No bookings yet.</td></tr>`;
    return;
  }

  body.innerHTML = data.bookings.slice(0, 8).map(b => `
    <tr>
      <td>
        <div style="font-weight:500;color:var(--ink);font-size:0.875rem;">${b.first_name} ${b.last_name}</div>
        <div style="font-size:0.75rem;color:var(--ink-mute);">${b.email}</div>
      </td>
      <td>${b.icon} ${b.service_name}</td>
      <td style="font-size:0.82rem;">${formatDate(b.booking_date)}</td>
      <td>${statusBadge(b.status)}</td>
    </tr>
  `).join('');
}

function initAdminCalendar() {
  const el = document.getElementById('adminMiniCal');
  if (!el || typeof FullCalendar === 'undefined') return;

  fetch('../php/bookings.php?action=calendar_events')
    .then(r => r.json())
    .then(data => {
      const cal = new FullCalendar.Calendar(el, {
        initialView: 'dayGridMonth',
        headerToolbar: { left: 'prev', center: 'title', right: 'next' },
        height: 320,
        events: data.events || [],
        eventClassNames: (arg) => {
          const s = arg.event.extendedProps.status;
          return s === 'confirmed' ? ['fc-event-green'] : s === 'cancelled' ? ['fc-event-red'] : ['fc-event-gold'];
        }
      });
      cal.render();
    });
}

async function loadCharts() {
  const data = await apiGet('../php/admin.php?action=monthly_stats');
  const barChart = document.getElementById('barChart');
  const barLabels = document.getElementById('barLabels');
  if (!barChart || !data.success) return;

  const months = data.months;
  const maxVal = Math.max(...months.map(m => m.count), 1);

  barChart.innerHTML = months.map(m => {
    const pct = Math.round((m.count / maxVal) * 100);
    return `
      <div class="bar-group">
        <div class="bar" style="height:${Math.max(pct, 4)}%;" title="${m.count} bookings">
          <div style="position:absolute;top:-22px;left:50%;transform:translateX(-50%);font-size:0.7rem;color:var(--ink-mute);">${m.count}</div>
        </div>
      </div>
    `;
  }).join('');

  if (barLabels) {
    barLabels.innerHTML = months.map(m => `<div class="bar-label" style="flex:1;text-align:center;">${m.month}</div>`).join('');
  }

  // Service popularity
  const svcData = await apiGet('../php/admin.php?action=service_stats');
  const popEl = document.getElementById('servicePop');
  if (popEl && svcData.success) {
    const total = svcData.services.reduce((sum, s) => sum + parseInt(s.count), 0) || 1;
    popEl.innerHTML = svcData.services.map(s => {
      const pct = Math.round((s.count / total) * 100);
      return `
        <div style="margin-bottom:14px;">
          <div style="display:flex;justify-content:space-between;margin-bottom:6px;font-size:0.82rem;">
            <span>${s.icon} ${s.name}</span>
            <span style="font-weight:500;">${s.count} bookings</span>
          </div>
          <div style="height:6px;background:var(--border);border-radius:3px;overflow:hidden;">
            <div style="height:100%;width:${pct}%;background:var(--gold);border-radius:3px;transition:.6s ease;"></div>
          </div>
        </div>
      `;
    }).join('') || '<p class="text-mute">No data yet.</p>';
  }

  // Status breakdown
  const statEl = document.getElementById('statusBreakdown');
  if (statEl && data.success) {
    const breakdown = data.status_breakdown || {};
    const total2 = Object.values(breakdown).reduce((a,b) => a + parseInt(b), 0) || 1;
    const colors = { confirmed:'var(--success)', pending:'#d4860a', completed:'#4f46e5', cancelled:'var(--danger)' };
    statEl.innerHTML = Object.entries(breakdown).map(([status, count]) => {
      const pct = Math.round((count / total2) * 100);
      return `
        <div style="margin-bottom:14px;">
          <div style="display:flex;justify-content:space-between;margin-bottom:6px;font-size:0.82rem;">
            <span style="text-transform:capitalize;">${status}</span>
            <span style="font-weight:500;">${count} (${pct}%)</span>
          </div>
          <div style="height:6px;background:var(--border);border-radius:3px;overflow:hidden;">
            <div style="height:100%;width:${pct}%;background:${colors[status]||'var(--gold)'};border-radius:3px;"></div>
          </div>
        </div>
      `;
    }).join('') || '<p class="text-mute">No data yet.</p>';
  }
}
