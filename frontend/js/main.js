 if (history.scrollRestoration) {
            history.scrollRestoration = 'manual';
        }
        const sections = document.querySelectorAll("section[id]");
        const revealElements = document.querySelectorAll(".reveal");
        const navLinks = document.querySelectorAll(".nav-menu a");

        const observerOptions = {
            root: null,
            rootMargin: "0px",
            threshold: [0,0.4]
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
            closeBtn.onclick = () =>
            {
                modal.style.display = "none";
            };
        }

        // Close Modal on Outside Click
        window.onclick = (e) => {
            if (e.target === modal) {
                modal.style.display = "none";
            }
        };

        //Handle From Submission
        contactForm = document.getElementById("contactForm");
        if (contactForm) {
        contactForm.onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const formStatus = document.getElementById("formStatus");
            const submitBtn = e.target.querySelector('button');

            // Reset state and show "Sending"
            submitBtn.disabled = true;
            formStatus.style.display = "block";
            formStatus.classList.remove('success', 'error');
            formStatus.innerHTML = "Sending Email...";

            try {
                const response = await fetch('/api/contact', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.text();

                if (response.ok) {
                    //  Success State
                    formStatus.classList.add('success');
                    formStatus.innerHTML = "Message Sent Succesfully";
                    e.target.reset();

                    setTimeout(() => {
                        formStatus.style.display = "none";
                        formStatus.classList.remove("success");
                    }, 5000);
                } else {
                    // Server Error State
                    formStatus.classList.add('error');
                    formStatus.innerHTML =  result || "Oops! Something went wrong.";
                }
            } catch (error) {
                // Network Error State
                formStatus.classList.add('error');
                formStatus.innerHTML = "Error Sending Message."
            } finally {
                submitBtn.disabled = false;
            }
        };
        }


