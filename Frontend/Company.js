document.addEventListener('DOMContentLoaded', () => {

    const observer = new IntersectionObserver(entries => {
      entries.forEach((e, i) => {
        if (e.isIntersecting) {
          const siblings = [...e.target.parentElement.children].filter(c => c.classList.contains('reveal'));
          siblings.forEach((s, idx) => { s.style.transitionDelay = (idx * 0.1) + 's'; });
          e.target.classList.add('visible');
        }
      });
    }, { threshold: 0.1 });

    const observeElements = () => {
        document.querySelectorAll('.reveal').forEach(el => {
            // Remove visible class so it can re-animate if needed, or just observe
            observer.observe(el);
        });
    };

    // Initial observation for elements already in DOM
    observeElements();

    // Fetch Stats
    fetch('../Backend/company/get_stats.php')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const container = document.getElementById('stats-container');
                if (container) {
                    container.innerHTML = data.data.map(stat => `
                        <div class="hero-stat">
                            <div class="hero-stat-num">${stat.value}</div>
                            <div class="hero-stat-label">${stat.label}</div>
                        </div>
                    `).join('');
                }
            }
        });

    // Fetch Milestones
    fetch('../Backend/company/get_milestones.php')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const container = document.getElementById('timeline-container');
                if (container) {
                    container.innerHTML = data.data.map((m, index) => {
                        const isLeft = index % 2 === 0;
                        if (isLeft) {
                            return `
                            <div class="timeline-item reveal">
                              <div class="tl-content">
                                <div class="tl-year">${m.year}</div>
                                <div class="tl-title">${m.title}</div>
                                <div class="tl-desc">${m.description}</div>
                              </div>
                              <div class="tl-spacer"><div class="tl-dot"></div></div>
                              <div class="tl-empty"></div>
                            </div>
                            `;
                        } else {
                            return `
                            <div class="timeline-item reveal">
                              <div class="tl-empty"></div>
                              <div class="tl-spacer"><div class="tl-dot"></div></div>
                              <div class="tl-content">
                                <div class="tl-year">${m.year}</div>
                                <div class="tl-title">${m.title}</div>
                                <div class="tl-desc">${m.description}</div>
                              </div>
                            </div>
                            `;
                        }
                    }).join('');
                    observeElements(); // Re-observe new elements
                }
            }
        });

    // Fetch Team Members
    fetch('../Backend/company/get_team.php')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const container = document.getElementById('team-container');
                if (container) {
                    container.innerHTML = data.data.map(t => `
                        <div class="team-card reveal">
                            <div class="team-avatar ${t.bg_class}">${t.avatar}</div>
                            <div class="team-body">
                                <div class="team-name">${t.name}</div>
                                <div class="team-role">${t.role}</div>
                                <div class="team-bio">${t.bio}</div>
                            </div>
                        </div>
                    `).join('');
                    observeElements();
                }
            }
        });

    // Fetch Awards
    fetch('../Backend/company/get_awards.php')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const container = document.getElementById('awards-container');
                if (container) {
                    container.innerHTML = data.data.map(a => `
                        <div class="award-card reveal">
                            <div class="award-icon">${a.icon}</div>
                            <div class="award-title">${a.title}</div>
                            <div class="award-org">${a.organization}</div>
                            <div class="award-year">${a.year}</div>
                        </div>
                    `).join('');
                    observeElements();
                }
            }
        });
});
