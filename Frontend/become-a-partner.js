'use strict';

// ── STATE ──
let currentStep = 1;
const totalSteps = 4;

// ── DOM REFS ──
const progressSteps = document.querySelectorAll('.progress-step');
const formSteps     = document.querySelectorAll('.form-step');
const formSection   = document.getElementById('formSection');
const successScreen = document.getElementById('successScreen');

// ── STEP NAVIGATION ──
function goToStep(n) {
  currentStep = n;

  formSteps.forEach((step, i) => {
    step.classList.toggle('active', i + 1 === n);
  });

  progressSteps.forEach((step, i) => {
    step.classList.remove('active', 'done');
    if (i + 1 < n)  step.classList.add('done');
    if (i + 1 === n) step.classList.add('active');
  });

  // Update step circle text to checkmark when done
  progressSteps.forEach((step, i) => {
    const circle = step.querySelector('.step-circle');
    if (step.classList.contains('done')) {
      circle.textContent = '✓';
    } else {
      circle.textContent = i + 1;
    }
  });

  // Scroll to top of form
  document.getElementById('formAnchor')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// Click on progress steps to jump (only to completed or current)
progressSteps.forEach((step, i) => {
  step.addEventListener('click', () => {
    if (i + 1 <= currentStep) goToStep(i + 1);
  });
});

// ── NEXT BUTTONS ──
document.querySelectorAll('.btn-next').forEach(btn => {
  btn.addEventListener('click', () => {
    const step = parseInt(btn.dataset.step);
    if (!validateStep(step)) return;

    btn.classList.add('loading');
    setTimeout(() => {
      btn.classList.remove('loading');
      goToStep(step + 1);
    }, 600);
  });
});

// ── PREV BUTTONS ──
document.querySelectorAll('.btn-prev').forEach(btn => {
  btn.addEventListener('click', () => {
    const step = parseInt(btn.dataset.step);
    goToStep(step - 1);
  });
});

// ── FINAL SUBMIT ──
const submitBtn = document.getElementById('submitBtn');
if (submitBtn) {
  submitBtn.addEventListener('click', async () => {
    if (!validateStep(4)) return;
    submitBtn.classList.add('loading');

    try {
      const formData = new FormData();

      const fields = [
        'fullName', 'mobile', 'email', 'whatsapp', 'dob',
        'address', 'city', 'state', 'pincode',
        'profession', 'experience', 'referrals', 'existingPartner',
        'bankName', 'accountHolder', 'accountNo', 'ifsc'
      ];

      fields.forEach(f => {
        const el = document.getElementById(f);
        if (el) formData.append(f, el.value);
      });

      // Partner Type
      const pType = document.querySelector('input[name="partnerType"]:checked');
      if (pType) formData.append('partnerType', pType.value);

      // Selected Products
      const productLabels = document.querySelectorAll('.checkbox-grid input:checked');
      const products = Array.from(productLabels).map(input => {
        return input.parentElement.textContent.replace(/[^\w\s-]/g, '').trim();
      }).filter(p => p);
      formData.append('products', JSON.stringify(products));

      // File Uploads
      const fileInputs = ['panFile', 'aadhaarFile', 'photoFile', 'bankFile'];
      fileInputs.forEach(f => {
        const input = document.getElementById(f);
        if (input && input.files && input.files[0]) {
          formData.append(f, input.files[0]);
        }
      });

      // Send Request
      const response = await fetch('../Backend/api/submit_partner.php', {
        method: 'POST',
        body: formData
      });

      const result = await response.json();

      if (response.ok && result.success) {
        submitBtn.classList.remove('loading');
        formSection.style.display = 'none';
        document.querySelector('.progress-bar-section').style.display = 'none';

        if (result.eligible) {
          // ── ELIGIBLE: show success screen then redirect to partner dashboard ──
          successScreen.classList.add('show');
          document.getElementById('refNumber').textContent = 'Reference: ' + result.reference_id;
          successScreen.scrollIntoView({ behavior: 'smooth', block: 'start' });

          localStorage.setItem('partner_approved', 'true'); // mark as approved partner
          setTimeout(() => {
            window.location.href = '../partner/partner1.html';
          }, 2000);
        } else {
          // ── NOT ELIGIBLE: show rejection screen ──
          const rejectionScreen = document.getElementById('rejectionScreen');
          if (rejectionScreen) {
            document.getElementById('rejectionReason').textContent =
              result.reason || 'You do not meet the eligibility criteria at this time.';
            rejectionScreen.classList.add('show');
            rejectionScreen.scrollIntoView({ behavior: 'smooth', block: 'start' });
          }
        }
      } else {
        throw new Error(result.message || 'Error submitting application.');
      }
    } catch (error) {
      submitBtn.classList.remove('loading');
      showToast(error.message, 'error');
      console.error(error);
    }
  });
}

// ── VALIDATION PER STEP ──
function validateStep(step) {
  let valid = true;

  if (step === 1) {
    valid = validateField('fullName')    & valid;
    valid = validateField('mobile')      & valid;
    valid = validateField('email')       & valid;
    valid = validateField('city')        & valid;
    valid = validateField('pincode')     & valid;

    // Check partner type selected
    const typeSelected = document.querySelector('input[name="partnerType"]:checked');
    if (!typeSelected) {
      showToast('Please select a partner type to continue.', 'error');
      valid = false;
    }
  }

  if (step === 2) {
    valid = validateField('profession')  & valid;
    valid = validateField('experience')  & valid;
    valid = validateField('referrals')   & valid;
    valid = validateField('bankName')    & valid;
    valid = validateField('accountNo')   & valid;
    valid = validateField('ifsc')        & valid;
  }

  if (step === 3) {
    valid = validateField('panNumber')   & valid;
<<<<<<< HEAD
=======
    // At least one upload (PAN is mandatory)
>>>>>>> e8a84dfef582ad42d309577d464e7b0aa4a02d59
    const panFile = document.getElementById('panFile');
    if (!panFile.files.length && !panFile.dataset.uploaded) {
      showToast('Please upload your PAN card document.', 'error');
      valid = false;
    }
  }

  if (step === 4) {
    const consent1 = document.getElementById('consent1');
    const consent2 = document.getElementById('consent2');
    if (!consent1.checked || !consent2.checked) {
      showToast('Please accept all declarations to proceed.', 'error');
      valid = false;
    }
  }

  return !!valid;
}

// ── FIELD VALIDATION ──
function validateField(id) {
  const el = document.getElementById(id);
  if (!el) return true;
  const val = el.value.trim();

  let msg = '';

  if (!val) {
    msg = 'This field is required.';
  } else {
    if (id === 'email'     && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val))   msg = 'Enter a valid email address.';
    if (id === 'mobile'    && !/^[6-9]\d{9}$/.test(val.replace(/\D/g,''))) msg = 'Enter a valid 10-digit mobile number.';
    if (id === 'pincode'   && !/^\d{6}$/.test(val))                         msg = 'Enter a valid 6-digit PIN code.';
    if (id === 'panNumber' && !/^[A-Z]{5}[0-9]{4}[A-Z]$/.test(val.toUpperCase())) msg = 'Enter a valid PAN (e.g. ABCDE1234F).';
    if (id === 'ifsc'      && !/^[A-Z]{4}0[A-Z0-9]{6}$/.test(val.toUpperCase()))  msg = 'Enter a valid IFSC code.';
    if (id === 'accountNo' && (val.length < 9 || val.length > 18))          msg = 'Enter a valid bank account number.';
  }

  if (msg) {
    showFieldError(el, msg);
    return false;
  } else {
    clearFieldError(el);
    el.classList.add('valid');
    return true;
  }
}

function showFieldError(el, msg) {
  el.classList.add('error'); el.classList.remove('valid');
  const errEl = el.closest('.form-group')?.querySelector('.field-error');
  if (errEl) { errEl.textContent = msg; errEl.classList.add('show'); }
}

function clearFieldError(el) {
  el.classList.remove('error');
  const errEl = el.closest('.form-group')?.querySelector('.field-error');
  if (errEl) errEl.classList.remove('show');
}

// Live validation on blur
document.querySelectorAll('.form-input, .form-select').forEach(el => {
  el.addEventListener('blur', () => {
    if (el.id && el.value.trim()) validateField(el.id);
  });
  el.addEventListener('input', () => {
    if (el.classList.contains('error')) clearFieldError(el);
  });
});

// Auto-uppercase PAN
const panInput = document.getElementById('panNumber');
if (panInput) {
  panInput.addEventListener('input', () => {
    panInput.value = panInput.value.toUpperCase();
  });
}

// Auto-uppercase IFSC
const ifscInput = document.getElementById('ifsc');
if (ifscInput) {
  ifscInput.addEventListener('input', () => {
    ifscInput.value = ifscInput.value.toUpperCase();
  });
}

// ── FILE UPLOAD AREAS ──
function setupUpload(areaId, inputId, previewId) {
  const area    = document.getElementById(areaId);
  const input   = document.getElementById(inputId);
  const preview = document.getElementById(previewId);
  if (!area || !input || !preview) return;

  area.addEventListener('click', () => input.click());

  area.addEventListener('dragover', e => {
    e.preventDefault(); area.classList.add('dragging');
  });
  area.addEventListener('dragleave', () => area.classList.remove('dragging'));
  area.addEventListener('drop', e => {
    e.preventDefault(); area.classList.remove('dragging');
    if (e.dataTransfer.files.length) handleFile(e.dataTransfer.files[0]);
  });

  input.addEventListener('change', () => {
    if (input.files.length) handleFile(input.files[0]);
  });

  function handleFile(file) {
    const allowed = ['image/jpeg','image/png','application/pdf'];
    if (!allowed.includes(file.type)) { showToast('Only JPG, PNG, or PDF files are allowed.', 'error'); return; }
    if (file.size > 5 * 1024 * 1024)  { showToast('File size must be under 5MB.', 'error'); return; }

    preview.querySelector('.upload-preview-name').textContent = file.name;
    preview.classList.add('show');
    input.dataset.uploaded = '1';
  }

  preview.querySelector('.upload-preview-remove')?.addEventListener('click', e => {
    e.stopPropagation();
    input.value = '';
    delete input.dataset.uploaded;
    preview.classList.remove('show');
  });
}

setupUpload('panUploadArea',    'panFile',    'panPreview');
setupUpload('aadhaarUploadArea','aadhaarFile','aadhaarPreview');
setupUpload('photoUploadArea',  'photoFile',  'photoPreview');
setupUpload('bankUploadArea',   'bankFile',   'bankPreview');

// ── TOAST ──
function showToast(msg, type = '') {
  const toast = document.getElementById('toast');
  toast.textContent = msg;
  toast.className = 'toast' + (type ? ' ' + type : '');
  void toast.offsetWidth;
  toast.classList.add('show');
  setTimeout(() => toast.classList.remove('show'), 3200);
}

// ── SCROLL REVEAL ──
const revealObserver = new IntersectionObserver(entries => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      const siblings = [...e.target.parentElement.children].filter(c => c.classList.contains('reveal'));
      siblings.forEach((s, i) => { s.style.transitionDelay = (i * 0.1) + 's'; });
      e.target.classList.add('visible');
    }
  });
}, { threshold: 0.1 });
document.querySelectorAll('.reveal').forEach(el => revealObserver.observe(el));

// ── INCOME CALCULATOR (hero) ──
<<<<<<< HEAD
=======
// Animate numbers in hero cards on load
>>>>>>> e8a84dfef582ad42d309577d464e7b0aa4a02d59
function animateValue(el, from, to, duration, prefix = '₹', suffix = '') {
  const start = performance.now();
  const update = (time) => {
    const elapsed = time - start;
    const progress = Math.min(elapsed / duration, 1);
    const eased = 1 - Math.pow(1 - progress, 3);
    const val = Math.round(from + (to - from) * eased);
    el.textContent = prefix + val.toLocaleString('en-IN') + suffix;
    if (progress < 1) requestAnimationFrame(update);
  };
  requestAnimationFrame(update);
}

<<<<<<< HEAD
=======
// Trigger count-up on hero visible
>>>>>>> e8a84dfef582ad42d309577d464e7b0aa4a02d59
const heroAmountEl = document.getElementById('heroMonthlyAmount');
if (heroAmountEl) {
  setTimeout(() => animateValue(heroAmountEl, 0, 45000, 1800), 400);
}
const heroAnnualEl = document.getElementById('heroAnnualAmount');
if (heroAnnualEl) {
  setTimeout(() => animateValue(heroAnnualEl, 0, 540000, 2000), 600);
}