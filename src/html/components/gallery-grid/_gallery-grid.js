document.addEventListener('DOMContentLoaded', () => {
    // Инициализация Fancybox
    // Убедитесь, что Fancybox подключен глобально
    if (typeof Fancybox !== 'undefined') {
        Fancybox.bind("[data-fancybox]", {
            // Опции (необязательно)
            infinite: true,
            Thumbs: {
                autoStart: false, // Отключить миниатюры внизу, если мешают
            },
        });
    }
});