/**
 * admin.js — Admin Dashboard (Fixed)
 *
 * BUG FIX: Entire file was using hardcoded MOCK_DATA with no real API calls.
 *           Login compared plaintext password on the frontend (client-side auth = insecure).
 *           Status updates were not persisted to the database.
 *
 * CHANGES:
 *   - Removed all MOCK_DATA; all data now fetched from admin_api.php
 *   - Login now calls POST ?action=admin_login → stores token in sessionStorage
 *   - Every table sends the X-Admin-Token header for authentication
 *   - Status changes (approve/reject) POST to admin_api.php and persist to MongoDB
 *   - Filter + search operate on fetched data cached in window.__adminCache
 */

'use strict';

const API = '../Backend/api/admin_api.php';

// ── Token helpers ──
function getToken() { return sessionStorage.getItem('fbAdminToken') || ''; }
function setToken(t) { sessionStorage.setItem('fbAdminToken', t); }
function clearToken() { sessionStorage.removeItem('fbAdminToken'); sessionStorage.removeItem('fbAdminAuth'); }

async function apiFetch(params, options = {}) {
  const url = API + '?' + new URLSearchParams(params).toString();
  const headers = { 'Content-Type': 'application/json', 'X-Admin-Token': getToken() };
  try {
    const res = await fetch(url, { headers, ...options });
    return await res.json();
  } catch (e) {
    console.error('API error:', e);
    return { success: false, message: 'Network error: ' + e.message };
  }
}

async function apiPost(action, body) {
  return apiFetch({ action }, {
    method: 'POST',
    body: JSON.stringify(body),
  });
}

// ── In-memory cache for fetched data ──
window.__adminCache = {};

// ── ADMIN LOGIN ──
async function adminLogin() {
  const email = document.getElementById('gateEmail').value.trim();
  const pass  = document.getElementById('gatePass').value;
  const msg   = document.getElementById('gateMsg');

  if (!email || !pass) { msg.textContent = '❌ Please enter email and password.'; return; }

  msg.textContent = 'Authenticating…';

  const data = await apiFetch({ action: 'admin_login' }, {
    method: 'POST',
    body: JSON.stringify({ email, password: pass }),
  });

  if (data.success) {
    setToken(data.token || '');
    sessionStorage.setItem('fbAdminAuth', JSON.stringify({ email, name: 'FUNDBEE Admin', time: Date.now() }));
    document.getElementById('adminGate').classList.add('hidden');
    document.getElementById('adminDashboard').classList.remove('hidden');
    document.getElementById('adminName').textContent = 'FUNDBEE Admin';
    await init();
  } else {
    msg.textContent = '❌ ' + (data.message || 'Invalid credentials.');
  }
}

function adminLogout() {
  clearToken();
  document.getElementById('adminGate').classList.remove('hidden');
  document.getElementById('adminDashboard').classList.add('hidden');
  document.getElementById('gateEmail').value = '';
  document.getElementById('gatePass').value = '';
  document.getElementById('gateMsg').textContent = '';
  window.__adminCache = {};
}

// ── TAB SWITCHING ──
function switchAdminTab(name) {
  document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(a => a.classList.remove('active'));
  const panel = document.getElementById('tab-' + name);
  if (panel) panel.classList.add('active');
  const navItem = document.querySelector(`.nav-item[data-tab="${name}"]`);
  if (navItem) navItem.classList.add('active');
}

document.querySelectorAll('.nav-item[data-tab]').forEach(a => {
  a.addEventListener('click', e => { e.preventDefault(); switchAdminTab(a.dataset.tab); });
});

// ── INIT: Load all data ──
async function init() {
  document.getElementById('overviewDate').textContent = new Date().toLocaleDateString('en-IN', {
    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
  });

  // Fetch all data in parallel
  const [statsRes, loansRes, partnersRes, contactsRes, usersRes, banksRes, docsRes, adminNotifsRes] = await Promise.all([
    apiFetch({ action: 'stats' }),
    apiFetch({ action: 'loan_applications' }),
    apiFetch({ action: 'partner_requests' }),
    apiFetch({ action: 'contact_inquiries' }),
    apiFetch({ action: 'users' }),
    apiFetch({ action: 'bank_accounts' }),
    apiFetch({ action: 'documents' }),
    apiFetch({ action: 'admin_notifications' }),
  ]);

  // Cache results
  window.__adminCache = {
    stats:        statsRes.success       ? statsRes                    : {},
    loans:        loansRes.success       ? loansRes.applications       : [],
    partners:     partnersRes.success    ? partnersRes.requests        : [],
    contacts:     contactsRes.success    ? contactsRes.inquiries       : [],
    users:        usersRes.success       ? usersRes.users              : [],
    banks:        banksRes.success       ? banksRes.accounts           : [],
    docs:         docsRes.success        ? docsRes.documents           : [],
    adminNotifs:  adminNotifsRes.success ? adminNotifsRes.notifications : [],
  };

  renderStats();
  renderOverviewRecent();
  renderLoanTable();
  renderPartnerTable();
  renderContactTable();
  renderUsersTable();
  renderBankTable();
  renderDocsTable();
  renderAdminNotifications();
  updateBadges();
}

async function refreshAll() {
  showToast('Refreshing data…');
  await init();
  showToast('Data refreshed ✅');
}

// ── STATS ──
function renderStats() {
  const s = window.__adminCache.stats;
  const loans    = window.__adminCache.loans    || [];
  const partners = window.__adminCache.partners || [];
  const users    = window.__adminCache.users    || [];

  const stats = [
    { icon: '📋', val: s.total_loans    ?? loans.length,    label: 'Total Loan Applications', change: `${s.pending_loans ?? 0} pending`, dir: 'up' },
    { icon: '🤝', val: s.total_partners ?? partners.length, label: 'Partner Requests',         change: `${s.pending_partners ?? 0} pending`, dir: 'up' },
    { icon: '👥', val: s.total_users    ?? users.length,    label: 'Registered Users',          change: 'Total registered', dir: 'up' },
    { icon: '💬', val: s.total_contacts ?? 0,               label: 'Contact Inquiries',          change: 'Awaiting response', dir: 'neutral' },
  ];

  const el = document.getElementById('statsGrid');
  if (!el) return;
  el.innerHTML = stats.map(s => `
    <div class="stat-card">
      <div class="stat-icon">${s.icon}</div>
      <div class="stat-info">
        <div class="stat-val">${s.val}</div>
        <div class="stat-label">${s.label}</div>
        <div class="stat-change ${s.dir}">${s.change}</div>
      </div>
    </div>
  `).join('');
}

function updateBadges() {
  const c = window.__adminCache;
  const pendingLoans    = (c.loans    || []).filter(l => l.status === 'pending').length;
  const pendingPartners = (c.partners || []).filter(p => p.status === 'pending').length;
  const contacts        = (c.contacts || []).length;
  const unreadNotifs    = (c.adminNotifs || []).filter(n => !n.read).length;

  const loanBadgeEl = document.getElementById('badge-loan');
  if (loanBadgeEl) loanBadgeEl.textContent = pendingLoans || '';

  const partnerBadgeEl = document.getElementById('badge-partner');
  if (partnerBadgeEl) partnerBadgeEl.textContent = pendingPartners || '';

  const contactBadgeEl = document.getElementById('badge-contact');
  if (contactBadgeEl) contactBadgeEl.textContent = contacts || '';

  const notifBadgeEl = document.getElementById('adminNotifBadge');
  if (notifBadgeEl) notifBadgeEl.textContent = unreadNotifs || '';

  ['loansBadge','overviewLoansBadge'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.textContent = pendingLoans;
  });
  ['partnersBadge','overviewPartnersBadge'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.textContent = pendingPartners;
  });
  const cb = document.getElementById('contactsBadge');
  if (cb) cb.textContent = contacts;
}

// ── OVERVIEW RECENT ──
function renderOverviewRecent() {
  const loans    = (window.__adminCache.loans    || []).slice(0, 4);
  const partners = (window.__adminCache.partners || []).slice(0, 4);

  const lEl = document.getElementById('overviewLoansList');
  if (lEl) {
    lEl.innerHTML = loans.length ? loans.map(l => `
      <div class="overview-row">
        <div>
          <div class="overview-name">${l.name}</div>
          <div class="overview-sub">${l.loan_type || l.type || ''} · ${l.date || ''}</div>
        </div>
        <span class="status-badge status-${l.status}">${l.status}</span>
      </div>
    `).join('') : '<p class="muted-text">No loan applications yet.</p>';
  }

  const pEl = document.getElementById('overviewPartnersList');
  if (pEl) {
    pEl.innerHTML = partners.length ? partners.map(p => `
      <div class="overview-row">
        <div>
          <div class="overview-name">${p.name}</div>
          <div class="overview-sub">${p.type || ''} · ${p.city || ''}</div>
        </div>
        <span class="status-badge status-${p.status}">${p.status}</span>
      </div>
    `).join('') : '<p class="muted-text">No partner requests yet.</p>';
  }
}

// ── LOAN APPLICATIONS TABLE ──
function renderLoanTable(data) {
  const loans = data || window.__adminCache.loans || [];
  const tbody = document.getElementById('loanTableBody');
  if (!tbody) return;
  if (!loans.length) { tbody.innerHTML = '<tr><td colspan="7" class="empty-cell">No loan applications found.</td></tr>'; return; }

  tbody.innerHTML = loans.map(l => `
    <tr>
      <td><span class="app-id">${l.app_id || l.id}</span></td>
      <td>${l.name}</td>
      <td>${l.loan_type || l.type || '—'}</td>
      <td>₹${l.amount ? Number(l.amount).toLocaleString('en-IN') : '—'}</td>
      <td>${l.date || l.submitted_at || '—'}</td>
      <td><span class="status-badge status-${l.status}">${l.status}</span></td>
      <td>
        <button class="btn-action" onclick="viewLoan('${l.app_id || l.id}')">View</button>
        ${l.status === 'pending' ? `
          <button class="btn-action approve" onclick="updateLoanStatus('${l.app_id || l.id}', 'approved')">Approve</button>
          <button class="btn-action reject"  onclick="updateLoanStatus('${l.app_id || l.id}', 'rejected')">Reject</button>
        ` : ''}
      </td>
    </tr>
  `).join('');
}

function viewLoan(id) {
  const l = (window.__adminCache.loans || []).find(x => (x.app_id || x.id) === id);
  if (!l) return;
  const modal = document.getElementById('loanModal');
  const body  = document.getElementById('loanModalBody');
  if (!body) return;
  body.innerHTML = `
    <div class="modal-row"><span class="modal-key">Application ID</span><span class="modal-val">${l.app_id || l.id}</span></div>
    <div class="modal-row"><span class="modal-key">Name</span><span class="modal-val">${l.name}</span></div>
    <div class="modal-row"><span class="modal-key">Email</span><span class="modal-val">${l.email}</span></div>
    <div class="modal-row"><span class="modal-key">Phone</span><span class="modal-val">${l.phone}</span></div>
    <div class="modal-row"><span class="modal-key">Loan Type</span><span class="modal-val">${l.loan_type || l.type}</span></div>
    <div class="modal-row"><span class="modal-key">Amount</span><span class="modal-val">₹${Number(l.amount || 0).toLocaleString('en-IN')}</span></div>
    <div class="modal-row"><span class="modal-key">Status</span><span class="modal-val"><span class="status-badge status-${l.status}">${l.status}</span></span></div>
    <div class="modal-row"><span class="modal-key">Date</span><span class="modal-val">${l.date || '—'}</span></div>
    ${l.message ? `<div class="modal-row"><span class="modal-key">Message</span><span class="modal-val">${l.message}</span></div>` : ''}
  `;
  if (modal) modal.classList.remove('hidden');
}

async function updateLoanStatus(id, status) {
  if (!confirm(`Set application ${id} to "${status}"?`)) return;
  const res = await apiPost('update_loan_status', { app_id: id, status });
  if (res.success) {
    // Update cached entry
    const loan = (window.__adminCache.loans || []).find(l => (l.app_id || l.id) === id);
    if (loan) loan.status = status;
    renderLoanTable();
    updateBadges();

    // Show disbursement result if applicable
    if (status === 'approved' && res.disbursement) {
      const d = res.disbursement;
      if (d.disbursed) {
        showToast(`✅ Loan approved & ₹${Number(d.amount).toLocaleString('en-IN')} disbursed to ${d.account_no}`);
        // Refresh bank accounts cache so the new balance is visible
        const banksRes = await apiFetch({ action: 'bank_accounts' });
        if (banksRes.success) {
          window.__adminCache.banks = banksRes.accounts;
          renderBankTable();
        }
      } else {
        showToast(`⚠️ Loan approved but disbursement skipped: ${d.reason}`, true);
      }
    } else {
      showToast(`Application ${id} → ${status}`);
    }
  } else {
    showToast('❌ ' + (res.message || 'Update failed'), true);
  }
}

function filterLoans() {
  const q      = (document.getElementById('loanSearch')?.value || '').toLowerCase();
  const status = document.getElementById('loanStatusFilter')?.value || '';
  const loans  = window.__adminCache.loans || [];
  const filtered = loans.filter(l => {
    const matchQ = !q || l.name.toLowerCase().includes(q) || (l.app_id || l.id).toLowerCase().includes(q);
    const matchS = !status || l.status === status;
    return matchQ && matchS;
  });
  renderLoanTable(filtered);
}

// ── PARTNER REQUESTS TABLE ──
function renderPartnerTable(data) {
  const partners = data || window.__adminCache.partners || [];
  const tbody = document.getElementById('partnerTableBody');
  if (!tbody) return;
  if (!partners.length) { tbody.innerHTML = '<tr><td colspan="7" class="empty-cell">No partner requests found.</td></tr>'; return; }

  tbody.innerHTML = partners.map(p => `
    <tr>
      <td><span class="app-id">${p.partner_id || p.id}</span></td>
      <td>${p.name}</td>
      <td>${p.type || '—'}</td>
      <td>${p.city || '—'}, ${p.state || '—'}</td>
      <td>${p.date || '—'}</td>
      <td><span class="status-badge status-${p.status}">${p.status}</span></td>
      <td>
        <button class="btn-action" onclick="viewPartner('${p.partner_id || p.id}')">View</button>
        ${p.status === 'pending' ? `
          <button class="btn-action approve" onclick="updatePartnerStatus('${p.partner_id || p.id}', 'approved')">Approve</button>
          <button class="btn-action reject"  onclick="updatePartnerStatus('${p.partner_id || p.id}', 'rejected')">Reject</button>
        ` : ''}
      </td>
    </tr>
  `).join('');
}

function viewPartner(id) {
  const p = (window.__adminCache.partners || []).find(x => (x.partner_id || x.id) === id);
  if (!p) return;
  const modal = document.getElementById('partnerModal');
  const body  = document.getElementById('partnerModalBody');
  if (!body) return;
  const docs = Array.isArray(p.documents) ? p.documents.filter(Boolean) : [];
  body.innerHTML = `
    <div class="modal-row"><span class="modal-key">Ref ID</span><span class="modal-val">${p.partner_id || p.id}</span></div>
    <div class="modal-row"><span class="modal-key">Name</span><span class="modal-val">${p.name}</span></div>
    <div class="modal-row"><span class="modal-key">Email</span><span class="modal-val">${p.email}</span></div>
    <div class="modal-row"><span class="modal-key">Phone</span><span class="modal-val">${p.phone}</span></div>
    <div class="modal-row"><span class="modal-key">Type</span><span class="modal-val">${p.type}</span></div>
    <div class="modal-row"><span class="modal-key">City</span><span class="modal-val">${p.city}, ${p.state}</span></div>
    <div class="modal-row"><span class="modal-key">Experience</span><span class="modal-val">${p.experience}</span></div>
    <div class="modal-row"><span class="modal-key">Referrals</span><span class="modal-val">${p.referrals}/mo</span></div>
    <div class="modal-row"><span class="modal-key">Bank</span><span class="modal-val">${p.bankName} · ${p.ifsc}</span></div>
    <div class="modal-row"><span class="modal-key">Documents</span><span class="modal-val">${docs.length ? docs.join(', ') : 'None uploaded'}</span></div>
    <div class="modal-row"><span class="modal-key">Status</span><span class="modal-val"><span class="status-badge status-${p.status}">${p.status}</span></span></div>
  `;
  if (modal) modal.classList.remove('hidden');
}

async function updatePartnerStatus(id, status) {
  if (!confirm(`Set partner ${id} to "${status}"?`)) return;
  const res = await apiPost('update_partner_status', { partner_id: id, status });
  if (res.success) {
    const p = (window.__adminCache.partners || []).find(x => (x.partner_id || x.id) === id);
    if (p) p.status = status;
    renderPartnerTable();
    updateBadges();
    showToast(`Partner ${id} → ${status}`);
  } else {
    showToast('❌ ' + (res.message || 'Update failed'), true);
  }
}

function filterPartners() {
  const q       = (document.getElementById('partnerSearch')?.value || '').toLowerCase();
  const status  = document.getElementById('partnerStatusFilter')?.value || '';
  const partners = window.__adminCache.partners || [];
  const filtered = partners.filter(p => {
    const matchQ = !q || p.name.toLowerCase().includes(q) || (p.partner_id || p.id).toLowerCase().includes(q);
    const matchS = !status || p.status === status;
    return matchQ && matchS;
  });
  renderPartnerTable(filtered);
}

// ── CONTACT INQUIRIES ──
function renderContactTable() {
  const contacts = window.__adminCache.contacts || [];
  const tbody = document.getElementById('contactTableBody');
  if (!tbody) return;
  if (!contacts.length) { tbody.innerHTML = '<tr><td colspan="6" class="empty-cell">No contact inquiries found.</td></tr>'; return; }

  tbody.innerHTML = contacts.map(c => `
    <tr>
      <td>${c.name}</td>
      <td>${c.email}</td>
      <td>${c.subject || '—'}</td>
      <td class="msg-cell">${c.message}</td>
      <td>${c.date || '—'}</td>
      <td>
        <button class="btn-action" onclick="viewContact('${c.email}')">View</button>
      </td>
    </tr>
  `).join('');
}

function viewContact(email) {
  const c = (window.__adminCache.contacts || []).find(x => x.email === email);
  if (!c) return;
  const modal = document.getElementById('contactModal');
  const body  = document.getElementById('contactModalBody');
  if (!body) return;
  body.innerHTML = `
    <div class="modal-row"><span class="modal-key">Name</span><span class="modal-val">${c.name}</span></div>
    <div class="modal-row"><span class="modal-key">Email</span><span class="modal-val">${c.email}</span></div>
    <div class="modal-row"><span class="modal-key">Subject</span><span class="modal-val">${c.subject || '—'}</span></div>
    <div class="modal-row"><span class="modal-key">Date</span><span class="modal-val">${c.date || '—'}</span></div>
    <div class="modal-row"><span class="modal-key">Message</span><span class="modal-val" style="white-space:pre-wrap">${c.message}</span></div>
  `;
  if (modal) modal.classList.remove('hidden');
}

// ── USERS TABLE ──
function renderUsersTable(data) {
  const users = data || window.__adminCache.users || [];
  const tbody = document.getElementById('userTableBody');
  if (!tbody) return;
  if (!users.length) { tbody.innerHTML = '<tr><td colspan="6" class="empty-cell">No users found.</td></tr>'; return; }

  tbody.innerHTML = users.map(u => `
    <tr>
      <td>${u.id || '—'}</td>
      <td>${u.name}</td>
      <td>${u.email}</td>
      <td>${u.phone || '—'}</td>
      <td>${u.joined || '—'}</td>
      <td><span class="status-badge status-${u.status}">${u.status}</span></td>
    </tr>
  `).join('');
}

function filterUsers() {
  const q     = (document.getElementById('userSearch')?.value || '').toLowerCase();
  const users = window.__adminCache.users || [];
  const filtered = users.filter(u =>
    !q || u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q)
  );
  renderUsersTable(filtered);
}

// ── BANK ACCOUNTS TABLE ──
function renderBankTable() {
  const banks = window.__adminCache.banks || [];
  const tbody = document.getElementById('bankTableBody');
  if (!tbody) return;
  if (!banks.length) { tbody.innerHTML = '<tr><td colspan="8" class="empty-cell">No bank accounts found.</td></tr>'; return; }

  tbody.innerHTML = banks.map(b => `
    <tr>
      <td>${b.account_no}</td>
      <td>${b.holder}</td>
      <td>₹${Number(b.balance || 0).toLocaleString('en-IN')}</td>
      <td>₹${Number(b.total_deposited || 0).toLocaleString('en-IN')}</td>
      <td style="color:#d97706;font-weight:600;">₹${Number(b.borrowed_amount || 0).toLocaleString('en-IN')}</td>
      <td>${b.txn_count ?? '—'}</td>
      <td><span class="status-badge status-${b.status}">${b.status}</span></td>
      <td><button class="btn-action" onclick="viewBankTransactions('${b.account_no}', '${b.holder}')">View Txns</button></td>
    </tr>
  `).join('');
}

async function viewBankTransactions(accountNo, holderName) {
  const modal = document.getElementById('bankTxnModal');
  const title = document.getElementById('bankTxnModalTitle');
  const body  = document.getElementById('bankTxnModalBody');
  if (!modal) return;
  title.textContent = `Transactions — ${accountNo} (${holderName})`;
  body.innerHTML = '<p style="padding:16px;">Loading…</p>';
  modal.classList.remove('hidden');

  const res = await apiFetch({ action: 'bank_transactions', account_no: accountNo, limit: 100 });
  if (!res.success) { body.innerHTML = `<p style="color:red;">Failed: ${res.message}</p>`; return; }
  const txns = res.transactions || [];
  if (!txns.length) { body.innerHTML = '<p style="padding:16px;color:#888;">No transactions yet.</p>'; return; }

  const typeColor = { loan_credit: '#16a34a', credit: '#2563eb', debit: '#dc2626', loan: '#d97706', loan_repayment: '#7c3aed' };
  const typeLabel = { loan_credit: '💸 Loan Disbursed', credit: '⬆ Credit', debit: '⬇ Debit', loan: '🔄 Loan', loan_repayment: '✅ Repayment' };

  body.innerHTML = `
    <table class="admin-table" style="font-size:13px;">
      <thead><tr><th>Date</th><th>Type</th><th>Title</th><th>Amount</th><th>Note</th></tr></thead>
      <tbody>
        ${txns.map(t => `
          <tr>
            <td style="white-space:nowrap;">${t.date}</td>
            <td><span style="color:${typeColor[t.type]||'#555'};font-weight:600;">${typeLabel[t.type]||t.type}</span></td>
            <td>${t.title}</td>
            <td style="font-weight:700;color:${t.amount>=0?'#16a34a':'#dc2626'};">
              ${t.amount >= 0 ? '+' : ''}₹${Math.abs(t.amount).toLocaleString('en-IN')}
            </td>
            <td style="color:#888;font-size:12px;">${t.note||'—'}</td>
          </tr>
        `).join('')}
      </tbody>
    </table>
  `;
}

// ── DOCUMENTS TABLE ──
function renderDocsTable(data) {
  const docs = data || window.__adminCache.docs || [];
  const tbody = document.getElementById('docsTableBody');
  if (!tbody) return;
  if (!docs.length) { tbody.innerHTML = '<tr><td colspan="5" class="empty-cell">No documents found.</td></tr>'; return; }

  tbody.innerHTML = docs.map(d => `
    <tr>
      <td>${d.name}</td>
      <td>${d.uploader}</td>
      <td><span class="doc-type-badge">${d.type}</span></td>
      <td>${d.size}</td>
      <td>${d.date}</td>
    </tr>
  `).join('');
}

function filterDocs() {
  const q    = (document.getElementById('docSearch')?.value || '').toLowerCase();
  const docs = window.__adminCache.docs || [];
  renderDocsTable(docs.filter(d => !q || d.name.toLowerCase().includes(d.uploader?.toLowerCase().includes(q) || d.name.toLowerCase().includes(q))));
}

// ── MODAL CLOSE ──
document.querySelectorAll('.modal-close, .modal-overlay').forEach(el => {
  el.addEventListener('click', () => {
    document.querySelectorAll('.admin-modal').forEach(m => m.classList.add('hidden'));
  });
});

// ── EXPORT CSV ──
function exportCSV(type) {
  const data = window.__adminCache[type] || [];
  if (!data.length) { showToast('No data to export.', true); return; }
  const keys = Object.keys(data[0]);
  const csv  = [keys.join(','), ...data.map(row => keys.map(k => JSON.stringify(row[k] ?? '')).join(','))].join('\n');
  const blob = new Blob([csv], { type: 'text/csv' });
  const a    = document.createElement('a');
  a.href     = URL.createObjectURL(blob);
  a.download = `fundbee_${type}_${new Date().toISOString().slice(0, 10)}.csv`;
  a.click();
  showToast(`Exported ${data.length} ${type} records.`);
}

// ── TOAST ──
function showToast(msg, isError = false) {
  let toast = document.getElementById('adminToast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'adminToast';
    toast.style.cssText = 'position:fixed;bottom:24px;right:24px;background:#1a6b3c;color:#fff;padding:12px 20px;border-radius:8px;z-index:9999;font-size:14px;box-shadow:0 4px 12px rgba(0,0,0,0.2);transition:opacity .3s';
    document.body.appendChild(toast);
  }
  toast.textContent = msg;
  toast.style.background = isError ? '#dc2626' : '#1a6b3c';
  toast.style.opacity = '1';
  clearTimeout(toast._t);
  toast._t = setTimeout(() => { toast.style.opacity = '0'; }, 3000);
}

// ── PAGE LOAD: check existing session ──
document.addEventListener('DOMContentLoaded', () => {
  const auth = sessionStorage.getItem('fbAdminAuth');
  if (auth && getToken()) {
    document.getElementById('adminGate')?.classList.add('hidden');
    document.getElementById('adminDashboard')?.classList.remove('hidden');
    const parsed = JSON.parse(auth);
    const nameEl = document.getElementById('adminName');
    if (nameEl) nameEl.textContent = parsed.name || 'Admin';
    init();
  }
});

// ── ADMIN NOTIFICATIONS (new loan applications alert) ──
function renderAdminNotifications() {
  const notifs = window.__adminCache.adminNotifs || [];
  const container = document.getElementById('adminNotifList');
  if (!container) return;

  const unread = notifs.filter(n => !n.read).length;
  const badge  = document.getElementById('adminNotifBadge');
  if (badge) badge.textContent = unread > 0 ? unread : '';

  if (notifs.length === 0) {
    container.innerHTML = '<div style="text-align:center;padding:30px;color:#6b7a8d;font-size:14px">No notifications yet.</div>';
    return;
  }

  container.innerHTML = notifs.map(n => `
    <div class="admin-notif-item ${n.read ? '' : 'unread'}" id="notif-${n.notif_id}">
      <div class="notif-icon">${n.type === 'new_loan_application' ? '📋' : '🔔'}</div>
      <div class="notif-body">
        <div class="notif-title">${n.title}</div>
        <div class="notif-msg">${n.message}</div>
        <div class="notif-meta">
          ${n.user_name ? `<span>👤 ${n.user_name}</span>` : ''}
          ${n.user_email ? `<span>✉️ ${n.user_email}</span>` : ''}
          ${n.user_phone ? `<span>📞 ${n.user_phone}</span>` : ''}
          <span>🕐 ${n.created_at}</span>
        </div>
      </div>
      <div class="notif-actions">
        ${!n.read ? `<button class="btn-ghost btn-sm" onclick="markAdminNotifRead('${n.notif_id}')">Mark Read</button>` : '<span style="color:#10b981;font-size:12px">✓ Read</span>'}
        ${n.type === 'new_loan_application' ? `<button class="btn-primary btn-sm" onclick="switchAdminTab('loan-applications')">View Loans</button>` : ''}
      </div>
    </div>`).join('');
}

async function markAdminNotifRead(notifId) {
  await apiPost('mark_admin_notif_read', { notif_id: notifId });
  const item = document.getElementById('notif-' + notifId);
  if (item) {
    item.classList.remove('unread');
    item.querySelector('.btn-ghost.btn-sm')?.replaceWith(Object.assign(document.createElement('span'), { textContent: '✓ Read', style: 'color:#10b981;font-size:12px' }));
  }
  // Update unread count
  const notifs = window.__adminCache.adminNotifs || [];
  const n = notifs.find(x => x.notif_id === notifId);
  if (n) { n.read = true; renderAdminNotifications(); }
}

async function markAllAdminNotifsRead() {
  await apiFetch({ action: 'mark_all_admin_notifs_read' }, { method: 'POST' });
  (window.__adminCache.adminNotifs || []).forEach(n => n.read = true);
  renderAdminNotifications();
  showToast('All notifications marked as read ✅');
}
