/**
 * projects-loader.js
 * ──────────────────
 * Fetches projects from the public API and renders them dynamically
 * into #projects-container on the portfolio home page.
 *
 * Usage in home.html:
 *   1. Replace your hardcoded project cards with:
 *        <div id="projects-container"></div>
 *   2. Add at the bottom of home.html before </body>:
 *        <script src="/js/projects-loader.js"></script>
 */

async function loadPortfolioProjects() {
  const container = document.getElementById('projects-container');
  if (!container) return;

  // Show loading state
  container.innerHTML = `
    <div style="text-align:center;padding:var(--spacing-xl);color:var(--text-muted);width:100%">
      <i class="fa-solid fa-spinner fa-spin" style="font-size:24px"></i>
    </div>`;

  try {
    const res  = await fetch('/api/public-projects');
    const data = await res.json();

    if (!data.projects || data.projects.length === 0) {
      container.innerHTML = `
        <p style="color:var(--text-muted);text-align:center;width:100%">
          No projects yet — check back soon.
        </p>`;
      return;
    }

    // Render project cards matching your existing .project-card markup
   container.innerHTML = data.projects.map(project => `
  <div class="project-card ${project.featured ? 'project-card--featured' : ''}">
    <div class="project-card-inner">

      <!-- Header row: title + featured badge -->
      <div class="project-card-header">
        <h3 class="project-title">${project.title}</h3>
        ${project.featured
          ? `<span class="featured-badge">
               <i class="fa-solid fa-star"></i> Featured
             </span>`
          : ''}
      </div>

      <!-- Description -->
      <p class="project-description">${project.description}</p>

      <!-- Tech Stack -->
      <div class="tech-stack">
        ${(project.tech_stack ?? []).map(t =>
          `<span class="tech-tag">${t}</span>`
        ).join('')}
      </div>

      <!-- Actions -->
      <div class="project-actions">
        ${project.detail_url
          ? `<a href="${project.detail_url}" class="btn-primary project-btn">
               About Project
             </a>`
          : ''}
        ${project.github_url
          ? `<a href="${project.github_url}"
                class="btn-outline project-btn"
                target="_blank"
                rel="noopener">
               <i class="fa-brands fa-github"></i> Source Code
             </a>`
          : ''}
      </div>

    </div>
  </div>
`).join('');

    // Re-attach mouse tracking spotlight effect to dynamically created cards
    container.querySelectorAll('.project-card').forEach(card => {
      card.addEventListener('mousemove', (e) => {
        const rect     = card.getBoundingClientRect();
        const xPercent = ((e.clientX - rect.left) / rect.width)  * 100;
        const yPercent = ((e.clientY - rect.top)  / rect.height) * 100;
        card.style.setProperty('--mouse-x', `${xPercent}%`);
        card.style.setProperty('--mouse-y', `${yPercent}%`);
      });

      card.addEventListener('mouseleave', () => {
        card.style.setProperty('--mouse-x', '50%');
        card.style.setProperty('--mouse-y', '50%');
      });
    });

  } catch (err) {
    console.error('Failed to load projects:', err);
    container.innerHTML = `
      <p style="color:var(--text-muted);text-align:center;width:100%">
        Could not load projects. Please try again later.
      </p>`;
  }
}

loadPortfolioProjects();