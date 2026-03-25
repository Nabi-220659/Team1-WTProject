/**
 * virtual-bank.js — Virtual Bank Frontend (Fixed)
 *
 * BUG FIX: Was using localStorage exclusively with a hardcoded default state
 *           (Rahul Sharma, ₹50,000 balance). The real PHP/MongoDB backend API
 *           (virtual_bank_api.php) existed but was never called.
 *
 * CHANGES:
 *   - All data now fetched from virtual_bank_api.php
 *   - user_id read from sessionStorage (set by login.js on authentication)
 *   - Deposit, Withdraw, Transfer, and Loan Repayment all POST to the real API
 *   - On first load, account is auto-created if one doesn't exist for the user
 *   - localStorage is only used as a UI loading cache (not as source of truth)
 */

'use strict';

const BANK_API = '../Backend/api/virtual_bank_api.php';

// ── Get current user ID (set by login.js) ──
function getUserId() {
  return sessionStorage.getItem('fundbee_user_id') || localStorage.getItem('fundbee_user_id') || 'user_demo_001';
}
function getUserName() {
  return sessionStorage.getItem('fundbee_user_name') || 'Rahul Sharma';
}

// ── API Helpers ──
async function bankGet(action, extra = {}) {
  const params = new URLSearchParams({ action, user_id: getUserId(), ...extra });
  try {
    const res = await fetch(`${BANK_API}?${params}`);
    return await res.json();
  } catch (e) {
    return { success: false, message: 'Network error: ' + e.message };
  }
}

async function bankPost(action, body = {}) {
  try {
    const res = await fetch(`${BANK_API}?action=${action}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ user_id: getUserId(), ...body }),
    });
    return await res.json();
  } catch (e) {
    return { success: false, message: 'Network error: ' + e.message };
  }
}

// ── In-memory state (populated from API) ──
let bankState = {
  accountHolder: getUserName(),
  accountNo: '—',
  balance: 0,
  totalDeposited: 0,
  transactions: [],
  loans: [],
};

// ── Format helpers ──
function fmtINR(n) { return '₹' + Math.abs(n).toLocaleString('en-IN'); }
function fmtDate(d) { return new Date(d).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }); }

// ── LOAD ACCOUNT FROM API ──
async function loadAccount() {
  // Try to fetch existing account
  let data = await bankGet('get_account');

  // If no account exists, create one
  if (!data.success) {
    const created = await bankPost('create_account', { user_name: getUserName() });
    if (created.success) {
      data = await bankGet('get_account');
    } else {
      showMsg(document.getElementById('depositMsg'), 'error', created.message || 'Could not create account.');
      return;
    }
  }

  if (data.success) {
    bankState.accountHolder = data.user_name || getUserName();
    bankState.accountNo     = data.account_no;
    bankState.balance       = data.balance;
    bankState.totalDeposited = data.total_deposited;
  }

  // Load transactions
  await loadTransactions();

  // Load active loans from loan_applications
  await loadLoans();

  renderDashboard();
  updateBalanceDisplays();
}

async function loadTransactions() {
  const data = await bankGet('transactions', { limit: 50 });
  if (data.success) {
    bankState.transactions = (data.transactions || []).map(t => ({
      id:     t.id,
      type:   t.type,
      title:  t.title,
      amount: t.amount,
      date:   t.date,
      note:   t.note || '',
    }));
  }
}

async function loadLoans() {
  // Fetch active loans from loan_applications for the EMI section
  try {
    const res = await fetch(`../Backend/api/get_my_loans.php?user_id=${encodeURIComponent(getUserId())}&status=active`);
    const data = await res.json();
    if (data.status === 'success') {
      bankState.loans = (data.data || [])
        .filter(l => l.status === 'active')
        .map(l => ({
          ref:         l.loan_id,
          type:        l.name,
          outstanding: parseFloat((l.outstanding || '0').replace(/[₹,]/g, '')),
          emi:         parseFloat((l.emi || '0').replace(/[₹,]/g, '')) || 0,
          nextDate:    new Date().toISOString().slice(0, 10),
        }));
    }
  } catch (e) {
    bankState.loans = [];
  }
}

// ── TAB SWITCHER ──
function switchTab(name) {
  document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(a => a.classList.remove('active'));
  const panel = document.getElementById('tab-' + name);
  if (panel) panel.classList.add('active');
  const navItem = document.querySelector(`.nav-item[data-tab="${name}"]`);
  if (navItem) navItem.classList.add('active');
  if (name === 'transactions') renderFullTxns();
  if (name === 'statements')   renderStmtPreview();
}

document.querySelectorAll('.nav-item[data-tab]').forEach(a => {
  a.addEventListener('click', e => { e.preventDefault(); switchTab(a.dataset.tab); });
});

// ── RENDER DASHBOARD ──
function renderDashboard() {
  document.getElementById('userAvatar').textContent  = bankState.accountHolder.charAt(0).toUpperCase();
  document.getElementById('sidebarName').textContent  = bankState.accountHolder;
  document.getElementById('sidebarAccNo').textContent = bankState.accountNo;
  document.getElementById('greetName').textContent    = bankState.accountHolder.split(' ')[0];
  document.getElementById('mainBalance').textContent  = fmtINR(bankState.balance);
  document.getElementById('mainAccNo').textContent    = 'Account: ' + bankState.accountNo;
  document.getElementById('totalDeposited').textContent = fmtINR(bankState.totalDeposited);

  const totalOutstanding = bankState.loans.reduce((s, l) => s + l.outstanding, 0);
  document.getElementById('loanOutstanding').textContent = fmtINR(totalOutstanding);

  if (bankState.loans.length > 0) {
    const next = bankState.loans.reduce((a, b) => new Date(a.nextDate) < new Date(b.nextDate) ? a : b);
    document.getElementById('nextEmi').textContent     = fmtINR(next.emi);
    document.getElementById('nextEmiDate').textContent = 'Due: ' + fmtDate(next.nextDate);
  }

  renderRecentTxns();
  renderLoanRepay();
  renderFullTxns();
}

function txnHTML(txn) {
  const isCredit = txn.amount > 0;
  const iconMap  = { credit: '💰', debit: '↑', loan: '🔄' };
  return `<div class="txn-item">
    <div class="txn-icon ${txn.type}">${iconMap[txn.type] || '💳'}</div>
    <div class="txn-details">
      <div class="txn-title">${txn.title}</div>
      <div class="txn-date">${fmtDate(txn.date)}${txn.note ? ' · ' + txn.note : ''}</div>
    </div>
    <div class="txn-amount ${isCredit ? 'credit' : 'debit'}">
      ${isCredit ? '+' : ''}${fmtINR(txn.amount)}
    </div>
  </div>`;
}

function renderRecentTxns() {
  const el     = document.getElementById('recentTxnList');
  const recent = [...bankState.transactions].slice(0, 5);
  if (!el) return;
  if (recent.length === 0) { el.innerHTML = '<div class="empty-state"><div class="empty-icon">📭</div><p>No transactions yet</p></div>'; return; }
  el.innerHTML = recent.map(txnHTML).join('');
}

function renderFullTxns(filter = '') {
  const el = document.getElementById('fullTxnList');
  if (!el) return;
  let list = [...bankState.transactions];
  if (filter) list = list.filter(t => t.title.toLowerCase().includes(filter) || (t.note || '').toLowerCase().includes(filter));
  if (list.length === 0) { el.innerHTML = '<div class="empty-state"><div class="empty-icon">🔍</div><p>No transactions found</p></div>'; return; }
  el.innerHTML = list.map(txnHTML).join('');
}

function filterTxns() {
  const q = (document.getElementById('txnSearch')?.value || '').toLowerCase();
  renderFullTxns(q);
}

function renderLoanRepay() {
  const el = document.getElementById('loanRepayList');
  if (!el) return;
  if (bankState.loans.length === 0) {
    el.innerHTML = '<div class="section-card"><div class="empty-state"><div class="empty-icon">✅</div><p>No active loans</p></div></div>';
    return;
  }
  el.innerHTML = bankState.loans.map(loan => `
    <div class="loan-repay-card">
      <div class="loan-repay-info">
        <div class="loan-repay-type">${loan.type}</div>
        <div class="loan-repay-detail">Ref: ${loan.ref} · Outstanding: ${fmtINR(loan.outstanding)} · Next due: ${fmtDate(loan.nextDate)}</div>
      </div>
      <div>
        <div class="loan-repay-emi">${fmtINR(loan.emi)}</div>
        <div class="loan-repay-emi-label">Monthly EMI</div>
      </div>
      <button class="pay-emi-btn" onclick="quickPayEMI('${loan.ref}', ${loan.emi})">Pay EMI</button>
    </div>
  `).join('');
}

// ── DEPOSIT ──
function setDepositAmt(n) { document.getElementById('depositAmt').value = n; }

async function doDeposit() {
  const amt    = parseFloat(document.getElementById('depositAmt').value);
  const method = document.getElementById('depositMethod').value;
  const msg    = document.getElementById('depositMsg');

  if (!amt || amt <= 0)      return showMsg(msg, 'error', 'Please enter a valid amount.');
  if (amt > 1000000)         return showMsg(msg, 'error', 'Maximum single deposit is ₹10,00,000.');

  showMsg(msg, '', 'Processing…');
  const data = await bankPost('deposit', { amount: amt, method });

  if (data.success) {
    bankState.balance = data.new_balance;
    bankState.totalDeposited += amt;
    bankState.transactions.unshift({
      id: data.transaction_id, type: 'credit',
      title: 'Deposit via ' + method.toUpperCase(),
      amount: amt, date: new Date().toISOString().slice(0, 10),
      note: 'Deposited by user',
    });
    renderDashboard();
    updateBalanceDisplays();
    document.getElementById('depositAmt').value = '';
    showMsg(msg, 'success', `✅ ${fmtINR(amt)} deposited successfully via ${method.toUpperCase()}`);
  } else {
    showMsg(msg, 'error', '❌ ' + data.message);
  }
}

// ── WITHDRAW ──
async function doWithdraw() {
  const amt  = parseFloat(document.getElementById('withdrawAmt').value);
  const bank = document.getElementById('withdrawBank').value.trim();
  const msg  = document.getElementById('withdrawMsg');

  if (!amt || amt <= 0) return showMsg(msg, 'error', 'Please enter a valid amount.');
  if (!bank)            return showMsg(msg, 'error', 'Please enter a destination bank account or UPI ID.');
  if (amt > bankState.balance) return showMsg(msg, 'error', `Insufficient balance. Available: ${fmtINR(bankState.balance)}`);

  showMsg(msg, '', 'Processing…');
  const data = await bankPost('withdraw', { amount: amt, destination: bank });

  if (data.success) {
    bankState.balance = data.new_balance;
    bankState.transactions.unshift({
      id: data.transaction_id, type: 'debit',
      title: 'Withdrawal to ' + bank, amount: -amt,
      date: new Date().toISOString().slice(0, 10), note: 'Withdrawal request',
    });
    renderDashboard();
    updateBalanceDisplays();
    document.getElementById('withdrawAmt').value = '';
    document.getElementById('withdrawBank').value = '';
    showMsg(msg, 'success', `✅ ${fmtINR(amt)} withdrawal initiated to ${bank}`);
  } else {
    showMsg(msg, 'error', '❌ ' + data.message);
  }
}

// ── TRANSFER ──
async function doTransfer() {
  const toAcc = document.getElementById('transferTo').value.trim();
  const amt   = parseFloat(document.getElementById('transferAmt').value);
  const note  = document.getElementById('transferNote').value.trim();
  const msg   = document.getElementById('transferMsg');

  if (!toAcc)           return showMsg(msg, 'error', 'Please enter a recipient account number.');
  if (!amt || amt <= 0) return showMsg(msg, 'error', 'Please enter a valid amount.');
  if (amt > bankState.balance) return showMsg(msg, 'error', `Insufficient balance. Available: ${fmtINR(bankState.balance)}`);

  showMsg(msg, '', 'Processing…');
  const data = await bankPost('transfer', { from_user_id: getUserId(), to_account_no: toAcc, amount: amt, note });

  if (data.success) {
    bankState.balance -= amt;
    bankState.transactions.unshift({
      id: Date.now().toString(), type: 'debit',
      title: 'Transfer to ' + toAcc, amount: -amt,
      date: new Date().toISOString().slice(0, 10), note,
    });
    renderDashboard();
    updateBalanceDisplays();
    document.getElementById('transferTo').value   = '';
    document.getElementById('transferAmt').value  = '';
    document.getElementById('transferNote').value = '';
    showMsg(msg, 'success', `✅ Successfully transferred ${fmtINR(amt)} to ${toAcc}`);
  } else {
    showMsg(msg, 'error', '❌ ' + data.message);
  }
}

// ── LOAN REPAYMENT ──
async function doRepay() {
  const ref = document.getElementById('repayRef').value.trim();
  const amt = parseFloat(document.getElementById('repayAmt').value);
  const msg = document.getElementById('repayMsg');

  if (!ref)             return showMsg(msg, 'error', 'Please enter a loan reference number.');
  if (!amt || amt <= 0) return showMsg(msg, 'error', 'Please enter a valid amount.');
  if (amt > bankState.balance) return showMsg(msg, 'error', `Insufficient balance. Available: ${fmtINR(bankState.balance)}`);

  showMsg(msg, '', 'Processing…');
  const data = await bankPost('loan_repayment', { loan_ref: ref, amount: amt });

  if (data.success) {
    bankState.balance = data.new_balance;
    const loan = bankState.loans.find(l => l.ref === ref);
    if (loan) loan.outstanding = Math.max(0, loan.outstanding - amt);
    bankState.transactions.unshift({
      id: data.transaction_id, type: 'loan',
      title: 'EMI Paid — ' + (loan ? loan.type : ref), amount: -amt,
      date: new Date().toISOString().slice(0, 10), note: ref,
    });
    renderDashboard();
    updateBalanceDisplays();
    document.getElementById('repayRef').value = '';
    document.getElementById('repayAmt').value = '';
    showMsg(msg, 'success', `✅ ${fmtINR(amt)} paid toward ${ref}`);
  } else {
    showMsg(msg, 'error', '❌ ' + data.message);
  }
}

function quickPayEMI(ref, emi) {
  if (emi > bankState.balance) {
    alert(`Insufficient balance. Your balance is ${fmtINR(bankState.balance)}, EMI is ${fmtINR(emi)}.`);
    return;
  }
  if (!confirm(`Pay EMI of ${fmtINR(emi)} for ${ref}?`)) return;
  document.getElementById('repayRef').value = ref;
  document.getElementById('repayAmt').value = emi;
  switchTab('loan-repay');
  doRepay();
}

// ── EMI CALCULATOR ──
function updateCalc() {
  const P = parseFloat(document.getElementById('calcPrincipal').value);
  const r = parseFloat(document.getElementById('calcRate').value) / 12 / 100;
  const n = parseInt(document.getElementById('calcTenure').value);

  document.getElementById('calcPrincipalVal').textContent = P.toLocaleString('en-IN');
  document.getElementById('calcRateVal').textContent = parseFloat(document.getElementById('calcRate').value).toFixed(1);
  document.getElementById('calcTenureVal').textContent = n;

  const emi      = r === 0 ? P / n : P * r * Math.pow(1 + r, n) / (Math.pow(1 + r, n) - 1);
  const total    = emi * n;
  const interest = total - P;

  document.getElementById('emiResult').textContent    = fmtINR(emi);
  document.getElementById('emiPrincipal').textContent = fmtINR(P);
  document.getElementById('emiInterest').textContent  = fmtINR(interest);
  document.getElementById('emiTotal').textContent     = fmtINR(total);
  drawDonut(P, interest);
}

function drawDonut(principal, interest) {
  const canvas = document.getElementById('emiChart');
  if (!canvas) return;
  const ctx   = canvas.getContext('2d');
  const cx = 80, cy = 80, r = 60, lineW = 16;
  const total  = principal + interest;
  const pAngle = (principal / total) * 2 * Math.PI;
  ctx.clearRect(0, 0, 160, 160);
  ctx.beginPath(); ctx.arc(cx, cy, r, -Math.PI / 2, -Math.PI / 2 + 2 * Math.PI);
  ctx.strokeStyle = 'rgba(239,68,68,0.3)'; ctx.lineWidth = lineW; ctx.stroke();
  ctx.beginPath(); ctx.arc(cx, cy, r, -Math.PI / 2, -Math.PI / 2 + pAngle);
  ctx.strokeStyle = '#3b72f0'; ctx.lineWidth = lineW; ctx.lineCap = 'round'; ctx.stroke();
  ctx.fillStyle = 'rgba(255,255,255,0.5)'; ctx.font = '11px DM Sans'; ctx.textAlign = 'center';
  ctx.fillText(Math.round(principal / total * 100) + '% P', cx, cy - 5);
  ctx.fillText(Math.round(interest / total * 100) + '% I', cx, cy + 12);
}

// ── STATEMENTS ──
function renderStmtPreview() {
  const el   = document.getElementById('stmtPreview');
  if (!el) return;
  const from = document.getElementById('stmtFrom')?.value;
  const to   = document.getElementById('stmtTo')?.value;
  let list   = [...bankState.transactions];
  if (from) list = list.filter(t => t.date >= from);
  if (to)   list = list.filter(t => t.date <= to);
  if (list.length === 0) { el.innerHTML = '<div class="empty-state"><div class="empty-icon">📄</div><p>No transactions in selected period</p></div>'; return; }
  el.innerHTML = '<div class="txn-list">' + list.map(txnHTML).join('') + '</div>';
}

function generateStatement() {
  renderStmtPreview();
  const msg = document.getElementById('stmtMsg');
  showMsg(msg, 'success', '✅ Statement ready below. Use browser Print (Ctrl+P) to save as PDF.');
}

// ── HELPERS ──
function showMsg(el, type, text) {
  if (!el) return;
  el.className = 'action-msg' + (type ? ' ' + type : '');
  el.textContent = text;
  if (type === 'success' || type === 'error') {
    setTimeout(() => { el.className = 'action-msg'; el.textContent = ''; }, 5000);
  }
}

function updateBalanceDisplays() {
  const bal  = fmtINR(bankState.balance);
  const tbd  = document.getElementById('transferBalDisplay');
  const wbd  = document.getElementById('withdrawBalDisplay');
  if (tbd) tbd.textContent = bal;
  if (wbd) wbd.textContent = bal;
}

// ── INIT ──
document.addEventListener('DOMContentLoaded', async () => {
  // Pre-fill default dates
  const today    = new Date().toISOString().slice(0, 10);
  const monthAgo = new Date(Date.now() - 30 * 86400000).toISOString().slice(0, 10);
  const sf = document.getElementById('stmtFrom');
  const st = document.getElementById('stmtTo');
  if (sf) sf.value = monthAgo;
  if (st) st.value = today;

  // Show loading state
  const mainBal = document.getElementById('mainBalance');
  if (mainBal) mainBal.textContent = '…';

  // Load real data from API
  await loadAccount();
  updateCalc();
});
