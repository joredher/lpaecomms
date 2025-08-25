const filterForm = document.getElementById("filter-form");
function changeSelection(input) {
  input.addEventListener("change", () => {
    filterForm.submit();
  });
}

function addToCartBtn(event) {
  event.preventDefault();
  event.stopPropagation(); // Fix: Use event instead of target

  const target = event.currentTarget; // element that triggered event
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
        // alert(`Added ${data.productName} to your cart!`);
      } else {
        showToast({
          title: "Error",
          message: data.error || "Could not add product.",
          type: "danger",
          image: "assets/images/icons/error.png",
        });
        // alert('Failed to add the product.');
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
      // alert('Network error occurred. Check console for details.');
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
  // console.log('LOG', cartCount.textContent)
  if (
    parseInt(cartCount.textContent) === 0 ||
    typeof parseInt(cartCount.textContent) === "undefined"
  ) {
    cartCount.classList.remove("d-block");
    cartCount.classList.add("d-none");
  } else {
    cartCount.classList.remove("d-none");
    cartCount.classList.add("d-block");
  }
}

// =========================
// API config
// =========================
const url = "https://addressr.p.rapidapi.com/addresses?q=";
const headers = {
  "x-rapidapi-key": "fdb9e0567dmsha6e1dfa8a5d4f52p1f769bjsn932503b8f8e9",
  "x-rapidapi-host": "addressr.p.rapidapi.com",
};
let checkoutSection = document.getElementById("checkout-section");
// =========================
// Search API Call
// =========================
async function searchAddress(query) {
  const options = {
    method: "GET",
    headers: headers,
  };
  try {
    const response = await fetch(`${url}${encodeURIComponent(query)}`, options);
    const result = await response.json();
    console.log(result);
    renderSuggestions(result);
  } catch (error) {
    console.error(error);
  }
}

// Optional: enhance suggestion UX with arrow key navigation
function enableKeyboardNavigation() {
  const suggestions = document.getElementById("suggestions");
  const input = document.getElementById("autocomplete-address");

  let selectedIndex = -1;

  input.addEventListener("keydown", function (e) {
    const items = suggestions.querySelectorAll("li");
    if (items.length === 0) return;

    if (e.key === "ArrowDown") {
      e.preventDefault();
      selectedIndex = (selectedIndex + 1) % items.length;
      updateHighlight(items);
    } else if (e.key === "ArrowUp") {
      e.preventDefault();
      selectedIndex = (selectedIndex - 1 + items.length) % items.length;
      updateHighlight(items);
    } else if (e.key === "Enter") {
      e.preventDefault();
      if (selectedIndex >= 0 && selectedIndex < items.length) {
        input.value = items[selectedIndex].textContent;
        suggestions.innerHTML = "";
        selectedIndex = -1;
      }
    }
  });

  function updateHighlight(items) {
    items.forEach((item, index) => {
      item.classList.toggle("active", index === selectedIndex);
    });
  }
}

// =========================
// Render Address Suggestions
// =========================
async function renderSuggestions(data) {
  const list = document.getElementById("suggestions");
  list.innerHTML = "";
  if (data && Array.isArray(data) && data.length > 0) {
    data.forEach((address) => {
      console.log("Show me ", address);
      const li = document.createElement("li");
      li.textContent = address.sla;
      li.className = "list-group-item list-group-item-action mx";
      li.style.cursor = "pointer";
      li.addEventListener("click", () => {
        const input = document.getElementById("autocomplete-address");
        const idHidden = document.getElementById("address-id");
        if (input) {
          input.value = address.sla;
          idHidden.value = address.pid;
          if (checkoutSection) {
            lookAddressDetailsById(address.pid);
          }
        }
        console.log("INPUT", input.value, idHidden.value);

        list.innerHTML = ""; // Clear suggestions
      });
      list.appendChild(li);
    });
  }
}

// =========================
// Attach listener after HTML loads
// =========================
function attachAutocomplete() {
  const autocomplete = document.getElementById("autocomplete-address");
  const suggestions = document.getElementById("suggestions");

  if (!autocomplete || !suggestions) {
    console.warn("⚠️ Input with ID 'autocomplete-address' not found!");
    return;
  }

  autocomplete.addEventListener("input", function () {
    const input = this.value.trim();
    if (input.length >= 3) {
      searchAddress(input);
    } else {
      suggestions.innerHTML = "";
    }
  });

  document.addEventListener("click", function (event) {
    if (!event.target.closest("#autocomplete-address")) {
      suggestions.innerHTML = "";
    }
  });
}

async function lookAddressDetailsById(addressId) {
  const street = document.getElementById("street");
  const apartment = document.getElementById("apartment");
  const city = document.getElementById("city");
  const zipcode = document.getElementById("zipcode");

  console.log("Address", addressId);
  try {
    await fetch(`/address?address=${addressId}`, {
      method: "GET",
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then((data) => {
        console.log("DATA", data);

        if (data.success) {
          let addressData = data.address;
          street.value =
            addressData["streetNumberFrom"] +
            ((!addressData["streetNumberTo"] ? "" : " - ") +
              (addressData["streetNumberTo"] === null
                ? ""
                : addressData["streetNumberTo"])) +
            ` ${addressData["streetName"]} ${addressData["streetType"]} ${addressData["suburb"]}`;
          apartment.value = `${addressData["typeApt"]} ${addressData["unitNumber"]}`;
          city.value = addressData.state;
          zipcode.value = addressData["postcode"];
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
        // alert('Network error occurred. Check console for details.');
      });
  } catch (error) {
    console.error(error);
  }
}

if (checkoutSection) {
  attachAutocomplete();
}

document.addEventListener("DOMContentLoaded", function () {
  // const filterForm = document.getElementById("filter-form");

  if (filterForm) {
    // Categories Page
    filterForm.querySelectorAll('input[type="checkbox"]').forEach((input) => {
      //
      // console.log('input', input);
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
    (el) => el.dataset.typeId === "4",
  );

  typeCheckboxes.forEach((cb) => {
    cb.addEventListener("change", () => {
      const anyChecked = Array.from(typeCheckboxes).some(
        (input) => input.checked && input.dataset.typeId !== "4",
      );

      allTypeCheckbox.checked = !anyChecked;
    });
  });

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

      const id = card.getAttribute("data-id");
      if (id) window.location.href = `?route=product&id=${id}`;
    });
  });

  verifiedIfCartCountIsNeeded();

  // Profile Account
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

    let offset = parseInt(btn.getAttribute("data-offset"), 10) || tbody.children.length;
    const limit = 5;

    btn.addEventListener("click", () => {
      fetch(`/orders?offset=${offset}&limit=${limit}`)
        .then((resp) => resp.json())
        .then((data) => {
          data.invoices.forEach((order) => {
            const tr = document.createElement("tr");
            const date = new Date(order.created_at).toLocaleDateString("en-AU", {
              day: "2-digit",
              month: "short",
              year: "numeric",
            });
            tr.innerHTML = `
              <td><a href="/orders.show?id=${order.id}" class="text-decoration-none" target="_blank">${order.invoice_number}</a></td>
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

  const backBtn = document.querySelector('.pd-back-button');
  let lastUrl = backBtn
    ? backBtn.getAttribute('href')
    : window.location.pathname + window.location.hash;

  function trackUrl(newUrl, prevUrl) {
    const params = new URLSearchParams({ url: newUrl });
    if (prevUrl) {
      params.append('prev', prevUrl);
    }
    fetch('/nav.track', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: params,
    }).catch((err) => console.error(err));
  }

  function handleSectionFromHash() {
    const section = window.location.hash
      ? window.location.hash.substring(1)
      : 'profile';
    setActiveLink(section);
    showSection(section);

    const newUrl = window.location.pathname + window.location.hash;
    if (backBtn) {
      backBtn.setAttribute('href', lastUrl || '/home');
    }
    trackUrl(newUrl, lastUrl);
    lastUrl = newUrl;
  }

  links.forEach((link) => {
    link.addEventListener("click", function (e) {
      e.preventDefault();
      const target = this.dataset.target;
      setActiveLink(target);
      showSection(target);
      window.location.hash = target;
    });
  });

  window.addEventListener("hashchange", handleSectionFromHash);

  handleSectionFromHash();

  attachLoadMoreOrders();
});
