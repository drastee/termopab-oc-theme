document.addEventListener('DOMContentLoaded', () => {
    const btn = document.querySelector('.scrolldown');

    if (!btn) return;

    btn.addEventListener('click', () => {
        const currentSection = btn.closest('section');
        if (!currentSection) return;

        const next = currentSection.nextElementSibling;
        if (!next) return;

        next.scrollIntoView({
            behavior: 'smooth'
        });
    });
});
