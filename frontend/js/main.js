if (history.scrollRestoration) {
    history.scrollRestoration = 'manual';
}
const sections = document.querySelectorAll("section[id]");
const revealElements = document.querySelectorAll(".reveal");
const navLinks = document.querySelectorAll(".nav-menu a");

const observerOptions = {
    root: null,
    rootMargin: "0px",
    threshold: [0, 0.4]
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {

        // Logic for Trigger Animations (Trigger at 10 % visibility)
        if (entry.target.classList.contains('reveal') && entry.isIntersecting) {
            entry.target.classList.add('is-visible');

            // ONLY Unobserve if it's NOT a section
            if (entry.target.tagName !== 'SECTION') {
                observer.unobserve(entry.target);
            }
        }

        // Logic Update Navbar Links (Trigger at 40% visibility)

        if (entry.target.tagName === 'SECTION') {
            const id = entry.target.getAttribute('id');
            const link = document.querySelector(`.nav-menu a[href="#${id}"]`);
            if (link) {
                if (entry.intersectionRatio > 0.4) {
                    link.classList.add("active");
                } else {
                    link.classList.remove("active");
                }
            }
        }


    });
}, observerOptions);

// Observer to watch everything

sections.forEach((section) => observer.observe(section));
revealElements.forEach((el) => observer.observe(el));

//  FETCH API

const modal = document.getElementById("contactModal");
const contactBtn = document.querySelector(".nav-menu a[href='#contact']");
const closeBtn = document.querySelector(".close-btn");

//Open Modal
if (contactBtn) {
    contactBtn.onclick = (e) => {
        e.preventDefault();
        modal.style.display = "block";
    };
}

//Close Modal
if (closeBtn) {
    closeBtn.onclick = () => {
        modal.style.display = "none";
    };
}

// Close Modal on Outside Click
window.onclick = (e) => {
    if (e.target === modal) {
        modal.style.display = "none";
    }
};
const contactForm = document.getElementById("contactForm");
// Real Time Validation
if (contactForm) {
    contactForm.querySelectorAll('input, textarea').forEach(field => {
        field.addEventListener('input', () => {
            if (field.checkValidity()) {
                removeFieldError(field.id);
            }
        });
    });

}


//Handle Form Submission

if (contactForm) {
    contactForm.onsubmit = async (e) => {
        e.preventDefault();

        //  clear previous errors
        clearErrors();

        // Validate Form
        if (!validateContactForm()) {
            return;
        }

        // Get Form Data
        const formData = new FormData(contactForm);
        const data = Object.fromEntries(formData.entries());
        const submitBtn = contactForm.querySelector('button[type="submit"]');

        // Reset state and show "Sending"
        submitBtn.disabled = true;
        formStatus.style.display = "block";
        formStatus.className = ''; // Clear all classes
        formStatus.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending Email...';

        try {
            const response = await fetch('/api/contact', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (response.ok) {
                // Success State
                formStatus.className = 'success';
                formStatus.innerHTML = '<i class="fa-solid fa-check-circle"></i> Message Sent Successfully!';
                contactForm.reset();

                // Timeout to hide message
                setTimeout(() => {
                    formStatus.style.display = 'none';
                    formStatus.className = '';
                }, 5000);

            } else {
                // Server Error State
                if (result.errors) {
                    displayServiceErrors(result.errors);
                    formStatus.style.display = 'none'; // Hide main status if field errors exist
                } else {
                    throw new Error(result.message || 'Server Error');
                }
            }
        } catch (error) {
            // Network/Generic Error State
            console.error('Error:', error);
            formStatus.className = 'error';
            formStatus.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> ' + (error.message || "Network Error. Please try again.");
        } finally {
            submitBtn.disabled = false;
        }
    };
}
// Validate Contact Form

function validateContactForm() {
    let isValid = true;
    const name = document.getElementById("name").value.trim();
    const email = document.getElementById("email").value.trim();
    const message = document.getElementById("message").value.trim();

    //validate name
    if (name === '') {
        showError('name', 'Name is required');
        isValid = false;
    } else if (name.length < 2) {
        showError('name', 'Name must be at least 2 characters');
        isValid = false;
    } else if (name.length > 50) {
        showError('name', 'Name must be less than 50 characters');
        isValid = false;
    }

    //validate email
    if (email === '') {
        showError('email', 'Email is required');
        isValid = false;
    } else {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            showError('email', 'Please enter a valid email address');
            isValid = false;
        }
    }

    //validate message
    if (message === '') {
        showError('message', 'Message is required');
        isValid = false;
    } else if (message.length < 10) {
        showError('message', 'Message must be at least 10 characters');
        isValid = false;
    } else if (message.length > 1000) {
        showError('message', 'Message must be less than 1000 characters');
        isValid = false;
    }

    return isValid;
}

function showError(elementId, message) {
    const input = document.getElementById(elementId);
    const span = document.getElementById(elementId + 'Error');

    input.classList.add("error");
    span.textContent = message;
    span.classList.add("error");
}

function removeFieldError(fieldId) {
    const input = document.getElementById(fieldId);
    input.classList.remove('error');

    const errorElement = document.getElementById(fieldId + 'Error');
    errorElement.textContent = '';
}

function clearErrors() {
    document.querySelectorAll(".error").forEach(el => {
        el.classList.remove('error');
        el.textContent = '';
    });
}

function displayServiceErrors(errors) {
    for (const [field, message] of Object.entries(errors)) {
        const errorElement = document.getElementById(`${field}Error`);
        if (errorElement) {
            errorElement.textContent = message;
        }
    }
}

