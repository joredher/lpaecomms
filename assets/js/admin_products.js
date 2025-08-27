document.getElementById('product-form')?.addEventListener('submit', async function (e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);

    await fetch('/api/products/save.php', {
        method: 'POST',
        body: formData
    });

    window.location.reload();
});
