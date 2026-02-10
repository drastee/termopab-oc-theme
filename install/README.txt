Установка OCMOD «Termopab Admin Menu»
=====================================

Этот модификатор добавляет в меню админки (Design) пункт «Тема Termopab» со ссылкой
на настройки темы. Пункт появляется только при установленной теме Termopab.

Установка:
----------
1. Создайте zip-архив termopab_admin_menu.ocmod.zip со структурой:
   - install.json (из этой папки)
   - ocmod/termopab_admin_menu.ocmod.xml (файл .ocmod.xml в папке ocmod/)

2. Загрузите архив: Extensions → Installer → Upload (выберите .ocmod.zip)

3. Нажмите Install для установки

4. Extensions → Modifications — включите модификатор «Termopab Admin Menu» (если выключен)

5. Нажмите Refresh, чтобы применить модификатор

Готово. В меню Design появится пункт «Тема Termopab».

Создание zip из командной строки:
---------------------------------
cd opencart/extension/termopab/install
mkdir -p ocmod
cp termopab_admin_menu.ocmod.xml ocmod/
zip -r termopab_admin_menu.ocmod.zip install.json ocmod/
