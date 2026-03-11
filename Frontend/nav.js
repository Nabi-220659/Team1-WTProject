// ── FUNDBEE SHARED NAV SCRIPT ──
// Runs on every page to:
// 1. Make the Login nav link gold styled
// 2. Show "Apply Now" button in nav only when the user is logged in

(function () {
    const navLinks = document.querySelector('.nav-links');
    if (!navLinks) return;

    // ── 1. Style Login link as gold ──
    const loginLink = navLinks.querySelector('a[href="login.html"]');
    if (loginLink) {
        loginLink.classList.add('nav-cta');
    }

    // ── 2. Inject "Apply Now" only when logged in ──
    const isLoggedIn = sessionStorage.getItem('fundbee_logged_in') === 'true';
    if (isLoggedIn) {
        // Don't add a duplicate if it's already there (e.g. become-a-partner.html)
        const existing = navLinks.querySelector('a[href="become-a-partner.html"].nav-cta');
        if (!existing) {
            const li = document.createElement('li');
            li.innerHTML = '<a href="become-a-partner.html" class="nav-cta nav-apply-now">Apply Now</a>';
            navLinks.appendChild(li);
        }
    }
})();
