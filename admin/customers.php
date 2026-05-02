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
  <title>Customers — Appoint Admin</title>
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
      <span class="topbar-title">Customers</span>
    </div>
    <div class="page-content">
      <div class="flex items-center gap-3 mb-6">
        <input type="text" id="searchInput" placeholder="Search by name or email..." style="padding:9px 14px;border:1.5px solid var(--border);border-radius:8px;font-family:var(--font-body);font-size:0.875rem;width:300px;background:var(--paper-card);" oninput="debounceSearch()">
      </div>

      <div class="card">
        <div class="table-wrapper">
          <table>
            <thead>
              <tr><th>Customer</th><th>Phone</th><th>Bookings</th><th>Last Appointment</th><th>Member Since</th></tr>
            </thead>
            <tbody id="custBody">
              <tr><td colspan="5" class="text-mute" style="text-align:center;padding:40px;">Loading...</td></tr>
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
<div class="toast-container" id="toastContainer"></div>
<script src="../js/app.js"></script>
<script>
let currentPage = 1;
let searchTimeout;

document.addEventListener('DOMContentLoaded', loadCustomers);

function debounceSearch() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => { currentPage=1; loadCustomers(); }, 350);
}

async function loadCustomers() {
  const search = document.getElementById('searchInput').value;
  const body = document.getElementById('custBody');
  body.innerHTML = `<tr><td colspan="5" class="text-mute" style="text-align:center;padding:40px;">Loading...</td></tr>`;

  const data = await apiGet(`../php/admin.php?action=customers&page=${currentPage}&search=${encodeURIComponent(search)}`);

  if (!data.success || !data.customers.length) {
    body.innerHTML = `<tr><td colspan="5"><div class="empty-state"><h3>No customers found</h3></div></td></tr>`;
    return;
  }

  body.innerHTML = data.customers.map(c => `
    <tr>
      <td>
        <div style="display:flex;align-items:center;gap:12px;">
          <div style="width:36px;height:36px;border-radius:50%;background:var(--gold-light);display:flex;align-items:center;justify-content:center;font-family:var(--font-display);font-size:0.9rem;color:var(--gold-dark);font-weight:600;">${c.avatar_initials || c.first_name[0]}</div>
          <div>
            <div style="font-weight:500;color:var(--ink);">${c.first_name} ${c.last_name}</div>
            <div style="font-size:0.75rem;color:var(--ink-mute);">${c.email}</div>
          </div>
        </div>
      </td>
      <td>${c.phone || '—'}</td>
      <td>
        <span style="font-weight:600;color:var(--gold-dark);">${c.booking_count}</span>
        <span class="text-mute" style="font-size:0.8rem;"> bookings</span>
      </td>
      <td style="font-size:0.82rem;">${c.last_booking ? formatDate(c.last_booking) : '—'}</td>
      <td style="font-size:0.82rem;color:var(--ink-mute);">${formatDate(c.created_at?.split(' ')[0])}</td>
    </tr>
  `).join('');

  const from = ((currentPage-1)*15)+1;
  const to = Math.min(currentPage*15, data.total);
  document.getElementById('pageInfo').textContent = `Showing ${from}–${to} of ${data.total} customers`;

  let pages = '';
  for (let i = 1; i <= data.pages; i++) {
    pages += `<button onclick="goPage(${i})" class="btn-secondary" style="padding:6px 12px;font-size:0.8rem;${i===currentPage?'background:var(--ink);color:#fff;border-color:var(--ink);':''}">${i}</button>`;
  }
  document.getElementById('pagination').innerHTML = pages;
}

function goPage(p) { currentPage=p; loadCustomers(); }
</script>
</body>
</html>
