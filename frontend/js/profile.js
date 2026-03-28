/**
 * profile.js
 * Handles: load profile, update profile info, change password,
 *          password strength meter, password visibility toggles
 */

let originalProfile = {};

/* ── Alert ────────────────────────────────────────────────── */

function showAlert(elementId, message, type = 'success') {
  const el = document.getElementById(elementId);
  el.textContent  = message;
  el.className    = `profile-alert profile-alert--${type}`;
  el.style.display = 'block';
  clearTimeout(el._timer);
  el._timer = setTimeout(() => {
    el.style.display = 'none';
  }, 5000);
}

/* ── Load profile ─────────────────────────────────────────── */

async function loadProfile() {
  try {
    const res  = await fetch('/api/user/profile');
    const data = await res.json();

    if (data.status === 'success') {
      const p = data.data;
      originalProfile = p;

      // Form fields
      document.getElementById('name').value  = p.name;
      document.getElementById('email').value = p.email;

      // Read-only fields
      const verifiedEl = document.getElementById('emailVerified');
      verifiedEl.innerHTML = p.email_verified
        ? `<span class="badge badge--green"><i class="fa-solid fa-circle-check" style="font-size:9px"></i> Verified</span>`
        : `<span class="badge badge--amber"><i class="fa-solid fa-clock" style="font-size:9px"></i> Pending verification</span>`;

      document.getElementById('createdAt').textContent = new Date(p.created_at)
        .toLocaleDateString(undefined, { day: '2-digit', month: 'long', year: 'numeric' });

      // Identity header
      updateIdentityHeader(p.name, p.email);

    } else {
      showAlert('profileAlert', 'Failed to load profile', 'error');
    }
  } catch (err) {
    console.error(err);
    showAlert('profileAlert', 'Network error', 'error');
  }
}

function updateIdentityHeader(name, email) {
  const identity = document.getElementById('profile-identity');
  const avatar   = document.getElementById('profile-avatar');
  const nameEl   = document.getElementById('profile-display-name');
  const metaEl   = document.getElementById('profile-display-meta');

  if (!identity) return;

  avatar.textContent  = getInitials(name);
  nameEl.textContent  = name;
  metaEl.textContent  = email;
  identity.style.display = 'flex';
}

function getInitials(name) {
  if (!name) return '?';
  const parts = name.trim().split(/\s+/);
  return parts.length >= 2
    ? (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
    : name.substring(0, 2).toUpperCase();
}

/* ── Save profile ─────────────────────────────────────────── */

document.getElementById('profileForm').addEventListener('submit', async e => {
  e.preventDefault();

  const name  = document.getElementById('name').value.trim();
  const email = document.getElementById('email').value.trim();

  if (!name || !email) {
    showAlert('profileAlert', 'Please fill in all fields', 'error');
    return;
  }

  const btn = document.getElementById('saveProfileBtn');
  setButtonLoading(btn, 'Saving…');

  try {
    const res  = await fetch('/api/user/profile', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ name, email }),
    });
    const data = await res.json();

    if (res.ok) {
      showAlert('profileAlert', data.message || 'Profile updated successfully', 'success');
      originalProfile = { ...originalProfile, name, email };
      updateIdentityHeader(name, email);
    } else {
      showAlert('profileAlert', data.message || 'Failed to update profile', 'error');
    }
  } catch (err) {
    console.error(err);
    showAlert('profileAlert', 'Network error', 'error');
  } finally {
    resetButton(btn, 'Save changes');
  }
});

document.getElementById('cancelProfileBtn').addEventListener('click', () => {
  document.getElementById('name').value  = originalProfile.name  || '';
  document.getElementById('email').value = originalProfile.email || '';
});

/* ── Password strength ────────────────────────────────────── */

const newPasswordInput  = document.getElementById('newPassword');
const confirmInput      = document.getElementById('confirmPassword');
const strengthWrap      = document.getElementById('pw-strength');
const strengthLabel     = document.getElementById('pw-strength-label');
const matchLabel        = document.getElementById('matchLabel');

const STRENGTH_CONFIG = [
  { label: 'Very weak', color: 'var(--admin-red)',   bars: 1 },
  { label: 'Weak',      color: 'var(--admin-red)',   bars: 1 },
  { label: 'Fair',      color: 'var(--admin-amber)', bars: 2 },
  { label: 'Good',      color: 'var(--admin-amber)', bars: 3 },
  { label: 'Strong',    color: 'var(--admin-green)', bars: 4 },
];

function scorePassword(pw) {
  let score = 0;
  if (pw.length >= 8)            score++;
  if (/[A-Z]/.test(pw))          score++;
  if (/[a-z]/.test(pw))          score++;
  if (/\d/.test(pw))             score++;
  if (/[@$!%*?&]/.test(pw))      score++;
  return Math.min(score, 4); // 0–4
}

function renderStrengthMeter(score) {
  const cfg = STRENGTH_CONFIG[score];
  strengthLabel.textContent = cfg.label;
  strengthLabel.style.color = cfg.color;

  for (let i = 1; i <= 4; i++) {
    const bar = document.getElementById(`pw-bar-${i}`);
    bar.style.background = i <= cfg.bars ? cfg.color : 'var(--admin-bg3)';
  }
}

newPasswordInput.addEventListener('input', () => {
  const val = newPasswordInput.value;

  if (val.length === 0) {
    strengthWrap.style.display = 'none';
    return;
  }

  strengthWrap.style.display = 'flex';
  renderStrengthMeter(scorePassword(val));

  if (confirmInput.value.length > 0) checkPasswordMatch();
});

function checkPasswordMatch() {
  if (newPasswordInput.value !== confirmInput.value) {
    matchLabel.textContent = 'Passwords do not match';
    matchLabel.style.color = 'var(--admin-red)';
    return false;
  }
  matchLabel.textContent = '';
  return true;
}

confirmInput.addEventListener('input', checkPasswordMatch);

/* ── Change password ──────────────────────────────────────── */

document.getElementById('passwordForm').addEventListener('submit', async e => {
  e.preventDefault();

  const currentPw  = document.getElementById('currentPassword').value;
  const newPw      = newPasswordInput.value;
  const confirmPw  = confirmInput.value;

  if (!currentPw || !newPw || !confirmPw) {
    showAlert('passwordAlert', 'Please fill in all password fields', 'error');
    return;
  }
  if (newPw !== confirmPw) {
    showAlert('passwordAlert', 'New passwords do not match', 'error');
    return;
  }
  if (scorePassword(newPw) < 3) {
    showAlert('passwordAlert', 'Please choose a stronger password', 'error');
    return;
  }

  const btn = document.getElementById('changePasswordBtn');
  setButtonLoading(btn, 'Changing…');

  try {
    const res  = await fetch('/api/user/password', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        current_password: currentPw,
        new_password:     newPw,
        confirm_password: confirmPw,
      }),
    });
    const data = await res.json();

    if (res.ok) {
      showAlert('passwordAlert', data.message || 'Password updated successfully', 'success');
      document.getElementById('currentPassword').value = '';
      newPasswordInput.value  = '';
      confirmInput.value      = '';
      matchLabel.textContent  = '';
      strengthWrap.style.display = 'none';
    } else {
      showAlert('passwordAlert', data.message || 'Failed to update password', 'error');
    }
  } catch (err) {
    console.error(err);
    showAlert('passwordAlert', 'Network error', 'error');
  } finally {
    resetButton(btn, 'Change password');
  }
});

/* ── Password visibility toggles ─────────────────────────── */

document.querySelectorAll('.profile-toggle-pw').forEach(btn => {
  btn.addEventListener('click', () => {
    const input = document.getElementById(btn.dataset.target);
    const icon  = btn.querySelector('i');
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    icon.classList.toggle('fa-eye',       !isHidden);
    icon.classList.toggle('fa-eye-slash',  isHidden);
  });
});

/* ── Button helpers ───────────────────────────────────────── */

function setButtonLoading(btn, text) {
  btn.disabled   = true;
  btn.innerHTML  = `<i class="fa-solid fa-circle-notch fa-spin" style="font-size:11px"></i> ${text}`;
}

function resetButton(btn, text) {
  btn.disabled  = false;
  btn.innerHTML = text;
}

/* ── Init ─────────────────────────────────────────────────── */

loadProfile();