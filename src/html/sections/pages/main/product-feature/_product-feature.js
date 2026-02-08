import Swiper from 'swiper';
import { Navigation } from 'swiper/modules';

document.addEventListener("DOMContentLoaded", () => {
    const container = document.querySelector('.product-feature__inner');
    if (!container) return;

    const currentEl = container.querySelector('.swiper-controls__current');
    const totalEl = container.querySelector('.swiper-controls__total');

    new Swiper(container, {
        modules: [Navigation],
        loop: false,
        slidesPerView: 1,
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
