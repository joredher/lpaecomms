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
    const firstnameInput = document.getElementById('user-firstname');
    const lastnameInput = document.getElementById('user-lastname');
    const emailError = document.getElementById('email-error');
    const firstnameError = document.getElementById('firstname-error');
    const lastnameError = document.getElementById('lastname-error');

    if (usernameInput) {
        usernameInput.readOnly = true;
    }
    function updateSubmitState() {
        if (!submitBtn) return;
        submitBtn.disabled = document.querySelectorAll('.is-invalid').length > 0;
    }

    async function checkEmail() {
        if (!emailInput) return;
        const email = emailInput.value.trim();
        if (!email) {
            emailInput.classList.remove('is-invalid');
            if (emailError) emailError.textContent = '';
            updateSubmitState();
            return;
        }
        try {
            const idVal = idInput.value || '';
            const res = await fetch(`/admin.users?check_email=${encodeURIComponent(email)}&id=${idVal}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            if (data.exists) {
                emailInput.classList.add('is-invalid');
                if (emailError) emailError.textContent = 'Email already exists';
            } else {
                emailInput.classList.remove('is-invalid');
                if (emailError) emailError.textContent = '';
            }
        } catch (e) {
            console.error('Failed to validate email', e);
        }
        updateSubmitState();
    }

    function validateName(input, errorEl) {
        if (!input) return;
        const val = input.value;
        if (/\d/.test(val)) {
            input.classList.add('is-invalid');
            if (errorEl) errorEl.textContent = 'Numbers are not allowed';
        } else {
            input.classList.remove('is-invalid');
            if (errorEl) errorEl.textContent = '';
        }
        updateSubmitState();
    }

    if (emailInput && usernameInput) {
        let emailTimer;
        emailInput.addEventListener('input', () => {
            const localPart = emailInput.value.split('@')[0] || '';
            const year = new Date().getFullYear();
            usernameInput.value = localPart ? `${localPart}${year}` : '';
            clearTimeout(emailTimer);
            emailTimer = setTimeout(checkEmail, 300);
        });
        emailInput.addEventListener('blur', checkEmail);
    }

    if (firstnameInput) {
        firstnameInput.addEventListener('input', () => validateName(firstnameInput, firstnameError));
    }

    if (lastnameInput) {
        lastnameInput.addEventListener('input', () => validateName(lastnameInput, lastnameError));
    }

    if (userModalEl) {
        userModalEl.addEventListener('hidden.bs.modal', () => {
            form.reset();
            idInput.value = '';
            [emailInput, firstnameInput, lastnameInput].forEach(inp => {
                if (inp) inp.classList.remove('is-invalid');
            });
            [emailError, firstnameError, lastnameError].forEach(el => {
                if (el) el.textContent = '';
            });
            if (submitBtn) {
                submitBtn.textContent = 'Save User';
                submitBtn.disabled = false;
            }
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
                checkEmail();
                validateName(firstnameInput, firstnameError);
                validateName(lastnameInput, lastnameError);
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
