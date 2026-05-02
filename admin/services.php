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
  <title>Services — Appoint Admin</title>
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
      <span class="topbar-title">Services</span>
      <div class="topbar-actions">
        <button class="btn-primary gold" onclick="openServiceModal()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><path d="M12 5v14M5 12h14"/></svg>
          Add Service
        </button>
      </div>
    </div>
    <div class="page-content">
      <div class="services-grid" id="servicesAdminGrid">
        <div class="text-mute">Loading services...</div>
      </div>
    </div>
  </main>
</div>

<!-- Service Modal -->
<div class="modal-overlay" id="serviceModal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title" id="serviceModalTitle">Add Service</span>
      <button class="modal-close" onclick="closeModal('serviceModal')">×</button>
    </div>
    <div class="modal-body">
      <div class="form-row">
        <div class="form-group">
          <label>Service Name</label>
          <input type="text" id="svcName" placeholder="e.g. Hair Styling" required>
        </div>
        <div class="form-group">
          <label>Icon (emoji)</label>
          <input type="text" id="svcIcon" placeholder="✂️" maxlength="4">
        </div>
      </div>
      <div class="form-group">
        <label>Description</label>
        <textarea id="svcDesc" rows="2" placeholder="Brief description of this service..."></textarea>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Duration (minutes)</label>
          <input type="number" id="svcDuration" placeholder="60" min="15" step="15">
        </div>
        <div class="form-group">
          <label>Price ($)</label>
          <input type="number" id="svcPrice" placeholder="85.00" min="0" step="0.01">
        </div>
      </div>
      <input type="hidden" id="svcId">
      <div id="svcErr" class="form-error"></div>
    </div>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="closeModal('serviceModal')">Cancel</button>
      <button class="btn-primary" onclick="saveService()">Save Service</button>
    </div>
  </div>
</div>

<div class="toast-container" id="toastContainer"></div>
<script src="../js/app.js"></script>
<script>
document.addEventListener('DOMContentLoaded', loadServices);

async function loadServices() {
  const data = await apiGet('../php/admin.php?action=get_services');
  const grid = document.getElementById('servicesAdminGrid');
  if (!data.success || !data.services.length) {
    grid.innerHTML = '<div class="text-mute">No services found.</div>'; return;
  }
  grid.innerHTML = data.services.map(s => `
    <div class="service-card" style="cursor:default;position:relative;${!s.is_active ? 'opacity:.5;' : ''}">
      ${!s.is_active ? '<div style="position:absolute;top:10px;right:10px;font-size:0.7rem;background:var(--danger-light);color:var(--danger);padding:2px 8px;border-radius:10px;">Inactive</div>' : ''}
      <div class="service-icon">${s.icon}</div>
      <div class="service-name">${s.name}</div>
      <div class="service-duration">${s.duration_minutes} min</div>
      <div class="service-price">$${parseFloat(s.price).toFixed(2)}</div>
      <div class="flex gap-2" style="margin-top:14px;justify-content:center;flex-wrap:wrap;">
        <button class="btn-secondary" style="font-size:0.76rem;padding:6px 12px;" onclick='editService(${s.id},"${escHtml(s.name)}","${escHtml(s.description||"")}",${s.duration_minutes},${s.price},"${s.icon}")'>Edit</button>
        <button class="btn-secondary" style="font-size:0.76rem;padding:6px 12px;" onclick="toggleService(${s.id})">${s.is_active ? 'Disable' : 'Enable'}</button>
        <button class="btn-danger" style="font-size:0.76rem;padding:6px 12px;" onclick="deleteService(${s.id})">Delete</button>
      </div>
    </div>
  `).join('');
}

function openServiceModal() {
  document.getElementById('serviceModalTitle').textContent = 'Add Service';
  document.getElementById('svcId').value = '';
  document.getElementById('svcName').value = '';
  document.getElementById('svcDesc').value = '';
  document.getElementById('svcDuration').value = '60';
  document.getElementById('svcPrice').value = '';
  document.getElementById('svcIcon').value = '🗓️';
  document.getElementById('svcErr').classList.remove('show');
  openModal('serviceModal');
}

function editService(id, name, desc, duration, price, icon) {
  document.getElementById('serviceModalTitle').textContent = 'Edit Service';
  document.getElementById('svcId').value = id;
  document.getElementById('svcName').value = name;
  document.getElementById('svcDesc').value = desc;
  document.getElementById('svcDuration').value = duration;
  document.getElementById('svcPrice').value = price;
  document.getElementById('svcIcon').value = icon;
  document.getElementById('svcErr').classList.remove('show');
  openModal('serviceModal');
}

async function saveService() {
  const errEl = document.getElementById('svcErr');
  errEl.classList.remove('show');
  const data = await apiPost('../php/admin.php', {
    action: 'save_service',
    id: parseInt(document.getElementById('svcId').value) || 0,
    name: document.getElementById('svcName').value,
    description: document.getElementById('svcDesc').value,
    duration_minutes: parseInt(document.getElementById('svcDuration').value) || 60,
    price: parseFloat(document.getElementById('svcPrice').value) || 0,
    icon: document.getElementById('svcIcon').value || '🗓️',
  });
  if (data.success) { closeModal('serviceModal'); showToast('Service saved!', 'success'); loadServices(); }
  else { errEl.textContent = data.error || 'Failed to save.'; errEl.classList.add('show'); }
}

async function toggleService(id) {
  const data = await apiPost('../php/admin.php', { action: 'toggle_service', id });
  if (data.success) { showToast('Service updated.', 'success'); loadServices(); }
  else showToast('Failed.', 'error');
}

async function deleteService(id) {
  if (!confirm('Delete this service? This cannot be undone.')) return;
  const data = await apiPost('../php/admin.php', { action: 'delete_service', id });
  if (data.success) { showToast('Service deleted.', 'success'); loadServices(); }
  else showToast('Failed.', 'error');
}

function escHtml(s) { return s.replace(/"/g, '&quot;').replace(/'/g, "\\'"); }
</script>
</body>
</html>
