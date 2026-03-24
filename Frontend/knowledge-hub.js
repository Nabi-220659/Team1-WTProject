document.addEventListener('DOMContentLoaded', () => {

  const API_BASE = '../Backend/knowledge-hub';

  const BADGE_CLASSES = {
    loans: 'badge-loans',
    credit: 'badge-credit',
    savings: 'badge-savings',
    tax: 'badge-tax',
    invest: 'badge-invest',
    guide: 'badge-guide',
  };

  const CATEGORY_LABELS = {
    loans: 'Loans',
    credit: 'Credit Score',
    savings: 'Savings',
    tax: 'Tax',
    invest: 'Investing',
    guide: 'Guide',
  };

  // ── Scroll reveal observer ──────────────────────────────────────────────────
  const observer = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        const siblings = [...e.target.parentElement.children]
          .filter(c => c.classList.contains('reveal'));
        siblings.forEach((s, idx) => { s.style.transitionDelay = (idx * 0.08) + 's'; });
        e.target.classList.add('visible');
      }
    });
  }, { threshold: 0.08 });

  function observeReveal() {
    document.querySelectorAll('.reveal:not(.visible)').forEach(el => observer.observe(el));
  }
  observeReveal();

  // ── Render Featured Article ─────────────────────────────────────────────────
  function renderFeatured(article) {
    const container = document.getElementById('featuredArticle');
    if (!container || !article) return;
    container.innerHTML = `
      <a href="#" class="featured-card reveal">
        <div class="featured-img">
          <img src="${article.image_path}" alt="${article.title}" onerror="this.style.display='none'">
          <div class="featured-img-overlay"></div>
          <span class="featured-tag">Must Read</span>
        </div>
        <div class="featured-body">
          <div class="featured-category">📘 Featured Article</div>
          <h3 class="featured-title">${article.title}</h3>
          <p class="featured-excerpt">${article.excerpt}</p>
          <div class="featured-meta">
            <span>${article.author}</span>
            <span class="meta-dot"></span>
            <span>${article.date}</span>
            <span class="meta-dot"></span>
            <span>${article.read_time} min read</span>
          </div>
          <span class="read-more">Read Article →</span>
        </div>
      </a>`;
    observeReveal();
  }

  // ── Render Articles Grid ────────────────────────────────────────────────────
  function renderArticles(articles) {
    const grid = document.getElementById('articlesGrid');
    const noRes = document.getElementById('noResults');
    if (!grid) return;

    const gridArticles = (articles || []).filter(a => !a.is_featured);

    if (gridArticles.length === 0) {
      grid.innerHTML = '';
      if (noRes) noRes.style.display = 'block';
      return;
    }
    if (noRes) noRes.style.display = 'none';

    grid.innerHTML = gridArticles.map(a => {
      const badge = BADGE_CLASSES[a.category] || '';
      const label = CATEGORY_LABELS[a.category] || a.category;
      return `
        <a href="#" class="article-card reveal" data-category="${a.category}">
          <div class="article-img">
            <img src="${a.image_path}" alt="${a.title}" onerror="this.style.display='none'">
            <span class="article-cat-badge ${badge}">${label}</span>
          </div>
          <div class="article-body">
            <div class="article-category">${label}</div>
            <h3 class="article-title">${a.title}</h3>
            <p class="article-excerpt">${a.excerpt}</p>
            <div class="article-footer">
              <span class="read-time">⏱ ${a.read_time} min read</span>
              <span>${a.date}</span>
            </div>
          </div>
        </a>`;
    }).join('');

    observeReveal();
  }

  // ── Fetch articles from backend ─────────────────────────────────────────────
  let currentCategory = 'all';
  let currentSearch = '';

  function fetchArticles(category, search) {
    category = category || 'all';
    search = search || '';

    let url = API_BASE + '/get_articles.php?v=' + Date.now();
    if (category !== 'all') url += '&category=' + encodeURIComponent(category);
    if (search) url += '&search=' + encodeURIComponent(search);

    fetch(url)
      .then(r => r.json())
      .then(data => {
        if (data.success) renderArticles(data.data);
        else console.error('API error:', data.message);
      })
      .catch(err => console.error('Fetch failed:', err));
  }

  // ── Initial page load ───────────────────────────────────────────────────────
  // Featured article
  fetch(API_BASE + '/get_articles.php?featured=1')
    .then(r => r.json())
    .then(data => {
      if (data.success && data.data.length > 0) renderFeatured(data.data[0]);
    })
    .catch(err => console.error('Featured fetch failed:', err));

  // All articles grid
  fetchArticles();

  // ── Category filter buttons (middle of page) ────────────────────────────────
  document.querySelectorAll('.cat-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      currentCategory = btn.dataset.cat;
      fetchArticles(currentCategory, currentSearch);
    });
  });

  // ── Topic pills (hero section) ──────────────────────────────────────────────
  document.querySelectorAll('.topic-pill').forEach(pill => {
    pill.addEventListener('click', () => {
      document.querySelectorAll('.topic-pill').forEach(p => p.classList.remove('active'));
      pill.classList.add('active');
      currentCategory = pill.dataset.topic;
      // Keep cat-buttons in sync
      document.querySelectorAll('.cat-btn').forEach(b => {
        b.classList.toggle('active', b.dataset.cat === currentCategory);
      });
      fetchArticles(currentCategory, currentSearch);
      // Scroll to articles section
      const articles = document.querySelector('.articles-section');
      if (articles) articles.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  });

  // ── Search bar (debounced) ──────────────────────────────────────────────────
  const searchInput = document.getElementById('searchInput');
  let searchTimer;
  if (searchInput) {
    searchInput.addEventListener('input', () => {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(() => {
        currentSearch = searchInput.value.trim();
        fetchArticles(currentCategory, currentSearch);
      }, 350);
    });
  }

  // ── Newsletter form ─────────────────────────────────────────────────────────
  const form = document.getElementById('newsletterForm');
  const emailEl = document.getElementById('newsletterEmail');
  const msgEl = document.getElementById('newsletterMsg');

  if (form) {
    form.addEventListener('submit', e => {
      e.preventDefault();
      const email = emailEl.value.trim();
      if (!email) return;
      const btn = form.querySelector('button[type="submit"]');
      btn.textContent = 'Subscribing…';
      btn.disabled = true;

      fetch(API_BASE + '/subscribe_newsletter.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email }),
      })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            msgEl.textContent = '🎉 ' + data.message;
            msgEl.style.color = '#4ade80';
            emailEl.value = '';
          } else {
            msgEl.textContent = '⚠️ ' + data.message;
            msgEl.style.color = '#f87171';
          }
        })
        .catch(() => {
          msgEl.textContent = '⚠️ Something went wrong. Please try again.';
          msgEl.style.color = '#f87171';
        })
        .finally(() => {
          btn.textContent = 'Subscribe';
          btn.disabled = false;
        });
    });
  }

  // ── EMI Calculator (no backend needed) ─────────────────────────────────────
  const loanSlider = document.getElementById('loanAmount');
  const rateSlider = document.getElementById('interestRate');
  const tenureSlider = document.getElementById('tenure');

  function formatINR(n) {
    if (n >= 10000000) return '₹' + (n / 10000000).toFixed(1) + ' Cr';
    if (n >= 100000) return '₹' + (n / 100000).toFixed(1) + ' L';
    if (n >= 1000) return '₹' + (n / 1000).toFixed(0) + 'K';
    return '₹' + n;
  }

  function calcEMI() {
    if (!loanSlider) return;
    const P = parseFloat(loanSlider.value);
    const r = parseFloat(rateSlider.value) / 100 / 12;
    const n = parseInt(tenureSlider.value);
    document.getElementById('loanAmountVal').textContent = formatINR(P);
    document.getElementById('interestVal').textContent = rateSlider.value + '% p.a.';
    document.getElementById('tenureVal').textContent = n + ' months';
    const emi = r === 0 ? P / n : P * r * Math.pow(1 + r, n) / (Math.pow(1 + r, n) - 1);
    const total = emi * n;
    document.getElementById('emiAmount').textContent = formatINR(Math.round(emi));
    document.getElementById('totalPayable').textContent = formatINR(Math.round(total));
    document.getElementById('totalInterest').textContent = formatINR(Math.round(total - P));
  }

  [loanSlider, rateSlider, tenureSlider].forEach(s => s && s.addEventListener('input', calcEMI));
  calcEMI();

});