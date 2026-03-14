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
/* ── Apply Now Modal ── */
function openApplyModal(e, loanType, loanName, icon) {
  e.preventDefault();

  // Populate modal header
  document.getElementById('modalLoanType').value = loanType;
  document.getElementById('modalLoanName').textContent = loanName;
  document.getElementById('modalIcon').textContent = icon;

  // Reset form state
  document.getElementById('applyForm').reset();
  document.getElementById('applyForm').style.display = '';
  document.getElementById('modalSuccess').style.display = 'none';
  document.getElementById('modalError').textContent = '';
  document.getElementById('modalSubmitBtn').disabled = false;
  document.getElementById('modalSubmitBtn').textContent = 'Submit Application →';

  // Show overlay
  document.getElementById('applyModalOverlay').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeApplyModal(e) {
  // If called from overlay click, only close when clicking the overlay itself (not the modal box)
  if (e && e.target !== document.getElementById('applyModalOverlay')) return;
  document.getElementById('applyModalOverlay').classList.remove('open');
  document.body.style.overflow = '';
}

async function submitApplication(e) {
  e.preventDefault();

  const errorEl = document.getElementById('modalError');
  const submitBtn = document.getElementById('modalSubmitBtn');
  errorEl.textContent = '';

  const payload = {
    loan_type : document.getElementById('modalLoanType').value,
    name      : document.getElementById('applyName').value.trim(),
    email     : document.getElementById('applyEmail').value.trim(),
    phone     : document.getElementById('applyPhone').value.trim(),
    amount    : document.getElementById('applyAmount').value.trim(),
    message   : document.getElementById('applyMessage').value.trim()
  };

  // Basic client-side validation
  if (!payload.name)  { errorEl.textContent = 'Please enter your full name.'; return; }
  if (!payload.phone || !/^[6-9]\d{9}$/.test(payload.phone)) {
    errorEl.textContent = 'Please enter a valid 10-digit Indian mobile number.'; return;
  }
  if (!payload.email || !/\S+@\S+\.\S+/.test(payload.email)) {
    errorEl.textContent = 'Please enter a valid email address.'; return;
  }

  submitBtn.disabled = true;
  submitBtn.textContent = 'Submitting…';

  try {
    const res  = await fetch('../Backend/products/submit_loan_application.php', {
      method : 'POST',
      headers: { 'Content-Type': 'application/json' },
      body   : JSON.stringify(payload)
    });
    const data = await res.json();

    if (data.success) {
      document.getElementById('applyForm').style.display = 'none';
      document.getElementById('modalSuccessMsg').textContent = data.message;
      document.getElementById('modalSuccess').style.display = 'flex';
    } else {
      const msgs = data.errors ? data.errors.join(' ') : (data.message || 'Something went wrong.');
      errorEl.textContent = msgs;
      submitBtn.disabled = false;
      submitBtn.textContent = 'Submit Application →';
    }
  } catch (err) {
    errorEl.textContent = 'Network error. Please try again.';
    submitBtn.disabled = false;
    submitBtn.textContent = 'Submit Application →';
  }
}

/* ── Advisor Modal (reuses same modal with advisor loan type) ── */
function openAdvisorModal(e) {
  openApplyModal(e, 'general', 'Advisor Callback', '📞');
  document.querySelector('.modal-subtitle').textContent =
    'Tell us your preferred time and a loan advisor will call you — free of charge.';
  // Swap the submit handler context
  document.getElementById('applyForm').onsubmit = submitAdvisorRequest;
  document.getElementById('modalSubmitBtn').textContent = 'Request Callback →';
}

async function submitAdvisorRequest(e) {
  e.preventDefault();

  const errorEl  = document.getElementById('modalError');
  const submitBtn = document.getElementById('modalSubmitBtn');
  errorEl.textContent = '';

  const payload = {
    name  : document.getElementById('applyName').value.trim(),
    phone : document.getElementById('applyPhone').value.trim(),
    email : document.getElementById('applyEmail').value.trim(),
    message: document.getElementById('applyMessage').value.trim()
  };

  if (!payload.name)  { errorEl.textContent = 'Please enter your full name.'; return; }
  if (!payload.phone || !/^[6-9]\d{9}$/.test(payload.phone)) {
    errorEl.textContent = 'Please enter a valid 10-digit Indian mobile number.'; return;
  }

  submitBtn.disabled = true;
  submitBtn.textContent = 'Requesting…';

  try {
    const res  = await fetch('../Backend/products/advisor_contact.php', {
      method : 'POST',
      headers: { 'Content-Type': 'application/json' },
      body   : JSON.stringify(payload)
    });
    const data = await res.json();

    if (data.success) {
      document.getElementById('applyForm').style.display = 'none';
      document.getElementById('modalSuccessMsg').textContent = data.message;
      document.getElementById('modalSuccess').style.display = 'flex';
    } else {
      const msgs = data.errors ? data.errors.join(' ') : (data.message || 'Something went wrong.');
      errorEl.textContent = msgs;
      submitBtn.disabled = false;
      submitBtn.textContent = 'Request Callback →';
    }
  } catch (err) {
    errorEl.textContent = 'Network error. Please try again.';
    submitBtn.disabled = false;
    submitBtn.textContent = 'Request Callback →';
  }
}

/* ── Close modal on Escape key ── */
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    document.getElementById('applyModalOverlay').classList.remove('open');
    document.body.style.overflow = '';
  }
});