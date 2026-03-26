let originalProfile = {};

// Show alert message
function showAlert(elementId, message, type = 'success') {
  const alert = document.getElementById(elementId);
  alert.textContent = message;
  alert.className = `alert alert-${type} visible`;
  setTimeout(() => {
    alert.classList.remove('visible');
  }, 5000);
}

// Load user profile
async function loadProfile() {
  try {
    const response = await fetch('/api/user/profile');
    const data = await response.json();

    if (data.status === 'success') {
      originalProfile = data.data;
      document.getElementById('name').value = data.data.name;
      document.getElementById('email').value = data.data.email;
      document.getElementById('emailVerified').value = data.data.email_verified ? 'Yes' : 'No';
      document.getElementById('createdAt').value = new Date(data.data.created_at).toLocaleDateString();
    } else {
      showAlert('profileAlert', 'Failed to load profile', 'error');
    }
  } catch (err) {
    console.error(err);
    showAlert('profileAlert', 'Network error', 'error');
  }
}

// Save profile changes
document.getElementById('profileForm').addEventListener('submit', async (e) => {
  e.preventDefault();

  const name = document.getElementById('name').value.trim();
  const email = document.getElementById('email').value.trim();

  if (!name || !email) {
    showAlert('profileAlert', 'Please fill in all fields', 'error');
    return;
  }

  const submitBtn = document.getElementById('saveProfileBtn');
  const originalText = submitBtn.innerHTML;
  submitBtn.innerHTML = '<span class="loading-spinner"></span> Saving...';
  submitBtn.disabled = true;

  try {
    const response = await fetch('/api/user/profile', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ name, email })
    });

    const data = await response.json();

    if (response.ok) {
      showAlert('profileAlert', data.message, 'success');
      originalProfile = { ...originalProfile, name, email };
    } else {
      showAlert('profileAlert', data.message || 'Failed to update profile', 'error');
    }
  } catch (err) {
    console.error(err);
    showAlert('profileAlert', 'Network error', 'error');
  } finally {
    submitBtn.innerHTML = originalText;
    submitBtn.disabled = false;
  }
});

// Cancel button - revert changes
document.getElementById('cancelProfileBtn').addEventListener('click', () => {
  document.getElementById('name').value = originalProfile.name;
  document.getElementById('email').value = originalProfile.email;
});

// Password strength validation
const passwordInput = document.getElementById('newPassword');
const confirmInput = document.getElementById('confirmPassword');
const strengthLabel = document.getElementById('strengthLabel');
const matchLabel = document.getElementById('matchLabel');

function checkPasswordStrength(password) {
  let strength = 0;
  if (password.length >= 8) strength++;
  if (/[A-Z]/.test(password)) strength++;
  if (/[a-z]/.test(password)) strength++;
  if (/\d/.test(password)) strength++;
  if (/[@$!%*?&]/.test(password)) strength++;

  if (strength <= 2) {
    strengthLabel.textContent = 'Weak password';
    strengthLabel.style.color = '#f87171';
  } else if (strength === 3 || strength === 4) {
    strengthLabel.textContent = 'Moderate password';
    strengthLabel.style.color = '#fbbf24';
  } else {
    strengthLabel.textContent = 'Strong password';
    strengthLabel.style.color = '#34d399';
  }
  return strength >= 4;
}

passwordInput.addEventListener('input', () => {
  const value = passwordInput.value;
  if (value.length === 0) {
    strengthLabel.textContent = '';
    return;
  }
  checkPasswordStrength(value);
  if (confirmInput.value.length > 0) {
    checkPasswordMatch();
  }
});

function checkPasswordMatch() {
  if (passwordInput.value !== confirmInput.value) {
    matchLabel.textContent = 'Passwords do not match';
    matchLabel.style.color = '#f87171';
    return false;
  } else {
    matchLabel.textContent = '';
    return true;
  }
}

confirmInput.addEventListener('input', () => {
  checkPasswordMatch();
});

// Change password
document.getElementById('passwordForm').addEventListener('submit', async (e) => {
  e.preventDefault();

  const currentPassword = document.getElementById('currentPassword').value;
  const newPassword = passwordInput.value;
  const confirmPassword = confirmInput.value;

  if (!currentPassword || !newPassword || !confirmPassword) {
    showAlert('passwordAlert', 'Please fill in all password fields', 'error');
    return;
  }

  if (newPassword !== confirmPassword) {
    showAlert('passwordAlert', 'New passwords do not match', 'error');
    return;
  }

  if (!checkPasswordStrength(newPassword)) {
    showAlert('passwordAlert', 'Please choose a stronger password', 'error');
    return;
  }

  const submitBtn = document.getElementById('changePasswordBtn');
  const originalText = submitBtn.innerHTML;
  submitBtn.innerHTML = '<span class="loading-spinner"></span> Changing...';
  submitBtn.disabled = true;

  try {
    const response = await fetch('/api/user/password', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        current_password: currentPassword,
        new_password: newPassword,
        confirm_password: confirmPassword
      })
    });

    const data = await response.json();

    if (response.ok) {
      showAlert('passwordAlert', data.message, 'success');
      document.getElementById('currentPassword').value = '';
      passwordInput.value = '';
      confirmInput.value = '';
      strengthLabel.textContent = '';
      matchLabel.textContent = '';
    } else {
      showAlert('passwordAlert', data.message || 'Failed to update password', 'error');
    }
  } catch (err) {
    console.error(err);
    showAlert('passwordAlert', 'Network error', 'error');
  } finally {
    submitBtn.innerHTML = originalText;
    submitBtn.disabled = false;
  }
});

// Password visibility toggle for all password fields
document.querySelectorAll('.toggle-password').forEach(button => {
  button.addEventListener('click', () => {
    const targetId = button.getAttribute('data-target');
    const input = document.getElementById(targetId);
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
      input.type = 'text';
      icon.classList.remove('fa-eye');
      icon.classList.add('fa-eye-slash');
    } else {
      input.type = 'password';
      icon.classList.remove('fa-eye-slash');
      icon.classList.add('fa-eye');
    }
  });
});

// Initial load
loadProfile();