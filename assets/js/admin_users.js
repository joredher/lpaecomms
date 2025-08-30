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
    const submitBtn = form ? form.querySelector('button[type="submit"]') : null;
    const modalTitle = userModalEl ? userModalEl.querySelector('.modal-title') : null;
    const confirmModalEl = document.getElementById('confirmModal');
    const confirmModal = confirmModalEl ? new bootstrap.Modal(confirmModalEl) : null;
    const confirmMessage = document.getElementById('confirmMessage');
    const confirmOk = document.getElementById('confirmOk');
    const usernameInput = document.getElementById('user-username');
    const emailInput = document.getElementById('user-email');

    if (usernameInput) {
        usernameInput.readOnly = true;
    }
    if (emailInput && usernameInput) {
        emailInput.addEventListener('input', () => {
            const localPart = emailInput.value.split('@')[0] || '';
            const year = new Date().getFullYear();
            usernameInput.value = localPart ? `${localPart}${year}` : '';
        });
    }

    if (userModalEl) {
        userModalEl.addEventListener('hidden.bs.modal', () => {
            form.reset();
            idInput.value = '';
            if (submitBtn) submitBtn.textContent = 'Save User';
            if (modalTitle) modalTitle.textContent = 'Add User';
        });
    }

    if (addBtn && userModal) {
        addBtn.addEventListener('click', () => {
            if (submitBtn) submitBtn.textContent = 'Save User';
            if (modalTitle) modalTitle.textContent = 'Add User';
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
                if (submitBtn) submitBtn.textContent = 'Update User';
                if (modalTitle) modalTitle.textContent = 'Edit User';
                if (userModal) userModal.show();
            });
        });
    }

    function attachDeleteHandlers() {
        document.querySelectorAll('.delete-user').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const url = btn.getAttribute('href');
                const name = btn.dataset.name || 'this user';
                if (!confirmModal) {
                    if (confirm(`Are you sure to delete ${name}?`)) {
                        window.location.href = url;
                    }
                    return;
                }
                confirmMessage.textContent = `Are you sure to delete ${name}?`;
                confirmOk.onclick = () => { window.location.href = url; };
                confirmModal.show();
            });
        });
    }

    function attachStatusHandlers() {
        document.querySelectorAll('.activate-user, .deactivate-user').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const url = btn.getAttribute('href');
                const name = btn.dataset.name || 'this user';
                const action = btn.classList.contains('activate-user') ? 'activate' : 'deactivate';
                if (!confirmModal) {
                    if (confirm(`Are you sure to ${action} ${name}?`)) {
                        window.location.href = url;
                    }
                    return;
                }
                confirmMessage.textContent = `Are you sure to ${action} ${name}?`;
                confirmOk.onclick = () => { window.location.href = url; };
                confirmModal.show();
            });
        });
    }

    attachEditHandlers();
    attachDeleteHandlers();
    attachStatusHandlers();

    async function loadPage(page) {
        try {
            const term = searchInput ? searchInput.value.trim() : '';
            const res = await fetch(`/admin.users?page_num=${page}&search=${encodeURIComponent(term)}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            tbody.innerHTML = data.rows;
            paginationContainer.innerHTML = data.pagination;
            attachEditHandlers();
            attachDeleteHandlers();
            attachStatusHandlers();
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

    let searchTimer;
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => loadPage(1), 300);
        });
    }
});
