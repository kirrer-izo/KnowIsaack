let currentPage = 1;
let currentLimit = 10;
let currentSearch = '';
let currentFeatured = null; // null = all, true = featured, false = not featured
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

async function loadProjects() {
  const url = new URL('/api/admin/projects', window.location.origin);
  url.searchParams.set('page', currentPage);
  url.searchParams.set('limit', currentLimit);
  if (currentSearch) url.searchParams.set('search', currentSearch);
  if (currentFeatured !== null) url.searchParams.set('featured', currentFeatured);

  try {
    const res = await fetch(url);
    const data = await res.json();

    if (data.status === 'success') {
      renderProjects(data.data.projects);
      totalPages = data.data.total_pages;
      updatePaginationControls();
    } else {
      showToast('Failed to load projects', 'error');
    }
  } catch (err) {
    console.error(err);
    showToast('Network error', 'error');
  }
}

function renderProjects(projects) {
  const tbody = document.getElementById('projects-table-body');

  if (!projects || projects.length === 0) {
    tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">No projects found</td></tr>';
    return;
  }

  tbody.innerHTML = projects.map(project => `
    <tr>
      <td>${project.id}</td>
      <td><strong>${escapeHtml(project.title)}</strong></td>
      <td>${escapeHtml(project.description.substring(0, 100))}${project.description.length > 100 ? '…' : ''}</td>
      <td class="tech-stack-cell">${(project.tech_stack || []).map(t => `<span class="tech-tag">${escapeHtml(t)}</span>`).join(' ')}</td>
      <td>${project.featured ? '<span class="badge-featured"><i class="fa-solid fa-star"></i> Featured</span>' : '—'}</td>
      <td>${new Date(project.created_at).toLocaleString()}</td>
      <td class="actions">
        <a href="/admin/edit?id=${project.id}" class="btn-edit"><i class="fa-solid fa-pen"></i> Edit</a>
        <button class="btn-delete" data-id="${project.id}" data-title="${escapeHtml(project.title)}">Delete</button>
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

// Event delegation for delete buttons
document.addEventListener('click', async (e) => {
  if (e.target.classList.contains('btn-delete')) {
    const projectId = e.target.dataset.id;
    const projectTitle = e.target.dataset.title;
    if (!projectId) return;
    if (confirm(`Delete project "${projectTitle}"? This action cannot be undone.`)) {
      try {
        const res = await fetch(`/api/admin/projects/${projectId}`, { method: 'DELETE' });
        const data = await res.json();
        if (res.ok) {
          showToast('Project deleted', 'success');
          loadProjects(); // refresh the list
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
  loadProjects();
});

document.getElementById('featured-filter').addEventListener('change', (e) => {
  const val = e.target.value;
  if (val === 'featured') currentFeatured = true;
  else if (val === 'not-featured') currentFeatured = false;
  else currentFeatured = null;
  currentPage = 1;
  loadProjects();
});

// Pagination
document.getElementById('prev-page').addEventListener('click', () => {
  if (currentPage > 1) {
    currentPage--;
    loadProjects();
  }
});
document.getElementById('next-page').addEventListener('click', () => {
  if (currentPage < totalPages) {
    currentPage++;
    loadProjects();
  }
});

// Export CSV
document.getElementById('export-csv').addEventListener('click', () => {
  window.location.href = '/api/admin/projects/export';
});

// Initial load
loadProjects();