// ── My Loans Backend ──

let loansData = [];
let currentStatus = 'all';
let currentSearch = '';

// Load loans from backend API
async function loadMyLoans() {
    try {
        const res = await fetch(`../Backend/api/get_my_loans.php?user_id=1&status=${encodeURIComponent(currentStatus)}&search=${encodeURIComponent(currentSearch)}`);
        const json = await res.json();
        if (json.status === 'success') {
            loansData = json.data;
            document.getElementById('summaryTotalLoans').textContent = json.summary.total_loans;
            document.getElementById('summaryTotalBorrowed').textContent = json.summary.total_borrowed;
            document.getElementById('summaryTotalRepaid').textContent = json.summary.total_repaid;
            document.getElementById('summaryTotalEmi').textContent = json.summary.emi_this_month;
            document.getElementById('summaryTotalOutstanding').textContent = json.summary.total_outstanding;

            document.getElementById('countAll').textContent = `(${json.counts.all})`;
            document.getElementById('countActive').textContent = `(${json.counts.active})`;
            document.getElementById('countPending').textContent = `(${json.counts.pending})`;
            document.getElementById('countClosed').textContent = `(${json.counts.closed})`;

            renderLoans();
        } else {
            console.error("Error fetching loans:", json.message);
        }
    } catch (err) {
        console.error(err);
    }
}

// Render loans
function renderLoans() {
    const list = document.getElementById('loanList');
    if (loansData.length === 0) {
        list.innerHTML = '<div class="no-loans" style="display:block">No loans found.</div>';
        return;
    }
    list.innerHTML = loansData.map((loan, idx) => `
        <div class="loan-card" data-status="${loan.status}" data-name="${loan.name.toLowerCase()}">
            <div style="display:flex;gap:14px;align-items:center">
                <div class="loan-type-icon ${loan.icon_class}">${loan.icon}</div>
                <div>
                    <div class="loan-name">${loan.name}</div>
                    <div class="loan-id">${loan.loan_id} · ${loan.date_info}</div>
                    <div style="margin-top:6px">
                        <div class="progress-label"><span>${loan.progress_label_1}</span><span>${loan.progress_label_2}</span></div>
                        <div class="progress-bg pb-light" style="height:6px">
                            <div class="progress-fill" style="width:${loan.progress_val};background:${loan.progress_bg}"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div>
                <div class="loan-meta-label">Amount</div>
                <div class="loan-meta-val">${loan.amount}</div>
            </div>
            <div>
                <div class="loan-meta-label">EMI</div>
                <div class="loan-meta-val">${loan.emi}</div>
            </div>
            <div>
                <div class="loan-meta-label">Rate</div>
                <div class="loan-meta-val">${loan.rate}</div>
            </div>
            <div><span class="pill pill-${loan.status === 'closed' ? 'gray' : (loan.status === 'pending' ? 'gold' : 'green')}">${loan.status === 'pending' ? 'Under Review' : loan.status.charAt(0).toUpperCase() + loan.status.slice(1)}</span></div>
            <button class="detail-btn" onclick="openDetail(${idx})">${loan.button_action}</button>
        </div>
    `).join('');
}

// Open loan detail modal
function openDetail(index) {
    const loan = loansData[index];
    const d = loan.details;
    if (!d) return;

    document.getElementById('modalTitle').textContent = d.title;
    document.getElementById('modalId').textContent = d.id;
    document.getElementById('dAmt').textContent = d.amt;
    document.getElementById('dEmi').textContent = d.emi;
    document.getElementById('dRate').textContent = d.rate;
    document.getElementById('dTenure').textContent = d.tenure;
    document.getElementById('dOutstanding').textContent = d.outstanding;
    document.getElementById('dNext').textContent = d.next;
    document.getElementById('dProgress').textContent = d.progress;

    const bar = document.getElementById('dBar');
    bar.style.width = d.progress;
    bar.style.background = d.status === 'closed' ? 'var(--muted)' : d.status === 'pending' ? 'var(--gold)' : 'var(--green)';

    const statusMap = {
        active: '<span class="pill pill-green">Active</span>',
        pending: '<span class="pill pill-gold">Under Review</span>',
        closed: '<span class="pill pill-gray">Closed</span>'
    };
    document.getElementById('modalStatus').innerHTML = statusMap[d.status];
    document.getElementById('modalActionBtn').textContent = d.action;

    const tl = d.timeline.map(t => `<div class="tl-item"><div class="tl-dot tl-dot-${t.dot}"></div><div class="tl-content"><h5>${t.title}</h5><p>${t.date} — ${t.note}</p></div></div>`).join('');
    document.getElementById('modalTimeline').innerHTML = tl;

    document.getElementById('detailModal').classList.add('open');
}

// Close detail modal
function closeDetail() {
    document.getElementById('detailModal').classList.remove('open');
}

// Filter loans
function filterLoans(status, btn) {
    document.querySelectorAll('.filter-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    currentStatus = status;
    loadMyLoans();
}

// Search loans
function searchLoansLocal(val) {
    currentSearch = val;
    loadMyLoans();
}

// Initialize event listeners
function initMyLoansEvents() {
    // Detail modal backdrop click
    const detailModal = document.getElementById('detailModal');
    if (detailModal) {
        detailModal.addEventListener('click', e => {
            if (e.target === detailModal) closeDetail();
        });
    }

    // Load initial loans
    loadMyLoans();
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMyLoansEvents);
} else {
    initMyLoansEvents();
}
