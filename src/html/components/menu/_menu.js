function initMenu() {
  
  // --- 1. Мобильное меню (Твой код) ---
  const menuButton = document.querySelector('.menu-button');
  const menu = document.getElementById('mobile-menu');
  const menuWrapper = menu?.querySelector('.main-wrapper');

  const isDesktop = () => window.matchMedia('(min-width: 992px)').matches;

  const openMenu = () => {
      if (!menuButton || !menu) return;
      menuButton.setAttribute('aria-expanded', 'true');
      menuButton.classList.add('is-active');
      menu.hidden = false;
      menu.classList.add('is-open');
      document.body.classList.add('no-scroll');
  };

  const closeMenu = () => {
      if (!menuButton || !menu) return;
      menuButton.setAttribute('aria-expanded', 'false');
      menuButton.classList.remove('is-active');
      menu.hidden = true;
      menu.classList.remove('is-open');
      document.body.classList.remove('no-scroll');
  };

  const toggleMenu = () => {
      if (!menuButton || !menu) return;
      const isOpen = menuButton.getAttribute('aria-expanded') === 'true';
      if (isOpen) {
          closeMenu();
      } else {
          openMenu();
      }
  };

  if (menuButton && menu) {
      menuButton.addEventListener('click', () => {
          if (isDesktop()) return;
          toggleMenu();
      });

      let closeTimer;
      const closeDelayMs = 350;

      const scheduleClose = () => {
          if (!isDesktop()) return;
          window.clearTimeout(closeTimer);
          closeTimer = window.setTimeout(() => {
              const isHoveringButton = menuButton.matches(':hover');
              const isHoveringWrapper = menuWrapper ? menuWrapper.matches(':hover') : false;
              if (isHoveringButton || isHoveringWrapper) return;
              closeMenu();
          }, closeDelayMs);
      };

      const cancelClose = () => {
          window.clearTimeout(closeTimer);
      };

      menuButton.addEventListener('mouseenter', () => {
          if (!isDesktop()) return;
          cancelClose();
          openMenu();
      });

      menuButton.addEventListener('mouseleave', () => {
          scheduleClose();
      });

      if (menuWrapper) {
          menuWrapper.addEventListener('mouseenter', () => {
              if (!isDesktop()) return;
              cancelClose();
          });

          menuWrapper.addEventListener('mouseleave', () => {
              scheduleClose();
          });
      }
  }

  // --- 2. Механика подменю: на десктопе — переход по ссылке, на мобилке — раскрытие ---
  document.querySelectorAll('.nav__submenu').forEach(submenu => {
      const trigger = submenu.previousElementSibling;
      if (!trigger) return;
      trigger.addEventListener('click', function(e) {
          if (isDesktop()) return; // десктоп: стандартный переход по href
          e.preventDefault();
          submenu.hidden = !submenu.hidden;
          this.classList.toggle('is-active', !submenu.hidden);
      });

      const parentItem = submenu.closest('li');
      if (!parentItem) return;

      parentItem.addEventListener('mouseenter', () => {
          if (!isDesktop()) return;
          submenu.hidden = false;
          trigger.classList.add('is-active');
      });

      parentItem.addEventListener('mouseleave', () => {
          if (!isDesktop()) return;
          submenu.hidden = true;
          trigger.classList.remove('is-active');
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

}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initMenu);
} else {
  initMenu();
}