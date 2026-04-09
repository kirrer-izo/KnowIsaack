/**
 * admin-project.js
 * Handles: project listing, search/filter, pagination, delete modal,
 *          CSV export, and the create/edit drawer.
 */

let currentPage     = 1;
let currentLimit    = 10;
let currentSearch   = '';
let currentFeatured = null; // null = all, true = featured, false = not featured
let totalPages      = 1;
let pendingDeleteId = null;

// Drawer state
let drawerMode   = 'create'; // 'create' | 'edit'
let drawerEditId = null;
let techStack    = [];       // current tag list

/* ══════════════════════════════════════════════════════════════
   Toast
══════════════════════════════════════════════════════════════ */

function showToast(message, type = 'success') {
  const toast = document.getElementById('toast');
  toast.textContent = message;
  toast.className   = `admin-toast admin-toast--${type} admin-toast--visible`;
  clearTimeout(toast._timer);
  toast._timer = setTimeout(() => {
    toast.classList.remove('admin-toast--visible');
  }, 3500);
}

/* ══════════════════════════════════════════════════════════════
   Load & render projects
══════════════════════════════════════════════════════════════ */

async function loadProjects() {
  const url = new URL('/api/admin/projects', window.location.origin);
  url.searchParams.set('page',  currentPage);
  url.searchParams.set('limit', currentLimit);
  if (currentSearch)            url.searchParams.set('search',   currentSearch);
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
    const desc = escapeHtml(p.description.substring(0, 90)) + (p.description.length > 90 ? '…' : '');
    const tags = (p.tech_stack || [])
      .map(t => `<span class="badge badge--blue">${escapeHtml(t)}</span>`)
      .join(' ');
    const feat = p.featured
      ? `<span class="badge badge--amber"><i class="fa-solid fa-star" style="font-size:9px"></i> Featured</span>`
      : `<span style="color:var(--admin-text3);font-size:13px">—</span>`;
    const date = new Date(p.created_at).toLocaleDateString(undefined, {
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
          <button class="table-btn js-edit-btn" data-id="${p.id}" data-title="${escapeHtml(p.title)}">
            <i class="fa-solid fa-pen" style="font-size:11px"></i> Edit
          </button>
          <button class="table-btn table-btn--danger js-delete-btn" data-id="${p.id}" data-title="${escapeHtml(p.title)}">
            <i class="fa-solid fa-trash" style="font-size:11px"></i> Delete
          </button>
        </td>
      </tr>`;
  }).join('');
}

/* ══════════════════════════════════════════════════════════════
   Pagination & meta
══════════════════════════════════════════════════════════════ */

function updatePagination() {
  document.getElementById('prev-page').disabled = currentPage <= 1;
  document.getElementById('next-page').disabled = currentPage >= totalPages;
  document.getElementById('page-info').textContent = `Page ${currentPage} of ${totalPages}`;
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

/* ══════════════════════════════════════════════════════════════
   Delete modal
══════════════════════════════════════════════════════════════ */

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
  btn.disabled  = true;
  btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Deleting…';

  try {
    const res  = await fetch(`/api/admin/projects/${pendingDeleteId}`, { method: 'DELETE' });
    const data = await res.json();

    if (res.ok) {
      showToast('Project deleted successfully', 'success');
      closeDeleteModal();
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

/* ══════════════════════════════════════════════════════════════
   Drawer — open / close
   Uses existing admin.css classes:
     .user-drawer-overlay--visible  (overlay fade-in)
     .user-drawer--open             (panel slide-in)
══════════════════════════════════════════════════════════════ */

function openDrawer(mode = 'create', projectData = null) {
  drawerMode   = mode;
  drawerEditId = projectData?.id ?? null;

  // Header copy
  document.getElementById('drawer-title').textContent =
    mode === 'edit' ? 'Edit project' : 'Add project';
  document.getElementById('drawer-subtitle').textContent =
    mode === 'edit' ? `Editing: ${projectData?.title ?? ''}` : 'Fill in the details below';
  document.getElementById('drawer-save-label').textContent =
    mode === 'edit' ? 'Save changes' : 'Save project';

  document.getElementById('drawer-save').disabled = false;

  populateDrawer(projectData);
  clearDrawerErrors();

  document.getElementById('drawer-overlay').classList.add('user-drawer-overlay--visible');
  document.getElementById('project-drawer').classList.add('user-drawer--open');
  document.body.style.overflow = 'hidden';

  // Focus first field after transition
  setTimeout(() => document.getElementById('field-title').focus(), 280);
}

function closeDrawer() {
  document.getElementById('drawer-overlay').classList.remove('user-drawer-overlay--visible');
  document.getElementById('project-drawer').classList.remove('user-drawer--open');
  document.body.style.overflow = '';
}

/* ── Populate or clear all fields ────────────────────────── */

function populateDrawer(data) {
  document.getElementById('field-title').value       = data?.title       ?? '';
  document.getElementById('field-description').value = data?.description ?? '';
  document.getElementById('field-live-url').value    = data?.detail_url  ?? '';   // map detail_url to the "Live URL" field
  document.getElementById('field-github-url').value  = data?.github_url  ?? '';
  document.getElementById('field-featured').checked  = data?.featured    ?? false;

  // Tags
  techStack = Array.isArray(data?.tech_stack) ? [...data.tech_stack] : [];
  renderTags();

  // Counter
  syncCounter('field-description', 'counter-description', 2000);
}

/* ══════════════════════════════════════════════════════════════
   Tag input
══════════════════════════════════════════════════════════════ */

function renderTags() {
  const wrap  = document.getElementById('tag-wrap');
  const input = document.getElementById('tag-input');

  // Remove existing chips, keep the text input
  wrap.querySelectorAll('.tag-chip').forEach(c => c.remove());

  techStack.forEach((tag, i) => {
    const chip = document.createElement('span');
    chip.className = 'tag-chip';
    chip.innerHTML = `${escapeHtml(tag)}
      <button class="tag-chip__remove" data-index="${i}" aria-label="Remove ${escapeHtml(tag)}">
        <i class="fa-solid fa-xmark"></i>
      </button>`;
    wrap.insertBefore(chip, input);
  });
}

function addTag(raw) {
  const tag = raw.trim().replace(/,+$/, '');
  if (!tag || techStack.includes(tag) || techStack.length >= 20) return;
  techStack.push(tag);
  renderTags();
}

function removeTag(index) {
  techStack.splice(index, 1);
  renderTags();
}

/* ══════════════════════════════════════════════════════════════
   Character counters
══════════════════════════════════════════════════════════════ */

function syncCounter(fieldId, counterId, max) {
  const len     = document.getElementById(fieldId).value.length;
  const counter = document.getElementById(counterId);
  counter.textContent = `${len} / ${max}`;
  counter.className   = 'char-counter' +
    (len >= max            ? ' is-over' : '') +
    (len >= max * 0.9 && len < max ? ' is-warn' : '');
}

/* ══════════════════════════════════════════════════════════════
   Validation
══════════════════════════════════════════════════════════════ */

function clearDrawerErrors() {
  document.querySelectorAll('.edit-error').forEach(el => el.style.display = 'none');
}

function showFieldError(fieldId, errId) {
  document.getElementById(errId).style.display = 'block';
  document.getElementById(fieldId)?.focus();
}

function isValidUrl(str) {
  if (!str) return true; // optional fields
  try { new URL(str); return true; } catch { return false; }
}

function isValidPath(path) {
    const pathRegex = /^\/[a-zA-Z0-9\-\._\/]*$/;
    return pathRegex.test(path);
}

function validateDrawer() {
  clearDrawerErrors();
  let valid = true;

  if (!document.getElementById('field-title').value.trim()) {
    showFieldError('field-title', 'err-title'); valid = false;
  }
  if (!isValidPath(document.getElementById('field-live-url').value.trim())) {
    showFieldError('field-live-url', 'err-live-url'); valid = false;
  }
  if (!isValidUrl(document.getElementById('field-github-url').value.trim())) {
    showFieldError('field-github-url', 'err-github-url'); valid = false;
  }

  return valid;
}

/* ══════════════════════════════════════════════════════════════
   Collect payload
══════════════════════════════════════════════════════════════ */

function collectPayload() {
  return {
    title:        document.getElementById('field-title').value.trim(),
    description:  document.getElementById('field-description').value.trim(),
    detail_url:   document.getElementById('field-live-url').value.trim() || null,   // use detail_url, not live_url
    github_url:   document.getElementById('field-github-url').value.trim() || null,
    featured:     document.getElementById('field-featured').checked,
    tech_stack:   techStack,
  };
}

/* ══════════════════════════════════════════════════════════════
   Save (create or update)
══════════════════════════════════════════════════════════════ */

async function saveProject() {
  if (!validateDrawer()) return;

  const btn   = document.getElementById('drawer-save');
  const label = document.getElementById('drawer-save-label');
  const icon  = btn.querySelector('i');

  btn.disabled     = true;
  label.textContent = 'Saving…';
  icon.className   = 'fa-solid fa-circle-notch fa-spin';

  const isEdit = drawerMode === 'edit';
  const url    = isEdit ? `/api/admin/projects/${drawerEditId}` : '/api/admin/projects';
  const method = isEdit ? 'PUT' : 'POST';

  try {
    const res  = await fetch(url, {
      method,
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify(collectPayload()),
    });
    const data = await res.json();

    if (res.ok) {
      showToast(isEdit ? 'Project updated' : 'Project created', 'success');
      closeDrawer();
      loadProjects();
    } else {
      showToast(data.message || (isEdit ? 'Update failed' : 'Create failed'), 'error');
    }
  } catch (err) {
    console.error(err);
    showToast('Network error', 'error');
  } finally {
    btn.disabled      = false;
    label.textContent = isEdit ? 'Save changes' : 'Save project';
    icon.className    = 'fa-solid fa-floppy-disk';
  }
}

/* ══════════════════════════════════════════════════════════════
   Load single project then open edit drawer
══════════════════════════════════════════════════════════════ */

async function openEditDrawer(id) {
  // Optimistically show drawer in loading state
  document.getElementById('drawer-title').textContent     = 'Edit project';
  document.getElementById('drawer-subtitle').textContent  = 'Loading…';
  document.getElementById('drawer-save-label').textContent = 'Save changes';
  document.getElementById('drawer-save').disabled = true;
  document.getElementById('drawer-overlay').classList.add('user-drawer-overlay--visible');
  document.getElementById('project-drawer').classList.add('user-drawer--open');
  document.body.style.overflow = 'hidden';

  try {
    const res  = await fetch(`/api/admin/projects/${id}`);
    const data = await res.json();

    if (res.ok && data.status === 'success') {
      openDrawer('edit', data.data);
    } else {
      showToast('Could not load project', 'error');
      closeDrawer();
    }
  } catch (err) {
    console.error(err);
    showToast('Network error', 'error');
    closeDrawer();
  }
}

/* ══════════════════════════════════════════════════════════════
   Event listeners
══════════════════════════════════════════════════════════════ */

// Open create drawer
document.getElementById('open-create-drawer').addEventListener('click', () => openDrawer('create'));

// Close drawer
document.getElementById('drawer-close').addEventListener('click',  closeDrawer);
document.getElementById('drawer-cancel').addEventListener('click', closeDrawer);
document.getElementById('drawer-overlay').addEventListener('click', closeDrawer);

// Save
document.getElementById('drawer-save').addEventListener('click', saveProject);

// Tag input: Enter / comma adds; Backspace on empty removes last
document.getElementById('tag-input').addEventListener('keydown', e => {
  if (e.key === 'Enter' || e.key === ',') {
    e.preventDefault();
    addTag(e.target.value);
    e.target.value = '';
  }
  if (e.key === 'Backspace' && !e.target.value && techStack.length) {
    techStack.pop();
    renderTags();
  }
});

// Tag remove via delegation
document.getElementById('tag-wrap').addEventListener('click', e => {
  const btn = e.target.closest('.tag-chip__remove');
  if (btn) { removeTag(Number(btn.dataset.index)); return; }
  // Clicking anywhere else in the wrap focuses the input
  document.getElementById('tag-input').focus();
});

// Character counters
document.getElementById('field-description').addEventListener('input', () =>
  syncCounter('field-description', 'counter-description', 2000));

// Escape closes whichever layer is topmost
document.addEventListener('keydown', e => {
  if (e.key !== 'Escape') return;
  if (document.getElementById('project-drawer').classList.contains('user-drawer--open')) {
    closeDrawer();
  } else {
    closeDeleteModal();
  }
});

// Table delegation — edit / delete
document.addEventListener('click', e => {
  const editBtn = e.target.closest('.js-edit-btn');
  if (editBtn) { openEditDrawer(editBtn.dataset.id); return; }

  const delBtn = e.target.closest('.js-delete-btn');
  if (delBtn)  { openDeleteModal(delBtn.dataset.id, delBtn.dataset.title); }
});

// Delete modal
document.getElementById('delete-cancel').addEventListener('click', closeDeleteModal);
document.getElementById('delete-confirm').addEventListener('click', confirmDelete);
document.getElementById('delete-modal').addEventListener('click', e => {
  if (e.target === e.currentTarget) closeDeleteModal();
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

/* ══════════════════════════════════════════════════════════════
   Helpers
══════════════════════════════════════════════════════════════ */

function escapeHtml(str) {
  if (str == null) return '';
  return String(str)
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

/* ══════════════════════════════════════════════════════════════
   Init
══════════════════════════════════════════════════════════════ */

loadProjects();