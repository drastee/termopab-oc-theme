import gsap from 'gsap';

document.addEventListener('DOMContentLoaded', () => {
  const hero = document.querySelector('.hero');
  if (!hero) return;

  const lines = hero.querySelectorAll('.hero__title > span');

  const tl = gsap.timeline({
    defaults: { ease: 'power3.out' },
  });

  // 1. Верхняя строка
  tl.to(lines[0], {
    opacity: 1,
    y: 0,
    duration: 0.9,
  });

  // 2. Центральная строка (главная)
  tl.to(
    lines[1],
    {
      opacity: 1,
      y: 0,
      scale: 1,
      duration: 1.1,
    },
    '-=0.4'
  );

  // 3. Нижняя строка (script)
  tl.to(
    lines[2],
    {
      opacity: 1,
      rotate: -10,
      x: '-0.8em',
      y: '-0.5em',
      duration: 1.2,
      ease: 'back.out(1.6)',
    },
    '-=0.6'
  );
});
