let currentPage = 1;
let currentLimit = 20;
let currentSearch = '';
let currentAction = null; // null = all, 'login', 'forgot_password'
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

function getActionBadge(action) {
  if (action === 'login') {
    return '<span class="action-badge action-login">Login</span>';
  }
  if (action === 'forgot_password') {
    return '<span class="action-badge action-forgot">Password Reset</span>';
  }
  return action;
}

async function loadRateLimits() {
  const url = new URL('/api/admin/rate-limits', window.location.origin);
  url.searchParams.set('page', currentPage);
  url.searchParams.set('limit', currentLimit);
  if (currentSearch) url.searchParams.set('search', currentSearch);
  if (currentAction) url.searchParams.set('action', currentAction);

  try {
    const res = await fetch(url);
    const data = await res.json();

    if (data.status === 'success') {
      renderRateLimits(data.data.rate_limits);
      totalPages = data.data.total_pages;
      updatePaginationControls();
    } else {
      showToast('Failed to load rate limits', 'error');
    }
  } catch (err) {
    console.error(err);
    showToast('Network error', 'error');
  }
}

function renderRateLimits(rateLimits) {
  const tbody = document.getElementById('rate-limits-table-body');

  if (!rateLimits || rateLimits.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6" style="text-align: center;">No rate limit records found</td></tr>';
    return;
  }

  tbody.innerHTML = rateLimits.map(limit => `
    <tr>
      <td>${limit.id}</td>
      <td><code>${escapeHtml(limit.identifier)}</code></td>
      <td>${getActionBadge(limit.action)}</td>
      <td class="${limit.attempts >= 5 ? 'attempts-high' : ''}">${limit.attempts}</td>
      <td>${new Date(limit.first_attempt_at).toLocaleString()}</td>
      <td>${new Date(limit.last_attempt_at).toLocaleString()}</td>
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
  loadRateLimits();
});

document.getElementById('action-filter').addEventListener('change', (e) => {
  const val = e.target.value;
  currentAction = val === 'all' ? null : val;
  currentPage = 1;
  loadRateLimits();
});

// Pagination
document.getElementById('prev-page').addEventListener('click', () => {
  if (currentPage > 1) {
    currentPage--;
    loadRateLimits();
  }
});
document.getElementById('next-page').addEventListener('click', () => {
  if (currentPage < totalPages) {
    currentPage++;
    loadRateLimits();
  }
});

// Export CSV
document.getElementById('export-csv').addEventListener('click', () => {
  const params = new URLSearchParams();
  if (currentSearch) params.set('search', currentSearch);
  if (currentAction) params.set('action', currentAction);
  window.location.href = '/api/admin/rate-limits/export?' + params.toString();
});

// Initial load
loadRateLimits();