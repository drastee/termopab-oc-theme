const modal = document.querySelector('#order-modal');
const openBtns = document.querySelectorAll('.open-order-btn');
const closeBtn = document.querySelector('.popup-close'); // Убедись, что кнопка-крестик есть в HTML

// 1. Открытие
openBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        modal.showModal();
        document.body.style.overflow = 'hidden'; 
    });
});

// 2. Закрытие
const closeModal = () => {
    modal.close();
    document.body.style.overflow = ''; 
};

// Если есть крестик
if (closeBtn) closeBtn.addEventListener('click', closeModal);

// 3. ЗАКРЫТИЕ ПО КЛИКУ НА ФОН (Работает везде)
modal.addEventListener('mousedown', (e) => {
  // Находим твой внутренний белый блок
  const container = modal.querySelector('.modal-cart__container');
  
  // Метод .contains проверяет, находится ли цель клика внутри контейнера
  // Если кликнули НЕ по контейнеру и НЕ внутри него — закрываем
  if (!container.contains(e.target)) {
    closeModal();
  }
});