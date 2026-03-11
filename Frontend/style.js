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

