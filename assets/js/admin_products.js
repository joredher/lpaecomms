document.addEventListener('DOMContentLoaded', () => {
    const formContainer = document.getElementById('productFormContainer');
    const addBtn = document.getElementById('add-product');
    const cancelBtn = document.getElementById('cancel-product');
    const form = document.getElementById('product-form');
    const idInput = document.getElementById('product-id');

    if (addBtn) {
        addBtn.addEventListener('click', () => {
            form.reset();
            idInput.value = '';
            formContainer.classList.remove('d-none');
        });
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', () => {
            form.reset();
            idInput.value = '';
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
            document.getElementById('product-category').value = btn.dataset.category || '';
            document.getElementById('product-type').value = btn.dataset.type || '';
            document.getElementById('product-sku').value = btn.dataset.sku || '';
        });
    });
});
