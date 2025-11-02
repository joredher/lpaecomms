// =========================
// Cart & Filter Utilities
// =========================
const filterForm = document.getElementById("filter-form");

function changeSelection(input) {
  input.addEventListener("change", () => {
    if (window.USE_AJAX_CATALOG) {
      document.dispatchEvent(new CustomEvent('catalog:filtersChanged', { bubbles: true }));
    } else {
      filterForm?.submit();
    }
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
    headers: { "Content-Type": "application/x-www-form-urlencoded", "X-CSRF-Token": (window.CSRF_TOKEN||'') },
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

function initFilterToggle() {
  const toggleBtn = document.getElementById('toggle-filters');
  const filtersPanel = document.getElementById('filters');
  if (!toggleBtn || !filtersPanel) return;

  toggleBtn.addEventListener('click', () => {
    const isOpen = filtersPanel.classList.toggle('is-open');
    toggleBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    if (isOpen) {
      filtersPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  });
}

// =========================
// AJAX Catalog Filters
// =========================
function initAjaxCatalogFilters() {
  const form = document.getElementById('filter-form');
  const list = document.getElementById('products-list');
  const pager = document.getElementById('pagination');
  const label = document.getElementById('results-label');

  if (!form || !list || !pager) return;

  let pending = null;

  function getState() {
    const cats = Array.from(form.querySelectorAll('input[name="category[]"]'));
    const types = Array.from(form.querySelectorAll('input[name="type[]"]'));
    const typeAll = form.querySelector('.type-checkbox[data-type-id="4"], input[name="type[]"][value="4"]');
    const selectedCats = cats.filter(cb => cb.checked);
    const selectedTypes = types.filter(cb => cb.checked && (cb.dataset.typeId !== '4' && cb.value !== '4'));
    return { cats, types, typeAll, selectedCats, selectedTypes };
  }

  function enforceRules() {
    const { cats, types, typeAll, selectedCats, selectedTypes } = getState();

    // Rule 2: If any A is active, B must not appear active
    if (selectedCats.length > 0) {
      types.forEach(cb => { cb.checked = false; });
    }

    // Rule 3: If any B (not "All") is active, A must not appear active
    if (selectedTypes.length > 0) {
      cats.forEach(cb => { cb.checked = false; });
      if (typeAll) typeAll.checked = false; // specific type overrides 'All'
    }

    // Rule 6: If user selects more than 3 options in A, revert to default (B option 9 = All)
    if (selectedCats.length > 3) {
      cats.forEach(cb => { cb.checked = false; });
      if (typeAll) typeAll.checked = true;
    }

    // Rule 4 & 5: If no A/B are selected at all, default B option 9 (All)
    const afterCats = form.querySelectorAll('input[name="category[]"]:checked').length;
    const afterTypes = Array.from(form.querySelectorAll('input[name="type[]"]:checked')).filter(cb => (cb.dataset.typeId ?? cb.value) !== '4').length;
    if (afterCats === 0 && afterTypes === 0) {
      if (typeAll) typeAll.checked = true;
    }
  }

  function buildUrl(params) {
    const url = new URL(window.location.origin + '/products');

    // keep existing q/query if present
    const current = new URL(window.location.href);
    ['q', 'query'].forEach((k) => {
      const val = current.searchParams.get(k);
      if (val) url.searchParams.set(k, val);
    });

    // from form (after enforcing rules)
    enforceRules();
    const fd = new FormData(form);
    for (const [k, v] of fd.entries()) {
      // Skip empty values
      if (v === null || v === '') continue;
      // Skip default sort
      if (k === 'sort' && v === 'popular') continue;
      // Skip Type "All" (value 4)
      if ((k === 'type[]' || k === 'type') && v === '4') continue;
      url.searchParams.append(k, v);
    }

    // overlay explicit params
    if (params) {
      Object.entries(params).forEach(([k, v]) => {
        if (k === 'route') return; // never propagate route
        url.searchParams.delete(k);
        if (v !== undefined && v !== null && v !== '') {
          url.searchParams.set(k, v);
        }
      });
    }
    // Rule 1: Price only applies when A or B has selections (excluding 'All')
    const catsActive = form.querySelectorAll('input[name="category[]"]:checked').length > 0;
    const typesActive = Array.from(form.querySelectorAll('input[name="type[]"]:checked')).some(cb => (cb.dataset.typeId ?? cb.value) !== '4');
    if (!catsActive && !typesActive) {
      url.searchParams.delete('min_price');
      url.searchParams.delete('max_price');
    }
    // Remove empty/invalid price params regardless
    const minVal = url.searchParams.get('min_price');
    const maxVal = url.searchParams.get('max_price');
    if (!minVal || isNaN(Number(minVal))) url.searchParams.delete('min_price');
    if (!maxVal || isNaN(Number(maxVal))) url.searchParams.delete('max_price');

    // Drop default sort if still present
    if (url.searchParams.get('sort') === 'popular') url.searchParams.delete('sort');

    return url;
  }

  async function applyFilters(params = {}, { push = true } = {}) {
    if (!('page_num' in params)) params.page_num = 1;

    const url = buildUrl(params);

    if (pending && typeof pending.abort === 'function') pending.abort();
    const controller = new AbortController();
    pending = controller;

    try {
      const res = await fetch(url.toString(), {
        method: 'GET',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
        signal: controller.signal,
      });
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const data = await res.json();

      list.innerHTML = data.html || '';
      pager.innerHTML = data.pagination || '';
      if (label && data.label) {
        const countStr = (typeof data.total_products !== 'undefined')
          ? `${data.label} (${data.total_products} Results)`
          : `${data.label} (${data.count ?? 0} Results)`;
        label.textContent = countStr;
        label.classList.toggle('is-active', data.label !== 'All');
      }

      if (typeof Currency?.apply === 'function') Currency.apply();
      if (typeof initClickableCards === 'function') initClickableCards();

      if (push) {
        history.pushState({ productsAjax: true }, '', url.toString());
      }
    } catch (e) {
      console.error('Filter fetch failed', e);
    }
  }

  // intercept programmatic submit in this context
  form.addEventListener('submit', (e) => {
    e.preventDefault();
    applyFilters();
  });

  // react to filter changes from changeSelection
  document.addEventListener('catalog:filtersChanged', () => { enforceRules(); applyFilters(); });

  // pagination clicks (event delegation)
  pager.addEventListener('click', (e) => {
    const a = e.target.closest('a.page-link');
    if (!a) return;
    e.preventDefault();
    const u = new URL(a.href, window.location.origin);
    const page = u.searchParams.get('page_num') || 1;
    applyFilters({ page_num: page });
  });

  // sort change (in case not covered by changeSelection)
  const sortSel = document.getElementById('sort');
  if (sortSel) {
    sortSel.addEventListener('change', () => applyFilters({ sort: sortSel.value }));
  }

  // debounced price updates for smoother UX
  const minEl = form.querySelector('input[name="min_price"]');
  const maxEl = form.querySelector('input[name="max_price"]');
  function debounce(fn, ms) { let t; return (...args)=>{ clearTimeout(t); t=setTimeout(()=>fn(...args), ms); }; }
  const updatePrice = debounce(() => {
    const minV = minEl?.value || '';
    const maxV = maxEl?.value || '';
    if (minV && maxV && Number(minV) > Number(maxV)) return;
    applyFilters({ min_price: minV, max_price: maxV });
  }, 400);
  if (minEl) minEl.addEventListener('input', updatePrice);
  if (maxEl) maxEl.addEventListener('input', updatePrice);

  // back/forward
  window.addEventListener('popstate', (ev) => {
    if (ev.state && ev.state.productsAjax) {
      const params = Object.fromEntries(new URLSearchParams(window.location.search));
      applyFilters(params, { push: false });
    } else {
      window.location.reload();
    }
  });
}

// =========================
// Currency Switcher
// =========================
const Currency = (function () {
  const RATES = {
    AUD: { rate: 1, symbol: '$', locale: 'en-AU', code: 'AUD', minFrac: 2, maxFrac: 2 },
    USD: { rate: 0.65, symbol: '$', locale: 'en-US', code: 'USD', minFrac: 2, maxFrac: 2 },
    GBP: { rate: 0.53, symbol: '£', locale: 'en-GB', code: 'GBP', minFrac: 2, maxFrac: 2 },
    COP: { rate: 2600, symbol: '$', locale: 'es-CO', code: 'COP', minFrac: 0, maxFrac: 0 },
    BRL: { rate: 3.2, symbol: 'R$', locale: 'pt-BR', code: 'BRL', minFrac: 2, maxFrac: 2 },
  };

  function get() {
    return localStorage.getItem('currency') || 'AUD';
  }

  function set(cur) {
    if (!RATES[cur]) return;
    localStorage.setItem('currency', cur);
  }

  function convertAud(audAmount, toCur) {
    const meta = RATES[toCur] || RATES.AUD;
    return audAmount * meta.rate;
  }

  function format(audAmount, cur) {
    const meta = RATES[cur] || RATES.AUD;
    const nf = new Intl.NumberFormat(meta.locale, {
      minimumFractionDigits: meta.minFrac,
      maximumFractionDigits: meta.maxFrac,
    });
    const converted = convertAud(audAmount, cur);
    return `${meta.symbol}${nf.format(converted)} ${meta.code}`;
  }

  function apply() {
    const cur = get();
    // Update header label
    const codeEl = document.getElementById('currency-code');
    if (codeEl) codeEl.textContent = cur;

    // Update all known price elements with data-price-aud
    document.querySelectorAll('[data-price-aud]').forEach((el) => {
      const raw = parseFloat(el.getAttribute('data-price-aud'));
      if (!isNaN(raw)) el.textContent = format(raw, cur);
    });
  }

  function init() {
    // Apply at load
    apply();

    // Hook dropdown options
    document.querySelectorAll('.currency-option').forEach((opt) => {
      opt.addEventListener('click', (e) => {
        e.preventDefault();
        const cur = opt.getAttribute('data-currency');
        if (cur) {
          set(cur);
          apply();
        }
      });
    });
  }

  return { init, apply, get };
})();
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
  // Enable AJAX enhancement for products catalog
  window.USE_AJAX_CATALOG = !!document.getElementById('products-list');
  if (window.USE_AJAX_CATALOG) {
    initAjaxCatalogFilters();
  }
  initFiltersAndSorting();
  initFilterToggle();
  initClickableCards();
  verifiedIfCartCountIsNeeded();
  initProfileNavigation();
  Currency.init();
  A11y.init();
  PaymentMock.init();
  initCheckoutExpiryWatcher();
  initProductGallery();
});

// =========================
// Accessibility Preferences
// =========================
const A11y = (function () {
  const STORE_KEY = 'a11y';

  function load() {
    try { return JSON.parse(localStorage.getItem(STORE_KEY) || '{}'); }
    catch { return {}; }
  }

  function save(prefs) {
    localStorage.setItem(STORE_KEY, JSON.stringify(prefs));
    try {
      if (window.IS_AUTHENTICATED) {
        fetch('/profile.saveA11y', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': (window.CSRF_TOKEN || '')
          },
          credentials: 'same-origin',
          body: JSON.stringify({ prefs })
        }).catch(() => {});
      }
    } catch (e) { /* no-op */ }
  }

  function apply(prefs) {
    const b = document.body;
    b.classList.toggle('a11y-text-lg', prefs.textSize === 'large');
    b.classList.toggle('a11y-text-xl', prefs.textSize === 'xlarge');
    b.classList.toggle('a11y-contrast', !!prefs.contrast);
    b.classList.toggle('theme-dark', !!prefs.darkMode);
    b.classList.toggle('a11y-underline-links', !!prefs.underlineLinks);
    b.classList.toggle('a11y-reduce-motion', !!prefs.reduceMotion);
    b.classList.toggle('a11y-focus-outline', !!prefs.focusOutline);
  }

  function syncForm(prefs) {
    const sel = document.getElementById('a11y-text-size');
    const c = document.getElementById('a11y-contrast');
    const d = document.getElementById('a11y-dark-mode');
    const u = document.getElementById('a11y-underline-links');
    const r = document.getElementById('a11y-reduce-motion');
    const f = document.getElementById('a11y-focus-outline');
    if (!sel) return; // modal not present
    sel.value = prefs.textSize || 'normal';
    if (c) c.checked = !!prefs.contrast;
    if (d) d.checked = !!prefs.darkMode;
    if (u) u.checked = !!prefs.underlineLinks;
    if (r) r.checked = !!prefs.reduceMotion;
    if (f) f.checked = !!prefs.focusOutline;
  }

  function bind() {
    const saveBtn = document.getElementById('a11y-save');
    const resetBtn = document.getElementById('a11y-reset');
    if (saveBtn) {
      saveBtn.addEventListener('click', () => {
        const prefs = load();
        const sel = document.getElementById('a11y-text-size');
        const c = document.getElementById('a11y-contrast');
        const d = document.getElementById('a11y-dark-mode');
        const u = document.getElementById('a11y-underline-links');
        const r = document.getElementById('a11y-reduce-motion');
        const f = document.getElementById('a11y-focus-outline');
        const next = {
          textSize: sel ? sel.value : (prefs.textSize || 'normal'),
          contrast: c ? c.checked : !!prefs.contrast,
          darkMode: d ? d.checked : !!prefs.darkMode,
          underlineLinks: u ? u.checked : !!prefs.underlineLinks,
          reduceMotion: r ? r.checked : !!prefs.reduceMotion,
          focusOutline: f ? f.checked : !!prefs.focusOutline,
        };
        save(next);
        apply(next);
      });
    }
    if (resetBtn) {
      resetBtn.addEventListener('click', () => {
        const defaults = { textSize: 'normal', contrast: false, darkMode: false, underlineLinks: false, reduceMotion: false, focusOutline: false };
        save(defaults);
        apply(defaults);
        syncForm(defaults);
      });
    }

    // When the modal opens, reflect current prefs
    const modalEl = document.getElementById('accessibilityModal');
    if (modalEl) {
      modalEl.addEventListener('show.bs.modal', () => syncForm(load()));
      // Live preview + autosave on change
      modalEl.addEventListener('change', () => {
        const sel = document.getElementById('a11y-text-size');
        const c = document.getElementById('a11y-contrast');
        const d = document.getElementById('a11y-dark-mode');
        const u = document.getElementById('a11y-underline-links');
        const r = document.getElementById('a11y-reduce-motion');
        const f = document.getElementById('a11y-focus-outline');
        const next = {
          textSize: sel ? sel.value : 'normal',
          contrast: c && c.checked,
          darkMode: d && d.checked,
          underlineLinks: u && u.checked,
          reduceMotion: r && r.checked,
          focusOutline: f && f.checked,
        };
        apply(next);
        save(next); // persist even if user closes without clicking Apply
      });
    }
  }

  function init() {
    let prefs = load();
    try {
      if (window.SERVER_A11Y && typeof window.SERVER_A11Y === 'object') {
        // Server prefs take precedence over local
        prefs = Object.assign({}, prefs, window.SERVER_A11Y);
        save(prefs); // keep localStorage in sync for next visits/logouts
      }
    } catch (e) { /* no-op */ }
    apply(prefs);
    bind();
  }

  return { init };
})();

// =========================
// Checkout expiry watcher
// =========================
function initCheckoutExpiryWatcher(){
  const root = document.getElementById('checkout-section');
  if (!root) return;
  const createdAt = parseInt(root.getAttribute('data-created-at')||'0',10) * 1000;
  const timeoutSec = parseInt(root.getAttribute('data-timeout-seconds')||'1800',10);
  if (!createdAt || !timeoutSec) return;

  const warnLeadMs = 2 * 60 * 1000; // warn 2 minutes before expiry
  function now(){ return Date.now(); }
  function expiresAtMs(){ return createdAt + timeoutSec*1000; }

  const warnAt = expiresAtMs() - warnLeadMs;
  const showWarn = () => {
    const modalEl = document.getElementById('cartExpiryModal');
    if (!modalEl) return;
    const modal = new bootstrap.Modal(modalEl, {backdrop: 'static', keyboard: false});
    const keepBtn = document.getElementById('keepCartAliveBtn');
    keepBtn?.addEventListener('click', async ()=>{
      try {
        const res = await fetch('/checkout.keepAlive', { method: 'POST' });
        const data = await res.json();
        if (data.ok) {
          // update base timestamp and reschedule
          root.setAttribute('data-created-at', Math.floor(Date.now()/1000).toString());
          modal.hide();
          if (typeof showToast === 'function') {
            showToast({ title: 'Session', message: 'Cart time extended for 30 minutes.', type: 'success', image: 'assets/images/icons/success.png' });
          }
          // re-arm timers
          setTimeout(showWarn, Math.max(0, (timeoutSec*1000) - warnLeadMs));
          setTimeout(hardExpire, timeoutSec*1000);
        } else {
          alert('Could not keep cart alive.');
        }
      } catch (e) { alert('Network error.'); }
    }, { once: true });
    modal.show();
  };

  const hardExpire = () => {
    // server will enforce, but guide the user gently
    if (typeof showToast === 'function') {
      showToast({ title: 'Session', message: 'Cart expired due to inactivity.', type: 'warning', image: 'assets/images/icons/error.png' });
    }
    // Optional: redirect after short delay
    setTimeout(()=>{ window.location.href = '/products'; }, 3000);
  };

  const timeToWarn = warnAt - now();
  const timeToExpire = expiresAtMs() - now();
  if (timeToWarn <= 0 && timeToExpire > 0) {
    showWarn();
  } else if (timeToExpire > 0) {
    setTimeout(showWarn, timeToWarn);
  }
  if (timeToExpire > 0) {
    setTimeout(hardExpire, timeToExpire);
  }
}

// =========================
// Product gallery + zoom
// =========================
function initProductGallery(){
  const mainImg = document.getElementById('pd-main-image');
  const thumbs = document.querySelectorAll('.pd-thumb');
  if (!mainImg) return;
  thumbs.forEach(t => {
    t.addEventListener('click', () => {
      thumbs.forEach(x=>x.classList.remove('active'));
      t.classList.add('active');
      const full = t.getAttribute('data-full') || t.getAttribute('src');
      if (full) { mainImg.src = full; }
    });
  });

  const zoomWrap = mainImg.closest('.pd-image-zoom');
  if (zoomWrap) {
    zoomWrap.addEventListener('mousemove', (e)=>{
      const r = zoomWrap.getBoundingClientRect();
      const x = ((e.clientX - r.left) / r.width) * 100;
      const y = ((e.clientY - r.top) / r.height) * 100;
      mainImg.style.transformOrigin = `${x}% ${y}%`;
    });
    zoomWrap.addEventListener('mouseleave', ()=>{ mainImg.style.transformOrigin = 'center center'; });
    // tap to toggle zoom on touch
    zoomWrap.addEventListener('click', ()=>{
      const scaleOn = mainImg.style.transform === 'scale(1.6)';
      mainImg.style.transform = scaleOn ? 'scale(1)' : 'scale(1.6)';
      if (!scaleOn) mainImg.style.transformOrigin = 'center center';
      setTimeout(()=>{ mainImg.style.transform = ''; }, 1200); // fall back to hover behavior
    });
  }
}

// =========================
// Buy Now: add to cart then go to checkout
// =========================
function buyNow(event, productId){
  if (event) { event.preventDefault(); event.stopPropagation(); }
  const id = productId || (event?.currentTarget?.getAttribute('data-product-id'));
  if (!id) { window.location.href = '/checkout'; return; }
  fetch('/cart.add', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    credentials: 'same-origin',
    body: new URLSearchParams({ productId: id })
  })
    .then(res => res.ok ? res.json() : Promise.reject(res))
    .then(data => {
      if (data && data.success) {
        updateCartCount?.(data.cartCount);
      }
      window.location.href = '/checkout';
    })
    .catch(() => { window.location.href = '/checkout'; });
}

// =========================
// Payment mock (test cards)
// =========================
const PaymentMock = (function(){
  const STORE = 'cards';

  function getCards(){
    try { return JSON.parse(localStorage.getItem(STORE) || '[]'); } catch { return []; }
  }
  function saveCards(cards){ localStorage.setItem(STORE, JSON.stringify(cards)); }

  function luhnOk(num){
    const s = (num||'').replace(/\D/g,''); let sum=0, alt=false;
    for (let i=s.length-1;i>=0;i--) { let n=parseInt(s[i],10); if(alt){ n*=2; if(n>9)n-=9; } sum+=n; alt=!alt; }
    return s.length>=12 && (sum%10===0);
  }

  function brandFromNumber(num){
    const s=(num||'').replace(/\D/g,'');
    if(/^4/.test(s)) return 'visa';
    if(/^(5[1-5]|2[2-7])/.test(s)) return 'mastercard';
    if(/^3[47]/.test(s)) return 'amex';
    return 'card';
  }

  function populateSelect(){
    const select = document.getElementById('cardSelect');
    if(!select) return;
    const cards = getCards();
    select.innerHTML = '<option value="">Select a saved test card…</option>' +
      cards.map((c,i)=>`<option value="${i}">${c.brand.toUpperCase()} •••• ${c.last4}</option>`).join('');
  }

  function bind(){
    const addBtn = document.getElementById('addCardBtn');
    const saveBtn = document.getElementById('saveCardBtn');
    const select = document.getElementById('cardSelect');
    const modalEl = document.getElementById('cardModal');
    const cardRadio = document.getElementById('card');
    const codRadio = document.getElementById('cod');
    const cardBox = document.getElementById('cardBox');

    if (cardRadio && codRadio && cardBox) {
      const toggle = () => { cardBox.style.display = cardRadio.checked ? 'block' : 'none'; };
      cardRadio.addEventListener('change', toggle);
      codRadio.addEventListener('change', toggle);
      toggle();
    }

    if (addBtn && modalEl) {
      const modal = new bootstrap.Modal(modalEl);
      addBtn.addEventListener('click', ()=>{ modal.show(); });
    }

    if (saveBtn) {
      saveBtn.addEventListener('click', ()=>{
        const p1 = (document.getElementById('cardNumber1')?.value || '').replace(/\D/g,'');
        const p2 = (document.getElementById('cardNumber2')?.value || '').replace(/\D/g,'');
        const p3 = (document.getElementById('cardNumber3')?.value || '').replace(/\D/g,'');
        const p4 = (document.getElementById('cardNumber4')?.value || '').replace(/\D/g,'');
        const number = `${p1}${p2}${p3}${p4}`;
        const brandSel = document.getElementById('cardBrand').value;
        if(!luhnOk(number)) { alert('Card number is invalid (Luhn check)'); return; }
        const last4 = number.replace(/\D/g,'').slice(-4);
        const brand = brandSel || brandFromNumber(number);
        const token = btoa(`${brand}-${last4}-${Date.now()}`);
        const cards = getCards();
        cards.push({ brand, last4, token });
        saveCards(cards);
        populateSelect();
        bootstrap.Modal.getInstance(document.getElementById('cardModal'))?.hide();
      });
    }

    if (select) {
      select.addEventListener('change', ()=>{
        const idx = parseInt(select.value,10);
        const cards = getCards();
        const c = cards[idx];
        const brandEl = document.getElementById('card_brand');
        const last4El = document.getElementById('card_last4');
        const tokenEl = document.getElementById('card_token');
        if (c) {
          brandEl && (brandEl.value = c.brand);
          last4El && (last4El.value = c.last4);
          tokenEl && (tokenEl.value = c.token);
        } else {
          brandEl && (brandEl.value = '');
          last4El && (last4El.value = '');
          tokenEl && (tokenEl.value = '');
        }
      });
    }

    // Auto-advance for card parts and uppercase for holder
    const parts = ['cardNumber1','cardNumber2','cardNumber3','cardNumber4'].map(id=>document.getElementById(id));
    parts.forEach((inp, idx)=>{
      if(!inp) return;
      inp.addEventListener('input', ()=>{
        inp.value = inp.value.replace(/\D/g,'').slice(0,4);
        if (inp.value.length === 4 && parts[idx+1]) parts[idx+1].focus();
      });
      inp.addEventListener('keydown', (e)=>{
        if (e.key === 'Backspace' && inp.selectionStart === 0 && parts[idx-1]) parts[idx-1].focus();
      });
    });
    const holder = document.getElementById('cardHolder');
    if (holder) holder.addEventListener('input', ()=>{ holder.value = holder.value.toUpperCase(); });
  }

  function init(){ populateSelect(); bind(); }
  return { init };
})();
