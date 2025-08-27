document.addEventListener('DOMContentLoaded', () => {
    const productModalEl = document.getElementById('productModal');
    const productModal = new bootstrap.Modal(productModalEl);
    const addBtn = document.getElementById('add-product');
    const form = document.getElementById('product-form');
    const idInput = document.getElementById('product-id');
    const categorySelect = document.getElementById('product-category');
    const typeSelect = document.getElementById('product-type');

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
        typeSelect.innerHTML = '<option value="">Select Type</option>';
    });

    if (addBtn) {
        addBtn.addEventListener('click', () => {
            loadTypes();
            productModal.show();
        });
    }

    const searchInput = document.getElementById('product-search');
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            const term = searchInput.value.toLowerCase();
            document.querySelectorAll('#products-table tbody tr').forEach(row => {
                const name = row.querySelector('.product-title').textContent.toLowerCase();
                const sku = row.querySelector('.product-sku').textContent.toLowerCase();
                const price = row.querySelector('.product-price').textContent.toLowerCase();
                row.style.display =
                    name.includes(term) || sku.includes(term) || price.includes(term) ? '' : 'none';
            });
        });
    }

    document.querySelectorAll('.edit-product').forEach(btn => {
        btn.addEventListener('click', () => {
            idInput.value = btn.dataset.id;
            document.getElementById('product-name').value = btn.dataset.name || '';
            document.getElementById('product-desc').value = btn.dataset.desc || '';
            document.getElementById('product-features').value = btn.dataset.features || '';
            document.getElementById('product-qty').value = btn.dataset.qty || '';
            document.getElementById('product-price').value = btn.dataset.price || '';
            document.getElementById('product-image').value = btn.dataset.image || '';
            document.getElementById('product-status').value = btn.dataset.status || 'A';
            categorySelect.value = btn.dataset.category || '';
            loadTypes(btn.dataset.type);
            productModal.show();
        });
    });
});
