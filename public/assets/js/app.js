/* ============================================================
   BizCore ERP — Main JavaScript
   Dark Mode | Sidebar | CSRF AJAX | Notifications | Utilities
   ============================================================ */

'use strict';

// ── CSRF Token ──────────────────────────────────────────────
const CSRF = {
    token: () => document.querySelector('meta[name="csrf-token"]')?.content ?? '',
    headers: () => ({ 'X-CSRF-TOKEN': CSRF.token(), 'X-Requested-With': 'XMLHttpRequest' }),
};

// ── Dark Mode ───────────────────────────────────────────────
const DarkMode = {
    KEY: 'dark_mode',

    init() {
        const stored = this.getStored();
        if (stored) this.apply(stored === '1');

        const btn = document.getElementById('darkModeToggle');
        if (btn) {
            btn.addEventListener('click', () => this.toggle());
            this.updateIcon(btn, this.isEnabled());
        }
    },

    getStored() {
        return document.cookie.split('; ')
            .find(r => r.startsWith(this.KEY + '='))
            ?.split('=')[1] ?? null;
    },

    isEnabled() {
        return document.body.classList.contains('dark-mode');
    },

    apply(enabled) {
        document.body.classList.toggle('dark-mode', enabled);
        const btn = document.getElementById('darkModeToggle');
        if (btn) this.updateIcon(btn, enabled);
    },

    toggle() {
        const enabled = !this.isEnabled();
        this.apply(enabled);
        document.cookie = `${this.KEY}=${enabled ? 1 : 0}; path=/; max-age=31536000; SameSite=Lax`;
    },

    updateIcon(btn, enabled) {
        const icon = btn.querySelector('i');
        if (icon) {
            icon.className = enabled ? 'fas fa-sun' : 'fas fa-moon';
        }
        btn.title = enabled ? 'Switch to Light Mode' : 'Switch to Dark Mode';
    },
};

// ── Sidebar ─────────────────────────────────────────────────
const Sidebar = {
    sidebar: null,
    mainContent: null,

    init() {
        this.sidebar     = document.getElementById('sidebar');
        this.mainContent = document.querySelector('.main-content');

        const collapsed  = localStorage.getItem('sidebar_collapsed') === '1';
        if (collapsed && this.sidebar) this.sidebar.classList.add('collapsed');

        document.getElementById('sidebarToggle')?.addEventListener('click',       () => this.toggleDesktop());
        document.getElementById('sidebarToggleMobile')?.addEventListener('click', () => this.toggleMobile());

        // Auto-expand submenu for current active link
        const activeLink = this.sidebar?.querySelector('.submenu .nav-link.active');
        if (activeLink) {
            activeLink.closest('.collapse')?.classList.add('show');
        }

        // Close mobile sidebar when clicking outside
        document.addEventListener('click', e => {
            if (window.innerWidth <= 768 &&
                this.sidebar?.classList.contains('open') &&
                !this.sidebar.contains(e.target) &&
                !document.getElementById('sidebarToggleMobile')?.contains(e.target)) {
                this.sidebar.classList.remove('open');
            }
        });
    },

    toggleDesktop() {
        if (!this.sidebar) return;
        const collapsed = this.sidebar.classList.toggle('collapsed');
        localStorage.setItem('sidebar_collapsed', collapsed ? '1' : '0');
    },

    toggleMobile() {
        this.sidebar?.classList.toggle('open');
    },
};

// ── Delete Confirm Modal ────────────────────────────────────
function confirmDelete(url, label = 'this item') {
    const modal = document.getElementById('deleteModal');
    const form  = document.getElementById('deleteForm');
    if (!modal || !form) return;

    form.action = url;
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
}

// ── Toast Notifications ─────────────────────────────────────
const Toast = {
    show(message, type = 'info', duration = 4000) {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const colors = { success: '#16a34a', danger: '#dc2626', warning: '#d97706', info: '#0891b2' };
        const icons  = { success: 'check-circle', danger: 'exclamation-circle', warning: 'exclamation-triangle', info: 'info-circle' };

        const id   = 'toast-' + Date.now();
        const html = `
            <div id="${id}" class="toast show align-items-center text-white border-0"
                 role="alert" style="background:${colors[type] ?? colors.info}; border-radius:10px; min-width:280px">
                <div class="d-flex">
                    <div class="toast-body d-flex align-items-center gap-2">
                        <i class="fas fa-${icons[type] ?? 'info-circle'}"></i>
                        <span>${String(message).replace(/</g, '&lt;')}</span>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto me-2 my-auto"
                            data-bs-dismiss="toast"></button>
                </div>
            </div>`;

        container.insertAdjacentHTML('beforeend', html);

        const el = document.getElementById(id);
        if (duration > 0) {
            setTimeout(() => {
                el?.remove();
            }, duration);
        }
    },
};

// ── AJAX Helpers ────────────────────────────────────────────
const Api = {
    async get(url, params = {}) {
        const qs  = new URLSearchParams(params).toString();
        const res = await fetch(qs ? `${url}?${qs}` : url, {
            headers: { ...CSRF.headers(), 'Accept': 'application/json' },
        });
        return res.json();
    },

    async post(url, data = {}) {
        const res = await fetch(url, {
            method:  'POST',
            headers: { ...CSRF.headers(), 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body:    JSON.stringify(data),
        });
        return res.json();
    },

    async delete(url) {
        const res = await fetch(url, {
            method:  'DELETE',
            headers: { ...CSRF.headers(), 'Accept': 'application/json' },
        });
        return res.json();
    },
};

// ── Product Search (AJAX) ────────────────────────────────────
function initProductSearch(inputEl, resultsEl, onSelect) {
    if (!inputEl || !resultsEl) return;

    let debounce;
    inputEl.addEventListener('input', () => {
        clearTimeout(debounce);
        const q = inputEl.value.trim();
        if (q.length < 2) { resultsEl.innerHTML = ''; resultsEl.style.display = 'none'; return; }

        debounce = setTimeout(async () => {
            try {
                const res = await Api.get('/api/v1/products', { search: q, per_page: 10 });
                if (res.success && res.data?.length) {
                    resultsEl.innerHTML = res.data.map(p => `
                        <button type="button" class="list-group-item list-group-item-action py-2 px-3 border-0"
                                data-id="${p.id}" data-sku="${escHtml(p.sku)}"
                                data-name="${escHtml(p.name)}" data-price="${p.selling_price}"
                                data-vat="${p.vat_rate}" data-stock="${p.current_stock ?? 0}">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold small">${escHtml(p.name)}</div>
                                    <code class="small text-muted">${escHtml(p.sku)}</code>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold text-primary small">৳${(+p.selling_price).toFixed(2)}</div>
                                    <div class="text-muted" style="font-size:.7rem">Stock: ${p.current_stock ?? 0}</div>
                                </div>
                            </div>
                        </button>`).join('');

                    resultsEl.style.display = 'block';

                    resultsEl.querySelectorAll('[data-id]').forEach(el => {
                        el.addEventListener('click', () => {
                            onSelect(el.dataset);
                            inputEl.value = '';
                            resultsEl.innerHTML = '';
                            resultsEl.style.display = 'none';
                        });
                    });
                } else {
                    resultsEl.innerHTML = '<div class="list-group-item text-muted small">No products found</div>';
                    resultsEl.style.display = 'block';
                }
            } catch (e) {
                console.error('Product search error', e);
            }
        }, 300);
    });

    document.addEventListener('click', e => {
        if (!resultsEl.contains(e.target) && e.target !== inputEl) {
            resultsEl.innerHTML = '';
            resultsEl.style.display = 'none';
        }
    });
}

// ── Notification Polling ─────────────────────────────────────
const Notifications = {
    pollInterval: 60000,

    async fetch() {
        try {
            const res = await Api.get('/api/v1/notifications', { unread: 1, per_page: 10 });
            if (res.success) this.render(res.data ?? []);
        } catch { /* silent */ }
    },

    render(notifications) {
        const list  = document.getElementById('notificationList');
        const badge = document.getElementById('notificationCount');
        if (!list) return;

        if (notifications.length === 0) {
            list.innerHTML = '<div class="text-center text-muted p-3 small">No new notifications</div>';
            if (badge) badge.style.display = 'none';
            return;
        }

        if (badge) {
            badge.textContent = notifications.length;
            badge.style.display = 'flex';
        }

        list.innerHTML = notifications.map(n => `
            <div class="notification-item ${n.is_read ? '' : 'unread'}">
                <div class="d-flex gap-2 align-items-start">
                    <i class="fas fa-bell small text-primary mt-1"></i>
                    <div>
                        <div class="small fw-semibold">${escHtml(n.title ?? '')}</div>
                        <div class="text-muted" style="font-size:.75rem">${escHtml(n.message ?? '')}</div>
                    </div>
                </div>
            </div>`).join('');
    },

    init() {
        if (!document.getElementById('notificationBtn')) return;
        this.fetch();
        setInterval(() => this.fetch(), this.pollInterval);
    },
};

async function markAllRead(e) {
    e.preventDefault();
    await Api.post('/api/v1/notifications/read-all');
    Notifications.fetch();
}

// ── DataTables Default Config ────────────────────────────────
function initDataTable(selector, opts = {}) {
    const el = document.querySelector(selector);
    if (!el || !$.fn.DataTable) return;

    $(el).DataTable({
        pageLength: 25,
        responsive: true,
        order:      [],
        language:   {
            search:     '',
            searchPlaceholder: 'Search...',
            lengthMenu: '_MENU_ per page',
            info:       'Showing _START_–_END_ of _TOTAL_',
        },
        dom: `<'row'<'col-sm-6'l><'col-sm-6'f>><'row'<'col-12'tr>><'row'<'col-sm-5'i><'col-sm-7'p>>`,
        ...opts,
    });
}

// ── Utilities ────────────────────────────────────────────────
function escHtml(str) {
    return String(str ?? '').replace(/[&<>"']/g, c => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[c]));
}

function formatCurrency(amount, symbol = '৳') {
    return `${symbol}${parseFloat(amount || 0).toLocaleString('en-BD', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function debounce(fn, wait = 300) {
    let t;
    return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), wait); };
}

// ── Invoice Builder ──────────────────────────────────────────
const InvoiceBuilder = {
    items:   [],
    vatRate: 15,

    init(vatRate = 15) {
        this.vatRate = vatRate;
        this.render();

        const searchInput   = document.getElementById('productSearch');
        const searchResults = document.getElementById('productSearchResults');

        initProductSearch(searchInput, searchResults, dataset => {
            this.addItem({
                id:    +dataset.id,
                sku:   dataset.sku,
                name:  dataset.name,
                price: +dataset.price,
                vat:   +dataset.vat,
                stock: +dataset.stock,
            });
        });
    },

    addItem(product) {
        const existing = this.items.find(i => i.id === product.id);
        if (existing) {
            existing.qty++;
        } else {
            this.items.push({ ...product, qty: 1, discount: 0 });
        }
        this.render();
    },

    remove(idx) {
        this.items.splice(idx, 1);
        this.render();
    },

    update(idx, field, value) {
        this.items[idx][field] = +value;
        this.render();
    },

    totals() {
        let subtotal = 0, vatTotal = 0, discTotal = 0;
        for (const item of this.items) {
            const lineSubtotal = item.qty * item.price;
            const lineDiscount = item.discount || 0;
            const lineVat      = (lineSubtotal - lineDiscount) * (item.vat / 100);
            subtotal += lineSubtotal;
            vatTotal += lineVat;
            discTotal += lineDiscount;
        }
        return {
            subtotal,
            discount: discTotal,
            vat:      vatTotal,
            total:    subtotal - discTotal + vatTotal,
        };
    },

    render() {
        const tbody   = document.getElementById('invoiceItems');
        const tfoot   = document.getElementById('invoiceTotals');
        const hidden  = document.getElementById('itemsJson');
        if (!tbody) return;

        if (this.items.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4">
                <i class="fas fa-search me-1"></i>Search and add products above
            </td></tr>`;
        } else {
            tbody.innerHTML = this.items.map((item, idx) => {
                const lineTotal = (item.qty * item.price) - (item.discount || 0);
                const lineVat   = lineTotal * (item.vat / 100);
                return `<tr>
                    <td>${escHtml(item.name)}<br><code class="small">${escHtml(item.sku)}</code></td>
                    <td class="text-end">৳${item.price.toFixed(2)}</td>
                    <td style="width:100px">
                        <input type="number" class="form-control form-control-sm text-end"
                               min="1" max="${item.stock}" value="${item.qty}"
                               oninput="InvoiceBuilder.update(${idx},'qty',this.value)">
                    </td>
                    <td style="width:110px">
                        <input type="number" class="form-control form-control-sm text-end"
                               min="0" value="${item.discount || 0}"
                               oninput="InvoiceBuilder.update(${idx},'discount',this.value)">
                    </td>
                    <td class="text-end">${item.vat}%</td>
                    <td class="text-end fw-semibold">৳${(lineTotal + lineVat).toFixed(2)}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger"
                                onclick="InvoiceBuilder.remove(${idx})">
                            <i class="fas fa-times"></i>
                        </button>
                    </td>
                </tr>`;
            }).join('');
        }

        const t = this.totals();
        if (tfoot) {
            tfoot.innerHTML = `
                <tr><td colspan="5" class="text-end text-muted">Subtotal:</td>
                    <td class="text-end fw-semibold">৳${t.subtotal.toFixed(2)}</td><td></td></tr>
                <tr><td colspan="5" class="text-end text-muted">Discount:</td>
                    <td class="text-end text-danger">-৳${t.discount.toFixed(2)}</td><td></td></tr>
                <tr><td colspan="5" class="text-end text-muted">VAT (${this.vatRate}%):</td>
                    <td class="text-end">৳${t.vat.toFixed(2)}</td><td></td></tr>
                <tr class="table-primary">
                    <td colspan="5" class="text-end fw-bold">Total:</td>
                    <td class="text-end fw-bold fs-5">৳${t.total.toFixed(2)}</td><td></td></tr>`;
        }

        if (hidden) {
            hidden.value = JSON.stringify(this.items.map(i => ({
                product_id: i.id,
                quantity:   i.qty,
                unit_price: i.price,
                discount:   i.discount || 0,
                vat_rate:   i.vat,
            })));
        }
    },
};

// ── Init ─────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    DarkMode.init();
    Sidebar.init();
    Notifications.init();

    // Auto-dismiss alerts after 5s
    document.querySelectorAll('.alert.alert-dismissible').forEach(el => {
        setTimeout(() => bootstrap.Alert.getOrCreateInstance(el)?.close(), 5000);
    });

    // Mark active submenu
    const path = window.location.pathname;
    document.querySelectorAll('.sidebar-nav .nav-link').forEach(link => {
        if (link.getAttribute('href') && path.startsWith(link.getAttribute('href')) && link.getAttribute('href') !== '/') {
            link.classList.add('active');
            link.closest('.collapse')?.classList.add('show');
        }
    });
});
