let currentPage = 1;
let currentLimit = 5;
let currentSearch = '';
let currentVerified = null; // null = all, true = verified, false = unverified
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

async function loadUsers() {
  const url = new URL('/api/admin/users', window.location.origin);
  url.searchParams.set('page', currentPage);
  url.searchParams.set('limit', currentLimit);
  if (currentSearch) url.searchParams.set('search', currentSearch);
  if (currentVerified !== null) url.searchParams.set('verified', currentVerified);

  try {
    const res = await fetch(url);
    const data = await res.json();

    if (data.status === 'success') {
      renderUsers(data.data.users);
      totalPages = data.data.total_pages;
      updatePaginationControls();
    } else {
      showToast('Failed to load users', 'error');
    }
  } catch (err) {
    console.error(err);
    showToast('Network error', 'error');
  }
}

function renderUsers(users) {
  const tbody = document.getElementById('users-table-body');

  if (!users || users.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6" style="text-align: center;">No users found</td></tr>';
    return;
  }

  tbody.innerHTML = users.map(user => `
    <tr>
      <td>${user.id}</td>
      <td>${escapeHtml(user.name)}</td>
      <td>${escapeHtml(user.email)}</td>
      <td>${user.email_verified ? 'Yes' : 'No'}</td>
      <td>${new Date(user.created_at).toLocaleString()}</td>
      <td class="actions">
        ${!user.email_verified ? `<button class="btn-resend" data-id="${user.id}">Resend verification</button>` : ''}
        <button class="btn-delete" data-id="${user.id}" data-name="${escapeHtml(user.name)}">Delete</button>
      </td>
    </tr>
  `).join('');
}

function escapeHtml(str) {
  return str.replace(/[&<>]/g, function(m) {
    if (m === '&') return '&amp;';
    if (m === '<') return '&lt;';
    if (m === '>') return '&gt;';
    return m;
  });
}

// Event delegation for buttons
document.addEventListener('click', async (e) => {
  const target = e.target;

  if (target.classList.contains('btn-resend')) {
    const userId = target.dataset.id;
    if (!userId) return;
    try {
      const res = await fetch(`/api/admin/users/${userId}/resend-verification`, { method: 'POST' });
      const data = await res.json();
      if (res.ok) {
        showToast('Verification email resent', 'success');
      } else {
        showToast(data.message || 'Failed to resend', 'error');
      }
    } catch (err) {
      console.error(err);
      showToast('Network error', 'error');
    }
  }

  if (target.classList.contains('btn-delete')) {
    const userId = target.dataset.id;
    const userName = target.dataset.name;
    if (!userId) return;
    if (confirm(`Delete user "${userName}"? This action cannot be undone.`)) {
      try {
        const res = await fetch(`/api/admin/users/${userId}`, { method: 'DELETE' });
        const data = await res.json();
        if (res.ok) {
          showToast('User deleted', 'success');
          loadUsers(); // refresh the list
        } else {
          showToast(data.message || 'Delete failed', 'error');
        }
      } catch (err) {
        console.error(err);
        showToast('Network error', 'error');
      }
    }
  }
});

// Filters and search
document.getElementById('search-input').addEventListener('input', (e) => {
  currentSearch = e.target.value;
  currentPage = 1;
  loadUsers();
});

document.getElementById('verified-filter').addEventListener('change', (e) => {
  const val = e.target.value;
  if (val === 'verified') currentVerified = true;
  else if (val === 'unverified') currentVerified = false;
  else currentVerified = null;
  currentPage = 1;
  loadUsers();
});

// Pagination
document.getElementById('prev-page').addEventListener('click', () => {
  if (currentPage > 1) {
    currentPage--;
    loadUsers();
  }
});
document.getElementById('next-page').addEventListener('click', () => {
  if (currentPage < totalPages) {
    currentPage++;
    loadUsers();
  }
});

// Export CSV
document.getElementById('export-csv').addEventListener('click', () => {
  window.location.href = '/api/admin/users/export';
});

// Initial load
loadUsers();