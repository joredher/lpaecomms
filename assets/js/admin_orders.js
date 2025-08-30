document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('order-search');
    const statusFilter = document.getElementById('status-filter');
    const tbody = document.getElementById('order-rows');
    const paginationContainer = document.getElementById('pagination-container');

    async function loadPage(page) {
        try {
            const term = searchInput ? searchInput.value.trim() : '';
            const statusVal = statusFilter ? statusFilter.value : '';
            const res = await fetch(`/admin.orders?page_num=${page}&search=${encodeURIComponent(term)}&status=${statusVal}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            if (tbody) tbody.innerHTML = data.rows;
            if (paginationContainer) paginationContainer.innerHTML = data.pagination;
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
});

