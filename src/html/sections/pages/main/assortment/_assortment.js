import Swiper from 'swiper';
import { Navigation } from 'swiper/modules';

document.addEventListener("DOMContentLoaded", () => {
    const container = document.querySelector('.assortment__inner');
    if (!container) return;

    const currentEl = document.querySelector('.swiper-controls__current');
    const totalEl = document.querySelector('.swiper-controls__total');

    const swiper = new Swiper(container, {
        modules: [Navigation],
        loop: false,
        slidesPerView: 1,
        
        spaceBetween: 50,
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev'
        },
        on: {
            init: function () {
                totalEl.textContent = this.slides.length;
                currentEl.textContent = this.realIndex + 1;
            },
            slideChange: function () {
                currentEl.textContent = this.realIndex + 1;
            }
        }
    });
});
