import Swiper from "swiper";
import { Navigation } from "swiper/modules";

new Swiper(".cases__slider", {
    modules: [Navigation],
    slidesPerView: 5,
    spaceBetween: 0,
    loop: true,
    centeredSlides: true,

    navigation: {
        nextEl: ".cases__slider .oval-nav__next",
        prevEl: ".cases__slider .oval-nav__prev"
    },

    breakpoints: {
        0: { slidesPerView: 1.3, centeredSlides: false, navigation: false},
        480: { slidesPerView: 1.8},
        768: { slidesPerView: 2.5},
        1200: { slidesPerView: 3}
    }
});
