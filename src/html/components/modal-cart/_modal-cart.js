const modal = document.querySelector('#order-modal');
const openBtns = document.querySelectorAll('.open-order-btn');
const closeBtn = document.querySelector('.popup-close');

function fillModalProduct(title, price, thumb) {
  const imgEl = document.getElementById('modal-cart-product-img');
  const titleEl = document.getElementById('modal-cart-product-title');
  const priceEl = document.getElementById('modal-cart-product-price');
  if (imgEl) {
    if (thumb) {
      imgEl.src = thumb;
      imgEl.alt = title || '';
      imgEl.style.display = '';
    } else {
      imgEl.style.display = 'none';
    }
  }
  if (titleEl) titleEl.textContent = title || '';
  if (priceEl) priceEl.textContent = price || '';
}

function closeModal() {
  modal.close();
  document.body.style.overflow = '';
}

openBtns.forEach(btn => {
  btn.addEventListener('click', () => {
    const productId = btn.dataset.productId;
    const cartAddUrl = btn.dataset.cartAdd;
    const cartInfoUrl = btn.dataset.cartInfo;
    const title = btn.dataset.productTitle || '';
    const price = btn.dataset.productPrice || '';
    const thumb = btn.dataset.productThumb || '';

    if (productId && cartAddUrl) {
      btn.disabled = true;
      fetch(cartAddUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ product_id: productId, quantity: 1 })
      })
        .then(r => r.json())
        .then(json => {
          if (json.error) {
            const msg = typeof json.error === 'string' ? json.error : (json.error.warning || Object.values(json.error).join(' '));
            alert(msg);
            return;
          }
          if (cartInfoUrl) {
            const cartEl = document.querySelector('#cart');
            if (cartEl) {
              fetch(cartInfoUrl).then(res => res.text()).then(html => { cartEl.innerHTML = html; });
            }
          }
          fillModalProduct(title, price, thumb);
          modal.showModal();
          document.body.style.overflow = 'hidden';
        })
        .catch(err => {
          console.error(err);
          alert('Помилка додавання до кошика');
        })
        .finally(() => { btn.disabled = false; });
    } else {
      fillModalProduct(title, price, thumb);
      modal.showModal();
      document.body.style.overflow = 'hidden';
    }
  });
});

if (closeBtn) closeBtn.addEventListener('click', closeModal);

modal.addEventListener('mousedown', (e) => {
  const container = modal.querySelector('.modal-cart__container');
  if (container && !container.contains(e.target)) {
    closeModal();
  }
});

const form = document.getElementById('modal-cart-form');
if (form) {
  const msg = () => (typeof window.MODAL_CART_MESSAGES !== 'undefined' ? window.MODAL_CART_MESSAGES : {});

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

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    const m = msg();
    const nameEl = form.querySelector('[name="name"]');
    const phoneEl = form.querySelector('[name="phone"]');
    const emailEl = form.querySelector('[name="email"]');
    const agreementEl = form.querySelector('[name="agreement"]');

    showError('modal-cart-error-name', '');
    showError('modal-cart-error-phone', '');
    showError('modal-cart-error-email', '');
    showError('modal-cart-error-agreement', '');
    setInvalid(nameEl, false);
    setInvalid(phoneEl, false);
    setInvalid(emailEl, false);

    let valid = true;

    if (!(nameEl && nameEl.value.trim())) {
      showError('modal-cart-error-name', m.required);
      setInvalid(nameEl, true);
      valid = false;
    }
    if (!(phoneEl && phoneEl.value.trim())) {
      showError('modal-cart-error-phone', m.required);
      setInvalid(phoneEl, true);
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

    // TODO: submit form (e.g. AJAX to your endpoint)
  });

  [form.querySelector('[name="name"]'), form.querySelector('[name="phone"]'), form.querySelector('[name="email"]')].forEach((input) => {
    if (input) {
      input.addEventListener('input', () => {
        showError('modal-cart-error-' + input.name, '');
        setInvalid(input, false);
      });
    }
  });
  const agreementInput = form.querySelector('[name="agreement"]');
  if (agreementInput) {
    agreementInput.addEventListener('change', () => showError('modal-cart-error-agreement', ''));
  }
}