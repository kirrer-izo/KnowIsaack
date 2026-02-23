// ============================
// SCROLL RESTORATION
// ============================
// ============================
// SCROLL RESTORATION
// ============================
if (history.scrollRestoration) {
  history.scrollRestoration = "manual";
}

//======================================
// REVEAL ANIMATION OBSERVER
// ======================================

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

const animationObserver = new IntersectionObserver(
  (entries) => {
const animationObserver = new IntersectionObserver(
  (entries) => {
  entries.forEach((entry) => {
    if (entry.isIntersecting && entry.target.classList.contains("reveal")) {
      entry.target.classList.add("is-visible");
    } else {
      entry.target.classList.remove("is-visible");
    }
  });
}, animationObserverOptions);

revealElements.forEach((el) => animationObserver.observe(el));
revealElements.forEach((el) => animationObserver.observe(el));

// ==================================
// NAVIGATION HIGHLIGHT OBSERVER
// ==================================

const sections = document.querySelectorAll("section[id]");

// ==================================
// NAVIGATION HIGHLIGHT OBSERVER
// ==================================

const sections = document.querySelectorAll("section[id]");

const navObserverOptions = {
  root: null,
  rootMargin: "-40% 0px -40% 0px",
  threshold: 0,
};

const navObserver = new IntersectionObserver(
  (entries) => {
const navObserver = new IntersectionObserver(
  (entries) => {
  entries.forEach((entry) => {
    if (entry.target.tagName !== "SECTION") {
      return;
    }
    if (entry.target.tagName !== "SECTION") {
      return;
    }
      const id = entry.target.getAttribute("id");
      const link = document.querySelector(`.nav-menu a[href="#${id}"]`);

      if (!link) {
        return;
      }

      if (!link) {
        return;
      }

        if (entry.isIntersecting) {
          link.classList.add("active");
        } else {
          link.classList.remove("active");
        }
        }
  });
}, navObserverOptions);

sections.forEach((section) => navObserver.observe(section));

// ===================================================
// MOUSE TRACKING (Gradient spotlight effect on cards)
// ===================================================
const cards = document.querySelectorAll('.skill-card, .project-card');

cards.forEach(card => {
  card.addEventListener('mousemove', (e) => {
    // Get card's position and size relative to the viewport
    const rect = card.getBoundingClientRect();

    // Calculate mouse position relative to the card
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;

    // Convert to percentage so that it works at any card size
    const xPercent = (x / rect.width) * 100;
    const yPercent = (y / rect.height) * 100;

    // Feed into CSS variables on that specific card
    card.style.setProperty('--mouse-x', `${xPercent}%`);
    card.style.setProperty('--mouse-y', `${yPercent}%`);

    // Reset when the mouse leaves
    card.addEventListener('mouseleave', () => {
      card.style.setProperty('--mouse-x', '50%');
      card.style.setProperty('--mouse-y', '50%');
    });
  });
});

// =============
// CONTACT MODAL
// =============

// ===================================================
// MOUSE TRACKING (Gradient spotlight effect on cards)
// ===================================================
const cards = document.querySelectorAll('.skill-card, .project-card');

cards.forEach(card => {
  card.addEventListener('mousemove', (e) => {
    // Get card's position and size relative to the viewport
    const rect = card.getBoundingClientRect();

    // Calculate mouse position relative to the card
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;

    // Convert to percentage so that it works at any card size
    const xPercent = (x / rect.width) * 100;
    const yPercent = (y / rect.height) * 100;

    // Feed into CSS variables on that specific card
    card.style.setProperty('--mouse-x', `${xPercent}%`);
    card.style.setProperty('--mouse-y', `${yPercent}%`);

    // Reset when the mouse leaves
    card.addEventListener('mouseleave', () => {
      card.style.setProperty('--mouse-x', '50%');
      card.style.setProperty('--mouse-y', '50%');
    });
  });
});

// =============
// CONTACT MODAL
// =============

//  FETCH API

const modal = document.getElementById("contactModal");
const closeBtn = document.querySelector(".close-btn");

//Open Modal
function openModal() {
  if (modal) {
function openModal() {
  if (modal) {
    modal.style.display = "block";
  }
  }
}

//Close Modal
function closeModal() {
  if (modal) {
function closeModal() {
  if (modal) {
    modal.style.display = "none";
  }
}

if (contactBtn) {
  contactBtn.addEventListener("click", (e) => {
    e.preventDefault();
    openModal();
  });
}

if (closeBtn) {
    closeBtn.addEventListener("click", closeModal());
}

// Close Modal on Outside Click
window.addEventListener("click", (e) => {
window.addEventListener("click", (e) => {
  if (e.target === modal) {
    closeModal();
    closeModal();
  }
});

// ======================================
// CONTACT FORM - VALIDATION & SUBMISSION
// ======================================

});

// ======================================
// CONTACT FORM - VALIDATION & SUBMISSION
// ======================================

const contactForm = document.getElementById("contactForm");
const formStatus = document.getElementById("formStatus");

const formStatus = document.getElementById("formStatus");

// Real Time Validation
if (contactForm) {
  contactForm.querySelectorAll("input, textarea").forEach((field) => {
    field.addEventListener("input", () => {
      if (field.checkValidity()) {
        removeFieldError(field.id);
      }
    });
  });

  contactForm.addEventListener("submit", handleFormSubmit);

  contactForm.addEventListener("submit", handleFormSubmit);
}

// --- Handle Form Submission ---
async function handleFormSubmit(e) {
  e.preventDefault();
  clearErrors();
// --- Handle Form Submission ---
async function handleFormSubmit(e) {
  e.preventDefault();
  clearErrors();

  if (!validateContactForm) {
    return;
  }

   // Get Form Data
    const formData = new FormData(contactForm);
    const submitBtn = contactForm.querySelector('button[type="submit"]');

    setSubmittingState(submitBtn, true);

    try {
      const response = await fetch("/api/contact", {
        method: "POST",
        body: formData,
      });

      const result = await response.json();

      if(response.ok) {
        showFormStatus(
          "success",
          '<i class="fa-solid fa-check-circle"></i> Message Sent Successfully!'
        );
      if(response.ok) {
        showFormStatus(
          "success",
          '<i class="fa-solid fa-check-circle"></i> Message Sent Successfully!'
        );
        contactForm.reset();
        setTimeout(() => hideFormStatus(), 5000);
      } else if (result.errors) {
        displayServiceErrors(result.errors);
        hideFormStatus();
      } else {
        throw new Error(result.message || "Server Error");
        setTimeout(() => hideFormStatus(), 5000);
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
      console.error("Submission error:", error);
      showFormStatus(
        "error",
        '<i class="fa-solid fa-circle-exclamation"></i> ' +
        (error.message || "Network Error. Please try again.")
      );
        (error.message || "Network Error. Please try again.")
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
      '<i class="fa-solid fa-spinner fa-spin"></i> Sending Email...'
    );
  }
}

function showFormStatus(className, html) {
  formStatus.style.display = "block";
  formStatus.className = className;
  formStatus.innerHTML = html;
}

function hideFormStatus() {
  formStatus.style.direction = "none";
  formStatus.className = "";
}

//  --- Validation ---
function validateContactForm() {
  const name = document.getElementById("name").value.trim();
  const email = document.getElementById("email").value.trim();
  const message = document.getElementById("message").value.trim();
  let isValid = true;
  let isValid = true;

  //validate name
  if (!name) {
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

  //validate email
  if (!email) {
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

  //validate message
  if (!message) {
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
  const span = document.getElementById(fieldId + "Error");
  input.classList.add("error");
  span.textContent = message;
}

function removeFieldError(fieldId) {
  const input = document.getElementById(fieldId);
  const errorEl = document.getElementById(fieldId + "Error");
  if (input) {
    input.classList.remove("error");
  }
  if (errorEl) {
    errorEl.textContent = "";
  }
  const errorEl = document.getElementById(fieldId + "Error");
  if (input) {
    input.classList.remove("error");
  }
  if (errorEl) {
    errorEl.textContent = "";
  }
}

function clearErrors() {
    // Only clear error class from inputs and textareas
  document.querySelectorAll("input.error, textarea.error").forEach((el) => {
    el.classList.remove("error");
    if (el.tagName !== "INPUT" && el.tagName !== "TEXTAREA") {
      el.textContent = "";
    }
  });
}

function displayServiceErrors(errors) {
  for (const [field, message] of Object.entries(errors)) {
    const errorEl = document.getElementById(`${field}Error`);
    if (errorEl) {
      errorEl.textContent = message;
    const errorEl = document.getElementById(`${field}Error`);
    if (errorEl) {
      errorEl.textContent = message;
    }
  }
}

