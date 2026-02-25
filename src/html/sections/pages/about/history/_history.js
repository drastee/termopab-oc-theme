import Swiper from 'swiper';
import { Navigation } from 'swiper/modules';
import 'swiper/css';

import mockData from './_history.mock.json';

export function initHistory(dataInput = null) {
  const root = document.querySelector('#history');
  if (!root) return;

  const data = dataInput || mockData;
  // Страховка от пустых данных
  if (!data || !data.length) return;

  const wrapper = root.querySelector('.swiper-wrapper');
  
  // 1. РЕНДЕР (Чистый, без лишних дивов)
  if (wrapper) {
    wrapper.innerHTML = data
      .map(item => `<div class="swiper-slide history__year">${item.year}</div>`)
      .join('');
  }

  const contentEl = root.querySelector('#history-content');
  const navNext = root.querySelector('.oval-nav__next');
  const navPrev = root.querySelector('.oval-nav__prev');

  // 2. ИНИЦИАЛИЗАЦИЯ
  const swiper = new Swiper(root.querySelector('.history__years-slider'), {
    modules: [Navigation],
    
    // Слайды резиновые по ширине текста
    slidesPerView: 'auto',
    
    // ВАЖНО: Активный слайд всегда прижат к левому краю
    centeredSlides: false,
    
    // Отступ между годами
    //spaceBetween: 25, 
    
    // Скорость анимации движения ленты
    speed: 600,
    
    // ГЛАВНЫЙ ФИКС: Добавляем пустое пространство в конце программно.
    // Берем ширину экрана, чтобы последний слайд мог уехать влево до упора.

    // Разрешаем клик. В комбинации с centeredSlides: false 
    // это заставит ленту прокрутиться так, чтобы кликнутый слайд стал первым.
    slideToClickedSlide: true,
    

    // Исправление багов с размерами
    watchSlidesProgress: true,
    normalizeSlideIndex: true,

    slidesOffsetAfter: window.innerWidth / 1.5, 

    breakpoints: {
      789:{
        spaceBetween: 63, 
      }
    },
    navigation: {
      nextEl: navNext,
      prevEl: navPrev,
    },
    
    on: {
      init: (s) => updateContent(s.activeIndex),
      slideChange: (s) => updateContent(s.activeIndex),
      // Необязательно: обновляем offset при ресайзе окна
      resize: (s) => {
          s.params.slidesOffsetAfter = window.innerWidth / 1.5;
          s.update();
      }
    }
  });

  function updateContent(index) {
    if (!contentEl || !data[index]) return;
    
    const item = data[index];
    
    // Анимация текста
    contentEl.style.opacity = '0';
    // Можно добавить transform для красивого выезда
    contentEl.style.transform = 'translateY(10px)'; 
    
    setTimeout(() => {
        contentEl.innerHTML = item.content;
        contentEl.style.opacity = '1';
        contentEl.style.transform = 'translateY(0)';
    }, 200);
  }
}

// Данные из OpenCart модуля (script#history-data) или mock для статики
function getHistoryData() {
  const el = document.getElementById('history-data');
  if (el) {
    if (!el.textContent) return [];
    try {
      const data = JSON.parse(el.textContent);
      return Array.isArray(data) ? data : [];
    } catch (e) {
      return [];
    }
  }
  return mockData;
}

document.addEventListener('DOMContentLoaded', () => {
  if (document.querySelector('#history')) {
    initHistory(getHistoryData());
  }
});

if (typeof window !== 'undefined') {
  window.initHistory = initHistory;
}
