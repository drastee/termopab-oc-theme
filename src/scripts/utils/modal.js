const SCROLL_LOCK_KEY = 'termopabScrollLocked';
const SCROLL_Y_KEY = 'termopabScrollY';

function ensureDialogSupport(modal) {
  if (!modal) return;
  if (typeof modal.showModal === 'function') return;
  const polyfill = window.dialogPolyfill;
  if (polyfill && typeof polyfill.registerDialog === 'function') {
    try {
      polyfill.registerDialog(modal);
    } catch {
      // ignore
    }
  }
}

function setBodyLocked(locked) {
  if (locked) {
    if (document.body.dataset[SCROLL_LOCK_KEY] === '1') return;
    const y = window.scrollY || 0;
    document.body.dataset[SCROLL_LOCK_KEY] = '1';
    document.body.dataset[SCROLL_Y_KEY] = String(y);
    document.body.style.position = 'fixed';
    document.body.style.top = '-' + y + 'px';
    document.body.style.left = '0';
    document.body.style.right = '0';
    document.body.style.width = '100%';
  } else {
    if (document.body.dataset[SCROLL_LOCK_KEY] !== '1') {
      document.body.style.position = '';
      document.body.style.top = '';
      document.body.style.left = '';
      document.body.style.right = '';
      document.body.style.width = '';
      return;
    }
    const y = parseInt(document.body.dataset[SCROLL_Y_KEY] || '0', 10) || 0;
    document.body.dataset[SCROLL_LOCK_KEY] = '0';
    document.body.dataset[SCROLL_Y_KEY] = '';

    document.body.style.position = '';
    document.body.style.top = '';
    document.body.style.left = '';
    document.body.style.right = '';
    document.body.style.width = '';

    const html = document.documentElement;
    const prevScrollBehavior = html.style.scrollBehavior;
    html.style.scrollBehavior = 'auto';
    requestAnimationFrame(() => {
      window.scrollTo(0, y);
      html.style.scrollBehavior = prevScrollBehavior;
    });
  }
}

export function createDialogModal(options) {
  const {
    modalSelector,
    innerSelector,
    closeSelector,
    openersSelector,
    lockScroll = true,
    focusSelector,
  } = options || {};

  const modal = modalSelector ? document.querySelector(modalSelector) : null;

  function open() {
    if (!modal) return;
    ensureDialogSupport(modal);

    if (typeof modal.showModal !== 'function') return;

    if (lockScroll) setBodyLocked(true);

    try {
      modal.showModal();
    } catch {
      if (lockScroll) setBodyLocked(false);
      return;
    }

    const focusEl = focusSelector ? modal.querySelector(focusSelector) : modal.querySelector('input, textarea, select, button');
    if (focusEl && typeof focusEl.focus === 'function') {
      requestAnimationFrame(() => {
        try {
          focusEl.focus({ preventScroll: true });
        } catch {
          focusEl.focus();
        }
      });
    }
  }

  function close() {
    if (!modal) return;
    if (typeof modal.close === 'function') {
      modal.close();
    }
    if (lockScroll) setBodyLocked(false);
  }

  if (modal) {
    const inner = innerSelector ? modal.querySelector(innerSelector) : null;
    const closeBtn = closeSelector ? modal.querySelector(closeSelector) : null;

    closeBtn?.addEventListener('click', close);

    if (inner) {
      modal.addEventListener('mousedown', (e) => {
        if (!inner.contains(e.target)) {
          close();
        }
      });
    }

    modal.addEventListener('close', () => {
      if (lockScroll) setBodyLocked(false);
    });
  }

  if (openersSelector) {
    document.querySelectorAll(openersSelector).forEach((btn) => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        open();
      });
    });
  }

  return { modal, open, close };
}
