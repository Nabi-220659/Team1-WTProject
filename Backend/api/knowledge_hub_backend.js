// ── Knowledge Hub Backend ──

let currentSearch = '';
let currentCategory = 'all';

// Utility function for currency formatting
function fmt(n) {
    return '₹' + Math.round(n).toLocaleString('en-IN');
}

// EMI Calculator
function calcEMI() {
    const P = +document.getElementById('calcAmt').value;
    const r = +document.getElementById('calcRate').value / 1200;
    const n = +document.getElementById('calcTenure').value;
    document.getElementById('calcAmtVal').textContent = fmt(P);
    document.getElementById('calcRateVal').textContent = document.getElementById('calcRate').value + '%';
    document.getElementById('calcTenureVal').textContent = n + ' months';
    const emi = P * r * Math.pow(1 + r, n) / (Math.pow(1 + r, n) - 1);
    const total = emi * n;
    const interest = total - P;
    document.getElementById('emiResult').textContent = fmt(emi);
    document.getElementById('totalAmt').textContent = fmt(total);
    document.getElementById('totalInt').textContent = fmt(interest);
}

// Load articles from backend API
async function loadArticles() {
    try {
        const res = await fetch(`../Backend/knowledge-hub/get_articles.php?category=${encodeURIComponent(currentCategory)}&search=${encodeURIComponent(currentSearch)}`);
        const json = await res.json();
        if (json.status === 'success') {
            renderArticles(json.data);
        } else {
            console.error('Error fetching articles:', json.message);
        }
    } catch (err) {
        console.error(err);
    }
}

// Render articles
function renderArticles(articles) {
    const featuredList = articles.filter(a => a.featured);
    const browseList = articles.filter(a => !a.featured);

    // Render Featured
    const featuredContainer = document.getElementById('featuredArticles');
    if (featuredList.length > 0) {
        let main = featuredList[0];
        let othersHtml = featuredList.slice(1).map(a => `
            <div class="article-card">
                <div class="ac-body" style="padding:18px 20px">
                    <div class="ac-cat ${a.category_class}">${a.category}</div>
                    <div class="ac-title">${a.title}</div>
                    <div class="ac-meta"><span>${a.read_time}</span><span class="af-read" style="font-size:11px">Read →</span></div>
                </div>
            </div>
        `).join('');

        featuredContainer.innerHTML = `
        <div class="article-featured">
            <div class="af-img" style="background:linear-gradient(135deg,rgba(11,29,58,0.05),rgba(26,79,214,0.08))">${main.icon || '📝'}</div>
            <div class="af-body">
                <div class="af-cat ${main.category_class}">${main.category}</div>
                <div class="af-title">${main.title}</div>
                <div class="af-excerpt">${main.excerpt || ''}</div>
                <div class="af-meta">
                    <div class="af-author">
                        <div class="af-avatar">${main.author_initials || 'A'}</div>${main.author || 'Admin'} · ${main.read_time}
                    </div>
                    <span class="af-read">Read Article →</span>
                </div>
            </div>
        </div>
        <div style="display:flex;flex-direction:column;gap:14px">${othersHtml}</div>
        `;
    } else {
        featuredContainer.innerHTML = '<p style="color:var(--muted)">No featured articles found.</p>';
    }

    // Render Browse
    const browseContainer = document.getElementById('browseArticles');
    if (browseList.length > 0) {
        browseContainer.innerHTML = browseList.map(a => `
        <div class="article-card">
            <div class="ac-img ${a.bg_class}">${a.icon || '📄'}</div>
            <div class="ac-body">
                <div class="ac-cat ${a.category_class}">${a.category}</div>
                <div class="ac-title">${a.title}</div>
                <div class="ac-meta"><span>${a.read_time}</span><span class="af-read" style="font-size:11px">Read →</span></div>
            </div>
        </div>
        `).join('');
    } else {
        browseContainer.innerHTML = '<p style="color:var(--muted)">No articles found.</p>';
    }
}

// Initialize event listeners
function initKnowledgeHubEvents() {
    // Calculate initial EMI
    calcEMI();

    // Search Input
    document.querySelector('.hub-search input').addEventListener('input', (e) => {
        currentSearch = e.target.value;
        loadArticles();
    });

    // Topic Chips
    document.querySelectorAll('.topic-chip').forEach(chip => {
        chip.addEventListener('click', (e) => {
            document.querySelectorAll('.topic-chip').forEach(c => {
                c.classList.remove('tc-active');
                c.classList.add('tc-inactive');
            });
            chip.classList.remove('tc-inactive');
            chip.classList.add('tc-active');

            let text = chip.textContent.trim();
            currentCategory = text === 'All Topics' ? 'all' : text;
            loadArticles();
        });
    });

    // Initial Load
    loadArticles();
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initKnowledgeHubEvents);
} else {
    initKnowledgeHubEvents();
}
