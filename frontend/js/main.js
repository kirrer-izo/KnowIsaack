// ============================
// SCROLL RESTORATION
// ============================
if (history.scrollRestoration) {
  history.scrollRestoration = "manual";
}

//======================================
// REVEAL ANIMATION OBSERVER
// ======================================

const revealElements = document.querySelectorAll(".reveal");

// Intersection Observer for Reveal Animations
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

// ==================================
// NAVIGATION HIGHLIGHT OBSERVER
// ==================================

const sections = document.querySelectorAll("section[id]");

const navObserverOptions = {
  root: null,
  rootMargin: "-40% 0px -40% 0px",
  threshold: 0,
};

const navObserver = new IntersectionObserver((entries) => {
  entries.forEach((entry) => {
    if (entry.target.tagName !== "SECTION") {
      return;
    }
    const id = entry.target.getAttribute("id");
    const link = document.querySelector(`.nav-menu a[href="/#${id}"]`);

    if (!link) {
      return;
    }

    if (entry.isIntersecting) {
      link.classList.add("active");
    } else {
      link.classList.remove("active");
    }
  });
}, navObserverOptions);

sections.forEach((section) => navObserver.observe(section));

// ===================================================
// MOUSE TRACKING (Gradient spotlight effect on cards)
// ===================================================
const cards = document.querySelectorAll(".skill-card, .project-card");

cards.forEach((card) => {
  card.addEventListener("mousemove", (e) => {
    // Get card's position and size relative to the viewport
    const rect = card.getBoundingClientRect();

    // Calculate mouse position relative to the card
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;

    // Convert to percentage so that it works at any card size
    const xPercent = (x / rect.width) * 100;
    const yPercent = (y / rect.height) * 100;

    // Feed into CSS variables on that specific card
    card.style.setProperty("--mouse-x", `${xPercent}%`);
    card.style.setProperty("--mouse-y", `${yPercent}%`);
  });

  // Moved mouseleave outside mousemove to avoid duplicate listeners
  card.addEventListener("mouseleave", () => {
    card.style.setProperty("--mouse-x", "50%");
    card.style.setProperty("--mouse-y", "50%");
  });
});

// =============
// CONTACT MODAL
// =============

let modal = null;
let isModalInitialized = false;

function initModal() {
  modal = document.getElementById("contactModal");

  if (!modal) {
    console.warn("[Modal] Modal element not found - will retry");
    return false;
  }

  // Prevent double initialization
  if (isModalInitialized) {
    return true;
  }

  const closeBtn = document.getElementById("modalCloseBtn");
  const cancelBtn = modal.querySelector(".btn-cancel");

  // Open function (expose globally)
  window.openContactModal = function () {
    if (!modal) return;
    modal.classList.add("is-open");
    document.body.style.overflow = "hidden";

    // Focus first input for accessibility
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

  // Close handlers
  if (closeBtn) {
    closeBtn.addEventListener("click", window.closeContactModal);
  }
  if (cancelBtn) {
    cancelBtn.addEventListener("click", window.closeContactModal);
  }

  // Outside click
  modal.addEventListener("click", (e) => {
    if (e.target === modal) window.closeContactModal();
  });

  // Escape key
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && modal && modal.classList.contains("is-open")) {
      window.closeContactModal();
    }
  });

  isModalInitialized = true;

  // Hook up all contact triggers
  setupContactTriggers();

  console.log("[Modal] Initialized successfully");
  return true;
}

function setupContactTriggers() {
  // Trigger 1: Navbar "Contact me" link
  const navContactLink = document.querySelector('.nav-menu a[href="#contact"]');
  if (navContactLink) {
    const newLink = navContactLink.cloneNode(true);
    navContactLink.parentNode.replaceChild(newLink, navContactLink);
    newLink.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      window.openContactModal();
    });
    console.log("[Modal] Navbar contact link hooked up");
  }

  // Trigger 2: Hero "Get in Touch" button
  const heroContactBtn = document.querySelector(
    '.btn-secondary[href="#contact"]',
  );
  if (heroContactBtn) {
    const newBtn = heroContactBtn.cloneNode(true);
    heroContactBtn.parentNode.replaceChild(newBtn, heroContactBtn);
    newBtn.addEventListener("click", (e) => {
      e.preventDefault();
      window.openContactModal();
    });
    console.log("[Modal] Hero contact button hooked up");
  }
}

// ======================================
// CONTACT FORM - VALIDATION & SUBMISSION
// ======================================

// Wait for form to exist before initializing
let contactForm = null;
let formStatus = null;

function initContactForm() {
  contactForm = document.getElementById("contactForm");
  formStatus = document.getElementById("formStatus");

  if (!contactForm) {
    console.warn("[Form] Contact form not found yet");
    return false;
  }

  // Real-time validation
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

// --- Handle Form Submission ---
async function handleFormSubmit(e) {
  e.preventDefault();
  clearErrors();

  const submitBtn = contactForm.querySelector('button[type="submit"]');

  if (!validateContactForm()) {
    return;
  }

  // Get Form Data
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
      setTimeout(() => window.closeContactModal(), 2000); // Auto-close modal after success
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

// --- Form Helpers ---
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

// --- Validation ---
function validateContactForm() {
  const name = document.getElementById("name")?.value.trim() || "";
  const email = document.getElementById("email")?.value.trim() || "";
  const message = document.getElementById("message")?.value.trim() || "";
  let isValid = true;

  // Validate name
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

  // Validate email
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

  // Validate message
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
  // Clear all input errors
  document.querySelectorAll("input.error, textarea.error").forEach((el) => {
    el.classList.remove("error");
    el.removeAttribute("aria-invalid");
  });

  // Clear all error spans
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

// ======================================
// INITIALIZATION - WAIT FOR COMPONENTS
// ======================================

// When components are loaded, initialize everything
document.addEventListener("components:loaded", () => {
  console.log("[App] Components loaded, initializing...");
  initModal();
  initContactForm();
});

// Fallback: Try to initialize immediately if components already loaded
if (document.readyState === "complete") {
  setTimeout(() => {
    if (
      document.getElementById("contactModal") ||
      document.getElementById("contactForm")
    ) {
      initModal();
      initContactForm();
    }
  }, 100);
}
