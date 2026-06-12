// ======================================================
// MAIN.JS - COMPLETE FILE (FIXED - Navbar links now clickable)
// ======================================================

// ============================
// SCROLL RESTORATION
// ============================
if (history.scrollRestoration) {
  history.scrollRestoration = "manual";
}

// ======================================
// REVEAL ANIMATION OBSERVER
// ======================================
const revealElements = document.querySelectorAll(".reveal");

const animationObserverOptions = {
  root: null,
  rootMargin: "0px 0px -200px 0px",
  threshold: 0,
};

const animationObserver = new IntersectionObserver((entries) => {
  entries.forEach((entry) => {
    if (entry.isIntersecting && entry.target.classList.contains("reveal")) {
      entry.target.classList.add("is-visible");
    } else {
      entry.target.classList.remove("is-visible");
    }
  });
}, animationObserverOptions);

revealElements.forEach((el) => animationObserver.observe(el));

// ===================================================
// SMOOTH SCROLLING FUNCTION
// ===================================================
function smoothScrollTo(targetId) {
  const targetElement = document.getElementById(targetId);
  if (targetElement) {
    const offset = 80; // Account for fixed navbar height
    const elementPosition = targetElement.getBoundingClientRect().top;
    const offsetPosition = elementPosition + window.pageYOffset - offset;

    window.scrollTo({
      top: offsetPosition,
      behavior: "smooth",
    });
    return true;
  }
  return false;
}

// ===================================================
// NAVIGATION HIGHLIGHT OBSERVER (Updated for new sections)
// ===================================================
function initNavHighlightObserver() {
  const sectionsToWatch = document.querySelectorAll("section[id]");

  // Check that nav links exist in the DOM at init time
  if (document.querySelectorAll(".nav-menu a").length === 0) return;

  const navObserverOptions = {
    root: null,
    rootMargin: "-30% 0px -50% 0px",
    threshold: 0,
  };

  const navObserver = new IntersectionObserver((entries) => {
    // Query links LIVE each time so we never hold stale references
    const navLinks = document.querySelectorAll(".nav-menu a");
    if (navLinks.length === 0) return;

    entries.forEach((entry) => {
      if (!entry.target || entry.target.tagName !== "SECTION") return;

      const id = entry.target.getAttribute("id");

      let matchingLink = null;
      navLinks.forEach((link) => {
        const href = link.getAttribute("href");
        // Match href to section id across all supported formats
        if (href === `/?#${id}` || href === `/#${id}` || href === `#${id}`) {
          matchingLink = link;
        }
      });

      if (!matchingLink) return;

      if (entry.isIntersecting) {
        navLinks.forEach((link) => link.classList.remove("active"));
        matchingLink.classList.add("active");
      }
    });
  }, navObserverOptions);

  sectionsToWatch.forEach((section) => navObserver.observe(section));
}

// ===================================================
// HANDLE NAVIGATION LINKS (FIXED - makes them clickable)
// ===================================================
function initNavLinks() {
  const navLinks = document.querySelectorAll(".nav-menu a");

  navLinks.forEach((link) => {
    // Skip links that are already initialized to prevent stale references
    if (link.hasAttribute("data-nav-initialized")) return;

    // Remove any existing listeners by cloning
    const newLink = link.cloneNode(true);
    newLink.setAttribute("data-nav-initialized", "true");
    link.parentNode.replaceChild(newLink, link);

    newLink.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();

      const href = this.getAttribute("href");

      // Handle contact trigger
      if (this.classList.contains("contact-trigger") || href === "#contact") {
        if (typeof window.openContactModal === "function") {
          window.openContactModal();
        }
        // Close mobile menu if open
        closeMobileMenu();
        return;
      }

      // Handle admin panel - let it navigate normally
      if (href === "/admin") {
        window.location.href = href;
        return;
      }

      // Extract target ID from href
      let targetId = null;
      if (href === "/#about") targetId = "about";
      else if (href === "/#about-me") targetId = "about-me";
      else if (href === "/#experience") targetId = "experience";
      else if (href === "/#skills") targetId = "skills";
      else if (href === "/#certifications") targetId = "certifications";
      else if (href === "/#projects") targetId = "projects";
      else if (href.startsWith("#")) targetId = href.substring(1);
      else if (href.startsWith("/#")) targetId = href.substring(2);

      if (targetId) {
        smoothScrollTo(targetId);
      }

      // Close mobile menu after clicking
      closeMobileMenu();
    });
  });
}

// ===================================================
// CLOSE MOBILE MENU FUNCTION
// ===================================================
function closeMobileMenu() {
  const navMenu = document.getElementById("navMenu");
  const navToggle = document.getElementById("navToggle");
  const body = document.body;

  if (navMenu && navMenu.classList.contains("active")) {
    navMenu.classList.remove("active");
    body.classList.remove("menu-open");
    body.style.overflow = "";

    if (navToggle) {
      const icon = navToggle.querySelector("i");
      if (icon) {
        icon.classList.remove("fa-times");
        icon.classList.add("fa-bars");
      }
    }
  }
}

// ===================================================
// MOUSE TRACKING (Gradient spotlight effect on cards)
// ===================================================
const cards = document.querySelectorAll(".skill-card, .project-card");

cards.forEach((card) => {
  card.addEventListener("mousemove", (e) => {
    const rect = card.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;
    const xPercent = (x / rect.width) * 100;
    const yPercent = (y / rect.height) * 100;
    card.style.setProperty("--mouse-x", `${xPercent}%`);
    card.style.setProperty("--mouse-y", `${yPercent}%`);
  });

  card.addEventListener("mouseleave", () => {
    card.style.setProperty("--mouse-x", "50%");
    card.style.setProperty("--mouse-y", "50%");
  });
});

// ===================================================
// RESPONSIVE NAVBAR WITH HAMBURGER MENU
// ===================================================
function initResponsiveNavbar() {
  const navToggle = document.getElementById("navToggle");
  const navMenu = document.getElementById("navMenu");
  const body = document.body;

  if (!navToggle || !navMenu) {
    console.warn("[Navbar] Toggle or menu not found");
    return false;
  }

  if (navToggle.hasAttribute("data-initialized")) return true;
  navToggle.setAttribute("data-initialized", "true");

  navToggle.addEventListener("click", function (e) {
    e.stopPropagation();
    navMenu.classList.toggle("active");
    body.classList.toggle("menu-open");

    const icon = navToggle.querySelector("i");
    if (navMenu.classList.contains("active")) {
      icon.classList.remove("fa-bars");
      icon.classList.add("fa-times");
      body.style.overflow = "hidden";
    } else {
      icon.classList.remove("fa-times");
      icon.classList.add("fa-bars");
      body.style.overflow = "";
    }
  });

  // Close menu when clicking outside
  document.addEventListener("click", function (e) {
    if (
      navMenu.classList.contains("active") &&
      !navMenu.contains(e.target) &&
      !navToggle.contains(e.target)
    ) {
      closeMobileMenu();
    }
  });

  // Close menu on escape key
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape" && navMenu.classList.contains("active")) {
      closeMobileMenu();
    }
  });

  console.log("[Navbar] Responsive navbar initialized");
  return true;
}

// ===================================================
// CONTACT MODAL
// ===================================================
let modal = null;
let isModalInitialized = false;

function initModal() {
  modal = document.getElementById("contactModal");

  if (!modal) {
    console.warn("[Modal] Modal element not found");
    return false;
  }

  if (isModalInitialized) return true;

  const closeBtn = document.getElementById("modalCloseBtn");
  const cancelBtn = modal.querySelector(".btn-cancel");

  window.openContactModal = function () {
    if (!modal) return;
    modal.classList.add("is-open");
    document.body.style.overflow = "hidden";
    setTimeout(() => {
      const firstInput = modal.querySelector("input, textarea");
      if (firstInput) firstInput.focus();
    }, 100);
  };

  window.closeContactModal = function () {
    if (!modal) return;
    modal.classList.remove("is-open");
    document.body.style.overflow = "";
  };

  if (closeBtn) closeBtn.addEventListener("click", window.closeContactModal);
  if (cancelBtn) cancelBtn.addEventListener("click", window.closeContactModal);

  modal.addEventListener("click", (e) => {
    if (e.target === modal) window.closeContactModal();
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && modal && modal.classList.contains("is-open")) {
      window.closeContactModal();
    }
  });

  isModalInitialized = true;
  setupContactTriggers();

  console.log("[Modal] Initialized");
  return true;
}

function setupContactTriggers() {
  // Navbar contact triggers
  const navContactTriggers = document.querySelectorAll(
    '.contact-trigger, .nav-menu a[href="#contact"]',
  );
  navContactTriggers.forEach((trigger) => {
    const newTrigger = trigger.cloneNode(true);
    trigger.parentNode.replaceChild(newTrigger, trigger);
    newTrigger.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      closeMobileMenu();
      if (typeof window.openContactModal === "function") {
        window.openContactModal();
      }
    });
  });

  // Hero "Get in Touch" button
  const heroContactBtn = document.querySelector(
    '.btn-secondary[href="#contact"]',
  );
  if (heroContactBtn) {
    const newBtn = heroContactBtn.cloneNode(true);
    heroContactBtn.parentNode.replaceChild(newBtn, heroContactBtn);
    newBtn.addEventListener("click", (e) => {
      e.preventDefault();
      if (typeof window.openContactModal === "function") {
        window.openContactModal();
      }
    });
  }

  console.log("[Modal] Contact triggers set up");
}

// ===================================================
// CONTACT FORM - VALIDATION & SUBMISSION
// ===================================================
let contactForm = null;
let formStatus = null;

function initContactForm() {
  contactForm = document.getElementById("contactForm");
  formStatus = document.getElementById("formStatus");

  if (!contactForm) {
    console.warn("[Form] Contact form not found");
    return false;
  }

  contactForm.querySelectorAll("input, textarea").forEach((field) => {
    field.addEventListener("input", () => {
      if (field.checkValidity()) {
        removeFieldError(field.id);
      }
    });
  });

  contactForm.addEventListener("submit", handleFormSubmit);
  console.log("[Form] Contact form initialized");
  return true;
}

async function handleFormSubmit(e) {
  e.preventDefault();
  clearErrors();

  const submitBtn = contactForm.querySelector('button[type="submit"]');

  if (!validateContactForm()) return;

  const formData = new FormData(contactForm);
  setSubmittingState(submitBtn, true);

  try {
    const response = await fetch("/api/contact", {
      method: "POST",
      body: formData,
    });

    const result = await response.json();

    if (response.ok) {
      showFormStatus(
        "success",
        '<i class="fa-solid fa-check-circle"></i> Message Sent Successfully!',
      );
      contactForm.reset();
      setTimeout(() => hideFormStatus(), 5000);
      setTimeout(() => window.closeContactModal(), 2000);
    } else if (result.errors) {
      displayServiceErrors(result.errors);
      hideFormStatus();
    } else {
      throw new Error(result.message || "Server Error");
    }
  } catch (error) {
    console.error("Submission error:", error);
    showFormStatus(
      "error",
      '<i class="fa-solid fa-circle-exclamation"></i> ' +
        (error.message || "Network Error. Please try again."),
    );
  } finally {
    setSubmittingState(submitBtn, false);
  }
}

function setSubmittingState(btn, isSubmitting) {
  btn.disabled = isSubmitting;
  if (isSubmitting) {
    showFormStatus(
      "",
      '<i class="fa-solid fa-spinner fa-spin"></i> Sending Email...',
    );
  }
}

function showFormStatus(className, html) {
  if (!formStatus) return;
  formStatus.style.display = "block";
  formStatus.className = className;
  formStatus.innerHTML = html;
}

function hideFormStatus() {
  if (!formStatus) return;
  formStatus.style.display = "none";
  formStatus.className = "";
}

function validateContactForm() {
  const name = document.getElementById("name")?.value.trim() || "";
  const email = document.getElementById("email")?.value.trim() || "";
  const message = document.getElementById("message")?.value.trim() || "";
  let isValid = true;

  if (!name) {
    showError("name", "Name is required");
    isValid = false;
  } else if (name.length < 2) {
    showError("name", "Name must be at least 2 characters");
    isValid = false;
  } else if (name.length > 50) {
    showError("name", "Name must be less than 50 characters");
    isValid = false;
  }

  if (!email) {
    showError("email", "Email is required");
    isValid = false;
  } else {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
      showError("email", "Please enter a valid email address");
      isValid = false;
    }
  }

  if (!message) {
    showError("message", "Message is required");
    isValid = false;
  } else if (message.length < 10) {
    showError("message", "Message must be at least 10 characters");
    isValid = false;
  } else if (message.length > 1000) {
    showError("message", "Message must be less than 1000 characters");
    isValid = false;
  }

  return isValid;
}

function showError(fieldId, message) {
  const input = document.getElementById(fieldId);
  const errorSpan = document.getElementById(fieldId + "Error");

  if (input) {
    input.classList.add("error");
    input.setAttribute("aria-invalid", "true");
  }
  if (errorSpan) {
    errorSpan.textContent = message;
    errorSpan.classList.add("error-text");
    errorSpan.setAttribute("role", "alert");
  }
}

function removeFieldError(fieldId) {
  const input = document.getElementById(fieldId);
  const errorSpan = document.getElementById(fieldId + "Error");

  if (input) {
    input.classList.remove("error");
    input.removeAttribute("aria-invalid");
  }
  if (errorSpan) {
    errorSpan.textContent = "";
    errorSpan.classList.remove("error-text");
  }
}

function clearErrors() {
  document.querySelectorAll("input.error, textarea.error").forEach((el) => {
    el.classList.remove("error");
    el.removeAttribute("aria-invalid");
  });
  document.querySelectorAll("[id$='Error']").forEach((el) => {
    el.textContent = "";
    el.classList.remove("error-text");
  });
}

function displayServiceErrors(errors) {
  for (const [field, message] of Object.entries(errors)) {
    const errorSpan = document.getElementById(`${field}Error`);
    if (errorSpan) {
      errorSpan.textContent = message;
      errorSpan.classList.add("error-text");
      const input = document.getElementById(field);
      if (input) {
        input.classList.add("error");
        input.setAttribute("aria-invalid", "true");
      }
    }
  }
}

// ===================================================
// INITIALIZATION - WAIT FOR COMPONENTS
// ===================================================
document.addEventListener("components:loaded", () => {
  console.log("[App] Components loaded, initializing...");

  initModal();
  initContactForm();
  initResponsiveNavbar();
  initNavLinks(); // CRITICAL: This makes navbar links clickable
  initNavHighlightObserver();
});

// Fallback initialization
if (document.readyState === "complete") {
  setTimeout(() => {
    if (
      document.getElementById("contactModal") ||
      document.getElementById("contactForm")
    ) {
      initModal();
      initContactForm();
    }
    initResponsiveNavbar();
    initNavLinks(); // CRITICAL: This makes navbar links clickable
    initNavHighlightObserver();
  }, 100);
}

// Also run when DOM is ready (safety fallback - initNavLinks is now
// idempotent so duplicate calls are harmless)
document.addEventListener("DOMContentLoaded", function () {
  setTimeout(() => {
    if (document.querySelector(".nav-menu")) {
      initNavLinks();
      initNavHighlightObserver();
    }
  }, 500);
});
