// =======================================================
// PROJECT PAGE - Shared JS
// Used by: live-your-books.html, sole-proprietor-crm.html
// =======================================================

// --- Scroll Restoration ---
if (history.scrollRestoration) {
    history.scrollRestoration = 'manual';
}

// Get the button
const backToTopBtn = document.getElementById("backToTop");

window.addEventListener("scroll", () => {
    if (!backToTopBtn) {
        return;
    }
    const scrolled = document.body.scrollTop > 20 || document.documentElement.scrollTop > 20;
    backToTopBtn.style.display = scrolled ? "block" : "none";
});

function topFunction() {
    window.scrollTo({
        top: 0,
        behavior: "smooth"
    });
}
