/**
 * admin-projects.js
 * Handles: project listing, search/filter, pagination, delete with modal confirm, CSV export
 */

let currentPage     = 1;
let currentLimit    = 10;
let currentSearch   = '';
let currentFeatured = null; // null = all, true = featured, false = not featured
let totalPages      = 1;
let pendingDeleteId = null;

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

/* ── Load projects ────────────────────────────────────────── */

async function loadProjects() {
  const url = new URL('/api/admin/projects', window.location.origin);
  url.searchParams.set('page',  currentPage);
  url.searchParams.set('limit', currentLimit);
  if (currentSearch)       url.searchParams.set('search',   currentSearch);
  if (currentFeatured !== null) url.searchParams.set('featured', currentFeatured);

  setTableLoading(true);

  try {
    const res  = await fetch(url);
    const data = await res.json();

    if (data.status === 'success') {
      renderProjects(data.data.projects);
      totalPages = data.data.total_pages;
      updateMeta(data.data.total, data.data.projects.length);
      updatePagination();
    } else {
      showToast('Failed to load projects', 'error');
      setTableEmpty('Could not load projects. Please try again.');
    }
  } catch (err) {
    console.error(err);
    showToast('Network error', 'error');
    setTableEmpty('Network error. Please check your connection.');
  }
}

function setTableLoading(on) {
  if (!on) return;
  document.getElementById('projects-table-body').innerHTML = `
    <tr>
      <td colspan="7" class="admin-table__loading">
        <i class="fa-solid fa-circle-notch fa-spin"></i> Loading projects…
      </td>
    </tr>`;
}

function setTableEmpty(msg) {
  document.getElementById('projects-table-body').innerHTML = `
    <tr><td colspan="7" class="admin-table__empty">${escapeHtml(msg)}</td></tr>`;
}

/* ── Render ───────────────────────────────────────────────── */

function renderProjects(projects) {
  const tbody = document.getElementById('projects-table-body');

  if (!projects || projects.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="7" class="admin-table__empty">
          <i class="fa-solid fa-folder-open" style="font-size:24px;margin-bottom:8px;display:block;opacity:.3"></i>
          No projects found
        </td>
      </tr>`;
    return;
  }

  tbody.innerHTML = projects.map(p => {
    const desc  = escapeHtml(p.description.substring(0, 90)) + (p.description.length > 90 ? '…' : '');
    const tags  = (p.tech_stack || [])
      .map(t => `<span class="badge badge--blue">${escapeHtml(t)}</span>`)
      .join(' ');
    const feat  = p.featured
      ? `<span class="badge badge--amber"><i class="fa-solid fa-star" style="font-size:9px"></i> Featured</span>`
      : `<span style="color:var(--admin-text3);font-size:13px">—</span>`;
    const date  = new Date(p.created_at).toLocaleDateString(undefined, {
      day: '2-digit', month: 'short', year: 'numeric'
    });

    return `
      <tr>
        <td style="color:var(--admin-text3);font-size:12px">${p.id}</td>
        <td class="cell-primary">${escapeHtml(p.title)}</td>
        <td style="max-width:220px;color:var(--admin-text2)">${desc}</td>
        <td class="tech-stack-cell">${tags}</td>
        <td>${feat}</td>
        <td style="color:var(--admin-text3);font-size:12px;white-space:nowrap">${date}</td>
        <td class="cell-actions">
          <a href="/admin/edit?id=${p.id}" class="table-btn">
            <i class="fa-solid fa-pen" style="font-size:11px"></i> Edit
          </a>
          <button
            class="table-btn table-btn--danger js-delete-btn"
            data-id="${p.id}"
            data-title="${escapeHtml(p.title)}"
          >
            <i class="fa-solid fa-trash" style="font-size:11px"></i> Delete
          </button>
        </td>
      </tr>`;
  }).join('');
}

/* ── Pagination & meta ────────────────────────────────────── */

function updatePagination() {
  const prev = document.getElementById('prev-page');
  const next = document.getElementById('next-page');
  const info = document.getElementById('page-info');

  prev.disabled = currentPage <= 1;
  next.disabled = currentPage >= totalPages;
  info.textContent = `Page ${currentPage} of ${totalPages}`;
}

function updateMeta(total, shown) {
  const meta = document.getElementById('results-meta');
  if (!meta) return;
  if (total === undefined) { meta.textContent = ''; return; }
  const start = (currentPage - 1) * currentLimit + 1;
  const end   = start + shown - 1;
  meta.textContent = total > 0
    ? `Showing ${start}–${end} of ${total} project${total !== 1 ? 's' : ''}`
    : '0 projects';
}

/* ── Delete modal ─────────────────────────────────────────── */

function openDeleteModal(id, title) {
  pendingDeleteId = id;
  document.getElementById('delete-modal-body').textContent =
    `"${title}" will be permanently removed. This action cannot be undone.`;
  document.getElementById('delete-modal').style.display = 'flex';
}

function closeDeleteModal() {
  pendingDeleteId = null;
  document.getElementById('delete-modal').style.display = 'none';
}

async function confirmDelete() {
  if (!pendingDeleteId) return;

  const btn = document.getElementById('delete-confirm');
  btn.disabled    = true;
  btn.innerHTML   = '<i class="fa-solid fa-circle-notch fa-spin"></i> Deleting…';

  try {
    const res  = await fetch(`/api/admin/projects/${pendingDeleteId}`, { method: 'DELETE' });
    const data = await res.json();

    if (res.ok) {
      showToast('Project deleted successfully', 'success');
      closeDeleteModal();
      // If we just deleted the last item on this page, go back one page
      if (currentPage > 1) currentPage--;
      loadProjects();
    } else {
      showToast(data.message || 'Delete failed', 'error');
    }
  } catch (err) {
    console.error(err);
    showToast('Network error', 'error');
  } finally {
    btn.disabled  = false;
    btn.innerHTML = '<i class="fa-solid fa-trash"></i> Delete';
  }
}

/* ── Event listeners ──────────────────────────────────────── */

// Event delegation — delete buttons inside table
document.addEventListener('click', e => {
  const btn = e.target.closest('.js-delete-btn');
  if (btn) {
    openDeleteModal(btn.dataset.id, btn.dataset.title);
  }
});

document.getElementById('delete-cancel').addEventListener('click', closeDeleteModal);
document.getElementById('delete-confirm').addEventListener('click', confirmDelete);

// Close modal on overlay click
document.getElementById('delete-modal').addEventListener('click', e => {
  if (e.target === e.currentTarget) closeDeleteModal();
});

// Close modal on Escape
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') closeDeleteModal();
});

// Search (debounced)
let searchTimer;
document.getElementById('search-input').addEventListener('input', e => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    currentSearch = e.target.value.trim();
    currentPage   = 1;
    loadProjects();
  }, 300);
});

// Featured filter
document.getElementById('featured-filter').addEventListener('change', e => {
  const val = e.target.value;
  currentFeatured = val === 'featured' ? true : val === 'not-featured' ? false : null;
  currentPage = 1;
  loadProjects();
});

// Pagination
document.getElementById('prev-page').addEventListener('click', () => {
  if (currentPage > 1) { currentPage--; loadProjects(); }
});
document.getElementById('next-page').addEventListener('click', () => {
  if (currentPage < totalPages) { currentPage++; loadProjects(); }
});

// Export CSV
document.getElementById('export-csv').addEventListener('click', () => {
  window.location.href = '/api/admin/projects/export';
});

/* ── Helpers ──────────────────────────────────────────────── */

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

loadProjects();