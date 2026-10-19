// Bank Mini Sekolah: interaksi loket (CSP-safe, tanpa inline script).
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
            // innerHTML, bukan textContent: tombol yang isinya elemen (mis. penghitung jumlah
            // baris terpilih) harus kembali utuh saat pengguna membatalkan dialog konfirmasi.
            if (!submit.dataset.originalHtml) submit.dataset.originalHtml = submit.innerHTML;
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

// Dropdown grup navigasi: klik/Enter untuk buka, Escape menutup dan mengembalikan fokus,
// klik di luar menutup. Tanpa hover-only karena layar sentuh tidak punya hover.
function bindNavGroups() {
    var groups = Array.prototype.slice.call(document.querySelectorAll('[data-nav-group]'));
    if (!groups.length) return;

    function parts(group) {
        return {
            button: group.querySelector('[data-nav-toggle]'),
            panel: group.querySelector('[data-nav-panel]')
        };
    }

    function close(group) {
        var p = parts(group);
        if (!p.button || !p.panel) return;
        p.panel.classList.add('hidden');
        p.button.setAttribute('aria-expanded', 'false');
    }

    function closeAll() {
        groups.forEach(function (group) {
            var p = parts(group);
            if (p.button && p.button.getAttribute('aria-expanded') === 'true') close(group);
        });
    }

    function open(group) {
        var p = parts(group);
        if (!p.button || !p.panel) return;
        p.panel.classList.remove('hidden');
        p.button.setAttribute('aria-expanded', 'true');
    }

    function items(panel) {
        return Array.prototype.slice.call(panel.querySelectorAll('a'));
    }

    groups.forEach(function (group) {
        var p = parts(group);
        if (!p.button || !p.panel) return;

        p.button.addEventListener('click', function () {
            var isOpen = p.button.getAttribute('aria-expanded') === 'true';
            closeAll();
            if (!isOpen) open(group);
        });

        p.button.addEventListener('keydown', function (e) {
            if (e.key !== 'ArrowDown') return;
            e.preventDefault();
            closeAll();
            open(group);
            var first = items(p.panel)[0];
            if (first) first.focus();
        });

        p.panel.addEventListener('keydown', function (e) {
            if (e.key !== 'ArrowDown' && e.key !== 'ArrowUp') return;
            var list = items(p.panel);
            var i = list.indexOf(document.activeElement);
            if (i === -1) return;
            e.preventDefault();
            var next = e.key === 'ArrowDown' ? i + 1 : i - 1;
            if (next < 0) {
                p.button.focus();
                return;
            }
            if (list[next]) list[next].focus();
        });
    });

    document.addEventListener('click', function (e) {
        var inside = groups.some(function (group) { return group.contains(e.target); });
        if (!inside) closeAll();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        var openGroup = groups.filter(function (group) {
            var p = parts(group);
            return p.button && p.button.getAttribute('aria-expanded') === 'true';
        })[0];
        if (!openGroup) return;
        close(openGroup);
        var p = parts(openGroup);
        if (p.button) p.button.focus();
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
                    if (submit.dataset.originalHtml) submit.innerHTML = submit.dataset.originalHtml;
                    // Tabel dengan pemilihan baris menghitung ulang keadaannya sendiri supaya
                    // tombol tidak aktif lagi saat tidak ada baris yang dicentang.
                    form.dispatchEvent(new CustomEvent('confirm-cancelled'));
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

function bindDepositPage() {
    var slip = document.getElementById('slip_amount');
    if (!slip) return;
    var slipPreview = document.getElementById('slip-preview');
    var balancePreview = document.getElementById('deposit-preview');
    var form = document.getElementById('deposit-form');
    var balanceBefore = form ? toInt(form.getAttribute('data-balance-before')) : 0;

    function refresh() {
        var slipVal = toInt(slip.value);
        if (slipPreview) slipPreview.textContent = 'Terbaca: ' + rupiah(slipVal);
        if (balancePreview) balancePreview.textContent = 'Saldo sesudah: ' + rupiah(balanceBefore + slipVal);
    }

    slip.addEventListener('input', refresh);
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

function bindActivationPage() {
    var form = document.getElementById('activation-form');
    if (!form) return;
    var amount = document.getElementById('amount');
    var preview = document.getElementById('activation-preview');
    if (!amount || !preview) return;

    function refresh() {
        preview.textContent = 'Terbaca: ' + rupiah(toInt(amount.value));
    }

    amount.addEventListener('input', refresh);
    refresh();
}

// Pemilihan baris pada tabel panjang (mis. pindah rombel): kotak "pilih semua" dan
// penghitung pada tombol supaya jumlah penabung yang ikut terlihat sebelum dikirim.
function bindRowSelection() {
    var items = Array.prototype.slice.call(document.querySelectorAll('[data-select-item]'));
    if (!items.length) return;

    var selectAll = document.querySelector('[data-select-all]');
    var submit = document.querySelector('[data-select-submit]');

    function refresh() {
        var checked = items.filter(function (item) { return item.checked; }).length;

        // Dicari ulang tiap kali: tombol bisa dipulihkan dari innerHTML setelah dialog
        // konfirmasi dibatalkan, sehingga elemen penghitung yang lama sudah lepas dari dokumen.
        var counter = document.querySelector('[data-select-count]');

        if (counter) counter.textContent = checked;
        if (submit) submit.disabled = checked === 0;

        if (selectAll) {
            selectAll.checked = checked === items.length;
            selectAll.indeterminate = checked > 0 && checked < items.length;
        }
    }

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            items.forEach(function (item) { item.checked = selectAll.checked; });
            refresh();
        });
    }

    if (submit && submit.form) submit.form.addEventListener('confirm-cancelled', refresh);

    items.forEach(function (item) { item.addEventListener('change', refresh); });
    refresh();
}

document.addEventListener('DOMContentLoaded', function () {
    bindMenuToggle();
    bindNavGroups();
    bindLoginToggle();
    bindAuthTabs();
    bindPrintButtons();
    // bindDisableOnSubmit harus lebih dulu dari bindConfirmForms: tombol dinonaktifkan
    // sebelum dialog muncul, lalu bindConfirmForms mengaktifkan lagi saat pengguna batal.
    bindDisableOnSubmit();
    bindConfirmForms();
    bindReversalForms();
    bindDepositPage();
    bindWithdrawPage();
    bindTransferPage();
    bindActivationPage();
    bindRowSelection();
});
