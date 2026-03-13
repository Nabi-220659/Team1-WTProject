/* ── Filter Tabs ── */
function filterCards(category, btn) {
  document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');

  const cards = document.querySelectorAll('.product-card');
  cards.forEach((card, i) => {
    const match = category === 'all' || card.dataset.category === category;
    card.style.display = match ? '' : 'none';

    if (match) {
      card.style.animation = 'none';
      card.offsetHeight; // reflow
      card.style.animation = `fadeUp 0.5s ${i * 0.07}s ease forwards`;
      card.style.opacity = '0';
    }
  });

  // Fix featured card column span when filtered
  const featured = document.querySelector('.product-card.featured');
  if (featured && featured.style.display !== 'none') {
    const visibleCount = [...cards].filter(c => c.style.display !== 'none').length;
    featured.style.gridColumn = (category === 'all' && visibleCount > 1) ? 'span 2' : 'span 1';
  }
}