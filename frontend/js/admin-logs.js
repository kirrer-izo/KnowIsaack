let currentPage = 1;
let currentLimit = 20;
let currentSearch = '';
let currentSuccess = null; // null = all, true = success, false = failed
let currentDateFrom = '';
let currentDateTo = '';
let totalPages = 1;

const toastEl = document.getElementById('toast');

function showToast(message, type = 'success') {
  toastEl.textContent = message;
  toastEl.className = `toast ${type}`;
  toastEl.style.display = 'block';
  setTimeout(() => {
    toastEl.style.display = 'none';
  }, 3500);
}

function updatePaginationControls() {
  const prevBtn = document.getElementById('prev-page');
  const nextBtn = document.getElementById('next-page');
  const pageInfo = document.getElementById('page-info');

  prevBtn.disabled = currentPage === 1;
  nextBtn.disabled = currentPage === totalPages;
  pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
}

async function loadLogs() {
  const url = new URL('/api/admin/logs', window.location.origin);
  url.searchParams.set('page', currentPage);
  url.searchParams.set('limit', currentLimit);
  if (currentSearch) url.searchParams.set('search', currentSearch);
  if (currentSuccess !== null) url.searchParams.set('success', currentSuccess);
  if (currentDateFrom) url.searchParams.set('date_from', currentDateFrom);
  if (currentDateTo) url.searchParams.set('date_to', currentDateTo);

  try {
    const res = await fetch(url);
    const data = await res.json();

    if (data.status === 'success') {
      renderLogs(data.data.logs);
      totalPages = data.data.total_pages;
      updatePaginationControls();
    } else {
      showToast('Failed to load logs', 'error');
    }
  } catch (err) {
    console.error(err);
    showToast('Network error', 'error');
  }
}

function renderLogs(logs) {
  const tbody = document.getElementById('logs-table-body');

  if (!logs || logs.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6" style="text-align: center;">No logs found</td></tr>';
    return;
  }

  tbody.innerHTML = logs.map(log => `
    <tr>
      <td>${log.id}</td>
      <td>${log.user_name ? escapeHtml(log.user_name) + ' (ID: ' + log.user_id + ')' : '—'}</td>
      <td>${escapeHtml(log.attempted_email || '—')}</td>
      <td>${escapeHtml(log.ip_address)}</td>
      <td>
        ${log.success
          ? '<span class="success-badge"><i class="fa-solid fa-check"></i> Success</span>'
          : '<span class="failed-badge"><i class="fa-solid fa-xmark"></i> Failed</span>'
        }
      </td>
      <td>${new Date(log.created_at).toLocaleString()}</td>
    </tr>
  `).join('');
}

function escapeHtml(str) {
  if (!str) return '';
  return str.replace(/[&<>]/g, function(m) {
    if (m === '&') return '&amp;';
    if (m === '<') return '&lt;';
    if (m === '>') return '&gt;';
    return m;
  });
}

// Filters and search
document.getElementById('search-input').addEventListener('input', (e) => {
  currentSearch = e.target.value;
  currentPage = 1;
  loadLogs();
});

document.getElementById('success-filter').addEventListener('change', (e) => {
  const val = e.target.value;
  if (val === 'success') currentSuccess = true;
  else if (val === 'failed') currentSuccess = false;
  else currentSuccess = null;
  currentPage = 1;
  loadLogs();
});

document.getElementById('date-from').addEventListener('change', (e) => {
  currentDateFrom = e.target.value;
  currentPage = 1;
  loadLogs();
});

document.getElementById('date-to').addEventListener('change', (e) => {
  currentDateTo = e.target.value;
  currentPage = 1;
  loadLogs();
});

// Pagination
document.getElementById('prev-page').addEventListener('click', () => {
  if (currentPage > 1) {
    currentPage--;
    loadLogs();
  }
});
document.getElementById('next-page').addEventListener('click', () => {
  if (currentPage < totalPages) {
    currentPage++;
    loadLogs();
  }
});

// Export CSV
document.getElementById('export-csv').addEventListener('click', () => {
  const params = new URLSearchParams();
  if (currentSearch) params.set('search', currentSearch);
  if (currentSuccess !== null) params.set('success', currentSuccess);
  if (currentDateFrom) params.set('date_from', currentDateFrom);
  if (currentDateTo) params.set('date_to', currentDateTo);
  window.location.href = '/api/admin/logs/export?' + params.toString();
});

// Initial load
loadLogs();