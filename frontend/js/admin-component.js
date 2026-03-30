/**
 * admin-component.js
 * Loads the admin navbar component and highlights the active nav link
 * based on the current URL path.
 */

async function loadAdminComponents() {
    const placeholder = document.getElementById('admin-navbar-placeholder');
    if (!placeholder) return;

    try {
        const res  = await fetch('/frontend/components/admin-navbar.html');
        const html = await res.text();
        placeholder.innerHTML = html;
        highlightActiveLink(placeholder);
    } catch (err) {
        console.error('Failed to load admin navbar:', err);
    }
}

function highlightActiveLink(navbar) {
    const currentPath = window.location.pathname;

    // Build a list of [href, <li>] pairs sorted longest-first so that
    // /admin/projects matches before /admin
    const links = Array.from(navbar.querySelectorAll('.nav-menu a'));

    let bestMatch = null;
    let bestLen   = 0;

    links.forEach(link => {
        const href = link.getAttribute('href');

        // Skip logout link
        if (!href || href === '/auth/logout') return;

        if (currentPath === href || currentPath.startsWith(href + '/')) {
            if (href.length > bestLen) {
                bestMatch = link;
                bestLen   = href.length;
            }
        }
    });

    if (bestMatch) {
        bestMatch.parentElement.classList.add('active');
    }
}

loadAdminComponents();