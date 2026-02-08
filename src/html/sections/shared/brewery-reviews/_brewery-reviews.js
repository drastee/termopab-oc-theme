import Swiper from "swiper";
import { Navigation } from "swiper/modules";

new Swiper(".brewery-reviews__slider", {
    modules: [Navigation],
    slidesPerView: 1.1,
    spaceBetween: 20,
    loop: true,
    centeredSlides: true,

    navigation: {
        nextEl: ".oval-nav__next",
        prevEl: ".oval-nav__prev"
    },

    breakpoints: {
        0: { slidesPerView: 1.1, centeredSlides: false},
        768: { slidesPerView: 2},
        1200: { slidesPerView: 3, spaceBetween: 180 }
    }
});
