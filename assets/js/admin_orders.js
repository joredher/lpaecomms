document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('order-search');
    const statusFilter = document.getElementById('status-filter');
    const tbody = document.getElementById('order-rows');
    const paginationContainer = document.getElementById('pagination-container');
    let currentPage = 1;

    function attachActionHandlers() {
        document.querySelectorAll('[data-order-action="cancel"]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                if (!confirm('Cancel this order?')) {
                    e.preventDefault();
                }
            });
        });
    }

    function initTooltips() {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
    }

    function attachStatusHandlers() {
        document.querySelectorAll('.order-status').forEach(sel => {
            sel.addEventListener('change', async () => {
                try {
                    const id = sel.dataset.orderId;
                    const status = sel.value;
                    const res = await fetch('/admin.updateStatus', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: `id=${encodeURIComponent(id)}&status=${encodeURIComponent(status)}`
                    });
                    const data = await res.json();
                    if (data.success) {
                        loadPage(currentPage);
                    } else {
                        console.error('Failed to update status');
                    }
                } catch (err) {
                    console.error('Failed to update status', err);
                }
            });
        });
    }

    async function loadPage(page) {
        try {
            currentPage = Number(page);
            const term = searchInput ? searchInput.value.trim() : '';
            const statusVal = statusFilter ? statusFilter.value : '';
            const url = `/admin.orders?page_num=${page}&search=${encodeURIComponent(term)}&status=${encodeURIComponent(statusVal)}`;
            const res = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            if (tbody) tbody.innerHTML = data.rows;
            if (paginationContainer) paginationContainer.innerHTML = data.pagination;
            attachActionHandlers();
            attachStatusHandlers();
            initTooltips();
        } catch (e) {
            console.error('Failed to load orders', e);
        }
    }

    if (statusFilter) {
        statusFilter.addEventListener('change', () => loadPage(1));
    }

    if (paginationContainer) {
        paginationContainer.addEventListener('click', (e) => {
            const link = e.target.closest('a.page-link');
            if (!link) return;
            e.preventDefault();
            loadPage(link.dataset.page);
        });
    }

    let searchTimer;
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => loadPage(1), 300);
        });
    }

    attachActionHandlers();
    attachStatusHandlers();
    initTooltips();
});
