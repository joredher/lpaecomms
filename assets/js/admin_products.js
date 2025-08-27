document.addEventListener('DOMContentLoaded', () => {
    const formContainer = document.getElementById('productFormContainer');
    const addBtn = document.getElementById('add-product');
    const cancelBtn = document.getElementById('cancel-product');
    const form = document.getElementById('product-form');
    const idInput = document.getElementById('product-id');
    const categorySelect = document.getElementById('product-category');
    const typeSelect = document.getElementById('product-type');

    async function loadTypes(categoryId, selectedType = '') {
        typeSelect.innerHTML = '<option value="">Select Type</option>';
        if (!categoryId) return;

        try {
            const res = await fetch(`/admin.types?category_id=${categoryId}`);
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

    if (categorySelect) {
        categorySelect.addEventListener('change', () => {
            loadTypes(categorySelect.value);
        });
    }

    if (addBtn) {
        addBtn.addEventListener('click', () => {
            form.reset();
            idInput.value = '';
            typeSelect.innerHTML = '<option value="">Select Type</option>';
            formContainer.classList.remove('d-none');
        });
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', () => {
            form.reset();
            idInput.value = '';
            typeSelect.innerHTML = '<option value="">Select Type</option>';
            formContainer.classList.add('d-none');
        });
    }

    document.querySelectorAll('.edit-product').forEach(btn => {
        btn.addEventListener('click', () => {
            formContainer.classList.remove('d-none');
            idInput.value = btn.dataset.id;
            document.getElementById('product-name').value = btn.dataset.name || '';
            document.getElementById('product-desc').value = btn.dataset.desc || '';
            document.getElementById('product-features').value = btn.dataset.features || '';
            document.getElementById('product-qty').value = btn.dataset.qty || '';
            document.getElementById('product-price').value = btn.dataset.price || '';
            document.getElementById('product-image').value = btn.dataset.image || '';
            document.getElementById('product-status').value = btn.dataset.status || 'A';
            categorySelect.value = btn.dataset.category || '';
            loadTypes(btn.dataset.category, btn.dataset.type);
        });
    });
});
