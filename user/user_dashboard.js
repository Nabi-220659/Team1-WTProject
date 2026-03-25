/**
 * user_dashboard.js
 * Loads real user data from user_api.php on every page load.
 * Works on: user1.html, My_loans.html, Emi_Schedule.html, Notificatons.html,
 *           Documents.html, user_settings.html, eligibility.html
 */

const USER_API  = '../Backend/user/user_api.php';
const AUTH_API  = '../Backend/api/auth.php';

// ── Auth guard: redirect to login if no session ──
function getToken() {
  return sessionStorage.getItem('fundbee_user_token') || '';
}

function authHeaders() {
  return { 'Content-Type': 'application/json', 'X-User-Token': getToken() };
}

async function apiGet(action, extra = {}) {
  const params = new URLSearchParams({ action, ...extra });
  try {
    const res = await fetch(USER_API + '?' + params, { headers: authHeaders() });
    return await res.json();
  } catch (e) {
    console.error('API GET error:', action, e);
    return { success: false, message: 'Network error' };
  }
}

async function apiPost(action, body = {}) {
  try {
    const res = await fetch(USER_API + '?action=' + action, {
      method: 'POST', headers: authHeaders(), body: JSON.stringify(body),
    });
    return await res.json();
  } catch (e) {
    console.error('API POST error:', action, e);
    return { success: false, message: 'Network error' };
  }
}

// ── Format helpers ──
function fmt(n) {
  if (!n && n !== 0) return '—';
  if (n >= 10000000) return '₹' + (n / 10000000).toFixed(1) + 'Cr';
  if (n >= 100000)   return '₹' + (n / 100000).toFixed(1)   + 'L';
  if (n >= 1000)     return '₹' + (n / 1000).toFixed(1)     + 'K';
  return '₹' + Number(n).toLocaleString('en-IN');
}
function fmtFull(n) {
  return '₹' + Number(n || 0).toLocaleString('en-IN');
}
function statusClass(s) {
  if (!s) return 'pending';
  s = s.toLowerCase();
  if (['active','approved','disbursed'].includes(s)) return 'active';
  if (['closed','completed'].includes(s)) return 'closed';
  return 'pending';
}
function statusLabel(s) {
  if (!s) return 'Pending';
  s = s.toLowerCase();
  if (s === 'active' || s === 'disbursed') return 'Active';
  if (s === 'approved') return 'Approved';
  if (s === 'closed' || s === 'completed') return 'Closed';
  if (s === 'pending') return 'Under Review';
  return s.charAt(0).toUpperCase() + s.slice(1);
}
function loanIcon(type) {
  const m = { personal:'👤', home:'🏠', business:'💼', education:'🎓', vehicle:'🚗', instant:'⚡' };
  return m[(type||'').toLowerCase()] || '🏦';
}
function greet() {
  const h = new Date().getHours();
  return h < 12 ? 'Good morning' : h < 17 ? 'Good afternoon' : 'Good evening';
}

// ── Populate sidebar & topbar with user info ──
function populateUserInfo(user) {
  if (!user) return;
  const name     = user.name     || sessionStorage.getItem('fundbee_user_name')     || 'User';
  const initials = user.initials || sessionStorage.getItem('fundbee_user_initials') || 'U';

  document.querySelectorAll('.sidebar-user-name, #sidebarUserName').forEach(el => el.textContent = name);
  document.querySelectorAll('.avatar, #topbarAvatar').forEach(el => el.textContent = initials);
  document.querySelectorAll('#welcomeName').forEach(el => el.textContent = name.split(' ')[0]);
  document.querySelectorAll('#greetText').forEach(el => el.textContent = greet());
}

// ══════════════════════════════════════════
// DASHBOARD PAGE (user1.html)
// ══════════════════════════════════════════
async function loadDashboard() {
  const data = await apiGet('dashboard');
  if (!data.success) {
    if (data.message && data.message.includes('authenticated')) {
      window.location.href = '../Frontend/login.html';
    }
    return;
  }

  const { user, kpis, upcoming_emis, recent_loans, unread_notifs } = data;

  // ── User info ──
  populateUserInfo(user);

  // CIBIL Score
  const cibil = user.cibil || user.cibil_score || 0;
  const el = document.getElementById('cibilScore');
  if (el) el.textContent = cibil;
  const gradeEl = document.getElementById('cibilGrade');
  if (gradeEl) gradeEl.textContent = user.cibil_grade || (cibil >= 750 ? 'Excellent' : cibil >= 700 ? 'Good' : 'Fair');

  // ── KPI Cards ──
  const kpiMap = {
    kpiActiveLoans:   kpis.active_loans,
    kpiBorrowed:      fmt(kpis.total_borrowed),
    kpiRepaid:        fmt(kpis.total_repaid),
    kpiEmiMonth:      fmtFull(kpis.emi_this_month),
  };
  Object.entries(kpiMap).forEach(([id, val]) => {
    const el = document.getElementById(id);
    if (el) el.textContent = val;
  });

  // Notification badge
  if (unread_notifs > 0) {
    document.querySelectorAll('.notif-dot, .notif-dot-gold').forEach(d => d.style.display = 'block');
    const badge = document.getElementById('notifCount');
    if (badge) badge.textContent = unread_notifs;
  }

  // ── Loans Table ──
  const tbody = document.getElementById('loansTableBody');
  if (tbody) {
    if (!recent_loans || recent_loans.length === 0) {
      tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:var(--muted);padding:30px">No loans yet. Apply for your first loan! 🎉</td></tr>';
    } else {
      tbody.innerHTML = recent_loans.map(l => {
        const sc   = statusClass(l.status);
        const lbl  = statusLabel(l.status);
        const icon = loanIcon(l.loan_type);
        const actionBtn = sc === 'closed'
          ? `<button class="action-link" onclick="downloadNOC('${l.loan_id}')">Download NOC</button>`
          : sc === 'pending'
          ? `<button class="action-link" onclick="trackLoan('${l.loan_id}')">Track Status</button>`
          : `<button class="action-link" onclick="viewLoanDetail('${l.loan_id}')">View Details</button>`;
        return `<tr>
          <td><div class="loan-type"><div class="loan-dot ${sc}"></div>${icon} ${l.loan_type.charAt(0).toUpperCase()+l.loan_type.slice(1)} Loan</div></td>
          <td style="color:var(--muted);font-size:13px">${l.loan_id}</td>
          <td>${fmtFull(l.amount)}</td>
          <td>${l.emi ? fmtFull(l.emi) : '—'}</td>
          <td>${l.next_due || '—'}</td>
          <td><span class="status-pill ${sc}">${lbl}</span></td>
          <td>${actionBtn}</td>
        </tr>`;
      }).join('');
    }
  }

  // ── Upcoming EMIs ──
  const emiList = document.getElementById('upcomingEmiList');
  if (emiList) {
    if (!upcoming_emis || upcoming_emis.length === 0) {
      emiList.innerHTML = '<div style="text-align:center;color:var(--muted);padding:20px;font-size:13px">No upcoming EMIs</div>';
    } else {
      emiList.innerHTML = upcoming_emis.map(e => `
        <div class="emi-item">
          <div class="emi-left">
            <div class="emi-icon">${loanIcon(e.loan_type)}</div>
            <div>
              <div class="emi-name">${e.loan_type.charAt(0).toUpperCase()+e.loan_type.slice(1)} Loan</div>
              <div class="emi-date">Due: ${e.due_date}</div>
            </div>
          </div>
          <div style="text-align:right">
            <div class="emi-amount">${fmtFull(e.emi)}</div>
            ${e.days_left <= 7 ? `<span class="emi-due-tag">Due in ${e.days_left} day${e.days_left===1?'':'s'}</span>` : ''}
          </div>
        </div>`).join('');
    }
  }
}

// ══════════════════════════════════════════
// LOAN APPLICATION MODAL
// ══════════════════════════════════════════
function openLoanModal() {
  const modal = document.getElementById('loanModal');
  if (modal) modal.classList.add('open');
}

function closeLoanModal() {
  const modal = document.getElementById('loanModal');
  if (modal) modal.classList.remove('open');
  // Reset form
  const form = document.getElementById('loanApplicationForm');
  if (form) form.reset();
  const sb = document.getElementById('successBanner');
  if (sb) sb.style.display = 'none';
  if (form) form.style.display = 'block';
  // Reset loan type selection
  document.querySelectorAll('.loan-type-btn').forEach((b, i) => {
    b.classList.toggle('selected', i === 0);
  });
}

function selectLoanType(btn) {
  document.querySelectorAll('.loan-type-btn').forEach(b => b.classList.remove('selected'));
  btn.classList.add('selected');
}

async function submitLoanApplication() {
  const selectedType = document.querySelector('.loan-type-btn.selected .lt-name')?.textContent?.trim() || 'Personal Loan';
  const loanType     = selectedType.replace(' Loan', '').toLowerCase();

  const amount     = document.getElementById('loanAmount')?.value;
  const tenureRaw  = document.getElementById('loanTenure')?.value || '24 Months';
  const tenure     = parseInt(tenureRaw);
  const name       = document.getElementById('loanName')?.value?.trim();
  const phone      = document.getElementById('loanPhone')?.value?.trim();
  const purpose    = document.getElementById('loanPurpose')?.value?.trim();
  const income     = document.getElementById('loanIncome')?.value;
  const empType    = document.getElementById('loanEmpType')?.value || 'Salaried';

  if (!amount || amount <= 0) { alert('Please enter a valid loan amount.'); return; }
  if (!name)  { alert('Please enter your full name.'); return; }
  if (!phone || !/^[6-9]\d{9}$/.test(phone.replace(/[\s\-+]/g,''))) { alert('Please enter a valid 10-digit mobile number.'); return; }

  const btn = document.getElementById('loanSubmitBtn');
  if (btn) { btn.disabled = true; btn.textContent = 'Submitting...'; }

  const data = await apiPost('apply_loan', {
    loan_type:       loanType,
    amount:          parseFloat(amount),
    tenure_months:   tenure,
    name,
    phone,
    purpose:         purpose || '',
    monthly_income:  parseFloat(income || 0),
    employment_type: empType,
  });

  if (btn) { btn.disabled = false; btn.textContent = 'Submit Application →'; }

  if (data.success) {
    const sb = document.getElementById('successBanner');
    if (sb) {
      sb.style.display = 'block';
      sb.innerHTML = `🎉 Application <strong>${data.application_id}</strong> submitted! Est. EMI: <strong>${fmtFull(data.emi_estimated)}/mo</strong> at ${data.interest_rate}. We'll contact you within 24 hrs.`;
    }
    const form = document.getElementById('loanApplicationForm');
    if (form) form.style.display = 'none';

    // Refresh dashboard data after 2s
    setTimeout(() => {
      closeLoanModal();
      loadDashboard();
    }, 3500);
  } else {
    alert(data.message || 'Failed to submit application. Please try again.');
  }
}

// ── Loan detail / track / NOC helpers ──
function viewLoanDetail(loanId) {
  sessionStorage.setItem('view_loan_id', loanId);
  window.location.href = 'My_loans.html';
}
function trackLoan(loanId) {
  sessionStorage.setItem('track_loan_id', loanId);
  window.location.href = 'My_loans.html';
}
function downloadNOC(loanId) {
  window.location.href = USER_API + '?action=download_noc&loan_id=' + encodeURIComponent(loanId);
}

// ── Logout ──
async function logoutUser() {
  await fetch(AUTH_API + '?action=logout', { method: 'POST', headers: authHeaders() });
  sessionStorage.clear();
  localStorage.removeItem('partner_approved');
  window.location.href = '../Frontend/login.html';
}

// ── Partner switch ──
function goToPartner() {
  if (localStorage.getItem('partner_approved') === 'true') {
    window.location.href = '../partner/partner1.html';
  } else {
    window.location.href = '../Frontend/become-a-partner.html';
  }
}

// ── Auto-load based on page ──
document.addEventListener('DOMContentLoaded', async () => {
  const token = getToken();

  // Fallback: populate from sessionStorage immediately (before API)
  const cachedName     = sessionStorage.getItem('fundbee_user_name')     || '';
  const cachedInitials = sessionStorage.getItem('fundbee_user_initials') || '';
  if (cachedName) {
    document.querySelectorAll('.sidebar-user-name, #sidebarUserName').forEach(el => el.textContent = cachedName);
    document.querySelectorAll('.avatar, #topbarAvatar').forEach(el => el.textContent = cachedInitials);
    document.querySelectorAll('#welcomeName').forEach(el => el.textContent = cachedName.split(' ')[0]);
    document.querySelectorAll('#greetText').forEach(el => el.textContent = greet());
  }

  // Load dashboard data if on user1.html
  const page = window.location.pathname.split('/').pop();
  if (page === 'user1.html' || page === '') {
    await loadDashboard();
  }

  // Logout buttons
  document.querySelectorAll('.logout-btn').forEach(btn => {
    btn.addEventListener('click', logoutUser);
  });
});
