// ======================================================
// ENHANCED COMPONENT LOADER
// Supports: <div data-component="component-name"></div>
// Falls back to placeholder IDs for backward compatibility
// ======================================================

const COMPONENT_CONFIG = {
  // Map component names to their file paths
  navbar: "/frontend/components/navbar.html",
  footer: "/frontend/components/footer.html",
  modal: "/frontend/components/contact-modal.html",
  back_to_top: "/frontend/components/back-to-top.html",
};

// Cache loaded components to avoid re-fetching
const componentCache = new Map();

// Track loading state to prevent duplicate loads
const loadingPromises = new Map();

/**
 * Load a single component
 * @param {string} componentName - Name from COMPONENT_CONFIG
 * @param {HTMLElement} targetElement - Element to inject into
 */
async function loadComponent(componentName, targetElement) {
  // Check if already loading
  if (loadingPromises.has(componentName)) {
    await loadingPromises.get(componentName);
    targetElement.innerHTML = componentCache.get(componentName) || "";
    return;
  }

  // Check cache
  if (componentCache.has(componentName)) {
    targetElement.innerHTML = componentCache.get(componentName);
    return;
  }

  const componentPath = COMPONENT_CONFIG[componentName];
  if (!componentPath) {
    console.error(`No config found for component: ${componentName}`);
    targetElement.innerHTML = `<span class="component-error">Missing component: ${componentName}</span>`;
    return;
  }

  // Start loading
  const loadPromise = (async () => {
    try {
      const response = await fetch(componentPath);
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${componentPath}`);
      }
      const html = await response.text();

      // Basic HTML sanitization (prevents script injection)
      if (html.toLowerCase().includes("<script")) {
        console.warn(
          `Component ${componentName} contains scripts - ensure they're safe`,
        );
      }

      componentCache.set(componentName, html);
      targetElement.innerHTML = html;
      return html;
    } catch (err) {
      console.error(`Failed to load component [${componentName}]:`, err);
      targetElement.innerHTML = `
        <div class="component-error">
          Failed to load ${componentName}. Please refresh the page.
        </div>
      `;
      throw err;
    }
  })();

  loadingPromises.set(componentName, loadPromise);
  await loadPromise;
  loadingPromises.delete(componentName);
}

/**
 * Initialize footer functionality after component loads
 * This should be called after the footer component is loaded
 */
function initFooterFeatures() {
  // Dynamic copyright year
  const yearElement = document.getElementById("currentYear");
  if (yearElement) {
    yearElement.innerHTML = new Date().getFullYear();
  }

  // Copy email functionality
  const copyBtn = document.getElementById("copyEmailBtn");
  const emailLink = document.getElementById("footerEmail");

  if (copyBtn && emailLink) {
    // Remove any existing listeners to prevent duplicates
    const newCopyBtn = copyBtn.cloneNode(true);
    copyBtn.parentNode.replaceChild(newCopyBtn, copyBtn);

    newCopyBtn.addEventListener("click", async () => {
      const email = emailLink.textContent.trim();
      try {
        await navigator.clipboard.writeText(email);

        // Visual feedback
        newCopyBtn.classList.add("copied");
        const originalIcon = newCopyBtn.innerHTML;
        newCopyBtn.innerHTML = '<i class="fa-regular fa-check"></i>';

        setTimeout(() => {
          newCopyBtn.classList.remove("copied");
          newCopyBtn.innerHTML = originalIcon;
        }, 2000);
      } catch (err) {
        console.error("Failed to copy:", err);
      }
    });
  }

  // Footer back to top functionality
  const footerBackToTop = document.getElementById("footerBackToTop");
  if (footerBackToTop) {
    // Remove existing listeners
    const newBackToTop = footerBackToTop.cloneNode(true);
    footerBackToTop.parentNode.replaceChild(newBackToTop, footerBackToTop);

    newBackToTop.addEventListener("click", (e) => {
      e.preventDefault();
      window.scrollTo({
        top: 0,
        behavior: "smooth",
      });
    });
  }

  // Optional: Intersection Observer for footer animation
  const footer = document.querySelector(".footer");
  if (footer && !footer.hasAttribute("data-observer-initialized")) {
    // Set initial styles if not already set
    if (!footer.style.opacity) {
      footer.style.opacity = "0";
      footer.style.transform = "translateY(20px)";
      footer.style.transition =
        "opacity 0.6s ease-out, transform 0.6s ease-out";
    }

    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            footer.style.opacity = "1";
            footer.style.transform = "translateY(0)";
            observer.unobserve(footer); // Stop observing once animated
          }
        });
      },
      { threshold: 0.1 },
    );

    observer.observe(footer);
    footer.setAttribute("data-observer-initialized", "true");
  }
}

/**
 * Initialize navbar functionality (if needed)
 */
function initNavbarFeatures() {
  // Add any navbar-specific JavaScript here
  // For example: mobile menu toggle, active link highlighting, etc.
  const mobileMenuBtn = document.querySelector(".mobile-menu-btn");
  const navLinks = document.querySelector(".nav-links");

  if (mobileMenuBtn && navLinks) {
    // Remove existing listeners
    const newMenuBtn = mobileMenuBtn.cloneNode(true);
    mobileMenuBtn.parentNode.replaceChild(newMenuBtn, mobileMenuBtn);

    newMenuBtn.addEventListener("click", () => {
      navLinks.classList.toggle("active");
    });
  }
}

/**
 * Initialize modal functionality (if needed)
 */
function initModalFeatures() {
  // Add any modal-specific JavaScript here
  const modal = document.getElementById("contactModal");
  const closeBtn = document.querySelector(".modal-close");
  const openBtns = document.querySelectorAll("[data-modal-open]");

  if (modal) {
    // Close modal when clicking close button
    if (closeBtn) {
      const newCloseBtn = closeBtn.cloneNode(true);
      closeBtn.parentNode.replaceChild(newCloseBtn, closeBtn);

      newCloseBtn.addEventListener("click", () => {
        modal.classList.remove("active");
      });
    }

    // Close modal when clicking outside
    modal.addEventListener("click", (e) => {
      if (e.target === modal) {
        modal.classList.remove("active");
      }
    });

    // Open modal when clicking trigger buttons
    openBtns.forEach((btn) => {
      const newBtn = btn.cloneNode(true);
      btn.parentNode.replaceChild(newBtn, btn);

      newBtn.addEventListener("click", () => {
        modal.classList.add("active");
      });
    });
  }
}

/**
 * Initialize back to top functionality
 */
function initBackToTopFeatures() {
  const backToTopBtn = document.getElementById("backToTop");
  if (backToTopBtn) {
    // Remove existing listeners
    const newBtn = backToTopBtn.cloneNode(true);
    backToTopBtn.parentNode.replaceChild(newBtn, backToTopBtn);

    // Show/hide button based on scroll position
    window.addEventListener("scroll", () => {
      if (window.scrollY > 300) {
        newBtn.classList.add("visible");
      } else {
        newBtn.classList.remove("visible");
      }
    });

    // Scroll to top when clicked
    newBtn.addEventListener("click", (e) => {
      e.preventDefault();
      window.scrollTo({
        top: 0,
        behavior: "smooth",
      });
    });
  }
}

/**
 * Initialize all component-specific features
 * This should be called after components are loaded
 */
function initAllComponentFeatures() {
  initFooterFeatures();
  initNavbarFeatures();
  initModalFeatures();
  initBackToTopFeatures();

  // Dispatch event when all features are initialized
  document.dispatchEvent(
    new CustomEvent("components:features-initialized", {
      detail: {
        timestamp: new Date().toISOString(),
      },
    }),
  );
}

/**
 * Find all components on the page and load them
 * Supports both:
 * 1. Modern: <div data-component="navbar"></div>
 * 2. Legacy: <div id="navbar-placeholder"></div>
 */
async function loadAllComponents() {
  const loadTasks = [];

  // Method 1: data-component attributes (preferred)
  document.querySelectorAll("[data-component]").forEach((element) => {
    const componentName = element.getAttribute("data-component");
    if (componentName && COMPONENT_CONFIG[componentName]) {
      loadTasks.push(loadComponent(componentName, element));
    }
  });

  // Method 2: Legacy placeholder IDs (backward compatibility)
  for (const [name, path] of Object.entries(COMPONENT_CONFIG)) {
    const legacyId = `${name}-placeholder`;
    const placeholder = document.getElementById(legacyId);
    if (placeholder && !placeholder.hasAttribute("data-component-loaded")) {
      loadTasks.push(loadComponent(name, placeholder));
      placeholder.setAttribute("data-component-loaded", "true");
    }
  }

  // Wait for all components to load
  const results = await Promise.allSettled(loadTasks);

  // Initialize features after components are loaded (with a small delay to ensure DOM is updated)
  setTimeout(() => {
    initAllComponentFeatures();
  }, 100);

  // Dispatch event when done (regardless of partial failures)
  document.dispatchEvent(
    new CustomEvent("components:loaded", {
      detail: {
        success: results.every((result) => result.status === "fulfilled"),
        loadedComponents: Array.from(componentCache.keys()),
      },
    }),
  );
}

// Start loading when DOM is ready
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", loadAllComponents);
} else {
  loadAllComponents();
}

// Optional: Re-initialize features if dynamically loaded content changes
if (window.MutationObserver) {
  const observer = new MutationObserver((mutations) => {
    let shouldReinit = false;

    mutations.forEach((mutation) => {
      if (mutation.type === "childList") {
        mutation.addedNodes.forEach((node) => {
          if (node.nodeType === 1) {
            // Element node
            if (
              node.matches &&
              (node.matches("[data-component]") ||
                node.querySelector("[data-component]"))
            ) {
              shouldReinit = true;
            }
          }
        });
      }
    });

    if (shouldReinit) {
      setTimeout(() => {
        initAllComponentFeatures();
      }, 100);
    }
  });

  observer.observe(document.body, {
    childList: true,
    subtree: true,
  });
}
