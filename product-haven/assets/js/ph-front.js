/**
 * Product Haven — Frontend JS
 * Elementor widgets: Stats & Timeline voor ingelogde klanten
 */
(function () {
    'use strict';

    const cfg = window.ph_data || {};
    const { ajax_url, nonce, currency, i18n = {} } = cfg;

    /* ============================================================
       AJAX helper
       ============================================================ */
    function ajax(action, data = {}) {
        return fetch(ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            body: new URLSearchParams({ action, nonce, ...data }),
        }).then(r => r.json());
    }

    function formatCurrency(val) {
        return currency + Number(val).toLocaleString('nl-NL', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    /* ============================================================
       Stats Widget
       ============================================================ */
    function initStatsWidgets() {
        document.querySelectorAll('.mopf-stats-widget').forEach(widget => {
            const days      = parseInt(widget.dataset.days || 30);
            const showChart = widget.dataset.showChart === '1';

            ajax('ph_front_stats', { days }).then(resp => {
                if (!resp.success) return;
                const d = resp.data;

                const revenue = widget.querySelector('.mopf-val-revenue');
                const orders  = widget.querySelector('.mopf-val-orders');
                const avg     = widget.querySelector('.mopf-val-avg');

                if (revenue) revenue.textContent = formatCurrency(d.revenue);
                if (orders)  orders.textContent  = d.orders;
                if (avg)     avg.textContent     = formatCurrency(d.avg_order);

                if (showChart && window.Chart) {
                    widget.querySelectorAll('.mopf-sparkline').forEach(canvas => {
                        const type   = canvas.dataset.type || 'revenue';
                        const values = type === 'revenue' ? (d.chart?.revenue || []) : (d.chart?.orders || []);
                        renderSparkline(canvas, values, type);
                    });
                }
            });
        });
    }

    function renderSparkline(canvas, values, type) {
        if (!values.length || !window.Chart) return;

        const color = type === 'revenue' ? '#10B981' : '#6366F1';

        new Chart(canvas, {
            type: 'line',
            data: {
                labels:   values.map((_, i) => i),
                datasets: [{
                    data:            values,
                    borderColor:     color,
                    backgroundColor: 'transparent',
                    borderWidth:     2,
                    pointRadius:     0,
                    tension:         0.4,
                }],
            },
            options: {
                responsive:          false,
                animation:           false,
                plugins: { legend: { display: false }, tooltip: { enabled: false } },
                scales:  { x: { display: false }, y: { display: false } },
            },
        });
    }

    /* ============================================================
       Timeline Widget
       ============================================================ */
    function initTimelineWidgets() {
        document.querySelectorAll('.mopf-timeline-widget').forEach(widget => {
            const showItems = widget.dataset.showItems === '1';
            const showTotal = widget.dataset.showTotal === '1';
            let page = 1;

            function loadPage(p) {
                page = p;
                ajax('ph_front_timeline', { page, per_page: 5 }).then(resp => {
                    const list   = widget.querySelector('#mopf-tl-list');
                    const empty  = widget.querySelector('#mopf-tl-empty');
                    const pager  = widget.querySelector('#mopf-tl-pagination');

                    if (!resp.success) {
                        if (list)  list.innerHTML  = '';
                        if (empty) empty.hidden    = false;
                        return;
                    }

                    const d = resp.data;

                    if (!d.orders.length) {
                        if (list)  list.innerHTML = '';
                        if (empty) empty.hidden   = false;
                        return;
                    }

                    if (empty) empty.hidden = true;
                    if (list)  list.innerHTML = d.orders.map(o => renderTimelineItem(o, showTotal, showItems)).join('');
                    if (pager) renderFrontPagination(pager, d.page, d.total_pages, loadPage);
                });
            }

            loadPage(1);
        });
    }

    function renderTimelineItem(o, showTotal, showItems) {
        const items = showItems && o.items.length
            ? `<ul class="mopf-tl-items">
                ${o.items.map(i => `<li>${esc(i.name)} <span class="mopf-tl-item-qty">× ${i.qty}</span></li>`).join('')}
               </ul>`
            : '';

        const total = showTotal ? `<span class="mopf-tl-total">${esc(o.total)}</span>` : '';

        return `
        <div class="mopf-tl-item">
            <div class="mopf-tl-dot-wrap">
                <div class="mopf-tl-dot mopf-status-dot-${esc(o.status)}"></div>
                <div class="mopf-tl-line"></div>
            </div>
            <div class="mopf-tl-card">
                <div class="mopf-tl-card-header">
                    <span class="mopf-tl-number"><a href="${esc(o.view_url || '#')}">#${esc(o.number)}</a></span>
                    <span class="mopf-status-badge mopf-status-${esc(o.status)}">${esc(o.status_label)}</span>
                </div>
                <div class="mopf-tl-meta">
                    <span class="mopf-tl-date">${esc(o.date_human)}</span>
                    ${total}
                </div>
                ${items}
            </div>
        </div>`;
    }

    function renderFrontPagination(container, page, totalPages, onPage) {
        if (totalPages <= 1) { container.innerHTML = ''; return; }

        let html = '';
        if (page > 1)          html += `<button class="mopf-page-btn" data-page="${page - 1}">‹</button>`;
        html += `<span style="align-self:center;font-size:13px;color:#64748B">${page} / ${totalPages}</span>`;
        if (page < totalPages) html += `<button class="mopf-page-btn" data-page="${page + 1}">›</button>`;

        container.innerHTML = html;
        container.querySelectorAll('.mopf-page-btn').forEach(btn => {
            btn.addEventListener('click', () => onPage(parseInt(btn.dataset.page)));
        });
    }

    /* ============================================================
       Utility
       ============================================================ */
    function esc(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    /* ============================================================
       Init
       ============================================================ */
    function init() {
        // In Elementor editor de preview wordt server-side gegenereerd — geen AJAX nodig.
        if ( window.elementorFrontend && window.elementorFrontend.isEditMode() ) return;

        initStatsWidgets();
        initTimelineWidgets();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Elementor frontend (gepubliceerde pagina): herinitialiseer widgets na renderen.
    if (window.elementorFrontend) {
        window.elementorFrontend.hooks?.addAction('frontend/element_ready/ph_order_stats.default', function () {
            if (!window.elementorFrontend.isEditMode()) initStatsWidgets();
        });
        window.elementorFrontend.hooks?.addAction('frontend/element_ready/ph_order_timeline.default', function () {
            if (!window.elementorFrontend.isEditMode()) initTimelineWidgets();
        });
    }

})();
