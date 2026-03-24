// ── TAB SWITCHER (Login / Register) ──
const tabs    = document.querySelectorAll('.auth-tab');
const panels  = document.querySelectorAll('.auth-panel');

tabs.forEach(tab => {
  tab.addEventListener('click', () => {
    tabs.forEach(t => t.classList.remove('active'));
    panels.forEach(p => p.classList.remove('active'));
    tab.classList.add('active');
    document.getElementById(tab.dataset.panel).classList.add('active');
    clearAllErrors();
  });
});

function switchToPanel(panelId) {
  tabs.forEach(t => t.classList.toggle('active', t.dataset.panel === panelId));
  panels.forEach(p => p.classList.toggle('active', p.id === panelId));
  clearAllErrors();
}


// ── PASSWORD VISIBILITY TOGGLE ──
document.querySelectorAll('.toggle-password').forEach(btn => {
  btn.addEventListener('click', () => {
    const input = btn.closest('.input-wrap').querySelector('.form-input');
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    btn.textContent = isHidden ? '🙈' : '👁️';
  });
});


// ── PASSWORD STRENGTH METER ──
const registerPassword = document.getElementById('registerPassword');
if (registerPassword) {
  registerPassword.addEventListener('input', () => {
    const val = registerPassword.value;
    const segs = document.querySelectorAll('#passwordStrength .strength-seg');
    const label = document.getElementById('strengthLabel');
    let score = 0;
    if (val.length >= 8) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    segs.forEach((seg, i) => {
      seg.className = 'strength-seg';
      if (score === 1 && i === 0) seg.classList.add('weak');
      if (score === 2 && i < 2)   seg.classList.add('medium');
      if (score === 3 && i < 3)   seg.classList.add('medium');
      if (score === 4)             seg.classList.add('strong');
    });

    const labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
    const colors = ['', '#ef4444', '#f5a623', '#f5a623', '#10b981'];
    label.textContent = val.length ? labels[score] : '';
    label.style.color = colors[score];
  });
}


// ── VALIDATION HELPERS ──
function showError(inputEl, msg) {
  inputEl.classList.add('error');
  inputEl.classList.remove('success');
  const errEl = inputEl.closest('.form-group')?.querySelector('.field-error');
  if (errEl) { errEl.textContent = msg; errEl.classList.add('show'); }
}

function showSuccess(inputEl) {
  inputEl.classList.remove('error');
  inputEl.classList.add('success');
  const errEl = inputEl.closest('.form-group')?.querySelector('.field-error');
  if (errEl) errEl.classList.remove('show');
}

function clearAllErrors() {
  document.querySelectorAll('.form-input').forEach(el => {
    el.classList.remove('error', 'success');
  });
  document.querySelectorAll('.field-error').forEach(el => {
    el.classList.remove('show');
  });
}

function validateEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function validatePhone(phone) {
  return /^[6-9]\d{9}$/.test(phone.replace(/[\s\-+]/g, ''));
}


// ── LOGIN FORM ──
const loginForm = document.getElementById('loginForm');
if (loginForm) {
<<<<<<< HEAD
  loginForm.addEventListener('submit', e => {
=======
  loginForm.addEventListener('submit', async e => {
>>>>>>> acd5ce854a5ed811a98a84318fc2c4a7830c81d3
    e.preventDefault();
    let valid = true;

    const identifier = document.getElementById('loginIdentifier');
    const password   = document.getElementById('loginPassword');
    const val = identifier.value.trim();

    if (!val) {
      showError(identifier, 'Please enter your mobile number or email.');
      valid = false;
    } else if (!validateEmail(val) && !validatePhone(val)) {
      showError(identifier, 'Enter a valid email address or 10-digit mobile number.');
      valid = false;
    } else {
      showSuccess(identifier);
    }

    if (!password.value.trim()) {
      showError(password, 'Please enter your password.');
      valid = false;
    } else if (password.value.length < 6) {
      showError(password, 'Password must be at least 6 characters.');
      valid = false;
    } else {
      showSuccess(password);
    }

    if (!valid) return;

<<<<<<< HEAD
    // Check login via PHP
    const btn = loginForm.querySelector('.btn-submit');
    btn.classList.add('loading');

    fetch('../user/user_login.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        loginIdentifier: identifier.value.trim(),
        loginPassword: password.value
      })
    })
    .then(res => res.json())
    .then(data => {
      btn.classList.remove('loading');
      if (data.success) {
        sessionStorage.setItem('fundbee_logged_in', 'true'); // ── mark logged in
        document.getElementById('loginSuccessScreen').style.display = 'block';
        loginForm.style.display = 'none';
        
        // Redirect to dashboard
        setTimeout(() => {
          window.location.href = data.redirect || '../user/user1.html';
        }, 1500);
      } else {
        showError(identifier, data.message || 'Login failed');
        showError(password, data.message || 'Login failed');
      }
    })
    .catch(err => {
      btn.classList.remove('loading');
      showToast('An error occurred. Please try again later.', true);
      console.error(err);
    });
=======
    // Simulate login with partner status check
    const btn = loginForm.querySelector('.btn-submit');
    btn.classList.add('loading');

    // Extract email if provided (for partner status check)
    const enteredValue = document.getElementById('loginIdentifier').value.trim();
    const isEmail = enteredValue.includes('@');

    try {
      // Check partner approval status from MongoDB if email was entered
      if (isEmail) {
        const res = await fetch(
          '../Backend/api/check_partner_status.php?email=' + encodeURIComponent(enteredValue)
        );
        const data = await res.json();
        if (data.approved) {
          localStorage.setItem('partner_approved', 'true');
          localStorage.setItem('partner_email', enteredValue);
        } else {
          localStorage.removeItem('partner_approved');
        }
      }
    } catch (e) {
      // If API fails, fall back to existing localStorage value
      console.warn('Partner status check failed:', e);
    }

    setTimeout(() => {
      btn.classList.remove('loading');
      sessionStorage.setItem('fundbee_logged_in', 'true');
      document.getElementById('loginSuccessScreen').style.display = 'block';
      loginForm.style.display = 'none';
    }, 1600);
>>>>>>> acd5ce854a5ed811a98a84318fc2c4a7830c81d3
  });
}


// ── REGISTER FORM ──
const registerForm = document.getElementById('registerForm');
if (registerForm) {
  registerForm.addEventListener('submit', e => {
    e.preventDefault();
    let valid = true;

    const firstName = document.getElementById('firstName');
    const lastName  = document.getElementById('lastName');
    const regPhone  = document.getElementById('regPhone');
    const regEmail  = document.getElementById('regEmail');
    const regPass   = document.getElementById('registerPassword');
    const terms     = document.getElementById('termsCheck');

    if (!firstName.value.trim()) { showError(firstName, 'First name is required.'); valid = false; }
    else showSuccess(firstName);

    if (!lastName.value.trim()) { showError(lastName, 'Last name is required.'); valid = false; }
    else showSuccess(lastName);

    if (!regPhone.value.trim() || !validatePhone(regPhone.value)) {
      showError(regPhone, 'Enter a valid 10-digit Indian mobile number.'); valid = false;
    } else showSuccess(regPhone);

    if (!regEmail.value.trim() || !validateEmail(regEmail.value)) {
      showError(regEmail, 'Enter a valid email address.'); valid = false;
    } else showSuccess(regEmail);

    if (!regPass.value || regPass.value.length < 8) {
      showError(regPass, 'Password must be at least 8 characters.'); valid = false;
    } else showSuccess(regPass);

    if (!terms.checked) {
      showToast('Please accept the Terms & Conditions to continue.', true);
      valid = false;
    }

<<<<<<< HEAD
    const btn = registerForm.querySelector('.btn-submit');
    btn.classList.add('loading');

    // Make AJAX request to register user directly
    fetch('register.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        firstName: document.getElementById('firstName').value.trim(),
        lastName: document.getElementById('lastName').value.trim(),
        regPhone: document.getElementById('regPhone').value.trim(),
        regEmail: document.getElementById('regEmail').value.trim(),
        registerPassword: document.getElementById('registerPassword').value
      })
    })
    .then(res => res.json())
    .then(data => {
      btn.classList.remove('loading');
      if (data.success) {
        sessionStorage.setItem('fundbee_logged_in', 'true'); // ── mark logged in
        registerForm.style.display = 'none';
        document.getElementById('registerSuccessScreen').style.display = 'block';
        
        // Redirect to dashboard
        setTimeout(() => {
          window.location.href = data.redirect || '../user/user1.html';
        }, 1500);
      } else {
        showToast(data.message || 'Registration failed', true);
      }
    })
    .catch(err => {
      btn.classList.remove('loading');
      showToast('Error: ' + err.message + ' (Make sure you are using http://localhost)', true);
      console.error(err);
    });
=======
    if (!valid) return;

    // Show OTP screen
    const phoneDisplay = document.getElementById('otpPhoneDisplay');
    if (phoneDisplay) phoneDisplay.textContent = regPhone.value.trim();

    registerForm.style.display = 'none';
    document.getElementById('otpScreen').style.display = 'block';
    startOtpTimer();
    focusFirstOtp();
>>>>>>> acd5ce854a5ed811a98a84318fc2c4a7830c81d3
  });
}


<<<<<<< HEAD

=======
// ── OTP SCREEN ──
function focusFirstOtp() {
  document.querySelector('.otp-input')?.focus();
}

document.querySelectorAll('.otp-input').forEach((input, i, inputs) => {
  input.addEventListener('input', () => {
    input.value = input.value.replace(/\D/g, '').slice(0, 1);
    if (input.value && i < inputs.length - 1) inputs[i + 1].focus();
  });
  input.addEventListener('keydown', e => {
    if (e.key === 'Backspace' && !input.value && i > 0) inputs[i - 1].focus();
  });
  input.addEventListener('paste', e => {
    e.preventDefault();
    const pasted = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);
    pasted.split('').forEach((char, j) => {
      if (inputs[j]) inputs[j].value = char;
    });
    const last = Math.min(pasted.length, inputs.length - 1);
    inputs[last].focus();
  });
});

const otpForm = document.getElementById('otpForm');
if (otpForm) {
  otpForm.addEventListener('submit', e => {
    e.preventDefault();
    const otpInputs = document.querySelectorAll('.otp-input');
    const code = [...otpInputs].map(i => i.value).join('');
    if (code.length < 6) {
      showToast('Please enter all 6 digits of the OTP.', true);
      return;
    }
    const btn = otpForm.querySelector('.btn-submit');
    btn.classList.add('loading');
    setTimeout(() => {
      btn.classList.remove('loading');
      sessionStorage.setItem('fundbee_logged_in', 'true'); // ── mark logged in
      document.getElementById('otpScreen').style.display = 'none';
      document.getElementById('registerSuccessScreen').style.display = 'block';
    }, 1400);
  });
}

// OTP Back button
document.getElementById('otpBack')?.addEventListener('click', () => {
  document.getElementById('otpScreen').style.display = 'none';
  document.getElementById('registerForm').style.display = 'block';
});

// OTP Timer
let otpInterval;
function startOtpTimer() {
  let seconds = 30;
  const countEl  = document.getElementById('otpCountdown');
  const resendBtn = document.getElementById('resendOtp');
  const timerWrap = document.getElementById('otpTimerWrap');

  if (resendBtn) resendBtn.disabled = true;
  if (timerWrap) timerWrap.style.display = 'inline';
  clearInterval(otpInterval);

  otpInterval = setInterval(() => {
    seconds--;
    if (countEl) countEl.textContent = seconds;
    if (seconds <= 0) {
      clearInterval(otpInterval);
      if (resendBtn) resendBtn.disabled = false;
      if (timerWrap) timerWrap.style.display = 'none';
    }
  }, 1000);
}

document.getElementById('resendOtp')?.addEventListener('click', () => {
  document.querySelectorAll('.otp-input').forEach(i => i.value = '');
  focusFirstOtp();
  startOtpTimer();
  showToast('A new OTP has been sent to your mobile number.');
});
>>>>>>> acd5ce854a5ed811a98a84318fc2c4a7830c81d3


// ── FORGOT PASSWORD ──
document.getElementById('forgotLink')?.addEventListener('click', e => {
  e.preventDefault();
  document.getElementById('loginPanel').classList.remove('active');
  document.getElementById('forgotPanel').classList.add('active');
  tabs.forEach(t => t.classList.remove('active'));
});

document.getElementById('backToLogin')?.addEventListener('click', () => {
  switchToPanel('loginPanel');
});

const forgotForm = document.getElementById('forgotForm');
if (forgotForm) {
  forgotForm.addEventListener('submit', e => {
    e.preventDefault();
    const email = document.getElementById('forgotEmail');
    if (!validateEmail(email.value.trim())) {
      showError(email, 'Enter a valid email address.'); return;
    }
    showSuccess(email);
    const btn = forgotForm.querySelector('.btn-submit');
    btn.classList.add('loading');
    setTimeout(() => {
      btn.classList.remove('loading');
      document.getElementById('forgotPanel').classList.remove('active');
      document.getElementById('forgotSuccessPanel').classList.add('active');
      tabs.forEach(t => t.classList.remove('active'));
    }, 1400);
  });
}

document.getElementById('backToLoginFromForgot')?.addEventListener('click', () => {
  document.getElementById('forgotSuccessPanel').classList.remove('active');
  switchToPanel('loginPanel');
});


// ── TOAST ──
function showToast(msg, isError = false) {
  const toast = document.getElementById('toast');
  toast.textContent = msg;
  toast.className = 'toast' + (isError ? ' error' : '');
  void toast.offsetWidth;
  toast.classList.add('show');
  setTimeout(() => toast.classList.remove('show'), 3200);
}


// ── SOCIAL LOGIN BUTTONS ──
document.querySelectorAll('.btn-social').forEach(btn => {
  btn.addEventListener('click', () => {
    const text = btn.textContent.toLowerCase();
    if (text.includes('google')) {
      window.location.href = '../Backend/api/oauth_google.php';
    } else if (text.includes('facebook')) {
      window.location.href = '../Backend/api/oauth_facebook.php';
    } else {
      showToast('Social login coming soon!');
    }
  });
});

// ── OAUTH SUCCESS HANDLER ──
document.addEventListener('DOMContentLoaded', () => {
  const params = new URLSearchParams(window.location.search);
  if (params.get('oauth_success') === 'true') {
    sessionStorage.setItem('fundbee_logged_in', 'true');
    const loginForm = document.getElementById('loginForm');
    if (loginForm) loginForm.style.display = 'none';
    
    // Show success screen in login panel
    document.querySelectorAll('.auth-panel').forEach(p => p.classList.remove('active'));
    const loginPanel = document.getElementById('loginPanel');
    if (loginPanel) loginPanel.classList.add('active');
    
    const successScreen = document.getElementById('loginSuccessScreen');
    if (successScreen) successScreen.style.display = 'block';
    
    const provider = params.get('provider') || 'OAuth';
    showToast('Successfully logged in with ' + provider.charAt(0).toUpperCase() + provider.slice(1));
    
    // Clean up URL
    window.history.replaceState({}, document.title, window.location.pathname);
  }
});


// ── REAL-TIME EMAIL VALIDATION ──
['loginIdentifier', 'regEmail', 'forgotEmail'].forEach(id => {
  const el = document.getElementById(id);
  if (!el) return;
  el.addEventListener('blur', () => {
    if (!el.value.trim()) return;
    const isEmail = el.id !== 'loginIdentifier' || el.value.includes('@');
    if (isEmail && !validateEmail(el.value.trim())) {
      showError(el, 'Enter a valid email address.');
    } else if (el.id === 'loginIdentifier' && !el.value.includes('@') && !validatePhone(el.value)) {
      showError(el, 'Enter a valid email or 10-digit mobile number.');
    } else {
      showSuccess(el);
    }
  });
});