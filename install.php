<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Setup — Appoint</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
  <style>
    .setup-page { min-height:100vh; display:flex; align-items:center; justify-content:center; background:var(--paper); padding:24px; }
    .setup-card { width:100%; max-width:600px; background:var(--paper-card); border-radius:var(--radius-lg); padding:48px; box-shadow:var(--shadow-lg); border:1px solid var(--border); }
    .setup-step { display:none; }
    .setup-step.active { display:block; animation:fadeIn .3s ease; }
    .step-result { padding:14px; border-radius:8px; margin:8px 0; font-size:0.85rem; display:flex; align-items:center; gap:10px; }
    .step-result.ok  { background:var(--success-light); color:var(--success); }
    .step-result.err { background:var(--danger-light);  color:var(--danger); }
    .step-result.warn{ background:#fef9ee; color:#d4860a; }
  </style>
</head>
<body>
<div class="setup-page">
  <div class="setup-card">
    <div class="logo" style="margin-bottom:36px;">
      <span class="logo-mark">A</span>
      <span class="logo-text">ppoint</span>
      <span style="margin-left:14px;font-size:0.82rem;color:var(--ink-mute);font-family:var(--font-body);">Installation Wizard</span>
    </div>

    <!-- Step 1: DB Config -->
    <div class="setup-step active" id="step1">
      <div class="section-title mb-4">Database Configuration</div>
      <p class="section-sub">Enter your MySQL database details to get started.</p>
      <div class="form-row">
        <div class="form-group">
          <label>DB Host</label>
          <input type="text" id="dbHost" value="localhost">
        </div>
        <div class="form-group">
          <label>DB Port</label>
          <input type="text" id="dbPort" value="3306">
        </div>
      </div>
      <div class="form-group">
        <label>Database Name</label>
        <input type="text" id="dbName" value="appoint_db">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>DB Username</label>
          <input type="text" id="dbUser" value="root">
        </div>
        <div class="form-group">
          <label>DB Password</label>
          <input type="password" id="dbPass" placeholder="(leave blank if none)">
        </div>
      </div>
      <div id="dbResults" style="margin:16px 0;"></div>
      <button class="btn-primary full" onclick="testAndInstall()">
        <span>Test Connection & Install Database</span>
      </button>
    </div>

    <!-- Step 2: Admin Account -->
    <div class="setup-step" id="step2">
      <div class="section-title mb-4">Create Admin Account</div>
      <p class="section-sub">This will be your administrator login.</p>
      <div class="form-row">
        <div class="form-group">
          <label>First Name</label>
          <input type="text" id="adminFirst" placeholder="Admin">
        </div>
        <div class="form-group">
          <label>Last Name</label>
          <input type="text" id="adminLast" placeholder="User">
        </div>
      </div>
      <div class="form-group">
        <label>Admin Email</label>
        <input type="email" id="adminEmail" placeholder="admin@yourdomain.com">
      </div>
      <div class="form-group">
        <label>Admin Password</label>
        <input type="password" id="adminPwd" placeholder="Min. 8 characters">
      </div>
      <div id="adminResults" style="margin:16px 0;"></div>
      <button class="btn-primary full" onclick="createAdmin()">
        <span>Create Admin & Finish Setup</span>
      </button>
    </div>

    <!-- Step 3: Done -->
    <div class="setup-step" id="step3">
      <div class="confirm-success">
        <div class="confirm-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <h2 class="font-display" style="font-size:1.8rem;font-weight:300;margin-bottom:12px;">Setup Complete!</h2>
        <p class="text-mute" style="margin-bottom:28px;">Your Appoint system is ready. Use the links below to get started.</p>
        <div class="flex gap-3" style="justify-content:center;flex-wrap:wrap;">
          <a href="index.php" class="btn-primary">Go to Login</a>
          <a href="admin/index.php" class="btn-secondary">Admin Panel</a>
        </div>
        <div class="demo-creds" style="margin-top:24px;text-align:left;">
          <p><strong>Demo Customer:</strong> customer@demo.com / demo123</p>
          <p><strong>Your Admin:</strong> use the credentials you just created</p>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
function showStep(n) {
  document.querySelectorAll('.setup-step').forEach((s,i) => s.classList.toggle('active', i+1 === n));
}

function resultHTML(items) {
  return items.map(i => `<div class="step-result ${i.type}"><span>${i.type==='ok'?'✓':i.type==='err'?'✕':'⚠'}</span><span>${i.msg}</span></div>`).join('');
}

async function testAndInstall() {
  const dbResults = document.getElementById('dbResults');
  dbResults.innerHTML = '<div class="step-result warn">⏳ Testing connection...</div>';

  const res = await fetch('php/install.php', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({
      action:'install',
      host: document.getElementById('dbHost').value,
      port: document.getElementById('dbPort').value,
      name: document.getElementById('dbName').value,
      user: document.getElementById('dbUser').value,
      pass: document.getElementById('dbPass').value,
    })
  });
  const data = await res.json();
  dbResults.innerHTML = resultHTML(data.steps || []);
  if (data.success) setTimeout(() => showStep(2), 800);
}

async function createAdmin() {
  const adminResults = document.getElementById('adminResults');
  adminResults.innerHTML = '<div class="step-result warn">⏳ Creating account...</div>';

  const res = await fetch('php/install.php', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({
      action:'create_admin',
      first_name: document.getElementById('adminFirst').value,
      last_name:  document.getElementById('adminLast').value,
      email:      document.getElementById('adminEmail').value,
      password:   document.getElementById('adminPwd').value,
    })
  });
  const data = await res.json();
  adminResults.innerHTML = resultHTML(data.steps || []);
  if (data.success) setTimeout(() => showStep(3), 800);
}
</script>
</body>
</html>
