document.addEventListener("DOMContentLoaded", () => {
    // Выбираем все элементы, которые должны вращаться
    const rotateElements = document.querySelectorAll(".js-rotate-item");
    if (rotateElements.length === 0) return;

    const isMobile = window.matchMedia("(pointer: coarse)").matches;
    let lastScrollY = window.scrollY;

    // Массив для хранения данных каждого элемента
    const items = Array.from(rotateElements).map(el => ({
        el: el,
        rotation: 0,
        targetRotation: 0,
        lastAngle: null
    }));

    /* ===== Утилита расчета угла ===== */
    function getAngle(event, el) {
        const rect = el.getBoundingClientRect();
        const centerX = rect.left + rect.width / 2;
        const centerY = rect.top + rect.height / 2;
        const dx = event.clientX - centerX;
        const dy = event.clientY - centerY;
        return Math.atan2(dy, dx);
    }

    /* ===== DESKTOP: Мышь ===== */
    if (!isMobile) {
        document.addEventListener("mousemove", (event) => {
            items.forEach(item => {
                const angle = getAngle(event, item.el);

                if (item.lastAngle !== null) {
                    let delta = angle - item.lastAngle;
                    if (delta > Math.PI) delta -= Math.PI * 2;
                    if (delta < -Math.PI) delta += Math.PI * 2;
                    item.targetRotation += delta * (180 / Math.PI);
                }
                item.lastAngle = angle;
            });
        });
    }

    /* ===== MOBILE: Скролл ===== */
    if (isMobile) {
        window.addEventListener("scroll", () => {
            const deltaScroll = window.scrollY - lastScrollY;
            items.forEach(item => {
                item.targetRotation += deltaScroll * 0.35;
            });
            lastScrollY = window.scrollY;
        }, { passive: true });
    }

    /* ===== АНИМАЦИЯ (Общий цикл) ===== */
    function animate() {
        items.forEach(item => {
            // Плавный переход
            item.rotation += (item.targetRotation - item.rotation) * 0.12;
            item.el.style.transform = `rotate(${item.rotation}deg)`;
        });
        requestAnimationFrame(animate);
    }

    animate();
});