document.addEventListener('DOMContentLoaded', () => {
    const productModalEl = document.getElementById('productModal');
    const productModal = new bootstrap.Modal(productModalEl);
    const addBtn = document.getElementById('add-product');
    const form = document.getElementById('product-form');
    const idInput = document.getElementById('product-id');
    const currentImageInput = document.getElementById('current-image');
    const categorySelect = document.getElementById('product-category');
    const typeSelect = document.getElementById('product-type');
    const qtyInput = document.getElementById('product-qty');
    const priceInput = document.getElementById('product-price');
    const statusSelect = document.getElementById('product-status');
    const publishGroup = document.getElementById('publish-at-group');
    const publishInput = document.getElementById('product-publish-at');
    const paginationContainer = document.getElementById('pagination-container');
    const tbody = document.getElementById('product-rows');
    const submitBtn = form ? form.querySelector('button[type="submit"]') : null;
    const modalTitle = productModalEl ? productModalEl.querySelector('.modal-title') : null;
    const confirmModalEl = document.getElementById('confirmModal');
    const confirmModal = confirmModalEl ? new bootstrap.Modal(confirmModalEl) : null;
    const confirmMessage = document.getElementById('confirmMessage');
    const confirmOk = document.getElementById('confirmOk');

    function clampQty() {
        let val = parseInt(qtyInput.value, 10);
        if (isNaN(val)) val = 0;
        val = Math.min(Math.max(val, 0), 999);
        qtyInput.value = val;
    }

    function formatPrice() {
        let val = parseFloat(priceInput.value.replace(/[^\d.]/g, ''));
        if (isNaN(val)) val = 0;
        val = Math.min(Math.max(val, 0), 999999);
        priceInput.value = new Intl.NumberFormat('en-AU', {
            style: 'currency',
            currency: 'AUD'
        }).format(val);
    }

    if (qtyInput) qtyInput.addEventListener('blur', clampQty);
    if (priceInput) priceInput.addEventListener('blur', formatPrice);
    function togglePublishAt() {
        if (!statusSelect) return;
        publishGroup.style.display = statusSelect.value === 'S' ? '' : 'none';
        if (statusSelect.value !== 'S') publishInput.value = '';
    }
    if (statusSelect) statusSelect.addEventListener('change', togglePublishAt);

    async function loadTypes(selectedType = '') {
        typeSelect.innerHTML = '<option value="">Select Type</option>';

        try {
            const res = await fetch(`/admin.types`);
            const data = await res.json();
            data.forEach(type => {
                const opt = document.createElement('option');
                opt.value = type.id;
                opt.textContent = type.name;
                typeSelect.appendChild(opt);
            });
            if (selectedType) typeSelect.value = selectedType;
        } catch (e) {
            console.error('Failed to load types', e);
        }
    }

    productModalEl.addEventListener('hidden.bs.modal', () => {
        form.reset();
        idInput.value = '';
        currentImageInput.value = '';
        typeSelect.innerHTML = '<option value="">Select Type</option>';
        publishInput.value = '';
        publishGroup.style.display = 'none';
        if (submitBtn) submitBtn.textContent = 'Save Product';
        if (modalTitle) modalTitle.textContent = 'Add Product';
    });

    if (addBtn) {
        addBtn.addEventListener('click', () => {
            loadTypes();
            currentImageInput.value = '';
            togglePublishAt();
            if (submitBtn) submitBtn.textContent = 'Save Product';
            if (modalTitle) modalTitle.textContent = 'Add Product';
            productModal.show();
        });
    }

    const searchInput = document.getElementById('product-search');
    const statusFilter = document.getElementById('status-filter');
    if (statusFilter) {
        statusFilter.addEventListener('change', () => loadPage(1));
    }

    function attachEditHandlers() {
        document.querySelectorAll('.edit-product').forEach(btn => {
            btn.addEventListener('click', () => {
                idInput.value = btn.dataset.id;
                document.getElementById('product-name').value = btn.dataset.name || '';
                document.getElementById('product-desc').value = btn.dataset.desc || '';
                document.getElementById('product-features').value = btn.dataset.features || '';
                document.getElementById('product-qty').value = btn.dataset.qty || '';
                priceInput.value = btn.dataset.price || '';
                formatPrice();
                currentImageInput.value = btn.dataset.image || '';
                const st = btn.dataset.status;
                document.getElementById('product-status').value =
                    st === 'A' ? 'P' : st === 'I' ? 'U' : (st || 'P');
                publishInput.value = (btn.dataset.publishAt || '').replace(' ', 'T').slice(0,16);
                togglePublishAt();
                categorySelect.value = btn.dataset.category || '';
                loadTypes(btn.dataset.type);
                if (submitBtn) submitBtn.textContent = 'Update Product';
                if (modalTitle) modalTitle.textContent = 'Edit Product';
                productModal.show();
            });
        });
    }

    function attachDeleteHandlers() {
        document.querySelectorAll('.delete-product').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const url = btn.getAttribute('href');
                const name = btn.dataset.name || 'this product';
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
    function initTooltips() {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
    }

    attachEditHandlers();
    attachDeleteHandlers();
    initTooltips();

    async function loadPage(page) {
        try {
            const term = searchInput ? searchInput.value.trim() : '';
            const statusVal = statusFilter ? statusFilter.value : '';
            const res = await fetch(`/admin.products?page_num=${page}&search=${encodeURIComponent(term)}&status=${statusVal}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            tbody.innerHTML = data.rows;
            paginationContainer.innerHTML = data.pagination;
            attachEditHandlers();
            attachDeleteHandlers();
            initTooltips();
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

    let searchTimer;
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => loadPage(1), 300);
        });
    }

    if (form) {
        form.addEventListener('submit', () => {
            clampQty();
            let val = parseFloat(priceInput.value.replace(/[^\d.]/g, ''));
            if (isNaN(val)) val = 0;
            val = Math.min(Math.max(val, 0), 999999);
            priceInput.value = val;
            if (statusSelect.value !== 'S') publishInput.value = '';
        });
    }
});
