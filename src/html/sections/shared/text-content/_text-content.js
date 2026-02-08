const btn = document.querySelector('.js-expand-btn');

if (btn) {
    btn.addEventListener('click', function() {
        const parent = this.closest('.text-content__inner');
        const content = parent.querySelector('.text-content__main');
        const span = this.querySelector('span');
        
        // Проверяем текущее состояние
        const isExpanded = parent.classList.contains('is-expanded');
        
        if (!isExpanded) {
            // РАЗВОРАЧИВАЕМ
            content.style.maxHeight = content.scrollHeight + "px";
            parent.classList.add('is-expanded');
            this.setAttribute('aria-expanded', 'true'); // Помечаем как развернуто
            span.textContent = this.dataset.textCollapse;
        } else {
            // СВОРАЧИВАЕМ
            content.style.maxHeight = "160px";
            parent.classList.remove('is-expanded');
            this.setAttribute('aria-expanded', 'false'); // Помечаем как свернуто
            span.textContent = this.dataset.textExpand;
        }
    });
}