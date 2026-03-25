// ── CONSTANTS ──
const AUTH_API = '../Backend/api/auth.php';

// ── TAB SWITCHER ──
const tabs   = document.querySelectorAll('.auth-tab');
const panels = document.querySelectorAll('.auth-panel');

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

document.querySelectorAll('.toggle-password').forEach(btn => {
  btn.addEventListener('click', () => {
    const input = btn.closest('.input-wrap').querySelector('.form-input');
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    btn.textContent = isHidden ? '🙈' : '👁️';
  });
});

const registerPassword = document.getElementById('registerPassword');
if (registerPassword) {
  registerPassword.addEventListener('input', () => {
    const val  = registerPassword.value;
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
    label.style.color  = colors[score];
  });
}

function showError(inputEl, msg) {
  inputEl.classList.add('error'); inputEl.classList.remove('success');
  const errEl = inputEl.closest('.form-group')?.querySelector('.field-error');
  if (errEl) { errEl.textContent = msg; errEl.classList.add('show'); }
}
function showSuccess(inputEl) {
  inputEl.classList.remove('error'); inputEl.classList.add('success');
  const errEl = inputEl.closest('.form-group')?.querySelector('.field-error');
  if (errEl) errEl.classList.remove('show');
}
function clearAllErrors() {
  document.querySelectorAll('.form-input').forEach(el => el.classList.remove('error', 'success'));
  document.querySelectorAll('.field-error').forEach(el => el.classList.remove('show'));
}
function validateEmail(e) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e); }
function validatePhone(p) { return /^[6-9]\d{9}$/.test(p.replace(/[\s\-+]/g, '')); }

// ── LOGIN FORM ──
const loginForm = document.getElementById('loginForm');
if (loginForm) {
  loginForm.addEventListener('submit', async e => {
    e.preventDefault();
    let valid = true;
    const identifier = document.getElementById('loginIdentifier');
    const password   = document.getElementById('loginPassword');
    const val = identifier.value.trim();
    if (!val) { showError(identifier, 'Please enter your mobile number or email.'); valid = false; }
    else if (!validateEmail(val) && !validatePhone(val)) { showError(identifier, 'Enter a valid email or 10-digit mobile number.'); valid = false; }
    else showSuccess(identifier);
    if (!password.value.trim()) { showError(password, 'Please enter your password.'); valid = false; }
    else if (password.value.length < 6) { showError(password, 'Password must be at least 6 characters.'); valid = false; }
    else showSuccess(password);
    if (!valid) return;

    const btn = loginForm.querySelector('.btn-submit');
    btn.classList.add('loading');

    try {
      const res  = await fetch(AUTH_API + '?action=login', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ identifier: val, password: password.value }),
      });
      const data = await res.json();
      btn.classList.remove('loading');

      if (!data.success) { showError(identifier, data.message || 'Login failed.'); return; }

      // Store full session data
      sessionStorage.setItem('fundbee_user_token',    data.token);
      sessionStorage.setItem('fundbee_user_id',       data.user_id);
      sessionStorage.setItem('fundbee_user_name',     data.name);
      sessionStorage.setItem('fundbee_user_first',    data.first_name);
      sessionStorage.setItem('fundbee_user_initials', data.initials);
      sessionStorage.setItem('fundbee_user_email',    data.email);
      sessionStorage.setItem('fundbee_user_phone',    data.phone);
      sessionStorage.setItem('fundbee_cibil',         data.cibil_score);
      sessionStorage.setItem('fundbee_logged_in',     'true');

      if (data.partner_approved) {
        localStorage.setItem('partner_approved', 'true');
        localStorage.setItem('partner_email',    data.email);
      } else {
        localStorage.removeItem('partner_approved');
      }

      // Update partner button text
      const partnerOpt = document.querySelector('#loginSuccessScreen .partner-opt');
      if (partnerOpt) {
        const h4 = partnerOpt.querySelector('h4');
        const p  = partnerOpt.querySelector('p');
        if (data.partner_approved) {
          if (h4) h4.textContent = 'Go to Partner Dashboard';
          if (p)  p.textContent  = 'View earnings, portfolio & premium analytics';
        } else {
          if (h4) h4.textContent = 'Become a Partner';
          if (p)  p.textContent  = 'Apply to access partner tools & premium analytics';
        }
      }

      document.getElementById('loginSuccessScreen').style.display = 'block';
      loginForm.style.display = 'none';
      showToast('Welcome back, ' + data.first_name + '! ✅');

    } catch (err) {
      btn.classList.remove('loading');
      showToast('Network error. Please try again.', true);
    }
  });
}

// ── REGISTER FORM ──
const registerForm = document.getElementById('registerForm');
if (registerForm) {
  registerForm.addEventListener('submit', async e => {
    e.preventDefault();
    let valid = true;
    const firstName = document.getElementById('firstName');
    const lastName  = document.getElementById('lastName');
    const regPhone  = document.getElementById('regPhone');
    const regEmail  = document.getElementById('regEmail');
    const regPass   = document.getElementById('registerPassword');
    const terms     = document.getElementById('termsCheck');

    if (!firstName.value.trim()) { showError(firstName, 'First name is required.'); valid = false; } else showSuccess(firstName);
    if (!lastName.value.trim())  { showError(lastName,  'Last name is required.'); valid = false; }  else showSuccess(lastName);
    if (!regPhone.value.trim() || !validatePhone(regPhone.value)) { showError(regPhone, 'Enter a valid 10-digit Indian mobile number.'); valid = false; } else showSuccess(regPhone);
    if (!regEmail.value.trim() || !validateEmail(regEmail.value)) { showError(regEmail, 'Enter a valid email address.'); valid = false; } else showSuccess(regEmail);
    if (!regPass.value || regPass.value.length < 8) { showError(regPass, 'Password must be at least 8 characters.'); valid = false; } else showSuccess(regPass);
    if (!terms.checked) { showToast('Please accept the Terms & Conditions to continue.', true); valid = false; }
    if (!valid) return;

    const btn = registerForm.querySelector('.btn-submit');
    btn.classList.add('loading');

    try {
      const res  = await fetch(AUTH_API + '?action=register', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          first_name: firstName.value.trim(),
          last_name:  lastName.value.trim(),
          email:      regEmail.value.trim(),
          phone:      regPhone.value.trim(),
          password:   regPass.value,
        }),
      });
      const data = await res.json();
      btn.classList.remove('loading');

      if (!data.success) {
        showToast(data.message || 'Registration failed.', true);
        return;
      }

      sessionStorage.setItem('fundbee_user_token',    data.token);
      sessionStorage.setItem('fundbee_user_id',       data.user_id);
      sessionStorage.setItem('fundbee_user_name',     data.name);
      sessionStorage.setItem('fundbee_user_first',    data.first_name || firstName.value.trim());
      sessionStorage.setItem('fundbee_user_initials', data.initials);
      sessionStorage.setItem('fundbee_user_email',    data.email);
      sessionStorage.setItem('fundbee_user_phone',    data.phone);
      sessionStorage.setItem('fundbee_cibil',         '720');
      sessionStorage.setItem('fundbee_logged_in',     'true');

      registerForm.style.display = 'none';
      document.getElementById('registerSuccessScreen').style.display = 'block';
      showToast('Account created! Welcome to FUNDBEE 🎉');

    } catch (err) {
      btn.classList.remove('loading');
      showToast('Network error. Please try again.', true);
    }
  });
}


// ── FORGOT PASSWORD ──
document.getElementById('forgotLink')?.addEventListener('click', e => {
  e.preventDefault();
  document.getElementById('loginPanel').classList.remove('active');
  document.getElementById('forgotPanel').classList.add('active');
  tabs.forEach(t => t.classList.remove('active'));
});
document.getElementById('backToLogin')?.addEventListener('click', () => switchToPanel('loginPanel'));

const forgotForm = document.getElementById('forgotForm');
if (forgotForm) {
  forgotForm.addEventListener('submit', e => {
    e.preventDefault();
    const email = document.getElementById('forgotEmail');
    if (!validateEmail(email.value.trim())) { showError(email, 'Enter a valid email address.'); return; }
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

// ── GOOGLE OAUTH ──
document.querySelectorAll('.btn-social').forEach(btn => {
  btn.addEventListener('click', () => {
    if (btn.textContent.toLowerCase().includes('google')) {
      window.location.href = '../Backend/api/oauth_google.php';
    } else { showToast('Social login coming soon!'); }
  });
});

// ── OAUTH SUCCESS HANDLER ──
document.addEventListener('DOMContentLoaded', () => {
  const params = new URLSearchParams(window.location.search);
  if (params.get('oauth_success') === 'true') {
    sessionStorage.setItem('fundbee_logged_in', 'true');
    const lf = document.getElementById('loginForm');
    if (lf) lf.style.display = 'none';
    document.querySelectorAll('.auth-panel').forEach(p => p.classList.remove('active'));
    document.getElementById('loginPanel')?.classList.add('active');
    document.getElementById('loginSuccessScreen').style.display = 'block';
    const provider = params.get('provider') || 'OAuth';
    showToast('Logged in with ' + provider.charAt(0).toUpperCase() + provider.slice(1));
    window.history.replaceState({}, document.title, window.location.pathname);
  }
});

['loginIdentifier', 'regEmail', 'forgotEmail'].forEach(id => {
  const el = document.getElementById(id);
  if (!el) return;
  el.addEventListener('blur', () => {
    if (!el.value.trim()) return;
    if (el.id !== 'loginIdentifier' || el.value.includes('@')) {
      if (!validateEmail(el.value.trim())) showError(el, 'Enter a valid email address.');
      else showSuccess(el);
    } else if (!validatePhone(el.value)) {
      showError(el, 'Enter a valid email or 10-digit mobile number.');
    } else { showSuccess(el); }
  });
});
