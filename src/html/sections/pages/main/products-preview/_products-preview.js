import Swiper from 'swiper';
import { Navigation } from 'swiper/modules';
import 'swiper/css';

document.addEventListener('DOMContentLoaded', () => {
    new Swiper('.products-preview__slider', {
        modules: [Navigation],
        slidesPerView: 1,
        spaceBetween: 30,
        autoHeight: true,
        loopAdditionalSlides: 1,

        navigation: {
            nextEl: '.products-preview__next',
            prevEl: '.products-preview__prev',
        },

        breakpoints: {
            768: {
                slidesPerView: 1.2,
            },
            1024: {
                autoHeight: false,
                slidesPerView: 1.5,
            },
            1280: {
                slidesPerView: 2,
                spaceBetween: 0
            }
        }
    });
});

