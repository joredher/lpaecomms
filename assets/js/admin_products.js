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
    });

    if (addBtn) {
        addBtn.addEventListener('click', () => {
            loadTypes();
            currentImageInput.value = '';
            togglePublishAt();
            productModal.show();
        });
    }

    const searchInput = document.getElementById('product-search');
    const statusFilter = document.getElementById('status-filter');

    function normalizeStatus(st) {
        if (st === 'A') return 'P';
        if (st === 'I') return 'U';
        return st;
    }

    function filterRows() {
        const term = searchInput ? searchInput.value.toLowerCase() : '';
        const statusVal = statusFilter ? statusFilter.value : '';
        document.querySelectorAll('#products-table tbody tr').forEach(row => {
            const name = row.querySelector('.product-title').textContent.toLowerCase();
            const sku = row.querySelector('.product-sku').textContent.toLowerCase();
            const price = row.querySelector('.product-price').textContent.toLowerCase();
            const rowStatus = normalizeStatus(row.dataset.status || '');
            const matchesSearch = name.includes(term) || sku.includes(term) || price.includes(term);
            const matchesStatus = !statusVal || rowStatus === statusVal;
            row.style.display = matchesSearch && matchesStatus ? '' : 'none';
        });
    }

    if (searchInput) searchInput.addEventListener('input', filterRows);
    if (statusFilter) statusFilter.addEventListener('change', filterRows);

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
                productModal.show();
            });
        });
    }

    attachEditHandlers();

    async function loadPage(page) {
        try {
            const res = await fetch(`/admin.products?page_num=${page}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            tbody.innerHTML = data.rows;
            paginationContainer.innerHTML = data.pagination;
            attachEditHandlers();
            filterRows();
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
