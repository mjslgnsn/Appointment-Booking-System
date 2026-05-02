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
  <title>Admin Overview — Appoint</title>
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
      <span class="topbar-title">Admin Overview</span>
      <div class="topbar-actions">
        <span class="badge badge-confirmed" style="padding:6px 14px;">Admin Panel</span>
      </div>
    </div>

    <div class="page-content">
      <div class="mb-8">
        <h2 class="section-title">Welcome back, <em>Admin</em></h2>
        <p class="section-sub">Here's your business snapshot for today.</p>
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
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          </div>
          <div class="stat-value" id="statRevenue">—</div>
          <div class="stat-label">Total Revenue</div>
        </div>
        <div class="stat-card blue">
          <div class="stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          </div>
          <div class="stat-value" id="statCustomers">—</div>
          <div class="stat-label">Total Customers</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          </div>
          <div class="stat-value" id="statUpcoming">—</div>
          <div class="stat-label">Upcoming Today</div>
        </div>
      </div>

      <!-- Grid -->
      <div class="grid-2 mb-8">
        <!-- Recent Bookings -->
        <div class="card">
          <div class="card-header">
            <span class="card-title">Recent Bookings</span>
            <a href="appointments.php" class="btn-secondary" style="font-size:0.8rem;padding:7px 14px;">Manage All</a>
          </div>
          <div class="table-wrapper">
            <table>
              <thead><tr><th>Customer</th><th>Service</th><th>Date</th><th>Status</th></tr></thead>
              <tbody id="recentBody">
                <tr><td colspan="4" class="text-mute" style="text-align:center;padding:24px;">Loading...</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Calendar -->
        <div class="card">
          <div class="card-header"><span class="card-title">Appointment Calendar</span></div>
          <div class="card-body"><div id="adminMiniCal"></div></div>
        </div>
      </div>

      <!-- Revenue Bar Chart -->
      <div class="chart-container mb-8">
        <div class="chart-title">Monthly Bookings (This Year)</div>
        <div class="bar-chart" id="barChart"></div>
        <div style="display:flex;gap:12px;margin-top:10px;" id="barLabels"></div>
      </div>

      <!-- Service Breakdown -->
      <div class="grid-2">
        <div class="card">
          <div class="card-header"><span class="card-title">Service Popularity</span></div>
          <div class="card-body" id="servicePop">Loading...</div>
        </div>
        <div class="card">
          <div class="card-header"><span class="card-title">Booking Status Breakdown</span></div>
          <div class="card-body" id="statusBreakdown">Loading...</div>
        </div>
      </div>
    </div>
  </main>
</div>
<div class="toast-container" id="toastContainer"></div>
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.css" rel="stylesheet"/>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.js"></script>
<script src="../js/app.js"></script>
<script src="../js/admin.js"></script>
</body>
</html>
