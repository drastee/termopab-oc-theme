Termopab Theme — установка
==========================

Меню админки (Тема Termopab, Проекти) добавляется через систему событий OC4 при установке темы.
OCMOD не используется.

Добавить меню БЕЗ переустановки темы (если уже всё настроено):
  Выполните install/add_event_manual.sql в phpMyAdmin (префикс tp_, замените при необходимости).

При установке расширения:
- Регистрируется событие admin/view/common/column_left/before
- В меню Design появляются: «Тема Termopab», «Проекти» (список + створити)

При удалении — событие снимается.

Таблицы проектов (project, project_description, project_image) и оглядів пивоварень (brewery_review, brewery_review_description, brewery_review_image):
- Создаются при первом посещении extension/termopab/install или по кнопке «Створити таблиці» на странице списка оглядів пивоварень.

Category custom fields (hero_image, breadcrumb_background, slots):
- Колонки hero_image, breadcrumb_background добавляются в таблицу category при установке темы или при запуске extension/termopab/install
  (кнопка «Створити таблиці проєктів» в налаштуваннях теми).
- События загружают/сохраняют поля в админке. Виджет встроен в category_form.twig.

  OCMOD удалён — не используется.

- Всі поля завжди видимі на вкладці Design. Parent/child визначається за path (глибина категорії).
