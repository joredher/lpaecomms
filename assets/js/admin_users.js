// JavaScript for admin users page
// Handles modal actions, pagination, search, and filtering

document.addEventListener('DOMContentLoaded', () => {
    const userModalEl = document.getElementById('userModal');
    const userModal = userModalEl ? new bootstrap.Modal(userModalEl) : null;
    const addBtn = document.getElementById('add-user');
    const form = document.getElementById('user-form');
    const idInput = document.getElementById('user-id');
    const paginationContainer = document.getElementById('pagination-container');
    const tbody = document.getElementById('user-rows');

    if (userModalEl) {
        userModalEl.addEventListener('hidden.bs.modal', () => {
            form.reset();
            idInput.value = '';
        });
    }

    if (addBtn && userModal) {
        addBtn.addEventListener('click', () => {
            userModal.show();
        });
    }

    function attachEditHandlers() {
        document.querySelectorAll('.edit-user').forEach(btn => {
            btn.addEventListener('click', () => {
                idInput.value = btn.dataset.id;
                document.getElementById('user-username').value = btn.dataset.username || '';
                document.getElementById('user-email').value = btn.dataset.email || '';
                document.getElementById('user-firstname').value = btn.dataset.firstname || '';
                document.getElementById('user-lastname').value = btn.dataset.lastname || '';
                document.getElementById('user-group').value = btn.dataset.group || '';
                document.getElementById('user-status').value = btn.dataset.status || 'A';
                if (userModal) userModal.show();
            });
        });
    }

    attachEditHandlers();

    async function loadPage(page) {
        try {
            const term = searchInput ? searchInput.value.trim() : '';
            const statusVal = statusFilter ? statusFilter.value : '';
            const res = await fetch(`/admin.users?page_num=${page}&search=${encodeURIComponent(term)}&status=${statusVal}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            tbody.innerHTML = data.rows;
            paginationContainer.innerHTML = data.pagination;
            attachEditHandlers();
        } catch (e) {
            console.error('Failed to load page', e);
        }
    }

    if (paginationContainer) {
        paginationContainer.addEventListener('click', (e) => {
            const link = e.target.closest('a.page-link');
            if (!link) return;
            e.preventDefault();
            const page = link.dataset.page;
            loadPage(page);
        });
    }

    const searchInput = document.getElementById('user-search');
    const statusFilter = document.getElementById('status-filter');

    if (statusFilter) {
        statusFilter.addEventListener('change', () => loadPage(1));
    }

    let searchTimer;
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => loadPage(1), 300);
        });
    }
});
