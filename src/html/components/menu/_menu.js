document.addEventListener('DOMContentLoaded', () => {
    
  // --- 1. Мобильное меню (Твой код) ---
  const menuButton = document.querySelector('.menu-button');
  const menu = document.getElementById('mobile-menu');

  if (menuButton && menu) {
      menuButton.addEventListener('click', () => {
          const isOpen = menuButton.getAttribute('aria-expanded') === 'true';

          menuButton.setAttribute('aria-expanded', String(!isOpen));
          menuButton.classList.toggle('is-active', !isOpen);

          menu.hidden = isOpen;
          menu.classList.toggle('is-open', !isOpen);

          document.body.classList.toggle('no-scroll', !isOpen);
      });
  }

  // --- 2. Механика подменю: на десктопе — переход по ссылке, на мобилке — раскрытие ---
  document.querySelectorAll('.nav__submenu').forEach(submenu => {
      const trigger = submenu.previousElementSibling;
      if (!trigger) return;
      trigger.addEventListener('click', function(e) {
          if (window.matchMedia('(min-width: 992px)').matches) return; // десктоп: стандартный переход по href
          e.preventDefault();
          submenu.hidden = !submenu.hidden;
          this.classList.toggle('is-active', !submenu.hidden);
      });
  });

  // --- 3. Плавная смена картинок (Cross-fade) ---
  const imageTriggers = document.querySelectorAll('[data-image]');
  const columnsArea = document.querySelector('.nav__columns');
  
  // Находим активную картинку при загрузке, чтобы запомнить дефолтную
  let defaultImageSrc = '';
  const initialActive = document.querySelector('.nav__image img.active');
  if (initialActive) {
      defaultImageSrc = initialActive.src;
  }

  // Функция переключения
  function switchImage(newSrc) {
      // Находим, кто сейчас активен, а кто ждет (буфер)
      const currentActive = document.querySelector('.nav__image img.active');
      const nextImage = document.querySelector('.nav__image img:not(.active)');

      // Проверки безопасности:
      // 1. Если картинок нет в HTML (не добавил второй тег) - выходим
      if (!currentActive || !nextImage) return;
      // 2. Если ссылки нет или она уже активна - выходим
      if (!newSrc || currentActive.src.includes(newSrc)) return;

      // Грузим фото в скрытую картинку
      nextImage.src = newSrc;

      // Когда загрузилось — плавно показываем
      nextImage.onload = () => {
          nextImage.classList.add('active');
          currentActive.classList.remove('active');
      };
  }

  // Слушатели наведения
  imageTriggers.forEach(trigger => {
      trigger.addEventListener('mouseenter', () => {
          const newSrc = trigger.getAttribute('data-image');
          switchImage(newSrc);
      });
  });

  // Возврат к дефолтной картинке при уходе
  if (columnsArea) {
      columnsArea.addEventListener('mouseleave', () => {
          switchImage(defaultImageSrc);
      });
  }

});