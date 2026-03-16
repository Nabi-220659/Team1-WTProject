// ── SCROLL REVEAL ──
const observer = new IntersectionObserver(entries => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      const siblings = [...e.target.parentElement.children]
        .filter(c => c.classList.contains('reveal'));
      siblings.forEach((s, i) => {
        s.style.transitionDelay = (i * 0.1) + 's';
      });
      e.target.classList.add('visible');
    }
  });
}, { threshold: 0.1 });
document.querySelectorAll('.reveal').forEach(el => observer.observe(el));


// ── EARNINGS CALCULATOR ──
const referralsInput   = document.getElementById('referrals');
const avgLoanInput     = document.getElementById('avgLoan');
const partnerTypeInput = document.getElementById('partnerType');

const referralsVal     = document.getElementById('referralsVal');
const avgLoanVal       = document.getElementById('avgLoanVal');

const monthlyEarning   = document.getElementById('monthlyEarning');
const annualEarning    = document.getElementById('annualEarning');
const perReferral      = document.getElementById('perReferral');
const totalLoans       = document.getElementById('totalLoans');

// Commission rates per partner type
const commissionRates = {
  dsa:       0.015,   // 1.5%
  connector: 0.010,   // 1.0%
  corporate: 0.008,   // 0.8%
  digital:   0.012    // 1.2%
};

function formatCurrency(n) {
  if (n >= 10000000) return '₹' + (n / 10000000).toFixed(1) + ' Cr';
  if (n >= 100000)   return '₹' + (n / 100000).toFixed(1) + ' L';
  if (n >= 1000)     return '₹' + (n / 1000).toFixed(0) + 'K';
  return '₹' + Math.round(n).toLocaleString('en-IN');
}

function calcEarnings() {
  const refs    = parseInt(referralsInput.value);
  const loan    = parseInt(avgLoanInput.value);
  const type    = partnerTypeInput.value;
  const rate    = commissionRates[type] || 0.01;

  const perRef  = loan * rate;
  const monthly = refs * perRef;
  const annual  = monthly * 12;
  const loans   = refs * loan;

  referralsVal.textContent = refs + ' referrals';
  avgLoanVal.textContent   = formatCurrency(loan);

  monthlyEarning.textContent = formatCurrency(monthly);
  annualEarning.textContent  = formatCurrency(annual) + ' / year';
  perReferral.textContent    = formatCurrency(perRef);
  totalLoans.textContent     = formatCurrency(loans);
}

if (referralsInput) {
  [referralsInput, avgLoanInput, partnerTypeInput].forEach(el => {
    el.addEventListener('input', calcEarnings);
  });
  calcEarnings();
}


// ── FAQ ACCORDION ──
document.querySelectorAll('.faq-question').forEach(btn => {
  btn.addEventListener('click', () => {
    const item    = btn.closest('.faq-item');
    const isOpen  = item.classList.contains('open');

    // Close all
    document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('open'));

    // Toggle clicked
    if (!isOpen) item.classList.add('open');
  });
});


// ── PARTNER APPLICATION FORM ──
const partnerForm = document.getElementById('partnerForm');
const formSuccess = document.getElementById('formSuccess');

if (partnerForm) {
  partnerForm.addEventListener('submit', e => {
    e.preventDefault();

    // Basic validation
    const required = partnerForm.querySelectorAll('[required]');
    let valid = true;
    required.forEach(field => {
      field.style.borderColor = '';
      if (!field.value.trim()) {
        field.style.borderColor = '#ef4444';
        valid = false;
      }
    });

    if (!valid) return;

    // Simulate submission
    const btn = partnerForm.querySelector('.form-submit');
    btn.textContent = 'Submitting…';
    btn.disabled = true;

    setTimeout(() => {
      partnerForm.style.display = 'none';
      formSuccess.style.display = 'block';
    }, 1200);
  });
}


// ── PARTNER TYPE HIGHLIGHT on card click ──
document.querySelectorAll('.type-card').forEach(card => {
  card.addEventListener('click', () => {
    document.querySelectorAll('.type-card').forEach(c => {
      c.style.borderColor = '';
      c.style.boxShadow = '';
    });
    card.style.borderColor = 'var(--blue)';
    card.style.boxShadow = '0 0 0 3px rgba(26,79,214,0.15)';

    // Pre-select in form
    const type = card.dataset.type;
    const sel  = document.getElementById('partnerTypeSel');
    if (sel && type) sel.value = type;

    // Scroll to form
    document.getElementById('apply')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
});


// ── INPUT FIELD LIVE VALIDATION ──
document.querySelectorAll('.form-input, .form-select, .form-textarea').forEach(field => {
  field.addEventListener('blur', () => {
    if (field.hasAttribute('required') && !field.value.trim()) {
      field.style.borderColor = '#ef4444';
    } else {
      field.style.borderColor = '';
    }
  });
  field.addEventListener('input', () => {
    if (field.value.trim()) field.style.borderColor = '';
  });
});