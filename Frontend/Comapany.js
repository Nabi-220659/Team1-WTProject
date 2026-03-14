
    const observer = new IntersectionObserver(entries => {
      entries.forEach((e, i) => {
        if (e.isIntersecting) {
          const siblings = [...e.target.parentElement.children].filter(c => c.classList.contains('reveal'));
          siblings.forEach((s, idx) => { s.style.transitionDelay = (idx * 0.1) + 's'; });
          e.target.classList.add('visible');
        }
      });
    }, { threshold: 0.1 });
    document.querySelectorAll('.reveal').forEach(el => observer.observe(el));