// admin-components.js – loads admin-specific components and highlights active link
async function loadAdminComponents() {
  const adminNavbar = document.getElementById('admin-navbar-placeholder');
  if (adminNavbar) {
    try {
      const response = await fetch('/frontend/components/admin-navbar.html');
      const html = await response.text();
      adminNavbar.innerHTML = html;

      // After navbar is loaded, highlight the active link
      const currentPath = window.location.pathname;
      const navLinks = adminNavbar.querySelectorAll('.nav-menu a');
      navLinks.forEach(link => {
        const href = link.getAttribute('href');
        // Skip logout link
        if (href === '/auth/logout') return;

        if (currentPath === href) {
          link.parentElement.classList.add('active');
        }
      });
    } catch (err) {
      console.error('Failed to load admin navbar:', err);
    }
  }
}
loadAdminComponents();