// ======================================================
// COMPONENT LOADER
// Fetches and injects shared navbar and footer into page
// ======================================================

async function loadComponent(placeholderId, componentPath) {
    const placeholder = document.getElementById(placeholderId);
    if (!placeholder) {
        return;
    }

    try {
        const response = await fetch(componentPath);
        if (!response.ok) {
            throw new Error(`Failed to load: ${componentPath}`);
        }
        placeholder.innerHTML = await response.text();
    } catch (err) {
        console.error(`Component load error [${placeholderId}]:`, err);
    }
}

// Dispatch a custom event once all components are loaded so page scripts
// that depend on the navbar/footer DOM can safely initialize
async function loadAllComponents() {
    await Promise.all([
        loadComponent("navbar-placeholder", "/frontend/components/navbar.html"),
        loadComponent("footer-placeholder", "/frontend/components/footer.html"),
    ]);
    
    document.dispatchEvent(new Event("components:loaded"));
}

loadAllComponents();