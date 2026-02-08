import Swiper from 'swiper';
import { Navigation, EffectFade } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/effect-fade';

document.addEventListener('DOMContentLoaded', () => {
    const yearsEl = document.querySelector('.history__years');
    const infoEl = document.querySelector('.history__info');
    
    if (!yearsEl || !infoEl) return;

    // Нижний слайдер (Текст)
    const infoSwiper = new Swiper(infoEl, {
        modules: [EffectFade],
        effect: 'fade',
        fadeEffect: { crossFade: true },
        allowTouchMove: false, 
        autoHeight: true,
        speed: 600
    });

    // Верхний слайдер (Годы)
    const yearsSwiper = new Swiper(yearsEl, {
        modules: [Navigation],
        slidesPerView: 'auto',
        centeredSlides: false, // Оставляем false (нам нужно слева)
        spaceBetween: 50,
        speed: 600,
        
        // --- ГЛАВНЫЙ ФИКС ---
        // Добавляем пустое место в конце, равное ширине экрана. 
        // Это позволит даже последнему слайду встать в самое начало (влево).
        slidesOffsetAfter: window.innerWidth, 
        
        // Важно для корректной работы с изменяемой шириной
        watchSlidesProgress: true,
        normalizeSlideIndex: false,

        navigation: {
            nextEl: '.oval-nav__next',
            prevEl: '.oval-nav__prev',
        },
        
        breakpoints: {
            320: { spaceBetween: 20 },
            768: { spaceBetween: 40 },
            1200: { spaceBetween: 60 }
        },

        on: {
            // Обработка клика
            click: function (swiper) {
                const clickedIndex = swiper.clickedIndex;
                if (typeof clickedIndex === 'undefined') return;

                // Принудительно скроллим к кликнутому слайду
                swiper.slideTo(clickedIndex);
                infoSwiper.slideTo(clickedIndex);
            },
            
            // Синхронизация при листании стрелками
            slideChange: function (swiper) {
                infoSwiper.slideTo(swiper.activeIndex);
            },

            // Пересчет размеров после завершения анимации увеличения шрифта
            transitionEnd: function(swiper) {
                swiper.update();
            }
        }
    });
});