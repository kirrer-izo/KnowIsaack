/**
 * password-strength.js
 * Reusable password strength meter and confirm password checker.
 * Include this script on any page that needs password validation.
 */

const STRENGTH_CONFIG = [
  { label: 'Very weak', color: '#f87171', bars: 1 },
  { label: 'Weak',      color: '#f87171', bars: 1 },
  { label: 'Fair',      color: '#fbbf24', bars: 2 },
  { label: 'Good',      color: '#fbbf24', bars: 3 },
  { label: 'Strong',    color: '#34d399', bars: 4 },
];

// Returns a score 0-4 based on password criteria
function scorePassword(pw) {
  let score = 0;
  if (pw.length >= 8)       score++;
  if (/[A-Z]/.test(pw))     score++;
  if (/[a-z]/.test(pw))     score++;
  if (/\d/.test(pw))        score++;
  if (/[@$!%*?&]/.test(pw)) score++;
  return Math.min(score, 4);
}

// Renders the 4 bar strength meter
function renderStrengthMeter(score, barPrefix, labelId) {
  const cfg      = STRENGTH_CONFIG[score];
  const labelEl  = document.getElementById(labelId);
  if (labelEl) {
    labelEl.textContent = cfg.label;
    labelEl.style.color = cfg.color;
  }
  for (let i = 1; i <= 4; i++) {
    const bar = document.getElementById(`${barPrefix}-${i}`);
    if (bar) bar.style.background = i <= cfg.bars ? cfg.color : 'var(--border-card)';
  }
}

// Initialises the strength meter on a password input
function initPasswordStrength(passwordInputId, barPrefix, labelId, strengthWrapperId) {
  const input       = document.getElementById(passwordInputId);
  const strengthWrap = strengthWrapperId ? document.getElementById(strengthWrapperId) : null;

  if (!input) return;

  input.addEventListener('input', () => {
    const val = input.value;

    if (val.length === 0) {
      if (strengthWrap) strengthWrap.style.display = 'none';
      return;
    }

    if (strengthWrap) strengthWrap.style.display = 'flex';
    renderStrengthMeter(scorePassword(val), barPrefix, labelId);
  });
}

// Initialises the confirm password match checker
function initConfirmPassword(passwordInputId, confirmInputId, matchLabelId) {
  const passwordInput = document.getElementById(passwordInputId);
  const confirmInput  = document.getElementById(confirmInputId);
  const matchLabel    = document.getElementById(matchLabelId);

  if (!passwordInput || !confirmInput) return;

  function checkMatch() {
    if (confirmInput.value.length === 0) return true;
    if (passwordInput.value !== confirmInput.value) {
      confirmInput.style.borderColor = '#f87171';
      if (matchLabel) {
        matchLabel.textContent = 'Passwords do not match';
        matchLabel.style.color = '#f87171';
      }
      return false;
    }
    confirmInput.style.borderColor = '#34d399';
    if (matchLabel) matchLabel.textContent = '';
    return true;
  }

  passwordInput.addEventListener('input', checkMatch);
  confirmInput.addEventListener('input', checkMatch);

  // Returns true if passwords match — use in form submit guard
  return checkMatch;
}