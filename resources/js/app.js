// Bank Mini Sekolah — interaksi loket (CSP-safe, tanpa inline script).
// Semua logika driven by keberadaan elemen / data-* agar lolos `default-src 'self'`.

function rupiah(n) {
    try {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(n || 0);
    } catch (e) {
        return 'Rp ' + (n || 0);
    }
}

function toInt(value) {
    var n = parseInt(value, 10);
    return isNaN(n) ? 0 : n;
}

function bindDisableOnSubmit() {
    document.querySelectorAll('form').forEach(function (form) {
        if (form.dataset.disableBound) return;
        form.dataset.disableBound = '1';
        form.addEventListener('submit', function () {
            var submit = form.querySelector('[type="submit"]');
            if (!submit || submit.disabled) return;
            if (submit.dataset.confirm === 'pending') return;
            submit.disabled = true;
            if (!submit.dataset.originalText) submit.dataset.originalText = submit.textContent;
            submit.textContent = submit.dataset.loadingText || 'Memproses...';
        });
    });
}

function bindMenuToggle() {
    var toggle = document.getElementById('menu-toggle');
    var panel = document.getElementById('menu-panel');
    if (!toggle || !panel) return;
    toggle.addEventListener('click', function () {
        var open = panel.classList.toggle('hidden') === false;
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !panel.classList.contains('hidden')) {
            panel.classList.add('hidden');
            toggle.setAttribute('aria-expanded', 'false');
            toggle.focus();
        }
    });
}

function bindLoginToggle() {
    var input = document.getElementById('secret');
    var toggle = document.getElementById('toggle-secret');
    if (!toggle || !input) return;
    toggle.addEventListener('click', function () {
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        toggle.textContent = show ? 'Sembunyi' : 'Tampil';
        toggle.setAttribute('aria-pressed', show ? 'true' : 'false');
        input.focus();
        try {
            var len = input.value.length;
            input.setSelectionRange(len, len);
        } catch (e) { /* abaikan */ }
    });
}

function bindAuthTabs() {
    var staffBtn = document.getElementById('tab-btn-staff');
    var nasabahBtn = document.getElementById('tab-btn-nasabah');
    var staffForm = document.getElementById('form-staff');
    var nasabahForm = document.getElementById('form-nasabah');
    if (!staffBtn || !nasabahBtn || !staffForm || !nasabahForm) return;

    function switchTab(tab, focus) {
        var isStaff = tab === 'staff';
        staffBtn.classList.toggle('active', isStaff);
        nasabahBtn.classList.toggle('active', !isStaff);
        staffBtn.setAttribute('aria-selected', isStaff ? 'true' : 'false');
        nasabahBtn.setAttribute('aria-selected', !isStaff ? 'true' : 'false');
        staffBtn.tabIndex = isStaff ? 0 : -1;
        nasabahBtn.tabIndex = !isStaff ? 0 : -1;
        staffForm.classList.toggle('hidden', !isStaff);
        nasabahForm.classList.toggle('hidden', isStaff);
        if (focus) (isStaff ? staffBtn : nasabahBtn).focus();
    }

    staffBtn.addEventListener('click', function () { switchTab('staff'); });
    nasabahBtn.addEventListener('click', function () { switchTab('nasabah'); });
    staffBtn.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowRight') switchTab('nasabah', true);
    });
    nasabahBtn.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowLeft') switchTab('staff', true);
    });

    if (nasabahForm.dataset.autoActive === '1') switchTab('nasabah');
}

function bindPrintButtons() {
    document.querySelectorAll('[data-print]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            setTimeout(function () { window.print(); }, 200);
        });
    });
}

function bindConfirmForms() {
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            var message = form.getAttribute('data-confirm') || 'Lanjutkan?';
            if (!window.confirm(message)) {
                e.preventDefault();
                var submit = form.querySelector('[type="submit"]');
                if (submit) {
                    submit.disabled = false;
                    if (submit.dataset.originalText) submit.textContent = submit.dataset.originalText;
                }
            }
        });
    });
}

function bindReversalForms() {
    document.querySelectorAll('form[data-reversal]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var receipt = form.getAttribute('data-reversal') || 'transaksi ini';
            var reasonInput = form.querySelector('input[name="reason"]');
            var r = window.prompt('Alasan koreksi untuk ' + receipt + ' (min 5 huruf):', 'Salah input transaksi');
            if (r === null || r.trim().length < 5) return;
            if (!window.confirm('Koreksi ' + receipt + '? Saldo akan dipulihkan.')) return;
            if (reasonInput) reasonInput.value = r.trim();
            var submit = form.querySelector('[type="submit"]');
            if (submit) {
                submit.disabled = true;
                submit.textContent = 'Memproses koreksi...';
            }
            form.submit();
        });
    });
}

// Helper hitung pecahan: tidak lagi memaksa total readonly.
// - Menampilkan subtotal rincian di [data-denomination-sum]
// - Tombol [data-denomination-apply] menyalin subtotal ke total utama
// - Total utama tetap bisa diketik manual (penting untuk selisih Rp1 / uang tak berpecahan)
function bindDenominationHelpers() {
    document.querySelectorAll('form').forEach(function (form) {
        var inputs = form.querySelectorAll('[data-denomination-input]');
        if (!inputs.length) return;
        var total = form.querySelector('[data-denomination-total]');
        var sumLabel = form.querySelector('[data-denomination-sum]');
        var applyBtn = form.querySelector('[data-denomination-apply]');
        var resetBtn = form.querySelector('[data-denomination-reset]');
        var lastSum = 0;

        function sum() {
            var s = 0;
            inputs.forEach(function (input) {
                s += toInt(input.value) * toInt(input.getAttribute('data-value'));
            });
            return s;
        }

        function refreshSum() {
            lastSum = sum();
            if (sumLabel) sumLabel.textContent = 'Subtotal rincian: ' + rupiah(lastSum);
            if (applyBtn) applyBtn.disabled = lastSum <= 0;
            return lastSum;
        }

        inputs.forEach(function (input) {
            input.addEventListener('input', function () {
                var s = refreshSum();
                // Auto-isi hanya bila total masih kosong / masih sama dengan hitungan sebelumnya
                // agar ketikan manual Teller (mis. selisih Rp1) tidak tertimpa.
                if (total && (total.value === '' || toInt(total.value) === toInt(total.dataset.lastDenom || '0'))) {
                    total.value = s > 0 ? String(s) : '';
                    total.dataset.lastDenom = String(s);
                    total.dispatchEvent(new Event('input', { bubbles: true }));
                }
            });
        });

        if (applyBtn && total) {
            applyBtn.addEventListener('click', function () {
                var s = sum();
                total.value = String(s);
                total.dataset.lastDenom = String(s);
                total.dispatchEvent(new Event('input', { bubbles: true }));
                total.focus();
            });
        }

        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                inputs.forEach(function (input) { input.value = ''; });
                refreshSum();
                if (total) {
                    total.value = '';
                    total.dataset.lastDenom = '0';
                    total.dispatchEvent(new Event('input', { bubbles: true }));
                }
            });
        }

        refreshSum();
    });
}

function bindDepositPage() {
    var slip = document.getElementById('slip_amount');
    var total = document.getElementById('physical_cash_amount');
    if (!slip || !total) return;
    var slipPreview = document.getElementById('slip-preview');
    var match = document.getElementById('cash-match');
    var balancePreview = document.getElementById('deposit-preview');
    var form = document.getElementById('deposit-form');
    var balanceBefore = form ? toInt(form.getAttribute('data-balance-before')) : 0;

    function refresh() {
        var slipVal = toInt(slip.value);
        var cashVal = toInt(total.value);
        if (slipPreview) slipPreview.textContent = 'Terbaca: ' + rupiah(slipVal);
        if (balancePreview) balancePreview.textContent = 'Saldo sesudah: ' + rupiah(balanceBefore + slipVal);
        if (match) {
            if (!slipVal || !cashVal) {
                match.textContent = 'Menunggu isian slip dan uang fisik.';
                match.className = 'text-[13px] font-semibold mt-1.5 text-stone-600';
            } else if (slipVal === cashVal) {
                match.textContent = 'Cocok: slip dan uang fisik sama.';
                match.className = 'text-[13px] font-semibold mt-1.5 text-brand-900';
            } else {
                match.textContent = 'Selisih ' + rupiah(Math.abs(slipVal - cashVal)) + '. Samakan dulu sebelum simpan.';
                match.className = 'text-[13px] font-semibold mt-1.5 text-red-800';
            }
        }
    }

    slip.addEventListener('input', refresh);
    total.addEventListener('input', refresh);
    refresh();
}

function bindWithdrawPage() {
    var amount = document.getElementById('amount');
    var preview = document.getElementById('amount-preview');
    var remain = document.getElementById('withdraw-preview');
    var form = document.getElementById('withdraw-form');
    if (!amount || !form) return;
    var balance = toInt(form.getAttribute('data-balance'));

    function refresh() {
        var val = toInt(amount.value);
        if (preview) preview.textContent = 'Terbaca: ' + rupiah(val);
        if (remain) remain.textContent = 'Sisa sesudah penarikan: ' + rupiah(balance - val);
    }

    amount.addEventListener('input', refresh);
    refresh();
}

function bindTransferPage() {
    var amount = document.getElementById('amount');
    var preview = document.getElementById('amount-preview');
    var remain = document.getElementById('transfer-preview');
    var form = document.getElementById('transfer-form');
    if (!amount || !form) return;
    var balance = toInt(form.getAttribute('data-balance'));

    function refresh() {
        var val = toInt(amount.value);
        if (preview) preview.textContent = 'Terbaca: ' + rupiah(val);
        if (remain) remain.textContent = 'Sisa sesudah: ' + rupiah(balance - val);
    }

    amount.addEventListener('input', refresh);
    refresh();
}

function bindClosingPage() {
    var form = document.getElementById('closing-form');
    var total = document.getElementById('physical_cash');
    var match = document.getElementById('closing-match');
    var submit = document.getElementById('closing-submit');
    var noteWrap = document.getElementById('difference-note-wrap');
    var noteInput = document.getElementById('difference_note');
    if (!form || !total) return;
    var systemBalance = toInt(form.getAttribute('data-system-balance'));

    function refresh() {
        var physical = total.value === '' ? null : toInt(total.value);
        if (physical === null || total.value === '') {
            if (match) {
                match.textContent = 'Ketik total uang laci. Rincian pecahan opsional di bawah.';
                match.className = 'text-[13px] font-semibold text-stone-600 mt-1.5';
            }
            if (noteWrap) noteWrap.classList.add('hidden');
            if (noteInput) noteInput.required = false;
            if (submit) submit.textContent = 'Buat laporan harian';
            return;
        }
        var diff = physical - systemBalance;
        if (diff === 0) {
            if (match) {
                match.textContent = 'Cocok dengan saldo sistem ' + rupiah(systemBalance) + '. Siap buat laporan.';
                match.className = 'text-[13px] font-semibold text-brand-900 mt-1.5';
            }
            if (noteWrap) noteWrap.classList.add('hidden');
            if (noteInput) noteInput.required = false;
            if (submit) submit.textContent = 'Buat laporan harian';
        } else {
            var kurang = diff < 0;
            if (match) {
                match.textContent = (kurang ? 'Kurang ' : 'Lebih ') + rupiah(Math.abs(diff)) +
                    ' dari sistem (' + rupiah(systemBalance) + '). Laporan tetap bisa disimpan dengan berita acara.';
                match.className = 'text-[13px] font-semibold text-red-800 mt-1.5';
            }
            if (noteWrap) noteWrap.classList.remove('hidden');
            if (noteInput) noteInput.required = true;
            if (submit) submit.textContent = 'Simpan dengan selisih ' + rupiah(Math.abs(diff));
        }
    }

    total.addEventListener('input', refresh);
    refresh();
}

document.addEventListener('DOMContentLoaded', function () {
    bindMenuToggle();
    bindLoginToggle();
    bindAuthTabs();
    bindPrintButtons();
    bindConfirmForms();
    bindReversalForms();
    bindDenominationHelpers();
    bindDepositPage();
    bindWithdrawPage();
    bindTransferPage();
    bindClosingPage();
    bindDisableOnSubmit();
});
