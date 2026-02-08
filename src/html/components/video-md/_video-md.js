document.addEventListener('DOMContentLoaded', () => {
    // Ищем главный контейнер
    const videoBlock = document.querySelector('.video-md');
    
    if (videoBlock) {
        // Убедитесь, что классы в HTML совпадают с этими селекторами
        const video = videoBlock.querySelector('.video-md__video');
        const button = videoBlock.querySelector('.video-md__button');
        const poster = videoBlock.querySelector('.video-md__poster');

        // --- ФУНКЦИИ ---

        const startVideo = () => {
            video.play();
            // Включаем стандартные панели браузера
            video.controls = true; 
            
            // Скрываем наши кастомные элементы
            button.classList.add('hidden');
            poster.classList.add('hidden');
        };

        const resetVideo = () => {
            // Сбрасываем видео
            video.load(); 
            // Отключаем стандартные панели, чтобы вернуть красивый постер
            video.controls = false; 
            
            // Возвращаем кнопку и постер
            button.classList.remove('hidden');
            poster.classList.remove('hidden');
        };

        // --- СОБЫТИЯ ---

        // 1. Запуск по клику на большую кнопку
        button.addEventListener('click', startVideo);

        // 2. Запуск по клику на сам постер (для удобства)
        poster.addEventListener('click', startVideo);

        // 3. Когда видео закончилось — возвращаем всё в исходное состояние
        video.addEventListener('ended', resetVideo);
        
        // Опционально: Если пользователь нажал паузу в стандартном меню,
        // мы обычно НЕ возвращаем постер, так как он перекроет видео.
        // Оставляем видео на паузе с видимыми контролами.
    }
});