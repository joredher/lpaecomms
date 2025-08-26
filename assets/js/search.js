function attachLiveSearch(searchBox, options = {}) {
    const input = searchBox.querySelector('input[name="search"]');
    if (!input) return;

    const endpoint = options.endpoint || '/search.products';
    const minChars = options.minChars || 3;

    // Prepare results container
    const results = document.createElement('div');
    results.classList.add('list-group', 'position-absolute', 'w-100', 'd-none');
    results.style.zIndex = '1000';
    results.style.top = '100%';
    searchBox.classList.add('position-relative');
    searchBox.appendChild(results);

    let controller = null;
    input.addEventListener('input', async (e) => {
        const term = e.target.value.trim();
        if (term.length < minChars) {
            results.innerHTML = '';
            results.classList.add('d-none');
            if (controller) controller.abort();
            return;
        }
        if (controller) controller.abort();
        controller = new AbortController();
        try {
            const response = await fetch(`${endpoint}?q=${encodeURIComponent(term)}`, {signal: controller.signal});
            if (!response.ok) return;
            const data = await response.json();
            results.innerHTML = data.html;
            if (data.html.trim()) {
                results.classList.remove('d-none');
            } else {
                results.classList.add('d-none');
            }
        } catch (err) {
            if (err.name !== 'AbortError') {
                console.error(err);
            }
        }
    });

    document.addEventListener('click', (e) => {
        if (!searchBox.contains(e.target)) {
            results.classList.add('d-none');
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.search-box').forEach(box => attachLiveSearch(box));
});
