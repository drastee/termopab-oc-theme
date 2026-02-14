-- Додати подію меню Termopab без перевстановлення теми.
-- Префікс таблиць: tp_ (замініть на інший, якщо потрібно).
-- Виконайте в phpMyAdmin.
-- Джерело: https://stackoverflow.com/questions/73530617/create-an-admin-menu-in-opencart-4

-- 0. Якщо "немає прав для доступу" — перевстановіть тему (Uninstall + Install).
--    Або вручну додайте права: System → Users → User Groups → Edit →
--    Access + Modify: extension/termopab/project, extension/termopab/brewery_review, extension/termopab/brewery_review_category, extension/termopab/theme/termopab, extension/termopab/install

-- 1. Якщо меню не з'являється — termopab має бути в extension_install (автозавантаження).
--    Виконайте (замініть tp_ на ваш префікс), якщо код termopab ще немає:
-- INSERT INTO tp_extension_install (extension_id, extension_download_id, name, description, code, version, author, link, status, date_added)
-- VALUES (0, 0, 'Termopab', 'Termopab theme', 'termopab', '1.0', '', '', 1, NOW());

-- 2. Подія меню
DELETE FROM `tp_event` WHERE `code` = 'termopab_admin_menu';
DELETE FROM `tp_event` WHERE `code` = 'termopab_debug_menu';

INSERT INTO `tp_event` (`code`, `description`, `trigger`, `action`, `status`, `sort_order`)
VALUES
(
  'termopab_admin_menu',
  'Termopab: меню «Тема Termopab», «Проекти», «Огляди пивоварень» в розділі Дизайн',
  'admin/view/common/column_left/before',
  'extension/termopab/event/menu.onColumnLeft',
  1,
  0
);
