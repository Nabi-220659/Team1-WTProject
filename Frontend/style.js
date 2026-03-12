// ── HERO SLIDER ──
const slides = document.querySelectorAll('.slide');
const dots = document.querySelectorAll('.dot');
let current = 0;
let timer;

function goTo(n) {
    slides[current].classList.remove('active');
    dots[current].classList.remove('active');
    current = (n + slides.length) % slides.length;
    slides[current].classList.add('active');
    dots[current].classList.add('active');
}

function autoPlay() {
    timer = setInterval(() => goTo(current + 1), 5000);
}

dots.forEach(d => {
    d.addEventListener('click', () => {
        clearInterval(timer);
        goTo(+d.dataset.index);
        autoPlay();
    });
});

autoPlay();

// ── SCROLL REVEAL ──
const observer = new IntersectionObserver(entries => {
    entries.forEach(e => {
        if (e.isIntersecting) {
            e.target.classList.add('visible');
            const siblings = [...e.target.parentElement.children]
                .filter(c => c.classList.contains('reveal'));
            siblings.forEach((s, i) => {
                s.style.transitionDelay = (i * 0.08) + 's';
            });
        }
    });
}, { threshold: 0.12 });

document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
// ══════════════════════════════════════════════
// ── FETCH STATS from get_stats.php ──
// Updates the 4 numbers in the Stats Band section
// ══════════════════════════════════════════════
async function fetchStats() {
    try {
        const res = await fetch('/Loan-Management-System/Backend/index/get_stats.php');
        const json = await res.json();
        if (!json.success) return;

        json.data.forEach(stat => {
            const item = document.querySelector(`[data-stat-key="${stat.key}"]`);
            if (!item) return;
            item.querySelector('.stat-number').textContent = stat.value;
            item.querySelector('.stat-label').textContent = stat.label;
        });
    } catch (e) {
        // Silently fall back to hardcoded HTML values
        console.warn('Stats API unavailable — using static fallback.');
    }
}


// ══════════════════════════════════════════════
// ── FETCH PRODUCTS from get_products.php ──
// Rebuilds the Services grid cards from MongoDB
// ══════════════════════════════════════════════
async function fetchProducts() {
    try {
        const res = await fetch('/Loan-Management-System/Backend/index/get_products.php');
        const json = await res.json();
        if (!json.success || !json.data.length) return;

        const grid = document.getElementById('servicesGrid');
        if (!grid) return;

        grid.innerHTML = json.data.map(p => `
      <a href="#" class="service-card reveal" data-modal="apply">
        <div class="service-img-wrap">
          <img src="${p.image}" alt="${p.name}" loading="lazy">
          ${p.badge ? `<span class="service-badge">${p.badge}</span>` : ''}
        </div>
        <div class="service-body">
          <div class="service-icon">${p.icon}</div>
          <div class="service-name">${p.name}</div>
          <div class="service-desc">${p.description}</div>
          <div class="service-rate">${p.interest_rate}</div>
        </div>
      </a>
    `).join('');

        // Re-observe the newly created cards for scroll reveal
        grid.querySelectorAll('.reveal').forEach(el => observer.observe(el));

        // Re-bind modal triggers on new cards
        bindModalTriggers();
    } catch (e) {
        console.warn('Products API unavailable — using static fallback.');
    }
}


// ── LOAD BACKEND DATA ON PAGE LOAD ──
fetchStats();
fetchProducts();


// ══════════════════════════════════════════════
// ── CONTACT MODAL ──
// Opened by "Apply Now" and "Talk to Expert" buttons
// POSTs to contact_inquiry.php
// ══════════════════════════════════════════════
const modal = document.getElementById('contactModal');
const modalOverlay = document.getElementById('modalOverlay');
const modalTitle = document.getElementById('modalTitle');
const modalType = document.getElementById('modalType');

function openModal(type) {
    if (!modal) return;
    modalType.value = type;
    modalTitle.textContent = type === 'expert' ? '💬 Talk to an Expert' : '🚀 Apply Now';
    modal.classList.add('active');
    modalOverlay.classList.add('active');
    document.body.style.overflow = 'hidden';
    document.getElementById('contactForm').reset();
    clearModalErrors();
}

function closeModal() {
    modal.classList.remove('active');
    modalOverlay.classList.remove('active');
    document.body.style.overflow = '';
}

function bindModalTriggers() {
    document.querySelectorAll('[data-modal]').forEach(btn => {
        // Avoid double-binding
        btn.removeEventListener('click', handleModalClick);
        btn.addEventListener('click', handleModalClick);
    });
}

function handleModalClick(e) {
    e.preventDefault();
    openModal(this.dataset.modal);
}

// Initial bind on page load
bindModalTriggers();

// Close on overlay click
modalOverlay?.addEventListener('click', closeModal);

// Close on ✕ button
document.getElementById('modalClose')?.addEventListener('click', closeModal);

// Close on Escape key
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeModal();
});


// ══════════════════════════════════════════════
// ── CONTACT FORM SUBMIT ──
// POSTs to Backend/index/contact_inquiry.php
// ══════════════════════════════════════════════
const contactForm = document.getElementById('contactForm');

contactForm?.addEventListener('submit', async e => {
    e.preventDefault();
    if (!validateContactForm()) return;

    const btn = contactForm.querySelector('.modal-submit-btn');
    btn.disabled = true;
    btn.textContent = 'Sending…';

    const payload = {
        name: document.getElementById('cName').value.trim(),
        email: document.getElementById('cEmail').value.trim(),
        phone: document.getElementById('cPhone').value.trim(),
        message: document.getElementById('cMessage').value.trim(),
        type: modalType.value
    };

    try {
        const res = await fetch('/Loan-Management-System/Backend/index/contact_inquiry.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const json = await res.json();

        if (json.success) {
            closeModal();
            showIndexToast('✅ ' + json.message);
        } else {
            const msg = json.errors ? json.errors.join(' ') : json.message;
            showIndexToast('❌ ' + msg, true);
        }
    } catch (err) {
        showIndexToast('❌ Something went wrong. Please try again.', true);
    } finally {
        btn.disabled = false;
        btn.textContent = 'Submit';
    }
});


// ── CONTACT FORM VALIDATION ──
function validateContactForm() {
    let valid = true;
    clearModalErrors();

    const name = document.getElementById('cName');
    const email = document.getElementById('cEmail');
    const phone = document.getElementById('cPhone');

    if (!name.value.trim()) {
        setModalErr(name, 'Full name is required.'); valid = false;
    }
    if (!email.value.trim() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
        setModalErr(email, 'Enter a valid email address.'); valid = false;
    }
    if (!phone.value.trim() || !/^[6-9]\d{9}$/.test(phone.value.replace(/[\s\-+]/g, ''))) {
        setModalErr(phone, 'Enter a valid 10-digit Indian mobile number.'); valid = false;
    }
    return valid;
}

function setModalErr(el, msg) {
    el.classList.add('modal-input-error');
    const span = el.parentElement.querySelector('.modal-err');
    if (span) { span.textContent = msg; span.style.display = 'block'; }
}

function clearModalErrors() {
    document.querySelectorAll('#contactForm .modal-err').forEach(s => {
        s.style.display = 'none'; s.textContent = '';
    });
    document.querySelectorAll('#contactForm .modal-input-error').forEach(el => {
        el.classList.remove('modal-input-error');
    });
}


// ── TOAST NOTIFICATION ──
function showIndexToast(msg, isError = false) {
    const t = document.getElementById('indexToast');
    if (!t) return;
    t.textContent = msg;
    t.style.background = isError ? '#ef4444' : '#10b981';
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3500);
}