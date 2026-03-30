/**
 * admin-logs.js
 * Handles: login activity listing, search, result filter,
 *          date range filter, pagination, CSV export (with active filters)
 */

let currentPage    = 1;
let currentLimit   = 20;
let currentSearch  = '';
let currentSuccess = null; // null = all, true = success, false = failed
let currentDateFrom = '';
let currentDateTo   = '';
let totalPages     = 1;

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

/* ── Load logs ────────────────────────────────────────────── */

async function loadLogs() {
  const url = new URL('/api/admin/logs', window.location.origin);
  url.searchParams.set('page',  currentPage);
  url.searchParams.set('limit', currentLimit);
  if (currentSearch)            url.searchParams.set('search',    currentSearch);
  if (currentSuccess !== null)  url.searchParams.set('success',   currentSuccess);
  if (currentDateFrom)          url.searchParams.set('date_from', currentDateFrom);
  if (currentDateTo)            url.searchParams.set('date_to',   currentDateTo);

  setTableLoading();

  try {
    const res  = await fetch(url);
    const data = await res.json();

    if (data.status === 'success') {
      renderLogs(data.data.logs);
      totalPages = data.data.total_pages;
      updateMeta(data.data.total, data.data.logs.length);
      updatePagination();
    } else {
      showToast('Failed to load logs', 'error');
      setTableEmpty('Could not load logs. Please try again.');
    }
  } catch (err) {
    console.error(err);
    showToast('Network error', 'error');
    setTableEmpty('Network error. Please check your connection.');
  }
}

function setTableLoading() {
  document.getElementById('logs-table-body').innerHTML = `
    <tr>
      <td colspan="6" class="admin-table__loading">
        <i class="fa-solid fa-circle-notch fa-spin"></i> Loading logs…
      </td>
    </tr>`;
}

function setTableEmpty(msg) {
  document.getElementById('logs-table-body').innerHTML = `
    <tr><td colspan="6" class="admin-table__empty">${escapeHtml(msg)}</td></tr>`;
}

/* ── Render ───────────────────────────────────────────────── */

function renderLogs(logs) {
  const tbody = document.getElementById('logs-table-body');

  if (!logs || logs.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="6" class="admin-table__empty">
          <i class="fa-solid fa-list" style="font-size:24px;margin-bottom:8px;display:block;opacity:.3"></i>
          No log entries found
        </td>
      </tr>`;
    return;
  }

  tbody.innerHTML = logs.map(log => {
    const user = log.user_name
      ? `<div style="display:flex;align-items:center;gap:8px">
           <div class="user-avatar" style="width:26px;height:26px;font-size:10px">${getInitials(log.user_name)}</div>
           <div>
             <div style="font-size:13px;font-weight:600;color:var(--admin-text)">${escapeHtml(log.user_name)}</div>
             <div style="font-size:11px;color:var(--admin-text3)">ID ${log.user_id}</div>
           </div>
         </div>`
      : `<span style="color:var(--admin-text3);font-size:13px">Guest</span>`;

    const resultBadge = log.success
      ? `<span class="badge badge--green"><i class="fa-solid fa-check" style="font-size:9px"></i> Success</span>`
      : `<span class="badge badge--red"><i class="fa-solid fa-xmark" style="font-size:9px"></i> Failed</span>`;

    const dt = new Date(log.created_at);
    const dateStr = dt.toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' });
    const timeStr = dt.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });

    return `
      <tr>
        <td style="color:var(--admin-text3);font-size:12px">${log.id}</td>
        <td>${user}</td>
        <td style="font-size:13px;color:var(--admin-text2)">${escapeHtml(log.attempted_email || '—')}</td>
        <td>
          <code class="admin-code">${escapeHtml(log.ip_address)}</code>
        </td>
        <td>${resultBadge}</td>
        <td>
          <div style="font-size:13px;color:var(--admin-text2)">${dateStr}</div>
          <div style="font-size:11px;color:var(--admin-text3)">${timeStr}</div>
        </td>
      </tr>`;
  }).join('');
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

  const filters = [];
  if (currentSuccess === true)  filters.push('successful');
  if (currentSuccess === false) filters.push('failed');
  if (currentDateFrom || currentDateTo) filters.push('date filtered');

  const filterNote = filters.length ? ` (${filters.join(', ')})` : '';
  meta.textContent = total > 0
    ? `Showing ${start}–${end} of ${total} entr${total !== 1 ? 'ies' : 'y'}${filterNote}`
    : `0 entries${filterNote}`;
}

/* ── Event listeners ──────────────────────────────────────── */

// Search (debounced)
let searchTimer;
document.getElementById('search-input').addEventListener('input', e => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    currentSearch = e.target.value.trim();
    currentPage   = 1;
    loadLogs();
  }, 300);
});

// Result filter
document.getElementById('success-filter').addEventListener('change', e => {
  const val = e.target.value;
  currentSuccess = val === 'success' ? true : val === 'failed' ? false : null;
  currentPage = 1;
  loadLogs();
});

// Date range
document.getElementById('date-from').addEventListener('change', e => {
  currentDateFrom = e.target.value;
  currentPage = 1;
  loadLogs();
});

document.getElementById('date-to').addEventListener('change', e => {
  currentDateTo = e.target.value;
  currentPage = 1;
  loadLogs();
});

// Clear date range
document.getElementById('clear-dates').addEventListener('click', () => {
  currentDateFrom = '';
  currentDateTo   = '';
  document.getElementById('date-from').value = '';
  document.getElementById('date-to').value   = '';
  currentPage = 1;
  loadLogs();
});

// Pagination
document.getElementById('prev-page').addEventListener('click', () => {
  if (currentPage > 1) { currentPage--; loadLogs(); }
});
document.getElementById('next-page').addEventListener('click', () => {
  if (currentPage < totalPages) { currentPage++; loadLogs(); }
});

// Export CSV — passes active filters so export matches current view
document.getElementById('export-csv').addEventListener('click', () => {
  const params = new URLSearchParams();
  if (currentSearch)           params.set('search',    currentSearch);
  if (currentSuccess !== null) params.set('success',   currentSuccess);
  if (currentDateFrom)         params.set('date_from', currentDateFrom);
  if (currentDateTo)           params.set('date_to',   currentDateTo);
  window.location.href = '/api/admin/logs/export?' + params.toString();
});

/* ── Helpers ──────────────────────────────────────────────── */

function getInitials(name) {
  if (!name) return '?';
  const parts = name.trim().split(/\s+/);
  return parts.length >= 2
    ? (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
    : name.substring(0, 2).toUpperCase();
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

loadLogs();