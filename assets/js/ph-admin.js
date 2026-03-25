/**
 * Product Haven — Admin JS
 * Dashboard: statistics, graph, timeline, modal, customer card, export, tabs, reports
 */
(function () {
    'use strict';

    const { ajax_url, nonce, currency, locale } = window.ph_admin || {};
    const i18n = window.ph_admin?.i18n || {};

/*
       State
*/
    let currentDays   = parseInt(document.querySelector('.ph-period-btn.is-active')?.dataset.days || 30);
    let currentPage   = 1;
    let currentStatus = 'any';
    let currentSearch = '';
    let chart         = null;
    let searchTimer   = null;
    let visibleDatasets = { revenue: true, orders: false };
    let topProductsLimit = 5;

    // Tab state — keep track of which reports have already been loaded
    const tabLoaded = { dashboard: false, revenue: false, categories: false, coupons: false, daily: false, customers: false, stock: false, products: false, sequential: false };

    // Cache: order objects stored during timeline rendering → no extra AJAX for modal
    const orderCache = new Map();

/*
       AJAX helper
*/
    function ajax(action, data = {}) {
        return fetch(ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            body: new URLSearchParams({ action, nonce, ...data }),
        }).then(r => r.json()).catch(() => ({ success: false, data: { message: 'Serverantwoord onleesbaar.' } }));
    }

/*
       Time helper — translates Unix timestamp to readable string
*/
    function timeAgo(ts) {
        if (!ts) return '';
        const diff = Math.floor(Date.now() / 1000) - ts;
        if (diff < 60)                       return i18n.time_just_now  || 'Just now';
        if (diff < 3600)  { const m = Math.floor(diff / 60);    return m + ' ' + (i18n.time_min_ago   || 'min ago'); }
        if (diff < 7200)                     return '1 ' + (i18n.time_hour_ago  || 'hour ago');
        if (diff < 86400) { const h = Math.floor(diff / 3600);  return h + ' ' + (i18n.time_hours_ago || 'hours ago'); }
        if (diff < 172800)                   return i18n.time_yesterday || 'Yesterday';
        if (diff < 604800) { const d = Math.floor(diff / 86400);  return d + ' ' + (i18n.time_days_ago  || 'd ago'); }
        { const w = Math.floor(diff / 604800); return w + ' ' + (i18n.time_weeks_ago || (w === 1 ? 'week ago' : 'weeks ago')); }
    }

/*
       TABS
*/
    function initTabs() {
        document.querySelectorAll('.ph-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                const target = tab.dataset.tab;
                document.querySelectorAll('.ph-tab').forEach(t => t.classList.remove('is-active'));
                document.querySelectorAll('.ph-tab-panel').forEach(p => p.classList.remove('is-active'));
                tab.classList.add('is-active');
                const panel = document.getElementById('ph-panel-' + target);
                if (panel) panel.classList.add('is-active');

                // Period selector hide on tabs that don't use a period
                const periodSel = document.getElementById('ph-period-selector');
                if (periodSel) {
                    periodSel.style.display = (target === 'settings' || target === 'products' || target === 'sequential') ? 'none' : '';
                }

                // Lazy load per tab
                if (target === 'revenue'    && !tabLoaded.revenue)    { loadRevenueReport(); tabLoaded.revenue = true; }
                if (target === 'categories' && !tabLoaded.categories)  { loadCategoriesReport(); tabLoaded.categories = true; }
                if (target === 'coupons'    && !tabLoaded.coupons)    { loadCouponsReport(); tabLoaded.coupons = true; }
                if (target === 'daily'      && !tabLoaded.daily)      { loadDailyReport(); tabLoaded.daily = true; }
                if (target === 'customers'  && !tabLoaded.customers)  { loadCustomersReport(); tabLoaded.customers = true; }
                if (target === 'stock'      && !tabLoaded.stock)      { loadStock(); tabLoaded.stock = true; }
                if (target === 'products'   && !tabLoaded.products)   { opQpInit(); tabLoaded.products = true; }
            });
        });
    }

/*
       Load Statistics
*/
    function loadStats(force = false) {
        ajax('ph_get_stats', { days: currentDays, ...(force ? { force_refresh: 1 } : {}) }).then(resp => {
            if (!resp.success) return;
            const d = resp.data;

            setStatCard('revenue',       formatCurrency(d.revenue));
            setStatCard('orders',        d.orders);
            setStatCard('avg_order',     formatCurrency(d.avg_order));
            setStatCard('new_customers', d.new_customers);

            const refCard = document.querySelector('[data-stat="revenue"] .ph-stat-sub');
            if (refCard && d.refunds > 0) refCard.textContent = (i18n.refunded_prefix || 'Refunded: ') + formatCurrency(d.refunds);
        });

        // Top products separately loaded with current limit
        loadTopProducts(force);
        // Low stock card
        loadLowStock();
    }

/*
       LOW STOCK CARD
*/
    let lowStockThreshold = 20;
    let lowStockTimer = null;

    function loadLowStock() {
        ajax('ph_get_low_stock', { threshold: lowStockThreshold }).then(resp => {
            if (!resp.success) return;
            renderLowStock(resp.data);
        });
    }

    function renderLowStock(products) {
        const body = document.getElementById('ph-low-stock-body');
        if (!body) return;

        // Update card header counter
        const card = document.getElementById('ph-low-stock-card');
        const heading = card?.querySelector('h2');
        if (heading) {
            // Remove old counter badge if it exists
            heading.querySelector('.ph-low-stock-count')?.remove();
            if (products.length > 0) {
                const badge = document.createElement('span');
                badge.className = 'ph-low-stock-count';
                badge.textContent = products.length;
                heading.appendChild(badge);
            }
        }

        if (!products.length) {
            body.innerHTML = '<p style="padding:16px 20px;color:#10B981;font-size:13px;margin:0;">' + (i18n.all_stock_ok || '✓ All products have sufficient stock.') + '</p>';
            return;
        }

        body.innerHTML = products.map(p => {
            const statusCls = p.status === 'out' ? 'ph-ls-status--out' : 'ph-ls-status--low';
            const statusTxt = p.status === 'out' ? (i18n.out_of_stock_short || 'Out!') : `${p.stock}×`;
            return `
            <div class="ph-ls-row" data-id="${p.id}">
                <img class="ph-ls-img" src="${esc(p.image)}" alt="" width="36" height="36">
                <span class="ph-ls-name" title="${esc(p.name)}">${esc(p.name)}</span>
                <span class="ph-ls-sku">${esc(p.sku)}</span>
                <span class="ph-ls-stock ${statusCls}">${statusTxt}</span>
                <button class="ph-ls-edit-btn" data-id="${p.id}" data-name="${esc(p.name)}" data-stock="${p.stock}" title="${i18n.edit_stock_title || 'Edit stock'}">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                </button>
            </div>`;
        }).join('');

        // Edit buttons
        body.querySelectorAll('.ph-ls-edit-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                openStockEditModal(
                    parseInt(btn.dataset.id),
                    btn.dataset.name,
                    parseInt(btn.dataset.stock)
                );
            });
        });
    }

/* 
       STOCK EDIT MODAL
*/
    function openStockEditModal(productId, name, currentStock) {
        document.getElementById('ph-stock-edit-product-id').value = productId;
        document.getElementById('ph-stock-edit-title').textContent = name;
        document.getElementById('ph-stock-edit-qty').value = currentStock;
        document.getElementById('ph-stock-edit-reason').value = '';
        document.getElementById('ph-stock-edit-backdrop').hidden = false;
        setTimeout(() => document.getElementById('ph-stock-edit-qty').focus(), 50);
    }

    function closeStockEditModal() {
        document.getElementById('ph-stock-edit-backdrop').hidden = true;
    }

    function saveStockEdit() {
        const productId = parseInt(document.getElementById('ph-stock-edit-product-id').value);
        const newStock  = parseInt(document.getElementById('ph-stock-edit-qty').value);
        const reason    = document.getElementById('ph-stock-edit-reason').value.trim()
                          || (i18n.stock_edit_default_reason || 'Updated via Product Haven');

        if (isNaN(newStock) || newStock < 0) {
            alert(i18n.stock_edit_invalid_qty || 'Please enter a valid number (0 or higher).');
            return;
        }

        const saveBtn = document.getElementById('ph-stock-edit-save');
        saveBtn.disabled = true;
        saveBtn.textContent = i18n.saving || 'Saving…';

        ajax('ph_update_stock', { product_id: productId, new_stock: newStock, reason }).then(resp => {
            saveBtn.disabled = false;
            saveBtn.textContent = i18n.order_edit_save || 'Save';
            if (!resp.success) { alert((i18n.error_prefix || 'Error: ') + (resp.data?.message || (i18n.unknown_error_short || 'unknown'))); return; }
            closeStockEditModal();
            loadLowStock(); // card refresh
        });
    }

    // Modal events
    document.getElementById('ph-stock-edit-close')?.addEventListener('click', closeStockEditModal);
    document.getElementById('ph-stock-edit-cancel')?.addEventListener('click', closeStockEditModal);
    document.getElementById('ph-stock-edit-save')?.addEventListener('click', saveStockEdit);
    document.getElementById('ph-stock-edit-backdrop')?.addEventListener('click', e => {
        if (e.target === e.currentTarget) closeStockEditModal();
    });
    document.getElementById('ph-stock-edit-qty')?.addEventListener('keydown', e => {
        if (e.key === 'Enter') saveStockEdit();
    });

    // Threshold input
    document.getElementById('ph-low-stock-threshold')?.addEventListener('change', e => {
        lowStockThreshold = Math.max(1, parseInt(e.target.value) || 20);
        loadLowStock();
    });

/*
       TOP PRODUCTS
*/
    function loadTopProducts(force = false) {
        ajax('ph_get_top_products', { days: currentDays, limit: topProductsLimit, ...(force ? { force_refresh: 1 } : {}) }).then(resp => {
            if (!resp.success) return;
            renderTopProducts(resp.data);
        });
    }

    function renderTopProducts(products) {
        const list = document.getElementById('ph-top-products');
        if (!list) return;
        if (!products.length) {
            list.innerHTML = '<li style="padding:12px 20px;color:#94A3B8;font-size:13px">' + (i18n.no_data || 'No data available.') + '</li>';
            return;
        }
        list.innerHTML = products.map((p, i) => {
            // Stock badge: only show if stock is managed and ≤ 20
            let stockBadge = '';
            if (p.stock !== null && p.stock !== undefined && p.stock_low) {
                const cls  = p.stock_out ? 'ph-stock-badge--out' : 'ph-stock-badge--low';
                const label = p.stock_out ? (i18n.out_of_stock_short || 'Out!') : `${p.stock} ${i18n.remaining || 'remaining'}`;
                const link  = p.edit_url
                    ? `<a href="${esc(p.edit_url)}" class="ph-stock-badge ${cls}" title="${i18n.edit_stock_title || 'Edit stock'}">${label}</a>`
                    : `<span class="ph-stock-badge ${cls}">${label}</span>`;
                stockBadge = link;
            }
            return `
            <li class="ph-top-item">
                <span class="ph-top-rank">${i + 1}</span>
                <span class="ph-top-name">${esc(p.name)}${stockBadge}</span>
                <span class="ph-top-qty">${p.qty}×</span>
                <span class="ph-top-rev">${currency}${Number(p.revenue).toLocaleString(locale, { minimumFractionDigits: 2 })}</span>
            </li>`;
        }).join('');

        // Update load-more button visibility
        const loadMoreBtn = document.getElementById('ph-top-load-more');
        if (loadMoreBtn) {
            loadMoreBtn.dataset.limit = topProductsLimit;
        }
    }

    function setStatCard(key, value) {
        const el = document.querySelector(`[data-stat="${key}"] .ph-stat-value`);
        if (el) {
            el.innerHTML = '';
            el.textContent = value;
        }
    }

    function formatCurrency(val) {
        return currency + Number(val).toLocaleString(locale, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

/*
       Chart
*/
    function loadChart(force = false) {
        ajax('ph_get_chart_data', { days: currentDays, type: 'both', ...(force ? { force_refresh: 1 } : {}) }).then(resp => {
            if (!resp.success) return;
            renderChart(resp.data);
        });
    }

    function renderChart(data) {
        const canvas = document.getElementById('ph-main-chart');
        if (!canvas) return;

        const datasets = [];

        if (visibleDatasets.revenue) {
            datasets.push({
                label:           i18n.chart_revenue || 'Revenue',
                data:            data.revenue,
                borderColor:     '#10B981',
                backgroundColor: 'rgba(16,185,129,.08)',
                fill:            true,
                tension:         0.4,
                pointRadius:     3,
                yAxisID:         'yRevenue',
            });
        }

        if (visibleDatasets.orders) {
            datasets.push({
                label:           i18n.chart_orders || 'Orders',
                data:            data.orders,
                borderColor:     '#6366F1',
                backgroundColor: 'rgba(99,102,241,.08)',
                fill:            true,
                tension:         0.4,
                pointRadius:     3,
                yAxisID:         'yOrders',
            });
        }

        const scales = {};
        if (visibleDatasets.revenue) {
            scales.yRevenue = {
                type: 'linear', position: 'left',
                grid: { color: '#F1F5F9' },
                ticks: {
                    callback: v => currency + v.toLocaleString(locale),
                    font: { size: 11 },
                    color: '#94A3B8',
                },
            };
        }
        if (visibleDatasets.orders) {
            scales.yOrders = {
                type: 'linear', position: visibleDatasets.revenue ? 'right' : 'left',
                grid: { drawOnChartArea: !visibleDatasets.revenue, color: '#F1F5F9' },
                ticks: { font: { size: 11 }, color: '#6366F1' },
            };
        }

        if (chart) chart.destroy();

        chart = new Chart(canvas, {
            type: 'line',
            data: { labels: data.labels, datasets },
            options: {
                responsive: true,
                animation:  false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0F172A',
                        titleFont: { size: 12, weight: 'bold' },
                        bodyFont:  { size: 12 },
                        padding: 12,
                        cornerRadius: 10,
                        callbacks: {
                            label: ctx => {
                                if (ctx.dataset.yAxisID === 'yRevenue') return ` ${currency}${Number(ctx.raw).toLocaleString(locale, { minimumFractionDigits: 2 })}`;
                                return ` ${ctx.raw}${i18n.chart_orders_suffix || ' orders'}`;
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11 }, color: '#94A3B8', maxTicksLimit: 10 },
                    },
                    ...scales,
                },
            },
        });
    }

/*
       Timeline
*/
    function loadTimeline() {
        const container = document.getElementById('ph-timeline');
        if (!container) return;

        container.innerHTML = skeletonRows(5);

        ajax('ph_get_timeline', {
            days:     currentDays,
            page:     currentPage,
            status:   currentStatus,
            search:   currentSearch,
            per_page: 20,
        }).then(resp => {
            if (!resp.success) { container.innerHTML = '<p style="padding:20px;color:#64748B">' + (i18n.loading_error || 'Failed to load.') + '</p>'; return; }
            renderTimeline(resp.data);
        });
    }

    function skeletonRows(n) {
        return Array.from({ length: n }).map(() =>
            `<div class="ph-skeleton-row">
                <span class="ph-skeleton ph-skeleton-badge"></span>
                <span class="ph-skeleton" style="width:120px"></span>
                <span class="ph-skeleton" style="width:80px"></span>
                <span class="ph-skeleton" style="width:50px"></span>
            </div>`
        ).join('');
    }

    function renderTimeline(data) {
        const container  = document.getElementById('ph-timeline');
        const pagination = document.getElementById('ph-pagination');

        if (!data.orders.length) {
            container.innerHTML = '<p style="padding:20px;text-align:center;color:#64748B">' + (i18n.no_orders_found || 'No orders found.') + '</p>';
            if (pagination) pagination.innerHTML = '';
            return;
        }

        data.orders.forEach(o => orderCache.set(o.id, o));

        container.innerHTML = data.orders.map(o => `
            <div class="ph-tl-row" data-order-id="${o.id}" tabindex="0" role="button">
                <span class="ph-tl-id">#${o.number}</span>
                <span class="ph-tl-customer">${esc(o.customer.name) || esc(o.customer.email)}</span>
                <span class="ph-tl-date">${timeAgo(o.date_human)}</span>
                <span class="ph-status-badge ph-status-${esc(o.status)}">${esc(o.status_label)}</span>
                <span class="ph-tl-total">${o.total}</span>
            </div>`
        ).join('');

        container.querySelectorAll('.ph-tl-row').forEach(row => {
            row.addEventListener('click',  () => openOrderModal(parseInt(row.dataset.orderId)));
            row.addEventListener('keydown', e => { if (e.key === 'Enter') openOrderModal(parseInt(row.dataset.orderId)); });
        });

        if (pagination) renderPagination(data.page, data.total_pages, pagination);
    }

    function renderPagination(page, totalPages, container) {
        if (totalPages <= 1) { container.innerHTML = ''; return; }

        let html = `<button class="ph-page-btn" ${page === 1 ? 'disabled' : ''} data-page="${page - 1}">‹</button>`;
        for (let p = Math.max(1, page - 2); p <= Math.min(totalPages, page + 2); p++) {
            html += `<button class="ph-page-btn ${p === page ? 'is-active' : ''}" data-page="${p}">${p}</button>`;
        }
        html += `<button class="ph-page-btn" ${page === totalPages ? 'disabled' : ''} data-page="${page + 1}">›</button>`;

        container.innerHTML = html;
        container.querySelectorAll('.ph-page-btn:not([disabled])').forEach(btn => {
            btn.addEventListener('click', () => {
                currentPage = parseInt(btn.dataset.page);
                loadTimeline();
            });
        });
    }

/*
       Order modal
*/
    function openOrderModal(orderId, forceReload = false) {
        const backdrop = document.getElementById('ph-modal-backdrop');
        const body     = document.getElementById('ph-modal-body');
        const number   = document.getElementById('ph-modal-number');
        const editLink = document.getElementById('ph-modal-edit-link');
        const statusEl = document.getElementById('ph-modal-status');

        if (!backdrop) return;

        const cached = !forceReload && orderCache.get(orderId);
        if (cached) {
            _renderOrderModal(cached, backdrop, body, number, editLink, statusEl);
        } else {
            // Order is not in cache, or forceReload=true (e.g. from customer card) — always fetch fresh
            backdrop.hidden = false;
            document.body.style.overflow = 'hidden';
            if (body) body.innerHTML = '<div class="ph-loading-spinner"></div>';
            ajax('ph_get_single_order', { order_id: orderId }).then(r => {
                if (!r.success) {
                    if (body) body.innerHTML = '<p style="padding:20px;color:#EF4444">' + (i18n.order_load_error || 'Order could not be loaded.') + '</p>';
                    return;
                }
                orderCache.set(r.data.id, r.data);
                _renderOrderModal(r.data, backdrop, body, number, editLink, statusEl);
            });
        }
    }

    function _renderOrderModal(o, backdrop, body, number, editLink, statusEl) {
        backdrop.hidden = false;
        document.body.style.overflow = 'hidden';

        if (number)   number.textContent = o.number;

        // Edit button link to order-edit modal (instead of WC link)
        const editBtn = document.getElementById('ph-modal-edit-btn');
        if (editBtn) {
            const newEditBtn = editBtn.cloneNode(true);
            editBtn.replaceWith(newEditBtn);
            // Always read the most current version from the cache so
            // changes that were just saved are also visible.
            newEditBtn.addEventListener('click', () => {
                const current = orderCache.get(o.id) || o;
                openOrderEditModal(current);
            });
        }

        if (statusEl) {
            statusEl.textContent = o.status_label;
            statusEl.className   = `ph-status-badge ph-status-${o.status}`;
        }

        body.innerHTML = renderModalBody(o);
        bindModalBodyEvents(body, o.id, o);
    }

    function bindModalBodyEvents(body, orderId, o) {
        // Submit note
        const noteForm = body.querySelector('#ph-note-form');
        if (noteForm) {
            noteForm.addEventListener('submit', e => {
                e.preventDefault();
                const input = noteForm.querySelector('.ph-note-input');
                const note  = input.value.trim();
                if (!note) return;
                const submitBtn = noteForm.querySelector('.ph-note-submit');
                submitBtn.disabled = true;
                ajax('ph_update_order_note', { order_id: orderId, note }).then(r => {
                    submitBtn.disabled = false;
                    if (r.success) {
                        input.value = '';
                        const notesEl = body.querySelector('#ph-notes-list');
                        if (notesEl) {
                            notesEl.insertAdjacentHTML('afterbegin',
                                `<div class="ph-note-item"><div class="ph-note-meta">${i18n.note_just_now_you || 'Just Now · You'}</div><div>${esc(note)}</div></div>`
                            );
                        }
                    }
                });
            });
        }

        // Open customer card
        const custBtn = body.querySelector('.ph-open-customer-card');
        if (custBtn && (o.customer.id || o.customer.email)) {
            custBtn.addEventListener('click', () => {
                closeModal();
                openCustomerPanel(o.customer.id, o.customer.email);
            });
        }

        // Change status
        body.querySelectorAll('.ph-status-action-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const action = btn.dataset.action;
                if (action === 'refund') {
                    openRefundModal(orderId, false);
                } else if (action === 'revert') {
                    openRefundModal(orderId, true);
                } else {
                    updateOrderStatus(orderId, btn.dataset.status);
                }
            });
        });

        // Delete order
        const deleteBtn = body.querySelector('#ph-delete-order-btn');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', () => deleteOrder(orderId));
        }
    }

    function closeModal() {
        const backdrop = document.getElementById('ph-modal-backdrop');
        if (backdrop) backdrop.hidden = true;
        document.body.style.overflow = '';
    }

/*
       Order edit modal
*/
    function openOrderEditModal(o) {
        const backdrop = document.getElementById('ph-order-edit-backdrop');
        if (!backdrop) return;

        // Fill in order number
        const numEl = document.getElementById('ph-order-edit-number');
        if (numEl) numEl.textContent = '#' + o.number;

        // Fill in hidden order id
        document.getElementById('ph-order-edit-id').value = o.id;

        // Fill in billing fields
        const c = o.customer || {};
        const nameParts = (c.name || '').trim().split(' ');
        document.getElementById('ph-oe-first-name').value = c.first_name || nameParts[0] || '';
        document.getElementById('ph-oe-last-name').value  = c.last_name  || nameParts.slice(1).join(' ') || '';
        document.getElementById('ph-oe-company').value    = c.company    || '';
        document.getElementById('ph-oe-email').value      = c.email      || '';
        document.getElementById('ph-oe-phone').value      = c.phone      || '';
        document.getElementById('ph-oe-address1').value   = c.address_1  || '';
        document.getElementById('ph-oe-address2').value   = c.address_2  || '';
        document.getElementById('ph-oe-city').value       = c.city       || '';
        document.getElementById('ph-oe-postcode').value   = c.postcode   || '';
        document.getElementById('ph-oe-country').value    = c.country    || '';
        document.getElementById('ph-oe-note').value       = '';

        // Reset error message
        const errEl = document.getElementById('ph-order-edit-error');
        if (errEl) { errEl.style.display = 'none'; errEl.textContent = ''; }

        // Reset save button
        const saveBtn = document.getElementById('ph-order-edit-save');
        if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = i18n.order_edit_save || 'Save'; }

        backdrop.hidden = false;

        // Close handlers via cloning
        const closeBtn  = document.getElementById('ph-order-edit-close');
        const cancelBtn = document.getElementById('ph-order-edit-cancel');
        const newClose  = closeBtn.cloneNode(true);
        const newCancel = cancelBtn.cloneNode(true);
        const newSave   = saveBtn.cloneNode(true);
        closeBtn.replaceWith(newClose);
        cancelBtn.replaceWith(newCancel);
        saveBtn.replaceWith(newSave);

        const closeEditModal = () => { backdrop.hidden = true; };
        newClose.addEventListener('click', closeEditModal);
        newCancel.addEventListener('click', closeEditModal);
        backdrop.addEventListener('click', e => { if (e.target === backdrop) closeEditModal(); }, { once: true });

        newSave.addEventListener('click', () => {
            newSave.disabled = true;
            newSave.textContent = i18n.order_edit_saving || 'Saving…';

            const data = {
                order_id:            o.id,
                billing_first_name:  document.getElementById('ph-oe-first-name').value,
                billing_last_name:   document.getElementById('ph-oe-last-name').value,
                billing_company:     document.getElementById('ph-oe-company').value,
                billing_email:       document.getElementById('ph-oe-email').value,
                billing_phone:       document.getElementById('ph-oe-phone').value,
                billing_address_1:   document.getElementById('ph-oe-address1').value,
                billing_address_2:   document.getElementById('ph-oe-address2').value,
                billing_city:        document.getElementById('ph-oe-city').value,
                billing_postcode:    document.getElementById('ph-oe-postcode').value,
                billing_country:     document.getElementById('ph-oe-country').value,
                internal_note:       document.getElementById('ph-oe-note').value,
            };

            ajax('ph_qp_save_order', data).then(r => {
                if (!r || !r.success) {
                    newSave.disabled = false;
                    newSave.textContent = i18n.order_edit_save || 'Save';
                    const err = document.getElementById('ph-order-edit-error');
                    if (err) { err.textContent = r?.data?.message || (i18n.unknown_error || 'Unknown error.'); err.style.display = ''; }
                    return;
                }
                // Update cached order and modal
                const updated = r.data;
                orderCache.set(updated.id, updated);
                closeEditModal();

                // Re-render the order modal body with the fresh data
                const body     = document.getElementById('ph-modal-body');
                const statusEl = document.getElementById('ph-modal-status');
                const numEl    = document.getElementById('ph-modal-number');
                if (numEl)    numEl.textContent    = updated.number;
                if (statusEl) {
                    statusEl.textContent = updated.status_label;
                    statusEl.className   = `ph-status-badge ph-status-${updated.status}`;
                }
                if (body) body.innerHTML = renderModalBody(updated);
                bindModalBodyEvents(body, updated.id, updated);

                // Re-register the editBtn in the header with the fresh data
                const editBtn = document.getElementById('ph-modal-edit-btn');
                if (editBtn) {
                    const newBtn = editBtn.cloneNode(true);
                    editBtn.replaceWith(newBtn);
                    newBtn.addEventListener('click', () => {
                        const current = orderCache.get(updated.id) || updated;
                        openOrderEditModal(current);
                    });
                }

                // Update name in timeline row
                const rowName = document.querySelector(`.ph-tl-row[data-order-id="${updated.id}"] .ph-tl-customer`);
                if (rowName) rowName.textContent = updated.customer.name;
            }).catch(() => {
                newSave.disabled = false;
                newSave.textContent = i18n.order_edit_save || 'Save';
                const err = document.getElementById('ph-order-edit-error');
                if (err) { err.textContent = i18n.connection_error || 'Connection error. Please try again.'; err.style.display = ''; }
            });
        });
    }

    function renderModalBody(o) {
        const items = o.items.map(i =>
            `<div class="ph-modal-item-row">
                <span class="ph-modal-item-name">${esc(i.name)}</span>
                <span class="ph-modal-item-qty">× ${i.qty}</span>
                <span class="ph-modal-item-sub">${i.subtotal}</span>
            </div>`
        ).join('');

        const notes = o.notes.length
            ? o.notes.map(n =>
                `<div class="ph-note-item ${n.customer_note ? 'ph-note-customer' : ''}">
                    <div class="ph-note-meta">${timeAgo(n.date)} · ${esc(n.added_by)}</div>
                    <div>${n.content}</div>
                </div>`
              ).join('')
            : `<p style="color:#94A3B8;font-size:13px">${i18n.modal_no_notes || 'No notes yet.'}</p>`;

        const custLink = (o.customer.id || o.customer.email)
            ? `<button class="ph-open-customer-card" style="background:none;border:none;color:#10B981;font-weight:600;cursor:pointer;font-size:12px;padding:0;">
                   ${i18n.modal_open_customer || '↗ Open customer card'}
               </button>`
            : '';

        return `
        <div class="ph-modal-section">
            <div class="ph-modal-section-title">${i18n.modal_customer || 'Customer'}</div>
            <div class="ph-modal-info-grid">
                <div class="ph-modal-info-item"><label>${i18n.modal_name || 'Name'}</label><span>${esc(o.customer.name)}</span> ${custLink}</div>
                <div class="ph-modal-info-item"><label>${i18n.modal_email_label || 'E-mail'}</label><span>${esc(o.customer.email)}</span></div>
                ${o.customer.phone ? `<div class="ph-modal-info-item"><label>${i18n.modal_phone_label || 'Phone'}</label><span>${esc(o.customer.phone)}</span></div>` : ''}
                <div class="ph-modal-info-item"><label>${i18n.modal_address || 'Address'}</label><span>${[o.customer.address_1, o.customer.address_2, o.customer.postcode, o.customer.city, o.customer.country].filter(Boolean).join(', ')}</span></div>
                <div class="ph-modal-info-item"><label>${i18n.modal_payment || 'Payment'}</label><span>${esc(o.payment)}</span></div>
                <div class="ph-modal-info-item"><label>${i18n.modal_date || 'Date'}</label><span>${esc(o.date)}</span></div>
                <div class="ph-modal-info-item"><label>${i18n.modal_total || 'Totaal'}</label><span style="font-weight:800">${o.total}</span></div>
            </div>
        </div>

        <div class="ph-modal-section">
            <div class="ph-modal-section-title">${i18n.modal_products || 'Products'} (${o.items_count})</div>
            ${items}
        </div>

        <div class="ph-modal-section">
            <div class="ph-modal-section-title">${i18n.modal_order_notes || 'Ordernotes'}</div>
            <div id="ph-notes-list">${notes}</div>
            <form id="ph-note-form" class="ph-note-form" style="margin-top:12px">
                <textarea class="ph-note-input" placeholder="${i18n.modal_note_placeholder || 'Add note…'}" rows="2"></textarea>
                <button type="submit" class="ph-note-submit">${i18n.modal_note_add || 'Add'}</button>
            </form>
        </div>

        <div class="ph-modal-section ph-status-actions-section">
            <div class="ph-modal-section-title">${i18n.modal_change_status || 'Change status'}</div>
            <div class="ph-status-actions">
                <button class="ph-status-action-btn ph-sa-complete${o.status === 'completed'  ? ' is-current' : ''}" data-status="completed"  ${o.status === 'completed'  ? 'disabled' : ''}>${i18n.modal_complete || '✓ Complete'}</button>
                <button class="ph-status-action-btn ph-sa-processing${o.status === 'processing' ? ' is-current' : ''}" data-status="processing" ${o.status === 'processing' ? 'disabled' : ''}>${i18n.modal_processing || '↻ Processing'}</button>
                <button class="ph-status-action-btn ph-sa-hold${o.status === 'on-hold'    ? ' is-current' : ''}" data-status="on-hold"    ${o.status === 'on-hold'    ? 'disabled' : ''}>${i18n.modal_on_hold || '⏸ On hold'}</button>
                <button class="ph-status-action-btn ph-sa-cancel${o.status === 'cancelled'  ? ' is-current' : ''}" data-status="cancelled"  ${o.status === 'cancelled'  ? 'disabled' : ''}>${i18n.modal_cancel || '✕ Cancel'}</button>
                <button class="ph-status-action-btn ph-sa-fail${o.status === 'failed'     ? ' is-current' : ''}" data-status="failed"     ${o.status === 'failed'     ? 'disabled' : ''}>${i18n.modal_failed || '⚠ Mislukt'}</button>
                ${o.status === 'refunded'
                    ? `<button class="ph-status-action-btn ph-sa-refund is-current ph-sa-revert" data-action="revert">${i18n.modal_revert_refund || '↩ Retour terugdraaien'}</button>`
                    : `<button class="ph-status-action-btn ph-sa-refund" data-action="refund">${i18n.modal_refund || '↩ Retour'}</button>`
                }
            </div>
        </div>

        <div class="ph-modal-section ph-danger-section">
            <div class="ph-modal-section-title">${i18n.modal_danger_zone || 'Danger zone'}</div>
            <button id="ph-delete-order-btn" class="ph-delete-order-btn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                ${i18n.modal_delete_order || 'Permanently delete order'}
            </button>
            <p class="ph-danger-warning">${i18n.modal_irreversible || '⚠ This action cannot be undone.'}</p>
        </div>`;
    }

/* 
       Delete order
*/
    function deleteOrder(orderId) {
        const backdrop  = document.getElementById('ph-delete-confirm-backdrop');
        const textEl    = document.getElementById('ph-delete-confirm-text');
        const okBtn     = document.getElementById('ph-delete-confirm-ok');
        const cancelBtn = document.getElementById('ph-delete-confirm-cancel');
        const closeBtn  = document.getElementById('ph-delete-confirm-close');
        if (!backdrop) return;

        if (textEl) textEl.textContent = (i18n.modal_delete_confirm || 'Are you sure you want to permanently delete order #%s?').replace('%s', orderId);
        backdrop.hidden = false;

        // Remove any error message from previous attempt
        backdrop.querySelectorAll('.ph-delete-error').forEach(el => el.remove());

        // Remove old listeners by cloning buttons
        const newOk     = okBtn.cloneNode(true);
        const newCancel = cancelBtn.cloneNode(true);
        const newClose  = closeBtn.cloneNode(true);
        // Reset state (disabled/text) from previous attempt
        newOk.disabled    = false;
        newOk.textContent = i18n.delete_confirm_ok || 'Permanently delete';
        okBtn.replaceWith(newOk);
        cancelBtn.replaceWith(newCancel);
        closeBtn.replaceWith(newClose);

        const closeConfirm = () => { backdrop.hidden = true; };

        newCancel.addEventListener('click', closeConfirm);
        newClose.addEventListener('click', closeConfirm);
        backdrop.addEventListener('click', e => { if (e.target === backdrop) closeConfirm(); }, { once: true });

        newOk.addEventListener('click', () => {
            newOk.disabled = true;
            newOk.textContent = i18n.delete_confirm_ok_busy || 'Deleting…';

            ajax('ph_delete_order', { order_id: orderId }).then(r => {
                closeConfirm();
                if (r.success) {
                    closeModal();
                    const row = document.querySelector(`.ph-tl-row[data-order-id="${orderId}"]`);
                    if (row) row.remove();
                    orderCache.delete(orderId);
                } else {
                    // Restore button text on error
                    newOk.disabled = false;
                    newOk.textContent = i18n.delete_confirm_ok || 'Permanently delete';
                    // Show error in the modal itself
                    const errEl = document.createElement('p');
                    errEl.className = 'ph-delete-error';
                    errEl.style.cssText = 'color:#EF4444;font-size:13px;margin:8px 0 0;text-align:right;';
                    errEl.textContent = (i18n.delete_error_prefix || 'Failed: ') + (r.data?.message || (i18n.delete_unknown_error || 'unknown error'));
                    newOk.closest('div').appendChild(errEl);
                }
            });
        });
    }

/*
       Customer Card Panel
*/
    function openCustomerPanel(customerId, customerEmail = '') {
        const backdrop = document.getElementById('ph-customer-backdrop');
        const panel    = document.getElementById('ph-customer-panel');
        const body     = document.getElementById('ph-customer-body');
        if (!backdrop || !panel) return;

        backdrop.hidden = false;
        body.innerHTML  = '<div class="ph-loading-spinner"></div>';

        ajax('ph_get_customer', { customer_id: customerId, customer_email: customerEmail }).then(resp => {
            if (!resp.success) { body.innerHTML = '<p style="padding:20px">' + (i18n.customer_not_found || 'Customer not found.') + '</p>'; return; }
            const c = resp.data;

            const statusColors = {
                completed:   '#10B981',
                processing:  '#F59E0B',
                'on-hold':   '#6366F1',
                cancelled:   '#EF4444',
                refunded:    '#94A3B8',
                failed:      '#EF4444',
                pending:     '#94A3B8',
            };

            const orderRows = c.orders.map(o => {
                const color = statusColors[o.status] || '#64748B';
                const itemsList = (o.items || []).map(it =>
                    `<span class="ph-cust-item">${esc(it.name)}${it.qty > 1 ? ` <em>×${it.qty}</em>` : ''}</span>`
                ).join('');
                return `<div class="ph-cust-order-row" data-order-id="${o.id}" title="Order #${esc(o.number)} openen">
                    <div class="ph-cust-order-cols">
                        <span class="ph-cust-order-num">#${esc(o.number)}</span>
                        <span class="ph-cust-order-date">${esc(o.date)}</span>
                        <span class="ph-cust-order-status" style="color:${color};font-weight:600">${esc(o.status_label || o.status)}</span>
                        <span class="ph-cust-order-total">${o.total}</span>
                    </div>
                    ${itemsList ? `<div class="ph-cust-items-row">${itemsList}</div>` : ''}
                </div>`;
            }).join('');

            body.innerHTML = `
            <div class="ph-cust-profile">
                <img class="ph-customer-avatar" src="${esc(c.avatar)}" alt="">
                <div class="ph-cust-profile-info">
                    <div class="ph-customer-name">${esc(c.name)}</div>
                    <div class="ph-customer-email">${esc(c.email)}</div>
                    ${c.city ? `<div class="ph-cust-city">📍 ${esc(c.city)}${c.country ? ', ' + esc(c.country) : ''}</div>` : ''}
                    <div class="ph-cust-since">${i18n.customer_since || 'Member since'} ${esc(c.registered)}</div>
                </div>
            </div>

            <div class="ph-customer-stats">
                <div class="ph-cstat-item">
                    <span class="ph-cstat-val">${c.order_count}</span>
                    <span class="ph-cstat-label">${i18n.cust_col_orders || 'Orders'}</span>
                </div>
                <div class="ph-cstat-item">
                    <span class="ph-cstat-val">${c.total_spent}</span>
                    <span class="ph-cstat-label">${i18n.customer_total_spent || 'Total spent'}</span>
                </div>
                <div class="ph-cstat-item">
                    <span class="ph-cstat-val">${c.avg_order}</span>
                    <span class="ph-cstat-label">${i18n.customer_avg_order || 'Avg. order'}</span>
                </div>
            </div>

            <div class="ph-cust-orders-wrap">
                <div class="ph-cust-orders-title">${i18n.customer_orders_history || 'Order history'}</div>
                <div class="ph-cust-orders-header">
                    <span>${i18n.customer_col_order || 'Order'}</span>
                    <span>${i18n.customer_col_date || 'Date'}</span>
                    <span>${i18n.customer_col_status || 'Status'}</span>
                    <span>${i18n.customer_col_amount || 'Amount'}</span>
                </div>
                ${orderRows || `<p style="padding:12px 0;color:#94A3B8;font-size:13px">${i18n.customer_orders_none || 'No orders found.'}</p>`}
            </div>`;

            // Order rows should be clickable → close customer card, open order modal
            body.querySelectorAll('.ph-cust-order-row[data-order-id]').forEach(row => {
                row.addEventListener('click', () => {
                    const orderId = parseInt(row.dataset.orderId, 10);
                    if (!orderId) return;
                    // Close customer card
                    const custBackdrop = document.getElementById('ph-customer-backdrop');
                    if (custBackdrop) custBackdrop.hidden = true;
                    // Always fetch fresh data from customer card: cache may contain stale data
                    // from another customer that was loaded earlier in the timeline.
                    openOrderModal(orderId, true);
                });
            });
        });
    }

/*
       Refund modal
*/
    function openRefundModal(orderId, isRevert) {
        const backdrop  = document.getElementById('ph-refund-modal-backdrop');
        const titleEl   = document.getElementById('ph-refund-modal-title');
        const descEl    = document.getElementById('ph-refund-modal-desc');
        const orderIdEl = document.getElementById('ph-refund-order-id');
        const refundWrap = document.getElementById('ph-refund-options-wrap');
        const revertWrap = document.getElementById('ph-revert-options-wrap');
        const errorEl   = document.getElementById('ph-refund-modal-error');
        const confirmBtn = document.getElementById('ph-refund-modal-confirm');
        if (!backdrop) return;

        orderIdEl.value = orderId;
        if (errorEl) { errorEl.style.display = 'none'; errorEl.textContent = ''; }
        if (confirmBtn) { confirmBtn.disabled = false; confirmBtn.textContent = i18n.refund_confirm_btn || 'Confirm'; }

        if (isRevert) {
            if (titleEl) titleEl.textContent = i18n.revert_modal_title || 'Revert return';
            if (descEl)  descEl.textContent  = (i18n.revert_modal_desc || 'Order #%s is on "Return". Choose the new status and whether existing refund records should be deleted.').replace('%s', orderId);
            if (refundWrap) refundWrap.style.display = 'none';
            if (revertWrap) revertWrap.style.display = '';
            // Reset checkboxes/radios
            const delChk = document.getElementById('ph-revert-delete-refunds');
            if (delChk) delChk.checked = false;
            const firstRevert = revertWrap ? revertWrap.querySelector('input[type=radio]') : null;
            if (firstRevert) firstRevert.checked = true;
        } else {
            if (titleEl) titleEl.textContent = i18n.refund_modal_title || 'Process refund';
            if (descEl)  descEl.textContent  = (i18n.refund_modal_desc || 'Do you want to mark order #%s as a return? Choose how you want to process the refund.').replace('%s', orderId);
            if (refundWrap) refundWrap.style.display = '';
            if (revertWrap) revertWrap.style.display = 'none';
            // Reset radio
            const firstRefund = refundWrap ? refundWrap.querySelector('input[type=radio]') : null;
            if (firstRefund) firstRefund.checked = true;
        }

        backdrop.hidden = false;

        // Remove old listeners by cloning
        const newConfirm = confirmBtn.cloneNode(true);
        const cancelBtn  = document.getElementById('ph-refund-modal-cancel');
        const closeBtn   = document.getElementById('ph-refund-modal-close');
        const newCancel  = cancelBtn.cloneNode(true);
        const newClose   = closeBtn.cloneNode(true);
        confirmBtn.replaceWith(newConfirm);
        cancelBtn.replaceWith(newCancel);
        closeBtn.replaceWith(newClose);

        const closeRefundModal = () => { backdrop.hidden = true; };
        newCancel.addEventListener('click', closeRefundModal);
        newClose.addEventListener('click', closeRefundModal);
        backdrop.addEventListener('click', e => { if (e.target === backdrop) closeRefundModal(); }, { once: true });

        newConfirm.addEventListener('click', () => {
            newConfirm.disabled = true;
            newConfirm.textContent = i18n.processing || 'Processing…';
            const errEl = document.getElementById('ph-refund-modal-error');

            if (isRevert) {
                const revertRadio = document.querySelector('input[name=ph_revert_type]:checked');
                const revertStatus = revertRadio ? revertRadio.value : 'processing';
                const deleteRefunds = document.getElementById('ph-revert-delete-refunds')?.checked ? '1' : '0';
                ajax('ph_revert_refund', { order_id: orderId, new_status: revertStatus, delete_refunds: deleteRefunds }).then(r => {
                    if (!r || !r.success) {
                        newConfirm.disabled = false;
                        newConfirm.textContent = i18n.refund_confirm_btn || 'Confirm';
                        if (errEl) { errEl.textContent = (i18n.failed_prefix || 'Failed: ') + (r?.data?.message || (i18n.unknown_error_short || 'unknown error')); errEl.style.display = ''; }
                        return;
                    }
                    closeRefundModal();
                    afterStatusChange(orderId, r.data.status, r.data.status_label);
                }).catch(() => {
                    newConfirm.disabled = false;
                    newConfirm.textContent = i18n.refund_confirm_btn || 'Confirm';
                    if (errEl) { errEl.textContent = i18n.connection_error || 'Connection error. Please try again.'; errEl.style.display = ''; }
                });
            } else {
                const refundRadio = document.querySelector('input[name=ph_refund_type]:checked');
                const refundType = refundRadio ? refundRadio.value : 'status_only';
                ajax('ph_process_refund', { order_id: orderId, refund_type: refundType }).then(r => {
                    if (!r || !r.success) {
                        newConfirm.disabled = false;
                        newConfirm.textContent = i18n.refund_confirm_btn || 'Confirm';
                        if (errEl) { errEl.textContent = (i18n.failed_prefix || 'Failed: ') + (r?.data?.message || (i18n.unknown_error_short || 'unknown error')); errEl.style.display = ''; }
                        return;
                    }
                    closeRefundModal();
                    afterStatusChange(orderId, r.data.status, r.data.status_label);
                }).catch(() => {
                    newConfirm.disabled = false;
                    newConfirm.textContent = i18n.refund_confirm_btn || 'Confirm';
                    if (errEl) { errEl.textContent = i18n.connection_error || 'Connection error. Please try again.'; errEl.style.display = ''; }
                });
            }
        });
    }

    /** Process status update in the UI after refund or revert */
    function afterStatusChange(orderId, status, statusLabel) {
        const body     = document.getElementById('ph-modal-body');
        const statusEl = document.getElementById('ph-modal-status');

        if (statusEl) {
            statusEl.textContent = statusLabel;
            statusEl.className   = `ph-status-badge ph-status-${status}`;
        }

        const cached = orderCache.get(orderId);
        if (cached) {
            cached.status       = status;
            cached.status_label = statusLabel;
            orderCache.set(orderId, cached);
            // Re-render modal body so button row is correct
            if (body) body.innerHTML = renderModalBody(cached);
            bindModalBodyEvents(body, orderId, cached);
        }

        const rowBadge = document.querySelector(`.ph-tl-row[data-order-id="${orderId}"] .ph-status-badge`);
        if (rowBadge) {
            rowBadge.textContent = statusLabel;
            rowBadge.className   = `ph-status-badge ph-status-${status}`;
        }
    }

/*
       Order status update
*/
    function updateOrderStatus(orderId, newStatus) {
        const body     = document.getElementById('ph-modal-body');
        const statusEl = document.getElementById('ph-modal-status');

        const allBtns = body ? body.querySelectorAll('.ph-status-action-btn') : [];
        allBtns.forEach(b => { b.disabled = true; b.classList.add('is-loading'); });

        ajax('ph_update_order_status', { order_id: orderId, status: newStatus }).then(r => {
            if (!r.success) {
                const currentStatus = (orderCache.get(orderId) || {}).status || '';
                allBtns.forEach(b => {
                    b.classList.remove('is-loading');
                    b.disabled = (b.dataset.status === currentStatus);
                    if (b.disabled) b.classList.add('is-current');
                });
                return;
            }

            const { status, status_label } = r.data;
            afterStatusChange(orderId, status, status_label);
        });
    }

/*
       Revenue Report
*/
    function loadRevenueReport(force = false) {
        const container = document.getElementById('ph-revenue-report');
        if (!container) return;
        container.innerHTML = '<div class="ph-loading-spinner"></div>';

        ajax('ph_get_revenue_report', { days: currentDays, ...(force ? { force_refresh: 1 } : {}) }).then(resp => {
            if (!resp.success) { container.innerHTML = '<p style="padding:20px;color:#64748B">' + (i18n.loading_error || 'Error loading.') + '</p>'; return; }
            const d = resp.data;

            const rows = [
                { label: i18n.rev_gross    || 'Gross Sales',   value: formatCurrency(d.gross_sales),  class: '' },
                { label: i18n.rev_refunds  || 'Refunds',  value: '− ' + formatCurrency(d.refunds), class: 'ph-revenue-minus' },
                { label: i18n.rev_coupons  || 'Coupons',    value: '− ' + formatCurrency(d.coupons), class: 'ph-revenue-minus' },
                { label: i18n.rev_net      || 'Net Sales',     value: formatCurrency(d.net_sales),    class: 'ph-revenue-net' },
                { label: i18n.rev_taxes    || 'Taxes',     value: formatCurrency(d.taxes),        class: '' },
                { label: i18n.rev_shipping || 'Shipping',   value: formatCurrency(d.shipping),     class: '' },
                { label: i18n.rev_total    || 'Total Sales', value: formatCurrency(d.total_sales),  class: 'ph-revenue-total' },
            ];

            container.innerHTML = `
            <div class="ph-revenue-table">
                ${rows.map(r => `
                    <div class="ph-revenue-row ${r.class}">
                        <span class="ph-revenue-label">${r.label}</span>
                        <span class="ph-revenue-value">${r.value}</span>
                    </div>`
                ).join('')}
            </div>`;
        });
    }

/*
       CATEGORIES REPORT
*/
    function loadCategoriesReport(force = false) {
        const container = document.getElementById('ph-categories-report');
        if (!container) return;
        container.innerHTML = '<div class="ph-loading-spinner"></div>';

        // Populate column headers in the card header (same pattern as coupons)
        const colHeader = document.querySelector('.ph-categories-col-header');
        if (colHeader) {
            const s = 'color:#64748B;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em';
            colHeader.innerHTML = `
                <span style="${s}">${i18n.top_categories || 'Top categories'}</span>
                <span style="${s}">${i18n.cat_col_items || 'Items sold'}</span>
                <span style="${s}">${i18n.cat_col_revenue || 'Revenue'}</span>`;
        }

        ajax('ph_get_top_categories', { days: currentDays, limit: 20, ...(force ? { force_refresh: 1 } : {}) }).then(resp => {
            if (!resp.success) { container.innerHTML = '<p style="padding:20px;color:#64748B">' + (i18n.loading_error || 'Error loading.') + '</p>'; return; }
            const cats = resp.data;

            if (!cats.length) {
                container.innerHTML = '<p style="padding:20px;color:#94A3B8;font-size:13px">' + (i18n.cat_no_data || 'No category data available.') + '</p>';
                return;
            }

            const maxQty = cats[0]?.qty || 1;
            container.innerHTML = `
            <div class="ph-report-table">
                ${cats.map((c, i) => `
                    <div class="ph-report-row">
                        <span class="ph-report-name">
                            <span class="ph-top-rank">${i + 1}</span>
                            ${esc(c.name)}
                            <span class="ph-bar-wrap"><span class="ph-bar" style="width:${Math.round((c.qty/maxQty)*100)}%"></span></span>
                        </span>
                        <span class="ph-report-qty">${c.qty}</span>
                        <span class="ph-report-rev">${formatCurrency(c.revenue)}</span>
                    </div>`
                ).join('')}
            </div>`;
        });
    }

/*
       COUPONS REPORT
*/
    function loadCouponsReport(force = false) {
        const container = document.getElementById('ph-coupons-report');
        if (!container) return;
        container.innerHTML = '<div class="ph-loading-spinner"></div>';

        // Populate column headers in the card header
        const colHeader = document.querySelector('.ph-coupons-col-header');
        if (colHeader) {
            const s = 'color:#64748B;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em';
            colHeader.innerHTML = `<span style="${s}">${i18n.coupon_col_code || 'Coupon code'}</span><span style="${s}">${i18n.coupon_col_used || 'Used (orders)'}</span><span style="${s}">${i18n.coupon_col_discount || 'Total discount'}</span>`;
        }

        ajax('ph_get_coupons_report', { days: currentDays, limit: 50, ...(force ? { force_refresh: 1 } : {}) }).then(resp => {
            if (!resp.success) { container.innerHTML = '<p style="padding:20px;color:#64748B">' + (i18n.loading_error || 'Error loading.') + '</p>'; return; }
            const coupons = resp.data;

            if (!coupons.length) {
                container.innerHTML = '<p style="padding:20px;color:#94A3B8;font-size:13px">' + (i18n.coupon_no_data || 'No coupons used in this period.') + '</p>';
                return;
            }

            container.innerHTML = `
            <div class="ph-report-table">
                ${coupons.map((c, i) => `
                    <div class="ph-report-row">
                        <span class="ph-report-name">
                            <span class="ph-top-rank">${i + 1}</span>
                            <code class="ph-coupon-code">${esc(c.code)}</code>
                        </span>
                        <span class="ph-report-qty">${c.order_count}×</span>
                        <span class="ph-report-rev ph-revenue-minus">− ${formatCurrency(c.discount_amount)}</span>
                    </div>`
                ).join('')}
            </div>`;
        });
    }

/* 
       DAILY REPORT
*/
    function loadDailyReport(force = false) {
        const container = document.getElementById('ph-daily-report');
        const dateInput = document.getElementById('ph-daily-date');
        if (!container) return;

        const date = dateInput ? dateInput.value : new Date().toISOString().slice(0, 10);
        container.innerHTML = '<div class="ph-loading-spinner"></div>';

        ajax('ph_get_daily_products', { date, ...(force ? { force_refresh: 1 } : {}) }).then(resp => {
            if (!resp.success) { container.innerHTML = '<p style="padding:20px;color:#64748B">' + (i18n.loading_error || 'Error loading.') + '</p>'; return; }
            const d = resp.data;

            if (!d.products.length) {
                container.innerHTML = `<p style="padding:20px;color:#94A3B8;font-size:13px">${(i18n.daily_no_sales || 'No sales found on %s.').replace('%s', esc(d.date))}</p>`;
                return;
            }

            const maxQty = d.products[0]?.qty || 1;
            container.innerHTML = `
            <div class="ph-daily-summary">
                <strong>${i18n.daily_date_label || 'Date:'}</strong> ${esc(d.date)} &nbsp;|&nbsp;
                <strong>${i18n.daily_total_label || 'Total revenue:'}</strong> ${formatCurrency(d.day_total)} &nbsp;|&nbsp;
                <strong>${i18n.daily_products_label || 'Products:'}</strong> ${d.products.length}
            </div>
            <div class="ph-report-table">
                <div class="ph-report-header">
                    <span>${i18n.daily_col_product || 'Product'}</span><span>${i18n.daily_col_qty || 'Units sold'}</span><span>${i18n.daily_col_revenue || 'Revenue'}</span>
                </div>
                ${d.products.map((p, i) => `
                    <div class="ph-report-row">
                        <span class="ph-report-name">
                            <span class="ph-top-rank">${i + 1}</span>
                            ${esc(p.name)}
                            <span class="ph-bar-wrap"><span class="ph-bar" style="width:${Math.round((p.qty/maxQty)*100)}%"></span></span>
                        </span>
                        <span class="ph-report-qty">${p.qty}×</span>
                        <span class="ph-report-rev">${formatCurrency(p.revenue)}</span>
                    </div>`
                ).join('')}
            </div>`;
        });
    }

/*
       CUSTOMERS REPORT
*/
    function loadCustomersReport(force = false) {
        const container = document.getElementById('ph-customers-report');
        if (!container) return;
        container.innerHTML = '<div class="ph-loading-spinner"></div>';

        const colHeader = document.querySelector('.ph-customers-col-header');
        if (colHeader) {
            const s = 'color:#64748B;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em';
            colHeader.innerHTML = `
                <span style="${s}">${i18n.top_customers || 'Top customers'}</span>
                <span style="${s}">${i18n.cust_col_orders || 'Orders'}</span>
                <span style="${s}">${i18n.cust_col_avg || 'Avg. order'}</span>
                <span style="${s}">${i18n.cust_col_last || 'Last order'}</span>
                <span style="${s}">${i18n.cust_col_total || 'Total spent'}</span>`;
        }

        ajax('ph_get_top_customers', { days: currentDays, limit: 25, ...(force ? { force_refresh: 1 } : {}) }).then(resp => {
            if (!resp.success) { container.innerHTML = '<p style="padding:20px;color:#64748B">' + (i18n.loading_error || 'Error loading.') + '</p>'; return; }
            const customers = resp.data;

            if (!customers.length) {
                container.innerHTML = '<p style="padding:20px;color:#94A3B8;font-size:13px">' + (i18n.cust_no_data || 'No customer data available for this period.') + '</p>';
                return;
            }

            const maxSpent = customers[0]?.total_spent || 1;
            container.innerHTML = `
            <div class="ph-report-table ph-customers-table">
                ${customers.map((c, i) => `
                    <div class="ph-report-row ph-customer-row ph-customer-row--clickable" data-customer-id="${c.user_id}" data-customer-email="${esc(c.email)}">
                        <span class="ph-report-name ph-customer-name-cell">
                            <span class="ph-top-rank">${i + 1}</span>
                            <span class="ph-customer-avatar-sm">${esc(c.name.charAt(0).toUpperCase())}</span>
                            <span class="ph-customer-details">
                                <strong>${esc(c.name)}</strong>
                                <small>${esc(c.email)}</small>
                            </span>
                        </span>
                        <span class="ph-report-qty">${c.order_count}×</span>
                        <span class="ph-report-qty">${formatCurrency(c.avg_order)}</span>
                        <span class="ph-report-qty ph-muted-text">${esc(c.last_order)}</span>
                        <span class="ph-report-rev">
                            ${formatCurrency(c.total_spent)}
                            <span class="ph-bar-wrap" style="max-width:80px;margin-top:4px"><span class="ph-bar" style="width:${Math.round((c.total_spent/maxSpent)*100)}%"></span></span>
                        </span>
                    </div>`
                ).join('')}
            </div>`;

            // Click handler: open customer card
            container.querySelectorAll('.ph-customer-row--clickable').forEach(row => {
                row.addEventListener('click', () => openCustomerPanel(
                    parseInt(row.dataset.customerId, 10),
                    row.dataset.customerEmail || ''
                ));
            });
        });
    }

/*
       CSV Export
*/
    function exportCsv() {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = ajax_url;
        const fields = { action: 'ph_export_csv', nonce, days: currentDays, status: currentStatus, search: currentSearch };
        Object.entries(fields).forEach(([k, v]) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = k;
            input.value = v;
            form.appendChild(input);
        });
        document.body.appendChild(form);
        form.submit();
        form.remove();
    }

/*
       Events
*/
    document.addEventListener('DOMContentLoaded', () => {

        // Initialise tabs
        initTabs();

        // Period buttons
        document.querySelectorAll('.ph-period-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.ph-period-btn').forEach(b => b.classList.remove('is-active'));
                btn.classList.add('is-active');
                currentDays = parseInt(btn.dataset.days);
                currentPage = 1;

                // Reset tab loaded state for reports (so they reload when returning)
                tabLoaded.revenue = tabLoaded.categories = tabLoaded.coupons = tabLoaded.customers = tabLoaded.stock = false;

                const activeTab = document.querySelector('.ph-tab.is-active')?.dataset.tab || 'dashboard';
                if (activeTab === 'dashboard') {
                    loadAll();
                } else if (activeTab === 'revenue') {
                    loadRevenueReport(true);
                } else if (activeTab === 'categories') {
                    loadCategoriesReport(true);
                } else if (activeTab === 'coupons') {
                    loadCouponsReport(true);
                } else if (activeTab === 'customers') {
                    loadCustomersReport(true);
                }
            });
        });

        // Status filter
        const statusSel = document.getElementById('ph-status-filter');
        if (statusSel) statusSel.addEventListener('change', () => {
            currentStatus = statusSel.value;
            currentPage   = 1;
            loadTimeline();
        });

        // Searchbar
        const searchEl = document.getElementById('ph-timeline-search');
        if (searchEl) searchEl.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                currentSearch = searchEl.value.trim();
                currentPage   = 1;
                loadTimeline();
            }, 350);
        });

        // Chart dataset toggles
        document.querySelectorAll('.ph-toggle-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const ds = btn.dataset.dataset;
                if (!ds) return;
                visibleDatasets[ds] = !visibleDatasets[ds];
                btn.classList.toggle('is-active', visibleDatasets[ds]);
                loadChart();
            });
        });

        // Top products — load more
        const loadMoreBtn = document.getElementById('ph-top-load-more');
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', () => {
                const step = parseInt(loadMoreBtn.dataset.step || 5);
                topProductsLimit += step;
                loadMoreBtn.dataset.limit = topProductsLimit;
                loadTopProducts();
            });
        }

        // Daily report — date selection + loading
        const dailyLoadBtn = document.getElementById('ph-daily-load-btn');
        if (dailyLoadBtn) {
            dailyLoadBtn.addEventListener('click', () => loadDailyReport(true));
        }
        const dailyDate = document.getElementById('ph-daily-date');
        if (dailyDate) {
            dailyDate.addEventListener('change', () => loadDailyReport(true));
        }

        // Export
        const exportBtn  = document.getElementById('ph-export-btn');
        if (exportBtn) exportBtn.addEventListener('click', exportCsv);

        const refreshBtn = document.getElementById('ph-refresh-btn');
        if (refreshBtn) refreshBtn.addEventListener('click', () => {
            refreshBtn.classList.add('is-spinning');
            refreshBtn.disabled = true;
            const activeTab = document.querySelector('.ph-tab.is-active')?.dataset.tab || 'dashboard';
            if (activeTab === 'dashboard')   loadAll(true);
            if (activeTab === 'revenue')     loadRevenueReport(true);
            if (activeTab === 'categories')  loadCategoriesReport(true);
            if (activeTab === 'coupons')     loadCouponsReport(true);
            if (activeTab === 'daily')       loadDailyReport(true);
            if (activeTab === 'customers')   loadCustomersReport(true);
            if (activeTab === 'stock')       loadStock();
            setTimeout(() => {
                refreshBtn.classList.remove('is-spinning');
                refreshBtn.disabled = false;
            }, 1000);
        });

        // Close modal
        const closeBtn = document.getElementById('ph-modal-close');
        const backdrop = document.getElementById('ph-modal-backdrop');
        const modal    = document.getElementById('ph-order-modal');

        if (closeBtn) closeBtn.addEventListener('click', closeModal);
        if (backdrop) backdrop.addEventListener('click', closeModal);
        if (modal)    modal.addEventListener('click', e => e.stopPropagation());
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

        // Close customer card
        const closePanel    = document.getElementById('ph-panel-close');
        const custBackdrop  = document.getElementById('ph-customer-backdrop');
        const custModal     = document.getElementById('ph-customer-panel');
        const closeCustPanel = () => { if (custBackdrop) custBackdrop.hidden = true; };
        if (closePanel)   closePanel.addEventListener('click', closeCustPanel);
        if (custBackdrop) custBackdrop.addEventListener('click', closeCustPanel);
        if (custModal)    custModal.addEventListener('click', e => e.stopPropagation());
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeCustPanel(); });

        // Initial load
        loadAll();

        // Stock tab UI events registration
        initStockTab();
    });

    function loadAll(force = false) {
        loadStats(force);
        loadTimeline();
        setTimeout(() => loadChart(force), 150);
    }

/*
       Utility
*/
    function esc(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

/*
       STOCK TAB
*/
    let stockState = {
        page: 1,
        perPage: 25,
        status: 'all',
        search: '',
        sortCol: 'stock',
        sortDir: 'asc',
        selected: new Set(),
        totalPages: 1,
    };

    function loadStock() {
        const tbody = document.getElementById('ph-stock-tbody');
        const empty = document.getElementById('ph-stock-empty');
        const summary = document.getElementById('ph-stock-summary');
        if (!tbody) return;
        tbody.innerHTML = '<tr><td colspan="8" style="padding:40px;text-align:center"><div class="ph-loading-spinner"></div></td></tr>';
        if (empty) empty.hidden = true;

        ajax('ph_stock_get', {
            page:     stockState.page,
            per_page: stockState.perPage,
            status:   stockState.status,
            search:   stockState.search,
            sort_col: stockState.sortCol,
            sort_dir: stockState.sortDir,
        }).then(function(res) {
            var data = res && res.success ? res.data : null;
            if (!data) return;
            renderStockSummary(data.summary || {});
            renderStockTable(data.products || []);
            renderStockPagination(data.total_pages || 1, data.total || 0);
        });
    }

    function renderStockSummary(summary) {
        document.querySelectorAll('[data-stock-stat]').forEach(function(el) {
            const key = el.getAttribute('data-stock-stat');
            el.textContent = summary[key] ?? 0;
        });
    }

    function renderStockTable(products) {
        const tbody = document.getElementById('ph-stock-tbody');
        const empty = document.getElementById('ph-stock-empty');
        const table = document.getElementById('ph-stock-table');
        stockState.selected.clear();
        updateBulkBar();

        if (!products.length) {
            tbody.innerHTML = '';
            if (empty) empty.hidden = false;
            if (table) table.hidden = true;
            return;
        }
        if (empty) empty.hidden = true;
        if (table) table.hidden = false;

        tbody.innerHTML = products.map(function(p) {
            const statusClass = { out: 'ph-stock-badge--out', low: 'ph-stock-badge--low', ok: 'ph-stock-badge--ok' }[p.status] || '';
            const statusLabel = { out: i18n.stock_out_short || 'Uitverkocht', low: i18n.stock_low_short || 'Laag', ok: i18n.stock_ok_short || 'In stock' }[p.status] || p.status;
            const stockVal   = p.stock === null || p.stock === undefined ? '–' : p.stock;
            const priceVal   = p.price ? '€' + parseFloat(p.price).toFixed(2) : '–';
            const valueVal   = p.stock_value ? '€' + parseFloat(p.stock_value).toFixed(2) : '–';
            const qtyClass   = p.status === 'out' ? 'ph-stock-qty--danger' : p.status === 'low' ? 'ph-stock-qty--warning' : '';
            return '<tr data-id="' + esc(p.id) + '">' +
                '<td><input type="checkbox" class="ph-stock-row-check" data-id="' + esc(p.id) + '"></td>' +
                '<td><a href="' + esc(p.edit_url) + '" target="_blank" class="ph-stock-product-link">' + esc(p.name) + '</a></td>' +
                '<td class="ph-stock-sku">' + esc(p.sku || '–') + '</td>' +
                '<td class="ph-stock-qty ' + qtyClass + '">' + esc(stockVal) + '</td>' +
                '<td><span class="ph-stock-badge ' + statusClass + '">' + esc(statusLabel) + '</span></td>' +
                '<td>' + esc(priceVal) + '</td>' +
                '<td>' + esc(valueVal) + '</td>' +
                '<td><button class="ph-btn ph-btn-sm ph-stock-edit-btn" data-id="' + esc(p.id) + '" data-name="' + esc(p.name) + '" data-qty="' + esc(stockVal) + '">' + (i18n.stock_edit_btn || 'Update') + '</button></td>' +
            '</tr>';
        }).join('');

        // Row checkboxes
        tbody.querySelectorAll('.ph-stock-row-check').forEach(function(cb) {
            cb.addEventListener('change', function() {
                const id = this.getAttribute('data-id');
                if (this.checked) stockState.selected.add(id);
                else stockState.selected.delete(id);
                updateBulkBar();
                syncSelectAll();
            });
        });

        // Edit buttons
        tbody.querySelectorAll('.ph-stock-edit-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                openStockTabEditModal(
                    this.getAttribute('data-id'),
                    this.getAttribute('data-name'),
                    this.getAttribute('data-qty')
                );
            });
        });
    }

    function renderStockPagination(totalPages, total) {
        const el = document.getElementById('ph-stock-pagination');
        if (!el) return;
        stockState.totalPages = totalPages;
        if (totalPages <= 1) { el.innerHTML = ''; return; }
        let html = '<div class="ph-stock-pages">';
        html += '<span class="ph-stock-total">' + total + (i18n.stock_total_label || ' products') + '</span>';
        for (let i = 1; i <= totalPages; i++) {
            html += '<button class="ph-stock-page-btn ' + (i === stockState.page ? 'is-active' : '') + '" data-page="' + i + '">' + i + '</button>';
        }
        html += '</div>';
        el.innerHTML = html;
        el.querySelectorAll('.ph-stock-page-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                stockState.page = parseInt(this.getAttribute('data-page'), 10);
                loadStock();
            });
        });
    }

    function updateBulkBar() {
        const bar = document.getElementById('ph-stock-bulk');
        const count = document.getElementById('ph-stock-selected-count');
        if (!bar) return;
        const n = stockState.selected.size;
        bar.hidden = n === 0;
        if (count) count.textContent = n + (i18n.stock_n_selected || ' selected');
    }

    function syncSelectAll() {
        const all = document.getElementById('ph-stock-select-all');
        if (!all) return;
        const checks = document.querySelectorAll('#ph-stock-tbody .ph-stock-row-check');
        const checked = document.querySelectorAll('#ph-stock-tbody .ph-stock-row-check:checked');
        all.checked = checks.length > 0 && checks.length === checked.length;
        all.indeterminate = checked.length > 0 && checked.length < checks.length;
    }

    function openStockTabEditModal(id, name, qty) {
        const backdrop = document.getElementById('ph-stock-edit-backdrop');
        const titleEl  = document.getElementById('ph-stock-modal-title');
        const idEl     = document.getElementById('ph-stock-edit-id');
        const qtyEl    = document.getElementById('ph-stock-edit-qty');
        const reasonEl = document.getElementById('ph-stock-edit-reason');
        if (!backdrop) return;
        if (titleEl)  titleEl.textContent = name ? (i18n.stock_modal_title_prefix || 'Stock: ') + name : (i18n.stock_modal_title_default || 'Update stock');
        if (idEl)     idEl.value = id;
        if (qtyEl)    { qtyEl.value = qty === '–' ? '' : qty; }
        if (reasonEl) reasonEl.value = '';
        backdrop.hidden = false;
        if (qtyEl) qtyEl.focus();
    }

    function closeStockTabEditModal() {
        const backdrop = document.getElementById('ph-stock-edit-backdrop');
        if (backdrop) backdrop.hidden = true;
    }

    function saveStockTabEdit() {
        const idEl     = document.getElementById('ph-stock-edit-id');
        const qtyEl    = document.getElementById('ph-stock-edit-qty');
        const reasonEl = document.getElementById('ph-stock-edit-reason');
        if (!idEl || !qtyEl) return;
        const updates = [{ id: idEl.value, qty: parseInt(qtyEl.value, 10), reason: reasonEl ? reasonEl.value : '' }];
        ajax('ph_stock_update', { updates: JSON.stringify(updates) }).then(function(res) {
            var data = res && res.success ? res.data : null;
            closeStockTabEditModal();
            stockAlert(data && data.message ? data.message : (i18n.stock_updated || 'Stock updated.'), 'success');
            loadStock();
        });
    }

    function initStockTab() {
        // Filter buttons
        document.querySelectorAll('.ph-stock-filter').forEach(function(btn) {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.ph-stock-filter').forEach(function(b) { b.classList.remove('is-active'); });
                this.classList.add('is-active');
                stockState.status = this.getAttribute('data-status');
                stockState.page = 1;
                loadStock();
            });
        });

        // Search
        var searchInput = document.getElementById('ph-stock-search');
        if (searchInput) {
            var searchTimer;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(function() {
                    stockState.search = searchInput.value;
                    stockState.page = 1;
                    loadStock();
                }, 350);
            });
        }

        // Sort
        document.querySelectorAll('.ph-stock-sortable').forEach(function(th) {
            th.addEventListener('click', function() {
                var col = this.getAttribute('data-col');
                if (stockState.sortCol === col) {
                    stockState.sortDir = stockState.sortDir === 'asc' ? 'desc' : 'asc';
                } else {
                    stockState.sortCol = col;
                    stockState.sortDir = 'asc';
                }
                document.querySelectorAll('.ph-stock-sortable').forEach(function(t) { t.classList.remove('is-sorted'); });
                this.classList.add('is-sorted');
                stockState.page = 1;
                loadStock();
            });
        });

        // Select all
        var selectAll = document.getElementById('ph-stock-select-all');
        if (selectAll) {
            selectAll.addEventListener('change', function() {
                document.querySelectorAll('#ph-stock-tbody .ph-stock-row-check').forEach(function(cb) {
                    cb.checked = selectAll.checked;
                    var id = cb.getAttribute('data-id');
                    if (selectAll.checked) stockState.selected.add(id);
                    else stockState.selected.delete(id);
                });
                updateBulkBar();
            });
        }

        // Bulk update
        var bulkBtn = document.getElementById('ph-stock-bulk-update');
        if (bulkBtn) {
            bulkBtn.addEventListener('click', function() {
                var qty = prompt((i18n.stock_bulk_prompt || 'New stock for %s products:').replace('%s', stockState.selected.size));
                if (qty === null || qty === '') return;
                var updates = Array.from(stockState.selected).map(function(id) {
                    return { id: id, qty: parseInt(qty, 10), reason: i18n.stock_bulk_reason || 'Bulk update' };
                });
                ajax('ph_stock_update', { updates: JSON.stringify(updates) }).then(function(res) {
                    var data = res && res.success ? res.data : null;
                    stockAlert(data && data.message ? data.message : (i18n.stock_updated || 'Stock updated.'), 'success');
                    stockState.selected.clear();
                    updateBulkBar();
                    loadStock();
                });
            });
        }

        // CSV export
        var exportBtn = document.getElementById('ph-stock-export-btn');
        if (exportBtn) {
            exportBtn.addEventListener('click', function() {
                var url = window.ph_admin.ajax_url + '?action=ph_stock_export_csv&nonce=' + window.ph_admin.nonce +
                    '&status=' + encodeURIComponent(stockState.status) +
                    '&search=' + encodeURIComponent(stockState.search);
                window.location.href = url;
            });
        }

        // Settings panel open/close
        var settingsBtn     = document.getElementById('ph-stock-settings-btn');
        var settingsOverlay = document.getElementById('ph-stock-settings-overlay');
        var settingsPanel   = document.getElementById('ph-stock-settings-panel');
        var settingsClose   = document.getElementById('ph-stock-settings-close');

        function openStockSettings() {
            if (settingsPanel)   { settingsPanel.hidden = false; }
            if (settingsOverlay) { settingsOverlay.hidden = false; }
        }
        function closeStockSettings() {
            if (settingsPanel)   { settingsPanel.hidden = true; }
            if (settingsOverlay) { settingsOverlay.hidden = true; }
        }

        if (settingsBtn)     settingsBtn.addEventListener('click', openStockSettings);
        if (settingsClose)   settingsClose.addEventListener('click', closeStockSettings);
        if (settingsOverlay) settingsOverlay.addEventListener('click', closeStockSettings);

        // Save settings
        var saveSettingsBtn = document.getElementById('ph-stock-save-settings');
        if (saveSettingsBtn) {
            saveSettingsBtn.addEventListener('click', function() {
                var settings = {
                    low_stock_threshold: document.getElementById('ph-set-threshold') ? document.getElementById('ph-set-threshold').value : 5,
                    alert_email:         document.getElementById('ph-set-email')     ? document.getElementById('ph-set-email').value     : '',
                    realtime_alerts:     document.getElementById('ph-set-realtime') && document.getElementById('ph-set-realtime').checked  ? '1' : '0',
                    daily_digest:        document.getElementById('ph-set-daily')    && document.getElementById('ph-set-daily').checked     ? '1' : '0',
                    alert_statuses:      [],
                };
                if (document.getElementById('ph-set-alert-out') && document.getElementById('ph-set-alert-out').checked)  settings.alert_statuses.push('out');
                if (document.getElementById('ph-set-alert-low') && document.getElementById('ph-set-alert-low').checked)  settings.alert_statuses.push('low');
                ajax('ph_stock_save_settings', { settings: JSON.stringify(settings) }).then(function(res) {
                    var data = res && res.success ? res.data : null;
                    closeStockSettings();
                    stockAlert(data && data.message ? data.message : (i18n.stock_saved || 'Settings saved.'), 'success');
                });
            });
        }

        // Test alert
        var testAlertBtn = document.getElementById('ph-stock-test-alert');
        if (testAlertBtn) {
            testAlertBtn.addEventListener('click', function() {
                testAlertBtn.disabled = true;
                testAlertBtn.textContent = 'Versturen…';
                ajax('ph_stock_send_test_alert', {}).then(function(res) {
                    var data = res && res.success ? res.data : null;
                    testAlertBtn.disabled = false;
                    testAlertBtn.textContent = i18n.send_test_alert || 'Send test alert';
                    stockAlert(data && data.message ? data.message : (i18n.stock_test_sent || 'Test alert sent.'), 'success');
                });
            });
        }

        // Edit modal close buttons
        var editBackdrop    = document.getElementById('ph-stock-edit-backdrop');
        var modalClose      = document.getElementById('ph-stock-modal-close');
        var modalCancel     = document.getElementById('ph-stock-modal-cancel');
        var modalSave       = document.getElementById('ph-stock-modal-save');

        if (modalClose)  modalClose.addEventListener('click', closeStockTabEditModal);
        if (modalCancel) modalCancel.addEventListener('click', closeStockTabEditModal);
        if (editBackdrop) {
            editBackdrop.addEventListener('click', function(e) {
                if (e.target === editBackdrop) closeStockTabEditModal();
            });
        }
        if (modalSave) modalSave.addEventListener('click', saveStockTabEdit);

        // Enter key in edit modal
        var editQty = document.getElementById('ph-stock-edit-qty');
        if (editQty) {
            editQty.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') saveStockTabEdit();
            });
        }
    }

    // Toast for stock tab
    function stockAlert(message, type) {
        var container = document.getElementById('ph-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'ph-toast-container';
            container.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:99999;display:flex;flex-direction:column;gap:8px;';
            document.body.appendChild(container);
        }
        var toast = document.createElement('div');
        toast.className = 'ph-toast ph-toast--' + (type || 'info');
        toast.style.cssText = 'background:#1E293B;color:#fff;padding:12px 20px;border-radius:8px;font-size:13px;font-weight:600;opacity:0;transition:opacity .25s;min-width:220px;box-shadow:0 4px 20px rgba(0,0,0,.25)';
        toast.textContent = message;
        container.appendChild(toast);
        requestAnimationFrame(function() { toast.style.opacity = '1'; });
        setTimeout(function() {
            toast.style.opacity = '0';
            setTimeout(function() { toast.remove(); }, 300);
        }, 3500);
    }

    // Init stock tab UI on page load (events don't depend on data)
    // NOTE: Gets called from DOMContentLoaded below

/*
       Utility
*/
    function esc(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

/*
       QUICK PRODUCTS MODULE
       Fully integrated into Product Haven — no external dependencies
*/
    const opQp = {
        currentPage: 1,
        searchTimer: null,
        selectedTags: {},
        mainMediaFrame: null,
        galleryFrame: null,
    };

    function opQpAjax(action, data = {}) {
        const body = new URLSearchParams({ action, nonce, ...data });
        return fetch(ajax_url, { method: 'POST', body }).then(r => r.json());
    }

    /* ── Sub-tabs ─────────────────────────────────────────────── */
    function opQpInitTabs() {
        document.querySelectorAll('.ph-qp-tab').forEach(btn => {
            btn.addEventListener('click', () => {
                const target = btn.dataset.qptab;
                document.querySelectorAll('.ph-qp-tab').forEach(b => b.classList.remove('is-active'));
                document.querySelectorAll('.ph-qp-panel').forEach(p => p.classList.remove('is-active'));
                btn.classList.add('is-active');
                document.getElementById('ph-qp-panel-' + target)?.classList.add('is-active');
            });
        });
    }

    function opQpSwitchTab(tab) {
        document.querySelector(`.ph-qp-tab[data-qptab="${tab}"]`)?.click();
    }

    /* ── Load Productlist ────────────────────────────────── */
    function opQpLoadProducts(page = 1) {
        opQp.currentPage = page;
        const wrap = document.getElementById('ph-qp-table-wrap');
        if (!wrap) return;
        wrap.innerHTML = '<div class="ph-loading-spinner" style="margin:40px auto;"></div>';

        opQpAjax('ph_qp_get_products', {
            page,
            per_page     : 20,
            search       : document.getElementById('ph-qp-search')?.value || '',
            status       : document.getElementById('ph-qp-filter-status')?.value || 'any',
            cat_id       : document.getElementById('ph-qp-filter-cat')?.value || 0,
            product_type : document.getElementById('ph-qp-filter-type')?.value || '',
            orderby      : document.getElementById('ph-qp-filter-orderby')?.value || 'date',
        }).then(resp => {
            if (!resp.success) {
                wrap.innerHTML = '<p style="padding:20px;color:#EF4444">' + (i18n.qp_load_error || 'Error loading.') + '</p>';
                return;
            }
            opQpRenderTable(resp.data);
        });
    }

    function opQpRenderTable(data) {
        const { products, total, pages, page } = data;
        const wrap = document.getElementById('ph-qp-table-wrap');
        const pag  = document.getElementById('ph-qp-pagination');
        const sym  = window.ph_admin?.currency || '€';

        const countEl = document.getElementById('ph-qp-total-count');
        if (countEl) countEl.textContent = total;

        if (!products.length) {
            wrap.innerHTML = '<p style="padding:28px;text-align:center;color:#94A3B8">' + (i18n.qp_no_products || 'No products found.') + '</p>';
            if (pag) pag.innerHTML = '';
            return;
        }

        const statusBadge = { publish: 'publish', draft: 'draft', private: 'private', pending: 'pending' };
        const statusLabel = {
            publish: i18n.qp_published || 'Published',
            draft:   i18n.qp_draft     || 'Draft',
            private: i18n.qp_private   || 'Private',
            pending: i18n.qp_pending   || 'Pending',
        };

        const rows = products.map(p => {
            const img = p.image
                ? `<img src="${esc(p.image)}" class="ph-qp-tbl-img" alt="">`
                : `<div class="ph-qp-tbl-img-placeholder"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg></div>`;

            const stockHtml = p.manage_stock
                ? `<input type="number" class="ph-qp-inline-edit ph-qp-stock-inline" data-id="${p.id}" data-field="stock_quantity" value="${p.stock_qty ?? 0}" min="0" step="1">`
                : `<span class="ph-qp-stock-${p.stock_status}">${opQpStockLabel(p.stock_status)}</span>`;

            return `<tr data-id="${p.id}">
                <td style="width:50px">${img}</td>
                <td>
                    <div class="ph-qp-tbl-name">${esc(p.name)}</div>
                    ${p.sku ? `<div class="ph-qp-tbl-sku">SKU: ${esc(p.sku)}</div>` : ''}
                    ${p.categories ? `<div class="ph-qp-tbl-sku">${esc(p.categories)}</div>` : ''}
                </td>
                <td><span class="ph-qp-badge ph-qp-badge-${statusBadge[p.status] || 'draft'}">${statusLabel[p.status] || p.status}</span></td>
                <td>
                    <input type="number" class="ph-qp-inline-edit ph-qp-price-inline" data-id="${p.id}" data-field="regular_price"
                           value="${p.regular_price}" step="0.01" min="0">
                    ${p.sale_price ? `<div class="ph-qp-tbl-sku" style="color:#10B981">${i18n.qp_sale_prefix || 'Aanbieding: '}${sym}${Number(p.sale_price).toFixed(2)}</div>` : ''}
                </td>
                <td>${stockHtml}</td>
                <td>
                    <div class="ph-qp-tbl-actions">
                        <button class="ph-qp-tbl-btn ph-qp-edit-btn" data-id="${p.id}" title="${i18n.qp_edit_title || 'Update'}" data-tooltip="${i18n.qp_edit_title || 'Update'}">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        </button>
                        <button class="ph-qp-tbl-btn ph-qp-dup-btn" data-id="${p.id}" title="${i18n.qp_duplicate_title || 'Duplicate'}" data-tooltip="${i18n.qp_duplicate_title || 'Duplicate'}">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                        </button>
                        <a class="ph-qp-tbl-btn" href="${esc(p.edit_url)}" target="_blank" title="${i18n.qp_wc_edit_title || 'Update in WC'}" data-tooltip="${i18n.qp_wc_edit_title || 'Update in WC'}">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                        </a>
                        <button class="ph-qp-tbl-btn ph-qp-tbl-danger ph-qp-del-btn" data-id="${p.id}" title="${i18n.qp_delete_title || 'Delete'}" data-tooltip="${i18n.qp_delete_title || 'Delete'}">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                        </button>
                    </div>
                </td>
            </tr>`;
        }).join('');

        wrap.innerHTML = `
        <table class="ph-qp-table">
            <thead>
                <tr>
                    <th></th>
                    <th>${i18n.qp_col_product || 'Product'}</th>
                    <th>${i18n.qp_col_status || 'Status'}</th>
                    <th>${i18n.qp_col_price || 'Price'} (${sym})</th>
                    <th>${i18n.qp_col_stock || 'Stock'}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>${rows}</tbody>
        </table>`;

        opQpRenderPagination(page, pages);
        opQpBindTableEvents();
    }

    function opQpStockLabel(s) {
        return {
            instock:     i18n.qp_instock     || 'In stock',
            outofstock:  i18n.qp_outofstock  || 'Out of stock',
            onbackorder: i18n.qp_onbackorder || 'On backorder',
        }[s] || s;
    }

    function opQpRenderPagination(current, total) {
        const pag = document.getElementById('ph-qp-pagination');
        if (!pag) return;
        if (total <= 1) { pag.innerHTML = ''; return; }

        let html = `<button class="ph-qp-page-btn" ${current === 1 ? 'disabled' : ''} data-page="${current - 1}">‹</button>`;
        for (let i = 1; i <= total; i++) {
            if (i === 1 || i === total || Math.abs(i - current) <= 2) {
                html += `<button class="ph-qp-page-btn ${i === current ? 'active' : ''}" data-page="${i}">${i}</button>`;
            } else if (Math.abs(i - current) === 3) {
                html += `<span style="padding:0 4px;color:#94A3B8">…</span>`;
            }
        }
        html += `<button class="ph-qp-page-btn" ${current === total ? 'disabled' : ''} data-page="${current + 1}">›</button>`;
        pag.innerHTML = html;

        pag.querySelectorAll('.ph-qp-page-btn:not(:disabled)').forEach(btn => {
            btn.addEventListener('click', () => opQpLoadProducts(parseInt(btn.dataset.page)));
        });
    }

    function opQpBindTableEvents() {
        document.querySelectorAll('.ph-qp-edit-btn').forEach(btn => {
            btn.addEventListener('click', () => opQpOpenEditor(parseInt(btn.dataset.id)));
        });

        document.querySelectorAll('.ph-qp-del-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                if (!confirm(i18n.qp_delete_confirm || 'Delete product?')) return;
                opQpAjax('ph_qp_delete_product', { product_id: btn.dataset.id }).then(r => {
                    if (r.success) { opQpToast(i18n.qp_deleted || 'Product deleted.'); opQpLoadProducts(opQp.currentPage); }
                    else opQpToast(r.data?.message || (i18n.qp_update_error || 'Error.'), 'error');
                });
            });
        });

        document.querySelectorAll('.ph-qp-dup-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                opQpAjax('ph_qp_duplicate_product', { product_id: btn.dataset.id }).then(r => {
                    if (r.success) { opQpToast((i18n.qp_duplicated_prefix || 'Duplicated: ') + r.data.name); opQpLoadProducts(opQp.currentPage); }
                    else opQpToast(r.data?.message || (i18n.qp_update_error || 'Error.'), 'error');
                });
            });
        });

        document.querySelectorAll('.ph-qp-price-inline, .ph-qp-stock-inline').forEach(input => {
            input.addEventListener('change', () => {
                opQpAjax('ph_qp_quick_edit', {
                    product_id : input.dataset.id,
                    field      : input.dataset.field,
                    value      : input.value,
                }).then(r => {
                    if (r.success) opQpToast(i18n.qp_updated || 'Updated.');
                    else opQpToast(i18n.qp_update_error || 'Error saving.', 'error');
                });
            });
        });
    }

    /* ── Filter events ────────────────────────────────────────── */
    function opQpInitListEvents() {
        document.getElementById('ph-qp-search')?.addEventListener('input', () => {
            clearTimeout(opQp.searchTimer);
            opQp.searchTimer = setTimeout(() => opQpLoadProducts(1), 350);
        });

        ['ph-qp-filter-status', 'ph-qp-filter-cat', 'ph-qp-filter-type', 'ph-qp-filter-orderby'].forEach(id => {
            document.getElementById(id)?.addEventListener('change', () => opQpLoadProducts(1));
        });

        document.getElementById('ph-qp-new-btn')?.addEventListener('click', () => {
            opQpResetEditor();
            opQpSwitchTab('editor');
        });
    }

    /* ── Editor ───────────────────────────────────────────────── */
    function opQpResetEditor() {
        document.getElementById('ph-qp-product-form')?.reset();
        document.getElementById('ph-qp-product-id').value = '0';
        document.getElementById('ph-qp-editor-tab-label').textContent = i18n.qp_new_product_label || 'Nieuw product';
        document.getElementById('ph-qp-wc-edit-link')?.classList.add('ph-qp-hidden');
        document.getElementById('ph-qp-image-id').value = '0';
        document.getElementById('ph-qp-image-preview')?.classList.add('ph-qp-hidden');
        document.getElementById('ph-qp-image-placeholder')?.classList.remove('ph-qp-hidden');
        document.getElementById('ph-qp-remove-image')?.classList.add('ph-qp-hidden');
        document.getElementById('ph-qp-gallery-grid').innerHTML = '';
        document.getElementById('ph-qp-gallery-ids').value = '';
        document.getElementById('ph-qp-tags-selected').innerHTML = '';
        document.getElementById('ph-qp-tag-ids').value = '';
        document.querySelectorAll('.ph-qp-cat-check').forEach(c => c.checked = false);
        document.querySelectorAll('.ph-qp-brand-check').forEach(c => c.checked = false);
        document.querySelectorAll('.ph-qp-attr-check').forEach(c => c.checked = false);
        document.getElementById('ph-qp-stock-fields')?.classList.add('ph-qp-hidden');
        opQp.selectedTags = {};
    }

    function opQpOpenEditor(productId) {
        opQpResetEditor();
        opQpSwitchTab('editor');
        document.getElementById('ph-qp-editor-tab-label').textContent = i18n.qp_loading_label || 'Loading…';

        opQpAjax('ph_qp_load_product', { product_id: productId }).then(resp => {
            if (!resp.success) { opQpToast(i18n.qp_not_found || 'Product not found.', 'error'); return; }
            opQpFillEditor(resp.data);
        });
    }

    function opQpFillEditor(d) {
        const set = (id, val) => { const el = document.getElementById(id); if (el) el.value = val ?? ''; };

        document.getElementById('ph-qp-product-id').value = d.product_id;
        document.getElementById('ph-qp-editor-tab-label').textContent = d.name || 'Editor';

        set('ph-qp-name',          d.name);
        set('ph-qp-sku',           d.sku);
        set('ph-qp-slug',          d.slug);
        set('ph-qp-status',        d.status);
        set('ph-qp-product-type',  d.product_type);
        set('ph-qp-desc',          d.description);
        set('ph-qp-short-desc',    d.short_description);
        set('ph-qp-regular-price', d.regular_price);
        set('ph-qp-sale-price',    d.sale_price);
        set('ph-qp-sale-from',     d.sale_price_dates_from);
        set('ph-qp-sale-to',       d.sale_price_dates_to);
        set('ph-qp-stock-status',  d.stock_status);
        set('ph-qp-backorders',    d.backorders);
        set('ph-qp-stock-qty',     d.stock_quantity);
        set('ph-qp-low-stock',     d.low_stock_amount);
        set('ph-qp-weight',        d.weight);
        set('ph-qp-tax-status',    d.tax_status);
        set('ph-qp-tax-class',     d.tax_class);
        set('ph-qp-shipping-class',d.shipping_class);
        set('ph-qp-visibility',    d.catalog_visibility);

        ['length', 'width', 'height'].forEach(dim => {
            const el = document.querySelector(`#ph-qp-product-form [name="${dim}"]`);
            if (el) el.value = d[dim] ?? '';
        });

        const chk = (id, val) => { const el = document.getElementById(id); if (el) el.checked = val === '1' || val === true; };
        chk('ph-qp-manage-stock',     d.manage_stock);
        chk('ph-qp-virtual',          d.virtual);
        chk('ph-qp-downloadable',     d.downloadable);
        chk('ph-qp-featured',         d.featured);
        chk('ph-qp-sold-individually',d.sold_individually);

        document.getElementById('ph-qp-stock-fields')?.classList.toggle('ph-qp-hidden', d.manage_stock !== '1');

        if (d.image_url) {
            document.getElementById('ph-qp-image-id').value = d.image_id;
            const preview = document.getElementById('ph-qp-image-preview');
            if (preview) { preview.src = d.image_url; preview.classList.remove('ph-qp-hidden'); }
            document.getElementById('ph-qp-image-placeholder')?.classList.add('ph-qp-hidden');
            document.getElementById('ph-qp-remove-image')?.classList.remove('ph-qp-hidden');
        }

        const galleryGrid = document.getElementById('ph-qp-gallery-grid');
        galleryGrid.innerHTML = '';
        const galleryIds = [];
        (d.gallery || []).forEach(img => {
            galleryIds.push(img.id);
            galleryGrid.insertAdjacentHTML('beforeend', opQpGalleryItemHtml(img.id, img.url));
        });
        document.getElementById('ph-qp-gallery-ids').value = galleryIds.join(',');
        opQpBindGalleryRemove();

        const catIds = (d.categories || []).map(String);
        document.querySelectorAll('.ph-qp-cat-check').forEach(c => {
            c.checked = catIds.includes(String(c.value));
        });

        const brandIds = (d.brands || []).map(String);
        document.querySelectorAll('.ph-qp-brand-check').forEach(c => {
            c.checked = brandIds.includes(String(c.value));
        });

        const selectedAttrs = d.attributes || {};
        document.querySelectorAll('.ph-qp-attr-check').forEach(c => {
            const attrName = c.dataset.attr;
            const termIds  = (selectedAttrs[attrName] || []).map(String);
            c.checked = termIds.includes(String(c.value));
        });

        // Tags
        opQp.selectedTags = {};
        document.getElementById('ph-qp-tags-selected').innerHTML = '';
        document.getElementById('ph-qp-tag-ids').value = '';
        const tagList = document.querySelectorAll('#ph-qp-tags-list option');
        const tagMap  = {};
        tagList.forEach(o => { tagMap[o.dataset.id] = o.value; });
        (d.tags || []).forEach(id => {
            const name = tagMap[id];
            if (name) opQpAddTagChip(name, id);
        });

        const editLink = document.getElementById('ph-qp-wc-edit-link');
        if (editLink && d.edit_url) {
            editLink.href = d.edit_url;
            editLink.classList.remove('ph-qp-hidden');
        }
    }

    /* ── Save form ────────────────────────────────────── */
    function opQpInitEditorForm() {
        const form = document.getElementById('ph-qp-product-form');
        if (!form) return;

        form.addEventListener('submit', e => {
            e.preventDefault();
            const name = document.getElementById('ph-qp-name')?.value.trim();
            if (!name) { opQpToast(i18n.qp_name_required || 'Productname is obliged.', 'error'); return; }

            const btn = document.getElementById('ph-qp-save-btn');
            btn.disabled    = true;
            btn.textContent = i18n.qp_saving || 'Saving…';

            const catIds   = [...document.querySelectorAll('.ph-qp-cat-check:checked')].map(c => c.value);
            const brandIds = [...document.querySelectorAll('.ph-qp-brand-check:checked')].map(c => c.value);

            const attrData = {};
            document.querySelectorAll('.ph-qp-attr-check:checked').forEach(c => {
                const key = 'attr_' + c.dataset.attr + '[]';
                if (!attrData[key]) attrData[key] = [];
                attrData[key].push(c.value);
            });

            const tagIds = document.getElementById('ph-qp-tag-ids').value;
            const tagArr = tagIds ? tagIds.split(',').filter(Boolean) : [];
            const galleryIds = document.getElementById('ph-qp-gallery-ids').value;
            const galArr     = galleryIds ? galleryIds.split(',').filter(Boolean) : [];

            const data = {
                product_id           : document.getElementById('ph-qp-product-id').value,
                name,
                sku                  : document.getElementById('ph-qp-sku')?.value,
                slug                 : document.getElementById('ph-qp-slug')?.value,
                status               : document.getElementById('ph-qp-status')?.value,
                product_type         : document.getElementById('ph-qp-product-type')?.value,
                description          : document.getElementById('ph-qp-desc')?.value,
                short_description    : document.getElementById('ph-qp-short-desc')?.value,
                regular_price        : document.getElementById('ph-qp-regular-price')?.value,
                sale_price           : document.getElementById('ph-qp-sale-price')?.value,
                sale_price_dates_from: document.getElementById('ph-qp-sale-from')?.value,
                sale_price_dates_to  : document.getElementById('ph-qp-sale-to')?.value,
                manage_stock         : document.getElementById('ph-qp-manage-stock')?.checked ? '1' : '0',
                stock_quantity       : document.getElementById('ph-qp-stock-qty')?.value,
                stock_status         : document.getElementById('ph-qp-stock-status')?.value,
                backorders           : document.getElementById('ph-qp-backorders')?.value,
                low_stock_amount     : document.getElementById('ph-qp-low-stock')?.value,
                weight               : document.querySelector('#ph-qp-product-form [name="weight"]')?.value,
                length               : document.querySelector('#ph-qp-product-form [name="length"]')?.value,
                width                : document.querySelector('#ph-qp-product-form [name="width"]')?.value,
                height               : document.querySelector('#ph-qp-product-form [name="height"]')?.value,
                virtual              : document.getElementById('ph-qp-virtual')?.checked ? '1' : '0',
                downloadable         : document.getElementById('ph-qp-downloadable')?.checked ? '1' : '0',
                shipping_class       : document.getElementById('ph-qp-shipping-class')?.value,
                tax_status           : document.getElementById('ph-qp-tax-status')?.value,
                tax_class            : document.getElementById('ph-qp-tax-class')?.value,
                catalog_visibility   : document.getElementById('ph-qp-visibility')?.value,
                featured             : document.getElementById('ph-qp-featured')?.checked ? '1' : '0',
                sold_individually    : document.getElementById('ph-qp-sold-individually')?.checked ? '1' : '0',
                image_id             : document.getElementById('ph-qp-image-id')?.value,
                'categories[]'       : catIds,
                'brands[]'           : brandIds,
                'tags[]'             : tagArr,
                'gallery_ids[]'      : galArr,
                ...attrData,
            };

            const body = new URLSearchParams();
            body.append('action', 'ph_qp_save_product');
            body.append('nonce', nonce);
            Object.entries(data).forEach(([k, v]) => {
                if (Array.isArray(v)) v.forEach(val => body.append(k, val));
                else body.append(k, v ?? '');
            });

            fetch(ajax_url, { method: 'POST', body })
                .then(r => r.json())
                .then(resp => {
                    btn.disabled = false;
                    btn.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> ${i18n.qp_save_btn || 'Opslaan'}`;

                    if (!resp.success) { opQpToast(resp.data?.message || (i18n.qp_save_error || 'Error saving.'), 'error'); return; }

                    const d = resp.data;
                    document.getElementById('ph-qp-product-id').value = d.product_id;
                    document.getElementById('ph-qp-editor-tab-label').textContent = d.name;

                    const editLink = document.getElementById('ph-qp-wc-edit-link');
                    if (editLink && d.edit_url) { editLink.href = d.edit_url; editLink.classList.remove('ph-qp-hidden'); }

                    opQpToast(i18n.qp_saved || 'Saved!', 'success');
                })
                .catch(() => {
                    btn.disabled    = false;
                    btn.textContent = i18n.qp_save_btn || 'Save';
                    opQpToast(i18n.qp_save_error || 'Error saving.', 'error');
                });
        });

        document.getElementById('ph-qp-manage-stock')?.addEventListener('change', e => {
            document.getElementById('ph-qp-stock-fields')?.classList.toggle('ph-qp-hidden', !e.target.checked);
        });

        document.getElementById('ph-qp-virtual')?.addEventListener('change', e => {
            document.getElementById('ph-qp-shipping-fields')?.classList.toggle('ph-qp-hidden', e.target.checked);
        });
    }

    /* ── Media uploader — main image ─────────────────────── */
    function opQpInitImageUploader() {
        const box         = document.getElementById('ph-qp-image-box');
        const selectBtn   = document.getElementById('ph-qp-select-image');
        const removeBtn   = document.getElementById('ph-qp-remove-image');
        const imageIdInput= document.getElementById('ph-qp-image-id');
        const preview     = document.getElementById('ph-qp-image-preview');
        const placeholder = document.getElementById('ph-qp-image-placeholder');

        function openFrame() {
            if (!window.wp?.media) { alert(i18n.qp_media_unavailable || 'WordPress Media is not available.'); return; }
            if (opQp.mainMediaFrame) { opQp.mainMediaFrame.open(); return; }
            opQp.mainMediaFrame = wp.media({
                title   : i18n.qp_media_title || 'Set product image',
                button  : { text: i18n.qp_media_btn || 'Set as image' },
                multiple: false,
                library : { type: 'image' },
            });
            opQp.mainMediaFrame.on('select', () => {
                const att = opQp.mainMediaFrame.state().get('selection').first().toJSON();
                imageIdInput.value = att.id;
                preview.src = att.url;
                preview.classList.remove('ph-qp-hidden');
                placeholder?.classList.add('ph-qp-hidden');
                removeBtn?.classList.remove('ph-qp-hidden');
            });
            opQp.mainMediaFrame.open();
        }

        box?.addEventListener('click', openFrame);
        selectBtn?.addEventListener('click', openFrame);

        removeBtn?.addEventListener('click', () => {
            imageIdInput.value = '0';
            preview.src = '';
            preview.classList.add('ph-qp-hidden');
            placeholder?.classList.remove('ph-qp-hidden');
            removeBtn.classList.add('ph-qp-hidden');
        });
    }

    /* ── Media uploader — gallery ─────────────────────────────── */
    function opQpInitGalleryUploader() {
        document.getElementById('ph-qp-add-gallery')?.addEventListener('click', () => {
            if (!window.wp?.media) return;
            if (opQp.galleryFrame) { opQp.galleryFrame.open(); return; }
            opQp.galleryFrame = wp.media({
                title   : i18n.qp_gallery_title || 'Select gallery images',
                button  : { text: i18n.qp_gallery_btn || 'Add to gallery' },
                multiple: true,
                library : { type: 'image' },
            });
            opQp.galleryFrame.on('select', () => {
                const selection = opQp.galleryFrame.state().get('selection');
                const grid      = document.getElementById('ph-qp-gallery-grid');
                const idsInput  = document.getElementById('ph-qp-gallery-ids');
                const existing  = idsInput.value ? idsInput.value.split(',').filter(Boolean) : [];

                selection.each(att => {
                    const id = String(att.id);
                    if (!existing.includes(id)) {
                        existing.push(id);
                        grid.insertAdjacentHTML('beforeend', opQpGalleryItemHtml(id, att.attributes.url));
                    }
                });

                idsInput.value = existing.join(',');
                opQpBindGalleryRemove();
            });
            opQp.galleryFrame.open();
        });
    }

    function opQpGalleryItemHtml(id, url) {
        return `<div class="ph-qp-gallery-item" data-id="${id}">
            <img src="${esc(url)}" alt="">
            <button type="button" class="ph-qp-gallery-remove" data-id="${id}" title="${i18n.qp_remove_gallery_title || 'Remove from gallery'}">×</button>
        </div>`;
    }

    function opQpBindGalleryRemove() {
        document.querySelectorAll('.ph-qp-gallery-remove').forEach(btn => {
            btn.onclick = () => {
                const id       = btn.dataset.id;
                const input    = document.getElementById('ph-qp-gallery-ids');
                const existing = input.value.split(',').filter(v => v && v !== id);
                input.value = existing.join(',');
                btn.closest('.ph-qp-gallery-item')?.remove();
            };
        });
    }

    /* ── Tag input ────────────────────────────────────────────── */
    function opQpInitTagInput() {
        const input    = document.getElementById('ph-qp-tag-input');
        const datalist = document.getElementById('ph-qp-tags-list');
        if (!input) return;

        input.setAttribute('list', 'ph-qp-tags-list');

        input.addEventListener('keydown', e => {
            if (e.key !== 'Enter' && e.key !== ',') return;
            e.preventDefault();
            const val = input.value.trim().replace(/,$/, '');
            if (!val) return;

            const opt = [...(datalist?.querySelectorAll('option') || [])].find(
                o => o.value.toLowerCase() === val.toLowerCase()
            );
            const id = opt ? opt.dataset.id : `new_${val}`;

            if (!Object.values(opQp.selectedTags).includes(val)) {
                opQpAddTagChip(val, id);
            }
            input.value = '';
        });
    }

    function opQpAddTagChip(name, id) {
        opQp.selectedTags[id] = name;
        const wrap = document.getElementById('ph-qp-tags-selected');
        if (!wrap) return;
        const chip = document.createElement('span');
        chip.className = 'ph-qp-tag-chip';
        chip.dataset.id = id;
        chip.innerHTML = `${esc(name)}<button type="button">×</button>`;
        chip.querySelector('button').addEventListener('click', () => {
            delete opQp.selectedTags[id];
            chip.remove();
            opQpUpdateTagIds();
        });
        wrap.appendChild(chip);
        opQpUpdateTagIds();
    }

    function opQpUpdateTagIds() {
        const input = document.getElementById('ph-qp-tag-ids');
        if (input) input.value = Object.keys(opQp.selectedTags).join(',');
    }

    /* ── Toast ─────────────────────────────────────────────────── */
    let opQpToastTimer;
    function opQpToast(msg, type = 'success') {
        // Use the existing Product Haven toast if available
        if (typeof showToast === 'function') { showToast(msg, type === 'error'); return; }

        let el = document.getElementById('ph-qp-toast');
        if (!el) {
            el = document.createElement('div');
            el.id = 'ph-qp-toast';
            el.style.cssText = 'position:fixed;bottom:28px;right:28px;padding:12px 20px;border-radius:10px;font-size:13px;font-weight:500;z-index:99999;box-shadow:0 4px 20px rgba(0,0,0,.2);color:#fff;transition:opacity .2s;';
            document.body.appendChild(el);
        }
        el.textContent = msg;
        el.style.background = type === 'error' ? '#EF4444' : '#10B981';
        el.style.opacity = '1';
        el.style.display = 'block';
        clearTimeout(opQpToastTimer);
        opQpToastTimer = setTimeout(() => { el.style.opacity = '0'; setTimeout(() => { el.style.display = 'none'; }, 200); }, 3000);
    }

    /* ── Init — gets called from the first click on the tab ── */
    function opQpInit() {
        opQpInitTabs();
        opQpInitListEvents();
        opQpInitEditorForm();
        opQpInitImageUploader();
        opQpInitGalleryUploader();
        opQpInitTagInput();
        opQpLoadProducts(1);
    }

/*
       LANGUAGE SWITCHER
*/
    function initLangSwitcher() {
        document.querySelectorAll('.ph-lang-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const lang = btn.dataset.lang;
                if (btn.classList.contains('is-active')) return;
                fetch(ajax_url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: new URLSearchParams({ action: 'ph_set_lang', nonce, lang }),
                }).then(r => r.json()).then(resp => {
                    if (resp.success) {
                        // Reload the page so PHP can render the new language
                        window.location.reload();
                    }
                });
            });
        });
    }

    // Initialize the language button immediately on load
    initLangSwitcher();

})();
