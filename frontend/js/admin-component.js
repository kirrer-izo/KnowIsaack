/**
 * admin-component.js
 * Loads the admin navbar component and highlights the active nav link
 * based on the current URL path.
 */

async function loadAdminComponents() {
  const placeholder = document.getElementById("admin-navbar-placeholder");
  if (!placeholder) return;

  try {
    const res = await fetch("/frontend/components/admin-navbar.html");
    const html = await res.text();
    placeholder.innerHTML = html;
    highlightActiveLink(placeholder);
  } catch (err) {
    console.error("Failed to load admin navbar:", err);
  }

  loadAdminRole();
}

function highlightActiveLink(navbar) {
  const currentPath = window.location.pathname;

  // Build a list of [href, <li>] pairs sorted longest-first so that
  // /admin/projects matches before /admin
  const links = Array.from(navbar.querySelectorAll(".nav-menu a"));

  let bestMatch = null;
  let bestLen = 0;

  links.forEach((link) => {
    const href = link.getAttribute("href");

    // Skip logout link
    if (!href || href === "/auth/logout") return;

    if (currentPath === href || currentPath.startsWith(href + "/")) {
      if (href.length > bestLen) {
        bestMatch = link;
        bestLen = href.length;
      }
    }
  });

  if (bestMatch) {
    bestMatch.parentElement.classList.add("active");
  }
}

loadAdminComponents();

// ─── Role Loading ─────────────────────────────────────────────────────────
// Fetches the current user's role once on every admin page load.
// Dispatches a 'roleLoaded' custom event so page modules can react
// without each making their own /api/session call.

async function loadAdminRole() {
  try {
    const res = await fetch("/api/session");
    const data = await res.json();
    window.adminRole =
      data?.user?.role ?? (data?.is_admin ? "admin" : "viewer");

    // UPDATE AVATAR — use actual user's name
    const name = data?.user?.name ?? "";
    const initials = name
      .trim()
      .split(/\s+/)
      .filter(Boolean)
      .map((w) => w[0])
      .slice(0, 2)
      .join("")
      .toUpperCase();

    const avatar = document.querySelector(".admin-avatar");
    if (avatar && initials) avatar.textContent = initials;
  } catch (e) {
    window.adminRole = "viewer";
  }

  // Reveal admin elements
  if (window.adminRole === "admin") {
    document.querySelectorAll(".admin-action-btn").forEach((el) => {
      el.classList.add("is-visible");
    });
  }

  document.dispatchEvent(
    new CustomEvent("roleLoaded", { detail: { role: window.adminRole } }),
  );
}
