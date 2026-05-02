// auth.js — Login & Registration logic

function switchTab(tab) {
  document.querySelectorAll('.tab').forEach((t, i) => {
    t.classList.toggle('active', (i === 0 && tab === 'login') || (i === 1 && tab === 'register'));
  });
  document.querySelectorAll('.auth-form').forEach((f, i) => {
    f.classList.toggle('active', (i === 0 && tab === 'login') || (i === 1 && tab === 'register'));
  });
}

async function handleLogin(e) {
  e.preventDefault();
  const btn = e.target.querySelector('button[type=submit]');
  const errEl = document.getElementById('loginError');
  errEl.classList.remove('show');
  btn.innerHTML = '<span class="loading"></span>';

  const res = await fetch('php/auth.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      action: 'login',
      email: document.getElementById('loginEmail').value,
      password: document.getElementById('loginPassword').value
    })
  });

  const data = await res.json();

  if (data.success) {
    btn.innerHTML = '✓ Redirecting...';
    window.location.href = data.redirect || 'dashboard.php';
  } else {
    errEl.textContent = data.error || 'Invalid credentials. Try the demo accounts below.';
    errEl.classList.add('show');
    btn.innerHTML = '<span>Sign In</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M5 12h14M12 5l7 7-7 7"/></svg>';
  }
}

async function handleRegister(e) {
  e.preventDefault();
  const btn = e.target.querySelector('button[type=submit]');
  const errEl = document.getElementById('registerError');
  const okEl = document.getElementById('registerSuccess');
  errEl.classList.remove('show');
  okEl.classList.remove('show');
  btn.innerHTML = '<span class="loading"></span>';

  const pwd = document.getElementById('regPassword').value;
  if (pwd.length < 8) {
    errEl.textContent = 'Password must be at least 8 characters.';
    errEl.classList.add('show');
    btn.innerHTML = '<span>Create Account</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M5 12h14M12 5l7 7-7 7"/></svg>';
    return;
  }

  const res = await fetch('php/auth.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      action: 'register',
      first_name: document.getElementById('regFirst').value,
      last_name: document.getElementById('regLast').value,
      email: document.getElementById('regEmail').value,
      phone: document.getElementById('regPhone').value,
      password: pwd
    })
  });

  const data = await res.json();

  if (data.success) {
    okEl.textContent = 'Account created! Redirecting to dashboard...';
    okEl.classList.add('show');
    setTimeout(() => window.location.href = 'dashboard.php', 1200);
  } else {
    errEl.textContent = data.error || 'Registration failed. Please try again.';
    errEl.classList.add('show');
    btn.innerHTML = '<span>Create Account</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M5 12h14M12 5l7 7-7 7"/></svg>';
  }
}
