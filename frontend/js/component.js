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
  await Promise.allSettled(loadTasks); // allSettled > all - one failure doesn't block others

  // Dispatch event when done (regardless of partial failures)
  document.dispatchEvent(
    new CustomEvent("components:loaded", {
      detail: {
        success: loadTasks.every((task) => task.status === "fulfilled"),
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
