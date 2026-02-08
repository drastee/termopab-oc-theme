document.addEventListener('DOMContentLoaded', () => {
    const tabs = document.querySelectorAll('.tabs__menu [role="tab"]');
    const panels = document.querySelectorAll('.tabs__content [role="tabpanel"]');

    tabs.forEach(tab => {
        tab.addEventListener('click', (e) => {
            const clickedTab = e.currentTarget;
            const targetId = clickedTab.getAttribute('aria-controls');
            const targetPanel = document.getElementById(targetId);

            // 1. Деактивируем все табы и скрываем все панели
            tabs.forEach(t => t.setAttribute('aria-selected', 'false'));
            panels.forEach(p => p.hidden = true);

            // 2. Активируем кликнутый таб и показываем его панель
            clickedTab.setAttribute('aria-selected', 'true');
            if (targetPanel) {
                targetPanel.hidden = false;
            }
        });
    });
});