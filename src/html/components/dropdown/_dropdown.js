document.addEventListener("click", (e) => {
    const dropdown = e.target.closest(".dropdown");

    // 1. Клик вне дропдауна (Закрыть все открытые JS-ом)
    if (!dropdown) {
        document.querySelectorAll(".dropdown.open").forEach(d => {
            d.classList.remove("open");
            const btn = d.querySelector(".dropdown__selected");
            const menu = d.querySelector(".dropdown__menu");
            
            if (btn) btn.setAttribute("aria-expanded", "false");
            if (menu) menu.hidden = true;
        });
        return;
    }

    const selectedBtn = dropdown.querySelector(".dropdown__selected");
    const menu = dropdown.querySelector(".dropdown__menu");
    const textSpan = selectedBtn.querySelector("span:not(.dropdown__icon)");

    // 2. Клик по кнопке открытия
    if (e.target.closest(".dropdown__selected")) {
        
        // ПРОВЕРКА: Если мы на десктопе (MD-UP), клик не должен переключать меню,
        // так как оно работает от ховера. 
        // Исключение: навигация с клавиатуры (если e.detail === 0)
        const isDesktop = window.matchMedia('(min-width: 992px)').matches; // Твой брейкпоинт md-up
        if (isDesktop && e.detail !== 0) {
            return; // Выходим, пусть работает CSS hover
        }

        // Логика для мобильных (как было)
        document.querySelectorAll(".dropdown.open").forEach(d => {
            if (d !== dropdown) {
                d.classList.remove("open");
                d.querySelector(".dropdown__menu").hidden = true;
                d.querySelector(".dropdown__selected").setAttribute("aria-expanded", "false");
            }
        });

        const isOpen = dropdown.classList.toggle("open");
        selectedBtn.setAttribute("aria-expanded", isOpen);
        menu.hidden = !isOpen;
        return;
    }

    // 3. Выбор опции
    const optionBtn = e.target.closest(".dropdown__option");
    
    if (optionBtn) {
        const value = optionBtn.dataset.value || optionBtn.textContent.trim();
        textSpan.textContent = optionBtn.textContent.trim();

        // JS закрывает состояние (для мобилок)
        dropdown.classList.remove("open");
        selectedBtn.setAttribute("aria-expanded", "false");
        menu.hidden = true; 
        
        // На десктопе меню останется видимым, пока мышка наведена (CSS hover),
        // но как только мышь уйдет — оно исчезнет. Это нормальное поведение.
    }
});