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
  <title>Reports — Appoint Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .report-kpi-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:20px; margin-bottom:32px; }
    .kpi-card { background:var(--paper-card); border:1px solid var(--border); border-radius:var(--radius); padding:28px 24px; text-align:center; }
    .kpi-value { font-family:var(--font-display); font-size:2.4rem; font-weight:600; color:var(--ink); }
    .kpi-label { font-size:0.82rem; color:var(--ink-mute); margin-top:6px; }
    .kpi-sub { font-size:0.78rem; color:var(--success); margin-top:8px; font-weight:500; }

    .bar-wrap { display:flex; align-items:flex-end; gap:6px; height:200px; margin-bottom:12px; }
    .bar-col { flex:1; display:flex; flex-direction:column; align-items:center; gap:0; }
    .bar-fill { width:100%; border-radius:5px 5px 0 0; transition:.5s cubic-bezier(.4,0,.2,1); position:relative; cursor:pointer; }
    .bar-fill:hover { filter:brightness(1.1); }
    .bar-val { font-size:0.68rem; color:var(--ink-mute); margin-bottom:3px; }
    .bar-mon { font-size:0.7rem; color:var(--ink-mute); margin-top:6px; }

    .pie-wrap { display:flex; gap:24px; align-items:center; flex-wrap:wrap; }
    .donut-svg { width:160px; height:160px; flex-shrink:0; }
    .donut-legend { flex:1; min-width:160px; }
    .legend-row { display:flex; align-items:center; gap:10px; margin-bottom:12px; font-size:0.82rem; }
    .legend-dot { width:12px; height:12px; border-radius:3px; flex-shrink:0; }

    .revenue-table table { width:100%; }
    @media(max-width:768px){ .report-kpi-grid{ grid-template-columns:1fr 1fr; } }
    @media(max-width:480px){ .report-kpi-grid{ grid-template-columns:1fr; } }
  </style>
</head>
<body>
<button class="hamburger" onclick="toggleSidebar()"><span></span><span></span><span></span></button>
<div class="app-layout">
  <?php include '../includes/sidebar.php'; ?>
  <main class="main-content">
    <div class="topbar">
      <span class="topbar-title">Reports & Analytics</span>
      <div class="topbar-actions">
        <select id="yearFilter" onchange="loadAll()" style="padding:9px 14px;border:1.5px solid var(--border);border-radius:8px;font-family:var(--font-body);font-size:0.82rem;background:var(--paper-card);">
          <option value="2026">2026</option>
          <option value="2025">2025</option>
          <option value="2024">2024</option>
        </select>
        <button class="btn-secondary" onclick="printReport()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
          Print Report
        </button>
      </div>
    </div>
    <div class="page-content" id="reportPage">

      <!-- KPIs -->
      <div class="report-kpi-grid">
        <div class="kpi-card">
          <div class="kpi-value" id="kpiRevenue">—</div>
          <div class="kpi-label">Total Revenue</div>
          <div class="kpi-sub" id="kpiRevSub"></div>
        </div>
        <div class="kpi-card">
          <div class="kpi-value" id="kpiBookings">—</div>
          <div class="kpi-label">Total Bookings</div>
          <div class="kpi-sub" id="kpiBkSub"></div>
        </div>
        <div class="kpi-card">
          <div class="kpi-value" id="kpiAvg">—</div>
          <div class="kpi-label">Avg. Booking Value</div>
          <div class="kpi-sub"></div>
        </div>
      </div>

      <!-- Monthly Revenue Chart -->
      <div class="chart-container mb-8">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
          <div class="chart-title" style="margin:0;">Monthly Revenue</div>
          <div style="display:flex;gap:16px;font-size:0.78rem;color:var(--ink-mute);">
            <span><span style="display:inline-block;width:10px;height:10px;background:var(--gold);border-radius:2px;margin-right:5px;"></span>Revenue</span>
            <span><span style="display:inline-block;width:10px;height:10px;background:var(--accent);border-radius:2px;margin-right:5px;"></span>Bookings</span>
          </div>
        </div>
        <div class="bar-wrap" id="revenueChart"></div>
        <div style="display:flex;gap:6px;" id="revenueLabels"></div>
      </div>

      <!-- Two column: Status Donut + Service Revenue Table -->
      <div class="grid-2 mb-8">
        <!-- Donut Chart -->
        <div class="chart-container">
          <div class="chart-title">Booking Status Distribution</div>
          <div class="pie-wrap">
            <svg class="donut-svg" viewBox="0 0 36 36" id="donutSvg">
              <circle cx="18" cy="18" r="15.9" fill="none" stroke="var(--border)" stroke-width="3"/>
            </svg>
            <div class="donut-legend" id="donutLegend"></div>
          </div>
        </div>

        <!-- Top Services -->
        <div class="chart-container revenue-table">
          <div class="chart-title">Revenue by Service</div>
          <table>
            <thead><tr><th>Service</th><th>Bookings</th><th>Revenue</th><th>Share</th></tr></thead>
            <tbody id="serviceRevTable"></tbody>
          </table>
        </div>
      </div>

      <!-- Recent High-Value Bookings -->
      <div class="card mb-8">
        <div class="card-header">
          <span class="card-title">Top Bookings This Period</span>
        </div>
        <div class="table-wrapper">
          <table>
            <thead><tr><th>Reference</th><th>Customer</th><th>Service</th><th>Date</th><th>Amount</th><th>Status</th></tr></thead>
            <tbody id="topBookingsTable">
              <tr><td colspan="6" class="text-mute" style="text-align:center;padding:24px;">Loading...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </main>
</div>
<div class="toast-container" id="toastContainer"></div>
<script src="../js/app.js"></script>
<script>
const DONUT_COLORS = {
  confirmed: '#27ae60',
  pending:   '#d4860a',
  completed: '#4f46e5',
  cancelled: '#c0392b'
};

document.addEventListener('DOMContentLoaded', loadAll);

async function loadAll() {
  const year = document.getElementById('yearFilter').value;
  const [monthly, svcStats, allBk] = await Promise.all([
    apiGet(`../php/reports.php?action=monthly_revenue&year=${year}`),
    apiGet(`../php/reports.php?action=service_revenue&year=${year}`),
    apiGet(`../php/bookings.php?action=all&page=1`),
  ]);

  if (monthly.success) {
    renderKPIs(monthly);
    renderRevenueChart(monthly.months);
    renderDonut(monthly.status_breakdown);
  }
  if (svcStats.success) renderServiceTable(svcStats.services);
  if (allBk.success)    renderTopBookings(allBk.bookings);
}

function renderKPIs(data) {
  const rev = parseFloat(data.total_revenue || 0);
  const bk  = parseInt(data.total_bookings || 0);
  document.getElementById('kpiRevenue').textContent = '$' + rev.toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2});
  document.getElementById('kpiBookings').textContent = bk;
  document.getElementById('kpiAvg').textContent = bk ? '$' + (rev/bk).toFixed(2) : '$0.00';
}

function renderRevenueChart(months) {
  const maxRev = Math.max(...months.map(m => parseFloat(m.revenue)), 1);
  const maxBk  = Math.max(...months.map(m => parseInt(m.count)), 1);

  document.getElementById('revenueChart').innerHTML = months.map(m => {
    const revPct = Math.round((parseFloat(m.revenue)/maxRev)*100);
    const bkPct  = Math.round((parseInt(m.count)/maxBk)*100);
    return `
      <div class="bar-col">
        <div class="bar-val">$${parseFloat(m.revenue).toFixed(0)}</div>
        <div style="display:flex;gap:3px;align-items:flex-end;flex:1;width:100%;">
          <div class="bar-fill" style="height:${Math.max(revPct,2)}%;background:var(--gold);flex:1;" title="Revenue: $${parseFloat(m.revenue).toFixed(2)}"></div>
          <div class="bar-fill" style="height:${Math.max(bkPct,2)}%;background:var(--accent);flex:1;opacity:.7;" title="Bookings: ${m.count}"></div>
        </div>
      </div>
    `;
  }).join('');

  document.getElementById('revenueLabels').innerHTML = months.map(m =>
    `<div class="bar-mon" style="flex:1;text-align:center;">${m.month}</div>`
  ).join('');
}

function renderDonut(breakdown) {
  if (!breakdown || !Object.keys(breakdown).length) return;
  const total = Object.values(breakdown).reduce((a,b) => a + parseInt(b), 0) || 1;
  const circumference = 2 * Math.PI * 15.9;
  let offset = 0;

  let circles = '';
  let legend = '';

  Object.entries(breakdown).forEach(([status, count]) => {
    const pct = parseInt(count) / total;
    const dash = pct * circumference;
    const gap  = circumference - dash;
    const color = DONUT_COLORS[status] || '#999';
    circles += `<circle cx="18" cy="18" r="15.9" fill="none" stroke="${color}" stroke-width="3.2"
      stroke-dasharray="${dash.toFixed(2)} ${gap.toFixed(2)}"
      stroke-dashoffset="${(circumference - offset).toFixed(2)}"
      transform="rotate(-90 18 18)" />`;
    offset += dash;

    legend += `<div class="legend-row">
      <div class="legend-dot" style="background:${color};"></div>
      <span style="text-transform:capitalize;flex:1;">${status}</span>
      <span style="font-weight:600;">${count} <span style="color:var(--ink-mute);font-weight:400;">(${Math.round(pct*100)}%)</span></span>
    </div>`;
  });

  document.getElementById('donutSvg').innerHTML = `<circle cx="18" cy="18" r="15.9" fill="none" stroke="var(--border)" stroke-width="3"/>` + circles;
  document.getElementById('donutLegend').innerHTML = legend;
}

function renderServiceTable(services) {
  const totalRev = services.reduce((s, r) => s + parseFloat(r.revenue), 0) || 1;
  document.getElementById('serviceRevTable').innerHTML = services.map(s => {
    const share = Math.round((parseFloat(s.revenue)/totalRev)*100);
    return `<tr>
      <td>${s.icon} <strong>${s.name}</strong></td>
      <td>${s.count}</td>
      <td style="font-weight:600;color:var(--gold-dark);">$${parseFloat(s.revenue).toFixed(2)}</td>
      <td>
        <div style="display:flex;align-items:center;gap:8px;">
          <div style="width:60px;height:6px;background:var(--border);border-radius:3px;overflow:hidden;">
            <div style="height:100%;width:${share}%;background:var(--gold);border-radius:3px;"></div>
          </div>
          <span style="font-size:0.78rem;color:var(--ink-mute);">${share}%</span>
        </div>
      </td>
    </tr>`;
  }).join('') || '<tr><td colspan="4" class="text-mute" style="text-align:center;padding:20px;">No data.</td></tr>';
}

function renderTopBookings(bookings) {
  const el = document.getElementById('topBookingsTable');
  if (!bookings.length) { el.innerHTML = '<tr><td colspan="6" class="text-mute" style="text-align:center;padding:20px;">No bookings.</td></tr>'; return; }
  el.innerHTML = bookings.slice(0,8).map(b => `
    <tr>
      <td><code style="font-size:0.75rem;color:var(--gold-dark);background:var(--paper-warm);padding:2px 7px;border-radius:4px;">${b.booking_ref}</code></td>
      <td>${b.first_name} ${b.last_name}</td>
      <td>${b.icon} ${b.service_name}</td>
      <td style="font-size:0.82rem;">${formatDate(b.booking_date)}</td>
      <td style="font-weight:600;">$${parseFloat(b.total_amount).toFixed(2)}</td>
      <td>${statusBadge(b.status)}</td>
    </tr>
  `).join('');
}

function printReport() { window.print(); }
</script>
</body>
</html>
