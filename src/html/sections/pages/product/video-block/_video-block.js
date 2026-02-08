document.addEventListener('DOMContentLoaded', () => {
    const videoBlock = document.querySelector('.video-block');
    
    if (videoBlock) {
        const video = videoBlock.querySelector('.video-block__video');
        const button = videoBlock.querySelector('.video-block__button');
        const poster = videoBlock.querySelector('.video-block__poster');
        
        // Новые элементы
        const progressContainer = videoBlock.querySelector('.video-block__progress');
        const progressLine = videoBlock.querySelector('.video-block__progress-line');

        // --- ФУНКЦИИ ---

        const startVideo = () => {
            video.play();
            button.classList.add('hidden');
            poster.classList.add('hidden');
            progressContainer.classList.add('active'); // Показываем полоску
        };

        const stopVideo = () => {
            video.pause();
            button.classList.remove('hidden');
            poster.classList.remove('hidden');
            // progressContainer.classList.remove('active'); // Можно скрывать полоску на паузе, если хотите
        };

        // --- СОБЫТИЯ ---

        button.addEventListener('click', startVideo);

        video.addEventListener('click', () => {
            if (!video.paused) {
                stopVideo();
            }
        });

        // 1. ОБНОВЛЕНИЕ ПОЛОСКИ ПРИ ПРОИГРЫВАНИИ
        video.addEventListener('timeupdate', () => {
            // Вычисляем процент (защита от деления на 0)
            const percentage = (video.currentTime / video.duration) * 100 || 0;
            progressLine.style.width = `${percentage}%`;
        });

        // 2. ПЕРЕМОТКА ПО КЛИКУ НА ПОЛОСКУ
        progressContainer.addEventListener('click', (e) => {
            // Останавливаем всплытие, чтобы клик по полоске не ставил видео на паузу (из-за клика по видео под ней)
            e.stopPropagation(); 

            const rect = progressContainer.getBoundingClientRect();
            const pos = (e.clientX - rect.left) / rect.width; // Координата клика от 0 до 1
            video.currentTime = pos * video.duration; // Ставим новое время
            
            // Если видео было на паузе (например, мы кликнули когда видели постер), запускаем его
            if(video.paused) {
                startVideo();
            }
        });

        video.addEventListener('ended', () => {
            video.load();
            button.classList.remove('hidden');
            poster.classList.remove('hidden');
            progressContainer.classList.remove('active'); // Скрываем полоску в конце
            progressLine.style.width = '0%';
        });
    }
});