/**
 * admin-rate-limits.js
 * Handles: rate limit listing, search, action filter,
 *          pagination, summary stat cards, CSV export
 */

const MAX_ATTEMPTS = 5; // Must match PHP RateLimiterService

let currentPage   = 1;
let currentLimit  = 20;
let currentSearch = '';
let currentAction = null; // null = all, 'login', 'forgot_password'
let totalPages    = 1;

/* ── Toast ────────────────────────────────────────────────── */

function showToast(message, type = 'success') {
  const toast = document.getElementById('toast');
  toast.textContent = message;
  toast.className   = `admin-toast admin-toast--${type} admin-toast--visible`;
  clearTimeout(toast._timer);
  toast._timer = setTimeout(() => {
    toast.classList.remove('admin-toast--visible');
  }, 3500);
}

/* ── Load ─────────────────────────────────────────────────── */

async function loadRateLimits() {
  const url = new URL('/api/admin/rate-limits', window.location.origin);
  url.searchParams.set('page',  currentPage);
  url.searchParams.set('limit', currentLimit);
  if (currentSearch) url.searchParams.set('search', currentSearch);
  if (currentAction) url.searchParams.set('action', currentAction);

  setTableLoading();

  try {
    const res  = await fetch(url);
    const data = await res.json();

    if (data.status === 'success') {
      renderRateLimits(data.data.rate_limits);
      totalPages = data.data.total_pages;
      updateMeta(data.data.total, data.data.rate_limits.length);
      updatePagination();
      updateStatCards(data.data.rate_limits, data.data.total);
    } else {
      showToast('Failed to load rate limits', 'error');
      setTableEmpty('Could not load rate limits. Please try again.');
    }
  } catch (err) {
    console.error(err);
    showToast('Network error', 'error');
    setTableEmpty('Network error. Please check your connection.');
  }
}

function setTableLoading() {
  document.getElementById('rate-limits-table-body').innerHTML = `
    <tr>
      <td colspan="6" class="admin-table__loading">
        <i class="fa-solid fa-circle-notch fa-spin"></i> Loading rate limits…
      </td>
    </tr>`;
}

function setTableEmpty(msg) {
  document.getElementById('rate-limits-table-body').innerHTML = `
    <tr><td colspan="6" class="admin-table__empty">${escapeHtml(msg)}</td></tr>`;
}

/* ── Render ───────────────────────────────────────────────── */

function renderRateLimits(limits) {
  const tbody = document.getElementById('rate-limits-table-body');

  if (!limits || limits.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="6" class="admin-table__empty">
          <i class="fa-solid fa-shield-halved" style="font-size:24px;margin-bottom:8px;display:block;opacity:.3"></i>
          No active rate limits — all clear
        </td>
      </tr>`;
    return;
  }

  tbody.innerHTML = limits.map(limit => {
    const actionBadge   = getActionBadge(limit.action);
    const attemptsCell  = getAttemptsCell(limit.attempts);
    const firstDt       = formatDateTime(limit.first_attempt_at);
    const lastDt        = formatDateTime(limit.last_attempt_at);
    const isMaxed       = limit.attempts >= MAX_ATTEMPTS;

    return `
      <tr${isMaxed ? ' class="rate-row--blocked"' : ''}>
        <td style="color:var(--admin-text3);font-size:12px">${limit.id}</td>
        <td>
          <div style="display:flex;align-items:center;gap:8px">
            ${isMaxed ? '<i class="fa-solid fa-ban" style="font-size:11px;color:var(--admin-red);flex-shrink:0"></i>' : ''}
            <code class="admin-code">${escapeHtml(limit.identifier)}</code>
          </div>
        </td>
        <td>${actionBadge}</td>
        <td>${attemptsCell}</td>
        <td>
          <div style="font-size:13px;color:var(--admin-text2)">${firstDt.date}</div>
          <div style="font-size:11px;color:var(--admin-text3)">${firstDt.time}</div>
        </td>
        <td>
          <div style="font-size:13px;color:var(--admin-text2)">${lastDt.date}</div>
          <div style="font-size:11px;color:var(--admin-text3)">${lastDt.time}</div>
        </td>
      </tr>`;
  }).join('');
}

function getActionBadge(action) {
  if (action === 'login') {
    return `<span class="badge badge--blue">
              <i class="fa-solid fa-right-to-bracket" style="font-size:9px"></i> Login
            </span>`;
  }
  if (action === 'forgot_password') {
    return `<span class="badge badge--amber">
              <i class="fa-solid fa-key" style="font-size:9px"></i> Password reset
            </span>`;
  }
  return `<span class="badge badge--purple">${escapeHtml(action)}</span>`;
}

function getAttemptsCell(attempts) {
  const pct   = Math.min(Math.round((attempts / MAX_ATTEMPTS) * 100), 100);
  const color = attempts >= MAX_ATTEMPTS
    ? 'var(--admin-red)'
    : attempts >= 3
      ? 'var(--admin-amber)'
      : 'var(--admin-blue)';

  const label = attempts >= MAX_ATTEMPTS
    ? `<span style="color:var(--admin-red);font-weight:600">${attempts}/${MAX_ATTEMPTS}</span>`
    : `<span style="color:var(--admin-text2)">${attempts}/${MAX_ATTEMPTS}</span>`;

  return `<div style="display:flex;flex-direction:column;gap:4px;min-width:80px">
            <div style="display:flex;align-items:center;gap:6px">
              ${label}
              ${attempts >= MAX_ATTEMPTS ? '<span class="badge badge--red" style="padding:1px 6px;font-size:10px">Blocked</span>' : ''}
            </div>
            <div style="height:3px;background:var(--admin-bg3);border-radius:999px;overflow:hidden">
              <div style="height:100%;width:${pct}%;background:${color};border-radius:999px;transition:width .3s"></div>
            </div>
          </div>`;
}

/* ── Stat cards ───────────────────────────────────────────── */

function updateStatCards(limits, total) {
  // Only count from the current (possibly filtered) page; totals come from API
  setText('stat-total', total ?? limits.length);

  // Count by action type from ALL data if total_by_action provided, else from current page
  const loginCount = limits.filter(l => l.action === 'login').length;
  const resetCount = limits.filter(l => l.action === 'forgot_password').length;

  // If the API returns aggregate counts, prefer those
  setText('stat-login', loginCount);
  setText('stat-reset', resetCount);
}

function setText(id, value) {
  const el = document.getElementById(id);
  if (el) el.textContent = value ?? '—';
}

/* ── Pagination & meta ────────────────────────────────────── */

function updatePagination() {
  document.getElementById('prev-page').disabled = currentPage <= 1;
  document.getElementById('next-page').disabled = currentPage >= totalPages;
  document.getElementById('page-info').textContent = `Page ${currentPage} of ${totalPages}`;
}

function updateMeta(total, shown) {
  const meta = document.getElementById('results-meta');
  if (!meta || total === undefined) return;
  const start = (currentPage - 1) * currentLimit + 1;
  const end   = start + shown - 1;
  meta.textContent = total > 0
    ? `Showing ${start}–${end} of ${total} record${total !== 1 ? 's' : ''}`
    : '0 records';
}

/* ── Event listeners ──────────────────────────────────────── */

// Search (debounced)
let searchTimer;
document.getElementById('search-input').addEventListener('input', e => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    currentSearch = e.target.value.trim();
    currentPage   = 1;
    loadRateLimits();
  }, 300);
});

// Action filter
document.getElementById('action-filter').addEventListener('change', e => {
  currentAction = e.target.value === 'all' ? null : e.target.value;
  currentPage   = 1;
  loadRateLimits();
});

// Pagination
document.getElementById('prev-page').addEventListener('click', () => {
  if (currentPage > 1) { currentPage--; loadRateLimits(); }
});
document.getElementById('next-page').addEventListener('click', () => {
  if (currentPage < totalPages) { currentPage++; loadRateLimits(); }
});

// Export CSV — carries active filters
document.getElementById('export-csv').addEventListener('click', () => {
  const params = new URLSearchParams();
  if (currentSearch) params.set('search', currentSearch);
  if (currentAction) params.set('action', currentAction);
  window.location.href = '/api/admin/rate-limits/export?' + params.toString();
});

/* ── Helpers ──────────────────────────────────────────────── */

function formatDateTime(dateStr) {
  if (!dateStr) return { date: '—', time: '' };
  const dt = new Date(dateStr);
  return {
    date: dt.toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' }),
    time: dt.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' }),
  };
}

function escapeHtml(str) {
  if (str == null) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

/* ── Init ─────────────────────────────────────────────────── */

loadRateLimits();