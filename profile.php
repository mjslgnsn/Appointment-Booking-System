<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Profile — Appoint</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<button class="hamburger" onclick="toggleSidebar()"><span></span><span></span><span></span></button>
<div class="app-layout">
  <?php include 'includes/sidebar.php'; ?>
  <main class="main-content">
    <div class="topbar">
      <span class="topbar-title">My Profile</span>
    </div>
    <div class="page-content">
      <div class="grid-2">
        <!-- Profile Card -->
        <div class="card">
          <div class="card-header"><span class="card-title">Personal Information</span></div>
          <div class="card-body">
            <div style="display:flex;align-items:center;gap:20px;margin-bottom:28px;">
              <div class="user-avatar" style="width:64px;height:64px;font-size:1.6rem;border-radius:50%;background:var(--gold);display:flex;align-items:center;justify-content:center;font-family:var(--font-display);color:var(--ink);font-weight:600;">
                <?= htmlspecialchars($_SESSION['avatar_initials'] ?? 'U') ?>
              </div>
              <div>
                <div style="font-family:var(--font-display);font-size:1.3rem;font-weight:400;"><?= htmlspecialchars(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '')) ?></div>
                <div style="font-size:0.82rem;color:var(--ink-mute);"><?= htmlspecialchars($_SESSION['email'] ?? '') ?></div>
              </div>
            </div>
            <form onsubmit="updateProfile(event)">
              <div class="form-row">
                <div class="form-group">
                  <label>First Name</label>
                  <input type="text" id="profFirst" value="<?= htmlspecialchars($_SESSION['first_name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                  <label>Last Name</label>
                  <input type="text" id="profLast" value="<?= htmlspecialchars($_SESSION['last_name'] ?? '') ?>">
                </div>
              </div>
              <div class="form-group">
                <label>Email Address</label>
                <input type="email" id="profEmail" value="<?= htmlspecialchars($_SESSION['email'] ?? '') ?>" required>
              </div>
              <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" id="profPhone" placeholder="+1 (555) 000-0000">
              </div>
              <div id="profileMsg" class="form-success"></div>
              <button type="submit" class="btn-primary">Save Changes</button>
            </form>
          </div>
        </div>

        <!-- Password -->
        <div>
          <div class="card mb-6">
            <div class="card-header"><span class="card-title">Change Password</span></div>
            <div class="card-body">
              <form onsubmit="changePassword(event)">
                <div class="form-group">
                  <label>Current Password</label>
                  <input type="password" id="pwCurrent" placeholder="••••••••" required>
                </div>
                <div class="form-group">
                  <label>New Password</label>
                  <input type="password" id="pwNew" placeholder="Min. 8 characters" required>
                </div>
                <div class="form-group">
                  <label>Confirm New Password</label>
                  <input type="password" id="pwConfirm" placeholder="••••••••" required>
                </div>
                <div id="pwMsg" class="form-success"></div>
                <div id="pwErr" class="form-error"></div>
                <button type="submit" class="btn-primary">Update Password</button>
              </form>
            </div>
          </div>

          <!-- Notification Preferences -->
          <div class="card">
            <div class="card-header"><span class="card-title">Notification Preferences</span></div>
            <div class="card-body">
              <?php foreach ([
                ['Email Confirmations','Receive email when a booking is confirmed'],
                ['Reminder Emails','24h reminder before each appointment'],
                ['Cancellation Alerts','Notify when a booking is cancelled'],
              ] as [$title, $desc]): ?>
              <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px solid var(--border);">
                <div>
                  <div style="font-weight:500;font-size:0.875rem;"><?= $title ?></div>
                  <div style="font-size:0.78rem;color:var(--ink-mute);"><?= $desc ?></div>
                </div>
                <label style="position:relative;display:inline-block;width:42px;height:24px;cursor:pointer;">
                  <input type="checkbox" checked style="display:none;" onchange="this.parentElement.querySelector('.toggle-track').style.background=this.checked?'var(--gold)':'var(--border)'">
                  <span class="toggle-track" style="position:absolute;inset:0;background:var(--gold);border-radius:12px;transition:.2s;"></span>
                  <span style="position:absolute;top:3px;left:3px;width:18px;height:18px;background:#fff;border-radius:50%;transition:.2s;"></span>
                </label>
              </div>
              <?php endforeach; ?>
              <button class="btn-primary" style="margin-top:16px;" onclick="showToast('Preferences saved!','success')">Save Preferences</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>
<div class="toast-container" id="toastContainer"></div>
<script src="js/app.js"></script>
<script>
async function updateProfile(e) {
  e.preventDefault();
  const msg = document.getElementById('profileMsg');
  const res = await apiPost('php/profile.php', {
    action: 'update',
    first_name: document.getElementById('profFirst').value,
    last_name: document.getElementById('profLast').value,
    email: document.getElementById('profEmail').value,
    phone: document.getElementById('profPhone').value,
  });
  msg.textContent = res.success ? 'Profile updated successfully!' : (res.error || 'Update failed.');
  msg.className = res.success ? 'form-success show' : 'form-error show';
}

async function changePassword(e) {
  e.preventDefault();
  const ok = document.getElementById('pwMsg');
  const err = document.getElementById('pwErr');
  ok.classList.remove('show'); err.classList.remove('show');
  const newPw = document.getElementById('pwNew').value;
  const conf  = document.getElementById('pwConfirm').value;
  if (newPw !== conf) { err.textContent = 'Passwords do not match.'; err.classList.add('show'); return; }
  const res = await apiPost('php/profile.php', {
    action: 'change_password',
    current: document.getElementById('pwCurrent').value,
    new_password: newPw
  });
  if (res.success) { ok.textContent = 'Password changed!'; ok.classList.add('show'); e.target.reset(); }
  else { err.textContent = res.error || 'Failed.'; err.classList.add('show'); }
}
</script>
</body>
</html>
