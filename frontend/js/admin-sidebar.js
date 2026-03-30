// admin-sidebar.js
document.addEventListener('DOMContentLoaded', () => {
  const sidebar = document.getElementById('adminSidebar');
  const collapseBtn = document.getElementById('collapseSidebar');
  const themeToggle = document.getElementById('themeToggle');

  // Load collapse state from localStorage
  const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
  if (isCollapsed) {
    sidebar.classList.add('collapsed');
  }

  // Toggle collapse
  if (collapseBtn) {
    collapseBtn.addEventListener('click', () => {
      sidebar.classList.toggle('collapsed');
      localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
    });
  }

  // Theme toggle
  const isDark = localStorage.getItem('theme') === 'dark';
  if (isDark) {
    document.body.classList.add('dark');
    themeToggle.innerHTML = '<i class="fa-regular fa-sun"></i><span>Light</span>';
  }

  themeToggle.addEventListener('click', () => {
    document.body.classList.toggle('dark');
    const isDarkNow = document.body.classList.contains('dark');
    localStorage.setItem('theme', isDarkNow ? 'dark' : 'light');
    themeToggle.innerHTML = isDarkNow
      ? '<i class="fa-regular fa-sun"></i><span>Light</span>'
      : '<i class="fa-regular fa-moon"></i><span>Dark</span>';
  });

  // Highlight active link based on current URL
  const currentPath = window.location.pathname;
  const navLinks = document.querySelectorAll('.sidebar-nav a');
  navLinks.forEach(link => {
    const href = link.getAttribute('href');
    if (href !== '/auth/logout' && currentPath === href) {
      link.classList.add('active');
    }
  });

  // Mobile menu toggle (hamburger)
  const hamburger = document.querySelector('.mobile-menu-toggle');
  if (hamburger) {
    hamburger.addEventListener('click', () => {
      sidebar.classList.toggle('open');
    });
  }
});