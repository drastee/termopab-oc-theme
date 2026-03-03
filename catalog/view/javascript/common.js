/**
 * Получение GET параметров из URL
 * Используем современный API URLSearchParams
 */
function getURLVar(key) {
    const params = new URLSearchParams(window.location.search);
    return params.get(key) || '';
}

/**
 * Вспомогательная функция для управления состоянием кнопок (Loading/Reset)
 */
function setButtonState(button, state) {
    if (!button) return;

    if (state === 'loading') {
        if (!button.dataset.originalHtml) {
            button.dataset.originalHtml = button.innerHTML;
        }
        button.disabled = true;
        // Сохраняем ширину, чтобы кнопка не "прыгала"
        button.style.width = `${button.offsetWidth}px`;
        button.innerHTML = `<span style="display:inline-flex;align-items:center;gap:8px;">
            <span>${button.dataset.originalHtml}</span>
            <i class="fa-solid fa-circle-notch fa-spin text-light"></i>
        </span>`;
    } else if (state === 'reset') {
        button.disabled = false;
        button.style.width = '';
        if (button.dataset.originalHtml) {
            button.innerHTML = button.dataset.originalHtml;
            delete button.dataset.originalHtml;
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {

    // ------------------------------------------------------------------------
    // Tooltips (Bootstrap 5 Native)
    // ------------------------------------------------------------------------
    const initTooltip = (element) => {
        let tooltip = bootstrap.Tooltip.getInstance(element);
        if (!tooltip) {
            tooltip = new bootstrap.Tooltip(element);
            tooltip.show();
        }
    };

    // Делегирование событий для тултипов
    document.body.addEventListener('mouseenter', (e) => {
        const target = e.target.closest('[data-bs-toggle="tooltip"]');
        if (target) {
            initTooltip(target);
        }
    }, true);

    // Скрытие тултипов при клике на любую кнопку
    document.body.addEventListener('click', (e) => {
        if (e.target.closest('button')) {
            document.querySelectorAll('.tooltip').forEach(el => el.remove());
        }
    });

    // ------------------------------------------------------------------------
    // Alert Observer (MutationObserver)
    // ------------------------------------------------------------------------
    const alertContainer = document.getElementById('alert');
    if (alertContainer) {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.addedNodes.length) {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === 1 && node.classList.contains('alert-dismissible')) {
                            setTimeout(() => {
                                // Плавное исчезновение через CSS transition
                                node.style.transition = 'opacity 1s';
                                node.style.opacity = '0';
                                setTimeout(() => node.remove(), 1000);
                            }, 3000);
                        }
                    });
                }
            });
        });

        observer.observe(alertContainer, {
            childList: true
        });
    }

    // ------------------------------------------------------------------------
    // Forms Handling (AJAX)
    // ------------------------------------------------------------------------
    document.addEventListener('submit', async (e) => {
        const form = e.target;
        const submitButton = e.submitter;

        // Проверяем атрибуты data-oc-toggle="ajax"
        if (form.getAttribute('data-oc-toggle') === 'ajax' || (submitButton && submitButton.getAttribute('data-oc-toggle') === 'ajax')) {
            e.preventDefault();

            const element = form;
            const button = submitButton;
            
            const action = button?.getAttribute('formaction') || form.getAttribute('action');
            const method = (button?.getAttribute('formmethod') || form.getAttribute('method') || 'POST').toUpperCase();
            const enctype = button?.getAttribute('formenctype') || form.getAttribute('enctype') || 'application/x-www-form-urlencoded';

            if (!action) {
                console.error('AJAX form submit: missing form action', form);
                return;
            }

            // Обновляем CKEditor, если есть
            if (typeof CKEDITOR !== 'undefined') {
                for (const instance in CKEDITOR.instances) {
                    CKEDITOR.instances[instance].updateElement();
                }
            }

            // Подготовка данных
            let body;
            let headers = {};

            if (enctype === 'multipart/form-data') {
                body = new FormData(form);
                // Content-Type для multipart не ставим вручную, fetch сделает это сам с boundary
            } else {
                body = new URLSearchParams(new FormData(form)).toString();
                headers['Content-Type'] = 'application/x-www-form-urlencoded';
            }

            setButtonState(button, 'loading');

            try {
                const response = await fetch(action.replace('&amp;', '&'), {
                    method: method,
                    headers: headers,
                    body: method === 'GET' ? null : body
                });

                const json = await response.json();

                // Контейнер для алертов: глобальный (#alert) или локальный возле формы
                const ensureAlertContainer = () => {
                    let container = document.getElementById('alert');
                    if (container) return container;

                    container = form.querySelector('.oc-ajax-alert');
                    if (!container) {
                        container = document.createElement('div');
                        container.className = 'oc-ajax-alert';
                        form.insertAdjacentElement('afterbegin', container);
                    }

                    return container;
                };

                const alertContainer = ensureAlertContainer();

                // Очистка старых алертов и классов ошибок
                alertContainer.querySelectorAll('.alert-dismissible').forEach(el => el.remove());
                form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                form.querySelectorAll('.invalid-feedback').forEach(el => el.classList.remove('d-block'));

                if (json['redirect']) {
                    location = json['redirect'];
                    return;
                }

                if (typeof json['error'] === 'string') {
                    alertContainer.insertAdjacentHTML('afterbegin', 
                        `<div class="alert alert-danger alert-dismissible"><i class="fa-solid fa-circle-exclamation"></i> ${json['error']} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`
                    );
                }

                if (typeof json['error'] === 'object') {
                    if (json['error']['warning']) {
                        alertContainer.insertAdjacentHTML('afterbegin', 
                            `<div class="alert alert-danger alert-dismissible"><i class="fa-solid fa-circle-exclamation"></i> ${json['error']['warning']} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`
                        );
                    }

                    for (const key in json['error']) {
                        const inputId = 'input-' + key.replace(/_/g, '-');
                        const errorId = 'error-' + key.replace(/_/g, '-');
                        
                        const inputEl = document.getElementById(inputId);
                        if (inputEl) {
                            inputEl.classList.add('is-invalid');
                            const controls = inputEl.querySelectorAll('.form-control, .form-select, .form-check-input, .form-check-label');
                            controls.forEach(c => c.classList.add('is-invalid'));
                        }

                        const errorEl = document.getElementById(errorId);
                        if (errorEl) {
                            errorEl.innerHTML = json['error'][key];
                            errorEl.classList.add('d-block');
                        }
                    }
                }

                if (json['success']) {
                    alertContainer.insertAdjacentHTML('afterbegin', 
                        `<div class="alert alert-success alert-dismissible"><i class="fa-solid fa-circle-check"></i> ${json['success']} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`
                    );

					// Callback form UX: reset + close callback modal + open success modal (if present)
					if (form && (form.id === 'callback-form' || form.classList.contains('callback-form'))) {
						try {
							form.reset();
						} catch (e) {}

						const openSuccessAfterScrollRestore = () => {
							if (typeof window.termopabOpenSuccessModal === 'function') {
								// Wait for modal close/unlock scroll + requestAnimationFrame(scrollTo)
								requestAnimationFrame(() => {
									requestAnimationFrame(() => {
										window.termopabOpenSuccessModal();
									});
								});
							}
						};

						if (typeof window.termopabCloseCallbackModal === 'function') {
							window.termopabCloseCallbackModal();
							openSuccessAfterScrollRestore();
						} else {
							openSuccessAfterScrollRestore();
						}
					}

                    // Refresh части контента (аналог $(target).load(url))
                    const loadUrl = form.getAttribute('data-oc-load');
                    const loadTarget = form.getAttribute('data-oc-target');

                    if (loadUrl && loadTarget) {
                        const targetEl = document.querySelector(loadTarget);
                        if (targetEl) {
                            const loadRes = await fetch(loadUrl);
                            const loadHtml = await loadRes.text();
                            targetEl.innerHTML = loadHtml;
                        }
                    }
                }

                // Обновление значений полей
                for (const key in json) {
                    const field = form.querySelector(`[name='${key}']`);
                    if (field) field.value = json[key];
                }

            } catch (error) {
                console.error(error);
            } finally {
                setButtonState(button, 'reset');
            }
        }
    });

    // ------------------------------------------------------------------------
    // Upload Functionality
    // ------------------------------------------------------------------------
    document.addEventListener('click', async (e) => {
        const button = e.target.closest('button[data-oc-toggle="upload"]');
        if (!button) return;

        if (button.disabled) return;

        // Удаляем старую форму загрузки, если есть
        const oldForm = document.getElementById('form-upload');
        if (oldForm) oldForm.remove();

        // Создаем скрытый input
        const fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.name = 'file';
        
        const form = document.createElement('form');
        form.id = 'form-upload';
        form.style.display = 'none';
        form.enctype = 'multipart/form-data';
        form.appendChild(fileInput);
        document.body.appendChild(form);

        fileInput.click();

        fileInput.addEventListener('change', async function() {
            const file = this.files[0];
            if (!file) return;

            // Проверка размера
            const maxSize = parseInt(button.getAttribute('data-oc-size-max')) || 0;
            if ((file.size / 1024) > maxSize) {
                alert(button.getAttribute('data-oc-size-error'));
                this.value = '';
                return;
            }

            const formData = new FormData(form);
            setButtonState(button, 'loading');

            try {
                const res = await fetch(button.getAttribute('data-oc-url'), {
                    method: 'POST',
                    body: formData
                });
                const json = await res.json();

                if (json['error']) alert(json['error']);
                if (json['success']) alert(json['success']);
                if (json['code']) {
                    const targetInput = document.querySelector(button.getAttribute('data-oc-target'));
                    if (targetInput) targetInput.value = json['code'];
                }

            } catch (err) {
                console.error(err);
            } finally {
                setButtonState(button, 'reset');
                form.remove();
            }
        });
    });

    // ------------------------------------------------------------------------
    // Autocomplete Logic
    // ------------------------------------------------------------------------
    const initAutocomplete = (element) => {
        const targetId = element.getAttribute('data-oc-target');
        const dropdown = document.getElementById(targetId);
        let timer = null;

        const request = () => {
            clearTimeout(timer);
            const existingLoader = dropdown.querySelector('#autocomplete-loading');
            if (existingLoader) existingLoader.remove();

            dropdown.insertAdjacentHTML('afterbegin', 
                '<li id="autocomplete-loading"><span class="dropdown-item text-center disabled"><i class="fa-solid fa-circle-notch fa-spin"></i></span></li>'
            );
            dropdown.classList.add('show');

            timer = setTimeout(async () => {
                // Получаем источник данных (обычно это URL в data-oc-source, но здесь логика была зашита в jQuery plugin options)
                // В OpenCart обычно вешают обработчик .autocomplete({ source: function... })
                // Здесь мы эмулируем создание события, чтобы внешний скрипт мог обработать запрос
                
                // ВАЖНО: Поскольку оригинальный код использовал $.fn.autocomplete(options), 
                // где options передавались при инициализации, в Vanilla JS нам нужно
                // либо вызывать функцию напрямую, либо диспатчить событие.
                
                // Пример реализации, если source лежит в атрибуте data-oc-source:
                 /*
                const sourceUrl = element.getAttribute('data-oc-source');
                if(sourceUrl) {
                     const res = await fetch(`${sourceUrl}&filter_name=${encodeURIComponent(element.value)}`);
                     const json = await res.json();
                     renderAutocomplete(json, dropdown);
                }
                */
               
               // Для совместимости с тем, как это часто делается в JS модулях:
               // Создадим кастомное событие, которое должен слушать контроллер страницы
               const event = new CustomEvent('autocomplete-request', { 
                   detail: { 
                       value: element.value, 
                       response: (json) => renderAutocomplete(json, dropdown, element) 
                    } 
                });
               element.dispatchEvent(event);

            }, 150);
        };

        element.addEventListener('focus', request);
        element.addEventListener('input', request);
        
        element.addEventListener('blur', (e) => {
             // Небольшая задержка, чтобы успел сработать клик по пункту меню
            setTimeout(() => {
                if (!dropdown.contains(document.activeElement)) {
                    dropdown.classList.remove('show');
                }
            }, 200);
        });

        // Делегирование клика по элементам списка
        dropdown.addEventListener('click', (e) => {
            const itemLink = e.target.closest('a');
            if (itemLink) {
                e.preventDefault();
                const value = itemLink.getAttribute('href');
                // Диспатчим событие выбора
                element.dispatchEvent(new CustomEvent('autocomplete-select', { detail: { value: value, item: itemLink.dataset } }));
                dropdown.classList.remove('show');
            }
        });
    };
    
    // Функция рендеринга (вспомогательная)
    const renderAutocomplete = (json, dropdown, element) => {
        let html = '';
        const category = {};

        // Удаляем лоадер
        const loader = dropdown.querySelector('#autocomplete-loading');
        if(loader) loader.remove();

        if (json.length) {
            json.forEach(item => {
                // Сохраняем данные элемента (аналог this.items[value] в оригинале)
                // В DOM проще всего хранить json прямо в dataset или использовать замыкание
                
                if (!item['category']) {
                    html += `<li><a href="${item['value']}" class="dropdown-item" data-label="${item['label']}">${item['label']}</a></li>`;
                } else {
                    const name = item['category'];
                    if (!category[name]) category[name] = [];
                    category[name].push(item);
                }
            });

            for (const name in category) {
                html += `<li><h6 class="dropdown-header">${name}</h6></li>`;
                category[name].forEach(item => {
                    html += `<li><a href="${item['value']}" class="dropdown-item" data-label="${item['label']}">${item['label']}</a></li>`;
                });
            }
        }
        dropdown.innerHTML = html;
        dropdown.classList.add('show');
    };

    // Инициализация автокомплита для всех input с атрибутом (нужно добавить data-toggle="autocomplete" в HTML, если его нет)
    // Или инициализировать вручную, как в jQuery коде
    document.querySelectorAll('input[data-oc-toggle="autocomplete"]').forEach(initAutocomplete);

// ------------------------------------------------------------------------
    // Currency & Language (ИСПРАВЛЕНО)
    // ------------------------------------------------------------------------
    const setupFormDropdown = (formId, inputName) => {
        const form = document.getElementById(formId);
        if (form) {
            // Если сервер возвращает JSON, форма ОБЯЗАТЕЛЬНО должна обрабатываться через AJAX.
            // Убедимся, что у формы есть нужный атрибут, чтобы сработал наш глобальный слушатель submit.
            form.setAttribute('data-oc-toggle', 'ajax');

            form.querySelectorAll('.dropdown-item').forEach(item => {
                item.addEventListener('click', (e) => {
                    e.preventDefault();
                    const input = form.querySelector(`input[name='${inputName}']`);
                    if (input) {
                        input.value = item.getAttribute('href');
                        
                        // ОШИБКА БЫЛА ЗДЕСЬ: form.submit() отправляет форму мимо AJAX.
                        // ИСПРАВЛЕНИЕ: используем requestSubmit(), чтобы сработал addEventListener('submit')
                        form.requestSubmit(); 
                    }
                });
            });
        }
    };

    setupFormDropdown('form-currency', 'code');
    setupFormDropdown('form-language', 'code');

    // ------------------------------------------------------------------------
    // Product List / Grid
    // ------------------------------------------------------------------------
    const btnList = document.getElementById('button-list');
    const btnGrid = document.getElementById('button-grid');
    const productList = document.getElementById('product-list');

    if (btnList && btnGrid && productList) {
        btnList.addEventListener('click', () => {
            productList.className = 'row row-cols-1 product-list';
            btnGrid.classList.remove('active');
            btnList.classList.add('active');
            localStorage.setItem('display', 'list');
        });

        btnGrid.addEventListener('click', () => {
            productList.className = 'row row-cols-1 row-cols-sm-2 row-cols-md-2 row-cols-lg-3';
            btnList.classList.remove('active');
            btnGrid.classList.add('active');
            localStorage.setItem('display', 'grid');
        });

        if (localStorage.getItem('display') === 'list') {
            btnList.click();
        } else {
            btnGrid.click();
        }
    }

    // ------------------------------------------------------------------------
    // Catalog filter (category parent) — кнопки .filter__button
    // Якщо це посилання <a>, переход по кліку без JS. Якщо <button data-href="..."> — перехід через JS.
    // ------------------------------------------------------------------------
    document.body.addEventListener('click', (e) => {
        const btn = e.target.closest('.filter__button');
        if (!btn || btn.tagName === 'A') return;
        const href = btn.getAttribute('data-href');
        if (href) {
            e.preventDefault();
            window.location = href.replace(/&amp;/g, '&');
        }
    });

    // ------------------------------------------------------------------------
    // Agree to Terms (Modal)
    // ------------------------------------------------------------------------
    document.body.addEventListener('click', async (e) => {
        const link = e.target.closest('.modal-link');
        if (!link) return;

        e.preventDefault();

        const existingModal = document.getElementById('modal-information');
        if (existingModal) existingModal.remove();

        try {
            const res = await fetch(link.getAttribute('href'));
            const html = await res.text();
            
            document.body.insertAdjacentHTML('beforeend', html);
            
            const modalEl = document.getElementById('modal-information');
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        } catch (err) {
            console.error(err);
        }
    });

    // ------------------------------------------------------------------------
    // Cookie Policy
    // ------------------------------------------------------------------------
    const cookieBtn = document.querySelector('#cookie button');
    if (cookieBtn) {
        cookieBtn.addEventListener('click', async function() {
            setButtonState(this, 'loading');
            try {
                const res = await fetch(this.value);
                const json = await res.json();
                
                if (json['success']) {
                    const cookieAlert = document.getElementById('cookie');
                    if (cookieAlert) {
                         // Анимация исчезновения
                        cookieAlert.style.transition = 'opacity 0.4s';
                        cookieAlert.style.opacity = '0';
                        setTimeout(() => cookieAlert.remove(), 400);
                    }
                }
            } catch (err) {
                console.error(err);
            } finally {
                setButtonState(this, 'reset');
            }
        });
    }

});

// ------------------------------------------------------------------------
// Chain Class (Promises instead of callbacks)
// ------------------------------------------------------------------------
class Chain {
    constructor() {
        this.start = false;
        this.data = [];
    }

    attach(call) {
        this.data.push(call);
        if (!this.start) {
            this.execute();
        }
    }

    async execute() {
        if (this.data.length) {
            this.start = true;
            const call = this.data.shift();
            
            // Предполагаем, что 'call' возвращает Promise
            try {
                await call();
                this.execute();
            } catch (e) {
                console.error("Chain execution failed", e);
                this.start = false;
            }
        } else {
            this.start = false;
        }
    }
}
const chain = new Chain();