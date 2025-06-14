const filterForm = document.getElementById("filter-form");
function changeSelection(input) {
    input.addEventListener('change', () => {
        filterForm.submit();
    });
}

function addToCartBtn(event) {
    event.preventDefault();
    event.stopPropagation(); // Fix: Use event instead of target

    const target = event.currentTarget; // element that triggered event
    console.log('Target', target);

    const productId = target.getAttribute('data-product-id');
    console.log('Product ID:', productId);

    fetch('/cart.add', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        credentials: 'same-origin',
        body: new URLSearchParams({ productId: productId })
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Data', data)
            if (data.success) {
                updateCartCount(data.cartCount);
                showToast({
                    title: 'Cart Updated',
                    message: `${data.productName} was added successfully!`,
                    type: 'success',
                    image: 'assets/images/header/u_cart.svg'
                });
                // alert(`Added ${data.productName} to your cart!`);
            } else {
                showToast({
                    title: 'Error',
                    message: data.error || 'Could not add product.',
                    type: 'danger',
                    image: 'assets/images/icons/error.png'
                });
                // alert('Failed to add the product.');
            }
        })
        .catch(error => {
            console.error('Fetch error:', error.response);
            showToast({
                title: 'Network Error',
                message: 'Could not connect to server.',
                type: 'danger',
                image: 'assets/images/icons/error.png'
            });
            // alert('Network error occurred. Check console for details.');
        });
}

function updateCartCount(count) {
    const cartCount = document.querySelector('#cart-count');
    if (cartCount) {
        cartCount.textContent = count;
        verifiedIfCartCountIsNeeded()
    }
}

/*Taking From Bootstrap*/
function showToast({
                       title = 'Notification',
                       message = '',
                       type = 'success', // success | danger | warning | info
                       image = '',
                       time = 'Just now'
                   } = {}) {
    const toastEl = document.getElementById('liveToast');
    const toastTitle = document.getElementById('toast-title');
    const toastBody = document.getElementById('toast-body');
    const toastTime = document.getElementById('toast-time');
    const toastImg = document.getElementById('toast-img');

    // Style by type
    toastEl.className = `toast align-items-center text-bg-${type} border-0`;

    // Set content
    toastTitle.textContent = title;
    toastBody.textContent = message;
    toastTime.textContent = time;
    toastImg.src = image || 'assets/images/icons/default.png';

    // Bootstrap show
    const toast = new bootstrap.Toast(toastEl);
    toast.show();
    setTimeout(() => {
        toast.hide()
    }, 5000)
}

function verifiedIfCartCountIsNeeded () {
    const cartCount = document.querySelector('#cart-count');
    console.log('LOG', cartCount.textContent)
    if (parseInt(cartCount.textContent) === 0 || typeof parseInt(cartCount.textContent) === 'undefined') {
        cartCount.classList.remove('d-block')
        cartCount.classList.add('d-none')
    } else {
        cartCount.classList.remove('d-none')
        cartCount.classList.add('d-block')
    }
}

document.addEventListener("DOMContentLoaded", function () {
    // const filterForm = document.getElementById("filter-form");

    if (filterForm) {
        // Categories Page
        filterForm.querySelectorAll('input[type="checkbox"]').forEach(input => {
            //
            // console.log('input', input);
            changeSelection(input);
        });

        filterForm.querySelectorAll('input[type="number"]').forEach(input => {
            console.log('Min', input.value !== '')
            let hasMinPrice = input.name === 'min_price' && input.value !== '';
            let hasMaxPrice = input.name === 'max_price' && input.value !== '';
            if (hasMinPrice && hasMaxPrice) {
                changeSelection(input);
            }

            if (!hasMinPrice && !hasMaxPrice) {
                input.value = ''
                changeSelection(input)
            }

        });
    }

    const sortSelect = document.getElementById("sort");
    if (sortSelect) {
        changeSelection(sortSelect)
    }

    // 🧠 Handle logic for "All" type
    const typeCheckboxes = document.querySelectorAll('.type-checkbox');
    const allTypeCheckbox = Array.from(typeCheckboxes).find(el => el.dataset.typeId === '4');

    typeCheckboxes.forEach(cb => {
        cb.addEventListener('change', () => {
            const anyChecked = Array.from(typeCheckboxes)
                .some(input => input.checked && input.dataset.typeId !== '4');

            allTypeCheckbox.checked = !anyChecked;
        });
    });

    const cards = document.querySelectorAll('.clickable-card');

    cards.forEach(card => {
        card.addEventListener('click', function (e) {
            const ripple = document.createElement('span');
            ripple.classList.add('ripple-effect');

            const rect = card.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            ripple.style.width = ripple.style.height = `${size}px`;

            ripple.style.left = `${e.clientX - rect.left - size / 2}px`;
            ripple.style.top = `${e.clientY - rect.top - size / 2}px`;

            card.appendChild(ripple);

            setTimeout(() => ripple.remove(), 600);
        });
    });

    cards.forEach(card => {
        card.addEventListener('click', (e) => {
            // Prevent click from Add button inside
            if (e.target.closest("button")) return;

            const id = card.getAttribute("data-id");
            if (id) window.location.href = `?route=product&id=${id}`;
        })
    })

    verifiedIfCartCountIsNeeded()

    // Profile Account
    const links = document.querySelectorAll(".nav-link-item");
    const sections = document.querySelectorAll(".profile-section");
    const output = document.getElementById("dynamic-content");

    function showSection(id) {
        sections.forEach(section => section.classList.add("d-none"));
        const selected = document.getElementById(id);
        if (selected) output.innerHTML = selected.innerHTML;
    }

    links.forEach(link => {
        link.addEventListener("click", function (e) {
            e.preventDefault();
            const target = this.dataset.target;

            // Remove active style
            links.forEach(l => l.classList.remove("fw-bold", "text-primary"));
            this.classList.add("fw-bold", "text-primary");

            showSection(target);
        });
    });

// Load default view
    showSection("profile");
    document.querySelector('[data-target="profile"]').classList.add("fw-bold", "text-primary");

});

