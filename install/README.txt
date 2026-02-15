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

Conditional Category Layout (Layout 15 = landing, Layout 16 = tech):
- Колонки landing_video_title, tech_spec_link добавляются в таблицу category при запуске extension/termopab/install
  (кнопка «Створити всі таблиці» в налаштуваннях теми).
- События загружают/сохраняют поля в админке. OCMOD устанавливается ТОЛЬКО через Extensions → Installer:

  Как упаковать и установить OCMOD (OpenCart 4):
  Структура .ocmod.zip: install.json (обязательно!) + ocmod/category_layout_mod.ocmod.xml
  Готовый архив уже создан: install/category_layout_mod.ocmod.zip
  1. Extensions → Installer → Upload → выберите category_layout_mod.ocmod.zip
  2. Extensions → Extensions → найдите Category Layout Mod → Install
  3. Extensions → Modifications → Refresh

  Пересобрать архив: cd install && zip -r category_layout_mod.ocmod.zip install.json ocmod/category_layout_mod.ocmod.xml

  Примечание: Extensions → Modifications не принимает загрузку XML напрямую — только .ocmod.zip через Installer.

- Создайте Layout 15 и Layout 16 в Design → Layouts, route product/category. В категории выберите Layout 15 или 16 в Design.
