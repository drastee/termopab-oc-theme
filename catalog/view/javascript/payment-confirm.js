/**
 * Termopab: обробка кнопки «Підтвердити замовлення» без jQuery.
 * Штатні шаблони способів оплати (bank_transfer тощо) використовують $ — підставляємо свою логіку.
 */
(function () {
  function getConfirmUrl(button) {
    var container = button.closest('fieldset') || button.closest('[class*="payment"]') || button.closest('form') || button.parentElement;
    if (!container) return null;
    var script = container.querySelector('script');
    if (!script || !script.textContent) return null;
    var m = script.textContent.match(/url:\s*['"]([^'"]+)['"]/);
    return m ? m[1] : null;
  }

  function handleConfirm(e) {
    var btn = e.target.id === 'button-confirm' ? e.target : (e.target.closest && e.target.closest('#button-confirm'));
    if (!btn) return;
    e.preventDefault();
    e.stopImmediatePropagation();

    var url = getConfirmUrl(btn);
    if (!url) {
      console.warn('Termopab payment-confirm: URL не знайдено');
      return;
    }

    btn.disabled = true;
    fetch(url, { method: 'get', credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (json) {
        if (json && json.error) {
          var alertBox = document.getElementById('alert');
          if (alertBox) {
            var div = document.createElement('div');
            div.className = 'alert alert-danger alert-dismissible';
            div.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> ' + (json.error || '') + ' <button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            alertBox.insertBefore(div, alertBox.firstChild);
          }
        }
        if (json && json.redirect) {
          location = json.redirect;
        }
      })
      .catch(function (err) {
        console.error('Termopab payment-confirm:', err);
      })
      .finally(function () {
        btn.disabled = false;
      });
  }

  document.body.addEventListener('click', handleConfirm, true);
})();
