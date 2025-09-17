// =========================
// Cart & Filter Utilities
// =========================
const filterForm = document.getElementById("filter-form");

function changeSelection(input) {
  input.addEventListener("change", () => {
    filterForm.submit();
  });
}

function addToCartBtn(event) {
  event.preventDefault();
  event.stopPropagation();

  const target = event.currentTarget;
  if (
    !target ||
    target.hasAttribute("disabled") ||
    target.classList.contains("disabled") ||
    target.getAttribute("aria-disabled") === "true"
  ) {
    return;
  }
  console.log("Target", target);

  const productId = target.getAttribute("data-product-id");
  console.log("Product ID:", productId);

  fetch("/cart.add", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    credentials: "same-origin",
    body: new URLSearchParams({ productId: productId }),
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      return response.json();
    })
    .then((data) => {
      console.log("Data", data);
      if (data.success) {
        updateCartCount(data.cartCount);
        showToast({
          title: "Cart Updated",
          message: `${data.productName} was added successfully!`,
          type: "success",
          image: "assets/images/header/u_cart.svg",
        });
      } else {
        showToast({
          title: "Error",
          message: data.error || "Could not add product.",
          type: "danger",
          image: "assets/images/icons/error.png",
        });
      }
    })
    .catch((error) => {
      console.error("Fetch error:", error.response);
      showToast({
        title: "Network Error",
        message: "Could not connect to server.",
        type: "danger",
        image: "assets/images/icons/error.png",
      });
    });
}

function updateCartCount(count) {
  const cartCount = document.querySelector("#cart-count");
  if (cartCount) {
    cartCount.textContent = count;
    verifiedIfCartCountIsNeeded();
  }
}

/*Taking From Bootstrap*/
function verifiedIfCartCountIsNeeded() {
  const cartCount = document.querySelector("#cart-count");
  if (!cartCount) return;

  const count = parseInt(cartCount.textContent, 10) || 0;
  if (count === 0) {
    cartCount.classList.remove("d-block");
    cartCount.classList.add("d-none");
  } else {
    cartCount.classList.remove("d-none");
    cartCount.classList.add("d-block");
  }
}


// =========================
// Initialization Helpers
// =========================
function initFiltersAndSorting() {
  if (filterForm) {
    filterForm.querySelectorAll('input[type="checkbox"]').forEach((input) => {
      changeSelection(input);
    });

    filterForm.querySelectorAll('input[type="number"]').forEach((input) => {
      console.log("Min", input.value !== "");
      let hasMinPrice = input.name === "min_price" && input.value !== "";
      let hasMaxPrice = input.name === "max_price" && input.value !== "";
      if (hasMinPrice && hasMaxPrice) {
        changeSelection(input);
      }

      if (!hasMinPrice && !hasMaxPrice) {
        input.value = "";
        changeSelection(input);
      }
    });
  }

  const sortSelect = document.getElementById("sort");
  if (sortSelect) {
    changeSelection(sortSelect);
  }

  // 🧠 Handle logic for "All" type
  const typeCheckboxes = document.querySelectorAll(".type-checkbox");
  const allTypeCheckbox = Array.from(typeCheckboxes).find(
    (el) => el.dataset.typeId === "4"
  );

  typeCheckboxes.forEach((cb) => {
    cb.addEventListener("change", () => {
      const anyChecked = Array.from(typeCheckboxes).some(
        (input) => input.checked && input.dataset.typeId !== "4"
      );

      allTypeCheckbox.checked = !anyChecked;
    });
  });
}

function initClickableCards() {
  const cards = document.querySelectorAll(".clickable-card");

  cards.forEach((card) => {
    card.addEventListener("click", function (e) {
      const ripple = document.createElement("span");
      ripple.classList.add("ripple-effect");

      const rect = card.getBoundingClientRect();
      const size = Math.max(rect.width, rect.height);
      ripple.style.width = ripple.style.height = `${size}px`;

      ripple.style.left = `${e.clientX - rect.left - size / 2}px`;
      ripple.style.top = `${e.clientY - rect.top - size / 2}px`;

      card.appendChild(ripple);

      setTimeout(() => ripple.remove(), 600);
    });
  });

  cards.forEach((card) => {
    card.addEventListener("click", (e) => {
      // Prevent click from Add button inside
      if (e.target.closest("button")) return;

      const slug = card.getAttribute("data-slug");
      if (slug) {
        window.location.href = `/product?slug=${slug}`;
      } else {
        const id = card.getAttribute("data-id");
        if (id) window.location.href = `/product?id=${id}`;
      }
    });
  });
}

function initProfileNavigation() {
  const links = document.querySelectorAll("[data-target]");
  const output = document.getElementById("dynamic-content");

  function showSection(target) {
    if (output && target) {
      fetch(`pages/user/profile_${target}.php`)
        .then((response) => response.text())
        .then((html) => {
          output.innerHTML = html;
          attachLoadMoreOrders();
          setTimeout(() => {
            attachAutocomplete();
            enableKeyboardNavigation();
          }, 20);
        })
        .catch((err) => {
          output.innerHTML = `<div class="alert alert-danger">Error loading section.</div>`;
          console.error(err);
        });
    }
  }

  function attachLoadMoreOrders() {
    const container = output || document;
    const btn = container.querySelector("#load-more-orders");
    const tbody = container.querySelector("#orders-table-body");
    if (!btn || !tbody) return;

    let offset =
      parseInt(btn.getAttribute("data-offset"), 10) || tbody.children.length;
    const limit = 5;

    btn.addEventListener("click", () => {
      fetch(`/orders?offset=${offset}&limit=${limit}`)
        .then((resp) => resp.json())
        .then((data) => {
          data.invoices.forEach((order) => {
            const tr = document.createElement("tr");
            const date = new Date(order.created_at).toLocaleDateString(
              "en-AU",
              {
                day: "2-digit",
                month: "short",
                year: "numeric",
              }
            );
            tr.innerHTML = `
              <td><a href="/orders.show?slug=${order.slug}" class="text-decoration-none" target="_blank">${order.invoice_number}</a></td>
              <td>${date}</td>
              <td>${order.status}</td>
              <td>AUD ${parseFloat(order.total_amount).toFixed(2)}</td>
            `;
            tbody.appendChild(tr);
          });

          offset += data.invoices.length;
          if (!data.hasMore) {
            btn.disabled = true;
            btn.textContent = "No more orders";
          }
        })
        .catch((err) => console.error(err));
    });
  }

  function setActiveLink(target) {
    links.forEach((l) => l.classList.remove("fw-bold", "text-primary"));
    const link = document.querySelector(`[data-target="${target}"]`);
    if (link) {
      link.classList.add("fw-bold", "text-primary");
    }
  }

  const backBtn = document.querySelector(".pd-back-button");
  let lastUrl = backBtn
    ? backBtn.getAttribute("href")
    : window.location.pathname + window.location.hash;

  function trackUrl(newUrl, prevUrl) {
    const params = new URLSearchParams({ url: newUrl });
    if (prevUrl) {
      params.append("prev", prevUrl);
    }
    fetch("/nav.track", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: params,
    }).catch((err) => console.error(err));
  }

  function handleSectionFromHash() {
    const section = window.location.hash
      ? window.location.hash.substring(1)
      : "profile";
    setActiveLink(section);
    showSection(section);

    const newUrl = window.location.pathname + window.location.hash;
    if (backBtn) {
      backBtn.setAttribute("href", lastUrl || "/home");
    }
    trackUrl(newUrl, lastUrl);
    lastUrl = newUrl;
  }

  links.forEach((link) => {
    link.addEventListener("click", function (e) {
      e.preventDefault();
      const target = this.dataset.target;
      window.location.hash = target;
    });
  });

  window.addEventListener("hashchange", handleSectionFromHash);

  handleSectionFromHash();

  attachLoadMoreOrders();
}

// =========================
// DOM Ready
// =========================
document.addEventListener("DOMContentLoaded", function () {
  initFiltersAndSorting();
  initClickableCards();
  verifiedIfCartCountIsNeeded();
  initProfileNavigation();
});
