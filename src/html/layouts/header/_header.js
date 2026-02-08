document.addEventListener("DOMContentLoaded", () => {
    const header = document.querySelector('.header');

    function onScroll() {
        if (window.scrollY > 10) {
            header.classList.add('header--scrolled');
        } else {
            header.classList.remove('header--scrolled');
        }
    }

    window.addEventListener('scroll', onScroll);
    onScroll();
});
