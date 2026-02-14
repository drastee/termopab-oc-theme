import Swiper from "swiper";
import { Navigation } from "swiper/modules";

document.querySelectorAll(".cases__slider").forEach((el) => {
    new Swiper(el, {
        modules: [Navigation],
        slidesPerView: 5,
        spaceBetween: 0,
        loop: true,
        centeredSlides: true,

        navigation: {
            nextEl: el.querySelector(".oval-nav__next"),
            prevEl: el.querySelector(".oval-nav__prev")
        },

        breakpoints: {
            0: { slidesPerView: 1.3, centeredSlides: false, navigation: false},
            480: { slidesPerView: 1.8},
            768: { slidesPerView: 2.5},
            1200: { slidesPerView: 3}
        }
    });
});
