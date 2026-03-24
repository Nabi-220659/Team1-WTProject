// ── Loan Products Backend ──

let currentCategory = 'all';
let currentSearch = '';
let applyingProduct = '';

// Load products from backend API
async function loadProducts() {
    try {
        const res = await fetch(`../Backend/products/get_products.php?category=${encodeURIComponent(currentCategory)}&search=${encodeURIComponent(currentSearch)}`);
        const json = await res.json();
        if (json.status === 'success') {
            renderProducts(json.data);
        }
    } catch (err) {
        console.error(err);
    }
}

// Render products
function renderProducts(products) {
    const grid = document.getElementById('productsGrid');
    if (products.length === 0) {
        grid.innerHTML = '<p style="color:var(--muted);grid-column:1/-1">No products found.</p>';
        return;
    }
    grid.innerHTML = products.map(p => `
        <div class="product-card ${p.featured ? 'featured' : ''}" data-cat="${p.category}" ${p.featured ? 'style="position:relative;overflow:hidden"' : ''}>
            ${p.featured ? '<div class="featured-ribbon">BEST DEAL</div>' : ''}
            <div class="pc-header">
                <div class="pc-icon" style="background:${p.icon_bg}">${p.icon}</div>
                <div class="pc-name">${p.name}</div>
                <span class="pc-tag ${p.tag_class}">${p.tag}</span>
            </div>
            <div class="pc-body">
                <div class="pc-rate">
                    <div class="pc-rate-val">${p.rate}</div>
                    <div class="pc-rate-lbl">${p.rate_label || 'p.a. onwards'}</div>
                </div>
                <div class="pc-features">
                    ${p.features.map(f => `<div class="pc-feat">${f}</div>`).join('')}
                </div>
            </div>
            <div class="pc-footer"><button class="apply-btn-product ${p.featured ? 'apb-gold' : 'apb-navy'}"
                    onclick="openApply('${p.name}','${p.rate}','${p.max_amount}','${p.max_tenure}')">Apply Now →</button></div>
        </div>
    `).join('');
}

// Filter products
function filterProducts(cat, btn) {
    document.querySelectorAll('.filter-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    currentCategory = cat;
    loadProducts();
}

// Open apply modal
function openApply(name, rate, max, tenure) {
    applyingProduct = name;
    document.getElementById('modalProductName').textContent = 'Apply for ' + name;
    document.getElementById('modalProductSub').textContent = `Rate from ${rate} · Up to ${max} · ${tenure} months`;
    document.getElementById('applyModal').classList.add('open');
}

// Close apply modal
function closeApply() {
    document.getElementById('applyModal').classList.remove('open');
}

// Submit application
async function submitApply() {
    const btn = document.querySelector('#applyModal .btn-primary-light');
    btn.textContent = 'Submitting...';
    btn.disabled = true;

    const payload = {
        product: applyingProduct,
        name: document.getElementById('applyName').value,
        mobile: document.getElementById('applyMobile').value,
        loan_amount: document.getElementById('applyAmount').value,
        tenure: document.getElementById('applyTenure').value,
        income: document.getElementById('applyIncome').value,
        purpose: document.getElementById('applyPurpose').value
    };

    try {
        const res = await fetch('../Backend/products/submit_loan_application.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const json = await res.json();
        if (json.status === 'success') {
            closeApply();
            const t = document.getElementById('toast');
            t.style.transform = 'translateY(0)';
            setTimeout(() => t.style.transform = 'translateY(80px)', 3000);
        } else {
            alert('Error: ' + json.message);
        }
    } catch (e) {
        alert('Connection error');
    }
    btn.textContent = 'Submit Application →';
    btn.disabled = false;
}

// Initialize event listeners
function initLoanProductsEvents() {
    // Filter tabs
    document.querySelectorAll('.filter-tab').forEach(tab => {
        if (!tab.onclick) {
            tab.addEventListener('click', (e) => {
                filterProducts(e.target.textContent.toLowerCase().split(/\s+/)[0], e.target);
            });
        }
    });

    // Search input
    const searchInput = document.querySelector('.search-input');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            currentSearch = e.target.value;
            loadProducts();
        });
    }

    // Apply modal backdrop click
    const applyModal = document.getElementById('applyModal');
    if (applyModal) {
        applyModal.addEventListener('click', e => {
            if (e.target === applyModal) closeApply();
        });
    }

    // Load initial products
    loadProducts();
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLoanProductsEvents);
} else {
    initLoanProductsEvents();
}
