// ========================================
// BACK TO TOP COMPONENT
// ========================================

let backToTopBtn = null;
let isBackToTopInitialized = false;

function initBackToTop() {
  backToTopBtn = document.getElementById("backToTop");

  if (!backToTopBtn) {
    console.warn("[BackToTop] Button not found");
    return false;
  }

  // Prevent double initialization
  if (isBackToTopInitialized) {
    return true;
  }

  // Show/hide button based on scroll position
  function toggleBackToTop() {
    if (window.scrollY > 400) {
      backToTopBtn.classList.add("visible");
    } else {
      backToTopBtn.classList.remove("visible");
    }
  }

  // Scroll to top with smooth behavior
  function scrollToTop() {
    window.scrollTo({
      top: 0,
      behavior: "smooth",
    });
  }

  // Add event listeners
  window.addEventListener("scroll", toggleBackToTop);
  backToTopBtn.addEventListener("click", scrollToTop);

  // Initial check
  toggleBackToTop();

  // Mark as initialized
  isBackToTopInitialized = true;
  backToTopBtn.setAttribute("data-initialized", "true");

  console.log("[BackToTop] Initialized");
  return true;
}

// Initialize when components are loaded
document.addEventListener("components:loaded", () => {
  initBackToTop();
});

// Fallback: Try to initialize immediately if button already exists
if (document.readyState === "complete") {
  setTimeout(() => {
    if (document.getElementById("backToTop")) {
      initBackToTop();
    }
  }, 100);
}
