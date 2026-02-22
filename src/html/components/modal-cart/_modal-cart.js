const modal = document.querySelector('#order-modal');
const openBtns = document.querySelectorAll('.open-order-btn');
const closeBtn = document.querySelector('.popup-close');

const MODAL_CART_DRAFT_KEY = 'termopab_modal_cart_draft';

const productsUrl = () => (typeof window.MODAL_CART_PRODUCTS_URL !== 'undefined' ? window.MODAL_CART_PRODUCTS_URL : '');
const removeUrl = () => (typeof window.MODAL_CART_REMOVE_URL !== 'undefined' ? window.MODAL_CART_REMOVE_URL : '');

function refreshCartHeader(cartInfoUrl) {
  if (cartInfoUrl) {
    const cartEl = document.querySelector('#cart');
    if (cartEl) {
      fetch(cartInfoUrl, { credentials: 'same-origin' }).then((res) => res.text()).then((html) => { cartEl.innerHTML = html; });
    }
  }
}

function loadAndRenderCartProducts() {
  const container = document.getElementById('modal-cart-products');
  const emptyEl = document.getElementById('modal-cart-products-empty');
  if (!container) return;

  const url = productsUrl();
  if (!url) {
    if (emptyEl) emptyEl.style.display = '';
    return;
  }

  fetch(url, { credentials: 'same-origin', cache: 'no-store' })
    .then((r) => r.json())
    .then((data) => {
      const products = data.products || [];
      container.querySelectorAll('.cart-product').forEach((el) => el.remove());
      if (emptyEl) emptyEl.style.display = products.length ? 'none' : '';

      products.forEach((p) => {
        const card = document.createElement('div');
        card.className = 'cart-product';
        card.dataset.cartId = String(p.cart_id);
        const imgSrc = p.thumb || '';
        const priceLine = p.quantity > 1
          ? (p.price_text || '') + ' × ' + p.quantity + ' = ' + (p.total_text || '')
          : (p.price_text || p.total_text || '');
        card.innerHTML =
          '<div class="cart-product__image">' +
          (imgSrc ? '<img src="' + imgSrc.replace(/"/g, '&quot;') + '" alt="" class="img-base">' : '') +
          '</div>' +
          '<div class="cart-product__info">' +
          '<h3 class="cart-product__title">' + (p.name || '').replace(/</g, '&lt;') + '</h3>' +
          '<p class="cart-product__price">' + priceLine + '</p>' +
          '</div>' +
          '<button type="button" class="cart-product__remove" aria-label="Видалити з кошика" data-cart-id="' +
          String(p.cart_id).replace(/"/g, '&quot;') + '">&times;</button>';
        if (emptyEl) {
          container.insertBefore(card, emptyEl);
        } else {
          container.appendChild(card);
        }
      });
    })
    .catch(() => {
      if (emptyEl) emptyEl.style.display = '';
    });
}

function closeModal() {
  if (modal) modal.close();
  document.body.style.overflow = '';
}

if (openBtns && openBtns.length) {
  openBtns.forEach((btn) => {
    btn.addEventListener('click', () => {
      const productId = btn.dataset.productId;
      const cartAddUrl = btn.dataset.cartAdd;
      const cartInfoUrl = btn.dataset.cartInfo || '';

      if (productId && cartAddUrl) {
        btn.disabled = true;
        fetch(cartAddUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({ product_id: productId, quantity: 1 }),
          credentials: 'same-origin'
        })
          .then((r) => r.json())
          .then((json) => {
            if (json.error) {
              const msg = typeof json.error === 'string' ? json.error : (json.error.warning || Object.values(json.error).join(' '));
              alert(msg);
              return;
            }
            refreshCartHeader(cartInfoUrl);
            loadAndRenderCartProducts();
            if (modal) {
              modal.showModal();
              document.body.style.overflow = 'hidden';
            }
          })
          .catch((err) => {
            console.error(err);
            alert('Помилка додавання до кошика');
          })
          .finally(() => { btn.disabled = false; });
      } else {
        loadAndRenderCartProducts();
        if (modal) {
          modal.showModal();
          document.body.style.overflow = 'hidden';
        }
      }
    });
  });
}

document.getElementById('modal-cart-products')?.addEventListener('click', (e) => {
  const removeBtn = e.target.closest('.cart-product__remove');
  if (!removeBtn) return;
  e.preventDefault();
  const cartId = removeBtn.dataset.cartId;
  const cartInfoUrl = document.querySelector('.open-order-btn')?.dataset.cartInfo || '';
  const url = removeUrl();
  if (!cartId || !url) return;
  removeBtn.disabled = true;
  fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ key: cartId }),
    credentials: 'same-origin'
  })
    .then((r) => r.json())
    .then((json) => {
      if (json.error) return;
      refreshCartHeader(cartInfoUrl);
      loadAndRenderCartProducts();
    })
    .finally(() => { removeBtn.disabled = false; });
});

if (closeBtn) closeBtn.addEventListener('click', closeModal);

if (modal) {
  modal.addEventListener('mousedown', (e) => {
    const container = modal.querySelector('.modal-cart__container');
    if (container && !container.contains(e.target)) {
      closeModal();
    }
  });
}

const form = document.getElementById('modal-cart-form');
if (form) {
  const msg = () => (typeof window.MODAL_CART_MESSAGES !== 'undefined' ? window.MODAL_CART_MESSAGES : {});
  const saveUrl = () => (typeof window.MODAL_CART_SAVE_URL !== 'undefined' ? window.MODAL_CART_SAVE_URL : '');
  const zonesUrl = () => (typeof window.MODAL_CART_ZONES_URL !== 'undefined' ? window.MODAL_CART_ZONES_URL : '');

  function showError(id, text) {
    const el = document.getElementById(id);
    if (el) {
      el.textContent = text || '';
      el.style.display = text ? '' : 'none';
    }
  }

  function setInvalid(input, invalid) {
    if (!input) return;
    input.classList.toggle('is-invalid', !!invalid);
    input.setAttribute('aria-invalid', invalid ? 'true' : 'false');
  }

  function validateEmail(value) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test((value || '').trim());
  }

  // Zones: when country select changes, fetch zones and fill zone select
  function bindZones() {
    const countrySelects = form.querySelectorAll('select.modal-cart-country');
    countrySelects.forEach((countrySelect) => {
      const zoneSelect = countrySelect.id ? form.querySelector('select.modal-cart-zone[data-country-target="' + countrySelect.id + '"]') : null;
      if (!zoneSelect || countrySelect._zonesBound) return;
      countrySelect._zonesBound = true;
      countrySelect.addEventListener('change', () => {
        const countryId = countrySelect.value || 0;
        const url = zonesUrl();
        if (!url) return;
        fetch(url + (url.indexOf('?') >= 0 ? '&' : '?') + 'country_id=' + encodeURIComponent(countryId), { credentials: 'same-origin' })
          .then(r => r.json())
          .then((data) => {
            const zones = data.zones || [];
            const current = zoneSelect.value;
            zoneSelect.innerHTML = '<option value="">' + (form.querySelector('[data-text-select]') ? form.querySelector('[data-text-select]').dataset.textSelect : '---') + '</option>';
            zones.forEach((z) => {
              const opt = document.createElement('option');
              opt.value = z.zone_id;
              opt.textContent = z.name;
              zoneSelect.appendChild(opt);
            });
            if (current && zones.some((z) => String(z.zone_id) === String(current))) {
              zoneSelect.value = current;
            }
          })
          .catch(() => {});
      });
    });
  }
  bindZones();

  // Same-address checkbox: show/hide shipping fields
  const addressMatch = form.querySelector('[name="address_match"]');
  const shippingFields = document.getElementById('modal-cart-shipping-fields');
  if (addressMatch && shippingFields) {
    function toggleShipping() {
      const hide = addressMatch.checked;
      shippingFields.style.display = hide ? 'none' : '';
      shippingFields.querySelectorAll('input, select, textarea').forEach((el) => {
        el.disabled = hide;
        if (hide) el.removeAttribute('required');
        else if (el.closest('.required')) el.setAttribute('required', 'required');
      });
    }
    addressMatch.addEventListener('change', toggleShipping);
    toggleShipping();
  }

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    const m = msg();
    const firstnameEl = form.querySelector('[name="firstname"]');
    const lastnameEl = form.querySelector('[name="lastname"]');
    const telephoneEl = form.querySelector('[name="telephone"]');
    const emailEl = form.querySelector('[name="email"]');
    const agreementEl = form.querySelector('[name="agreement"]');

    // Clear previous errors
    form.querySelectorAll('.modal-cart__error').forEach((el) => {
      el.textContent = '';
      el.style.display = 'none';
    });
    form.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));

    let valid = true;

    if (!(firstnameEl && firstnameEl.value.trim())) {
      showError('modal-cart-error-firstname', m.required);
      setInvalid(firstnameEl, true);
      valid = false;
    }
    if (!(lastnameEl && lastnameEl.value.trim())) {
      showError('modal-cart-error-lastname', m.required);
      setInvalid(lastnameEl, true);
      valid = false;
    }
    if (!(telephoneEl && telephoneEl.value.trim())) {
      showError('modal-cart-error-telephone', m.required);
      setInvalid(telephoneEl, true);
      valid = false;
    }
    const emailVal = emailEl ? emailEl.value.trim() : '';
    if (!emailVal) {
      showError('modal-cart-error-email', m.required);
      setInvalid(emailEl, true);
      valid = false;
    } else if (!validateEmail(emailVal)) {
      showError('modal-cart-error-email', m.email);
      setInvalid(emailEl, true);
      valid = false;
    }
    if (!(agreementEl && agreementEl.checked)) {
      showError('modal-cart-error-agreement', m.agreement);
      valid = false;
    }

    if (!valid) return;

    const save = saveUrl();
    if (!save) {
      console.warn('MODAL_CART_SAVE_URL not set');
      return;
    }

    const formData = new FormData(form);
    if (agreementEl && agreementEl.checked) {
      formData.set('agreement', '1');
    }
    const body = new URLSearchParams(formData);

    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) submitBtn.disabled = true;

    fetch(save, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body.toString(),
      credentials: 'same-origin'
    })
      .then((r) => r.json())
      .then((json) => {
        // Сначала показываем ошибки — редирект только если ошибок нет
        if (json.error && typeof json.error === 'object') {
          let firstErrorEl = null;
          Object.keys(json.error).forEach((key) => {
            const errId = 'modal-cart-error-' + key.replace(/_/g, '-');
            showError(errId, json.error[key]);
            const input = form.querySelector('[name="' + key.replace(/(_[0-9]+)$/, '') + '"]') || form.querySelector('[name="' + key + '"]') || document.getElementById(errId.replace('modal-cart-error-', 'modal-cart-'));
            if (input) setInvalid(input, true);
            if (!firstErrorEl) firstErrorEl = document.getElementById(errId) || input;
          });
          if (firstErrorEl) firstErrorEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
          return;
        }
        if (json.redirect) {
          clearDraft();
          window.location.href = json.redirect;
        }
      })
      .catch((err) => {
        console.error(err);
        alert('Помилка відправки форми');
      })
      .finally(() => {
        if (submitBtn) submitBtn.disabled = false;
      });
  });

  ['firstname', 'lastname', 'telephone', 'email'].forEach((name) => {
    const input = form.querySelector('[name="' + name + '"]');
    if (input) {
      input.addEventListener('input', () => {
        showError('modal-cart-error-' + name, '');
        setInvalid(input, false);
      });
    }
  });
  const agreementInput = form.querySelector('[name="agreement"]');
  if (agreementInput) {
    agreementInput.addEventListener('change', () => showError('modal-cart-error-agreement', ''));
  }

  const paymentExtra = document.getElementById('modal-cart-payment-extra');
  const paymentRadios = form.querySelectorAll('input[name="payment_method"]');
  function togglePaymentContent() {
    const selected = form.querySelector('input[name="payment_method"]:checked');
    if (!paymentExtra || !selected) return;
    const code = selected.value;
    paymentExtra.querySelectorAll('.modal-cart__payment-method-content').forEach((el) => {
      el.style.display = el.dataset.paymentCode === code ? 'block' : 'none';
    });
  }
  if (paymentRadios.length) {
    paymentRadios.forEach((radio) => radio.addEventListener('change', togglePaymentContent));
    togglePaymentContent();
  }

  // Чернетка форми в localStorage — відновлення після перезавантаження (як у стандартному checkout)
  function getFormDraft() {
    const draft = {};
    for (const el of form.elements) {
      if (!el.name || el.disabled) continue;
      if (el.type === 'radio') {
        if (el.checked) draft[el.name] = el.value;
      } else if (el.type === 'checkbox') {
        draft[el.name] = el.checked ? (el.value || '1') : '';
      } else {
        draft[el.name] = el.value;
      }
    }
    return draft;
  }

  function applyFormDraft(data) {
    if (!data || typeof data !== 'object') return;
    for (const name of Object.keys(data)) {
      const value = data[name];
      const els = form.querySelectorAll('[name="' + name.replace(/[[\]]/g, '\\$&') + '"]');
      if (!els.length) continue;
      const first = els[0];
      if (first.type === 'radio') {
        els.forEach((r) => { r.checked = (r.value === value); });
      } else if (first.type === 'checkbox') {
        first.checked = (value === '1' || value === true || value === 'on' || value === 1);
      } else {
        first.value = value == null ? '' : String(value);
      }
    }
    if (addressMatch && shippingFields) {
      const hide = addressMatch.checked;
      shippingFields.style.display = hide ? 'none' : '';
      shippingFields.querySelectorAll('input, select, textarea').forEach((el) => {
        el.disabled = hide;
        if (hide) el.removeAttribute('required');
        else if (el.closest('.required')) el.setAttribute('required', 'required');
      });
    }
    togglePaymentContent();
  }

  function saveDraft() {
    try {
      localStorage.setItem(MODAL_CART_DRAFT_KEY, JSON.stringify(getFormDraft()));
    } catch (e) {}
  }

  function restoreDraft() {
    try {
      const raw = localStorage.getItem(MODAL_CART_DRAFT_KEY);
      if (raw) applyFormDraft(JSON.parse(raw));
    } catch (e) {}
  }

  function clearDraft() {
    try {
      localStorage.removeItem(MODAL_CART_DRAFT_KEY);
    } catch (e) {}
  }

  let draftSaveTimer = null;
  form.addEventListener('input', () => {
    clearTimeout(draftSaveTimer);
    draftSaveTimer = setTimeout(saveDraft, 400);
  });
  form.addEventListener('change', () => {
    clearTimeout(draftSaveTimer);
    draftSaveTimer = setTimeout(saveDraft, 400);
  });

  restoreDraft();
}
