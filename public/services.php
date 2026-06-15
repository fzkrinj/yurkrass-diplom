<?php
// Страница с перечнем услуг
$activePage = 'services';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Услуги — Юркрас</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/partials/header.php'; ?>

<main>
    <section class="page-header">
        <div class="container">
            <h1>Наши услуги</h1>
            <p>Комплексная юридическая поддержка для физических и юридических лиц.</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="services-controls">
                <div class="card services-control-card">
                    <div class="services-control-title">
                        <span class="services-control-title__dot" aria-hidden="true"></span>
                        <h2>Фильтр по категориям</h2>
                    </div>
                    <label class="services-control-label">
                        <span>Выберите категорию услуг</span>
                        <select id="servicesFilterSelect">
                            <option value="all">Все категории</option>
                            <option value="family">Семейное право</option>
                            <option value="property">Недвижимость</option>
                            <option value="corporate">Корпоративное право</option>
                            <option value="criminal">Уголовная защита</option>
                        </select>
                    </label>
                </div>

                <div class="card services-control-card">
                    <div class="services-control-title">
                        <span class="services-control-title__dot services-control-title__dot--accent" aria-hidden="true"></span>
                        <h2>Калькулятор стоимости</h2>
                    </div>

                    <div class="services-calc-grid">
                        <label class="services-control-label">
                            <span>Категория услуги</span>
                            <select id="servicesCalcSelect">
                                <option value="all">Выберите категорию</option>
                                <option value="family">Семейное право</option>
                                <option value="property">Недвижимость</option>
                                <option value="corporate">Корпоративное право</option>
                                <option value="criminal">Уголовная защита</option>
                            </select>
                        </label>

                        <label class="services-control-label">
                            <span>Примерный бюджет дела (руб.)</span>
                            <input id="servicesBudgetInput" type="number" inputmode="numeric" placeholder="Введите сумму" />
                        </label>
                    </div>

                    <div class="services-calc-urgent">
                        <label class="services-urgent-check">
                            <input id="servicesUrgentInput" type="checkbox" />
                            <span>Срочное дело (+30%)</span>
                        </label>
                    </div>

                    <button id="servicesCalcBtn" type="button" class="btn btn-primary services-calc-btn">
                        Рассчитать стоимость
                    </button>

                    <div id="servicesCalcResult" class="services-calc-result" style="display:none;"></div>
                </div>
            </div>

            <div class="cards cards--services-categories" id="servicesCategories">
                <article class="card services-category-card" data-category="family">
                    <h2>Семейное право</h2>
                    <ul class="services-category-list">
                        <li>Развод</li>
                        <li>Раздел имущества</li>
                        <li>Алименты</li>
                        <li>Опека</li>
                    </ul>
                    <div class="services-base-price" data-base-price="25000">
                        <p class="services-base-price__label">От</p>
                        <p class="services-base-price__value">25 000 ₽</p>
                    </div>
                </article>

                <article class="card services-category-card" data-category="property">
                    <h2>Недвижимость</h2>
                    <ul class="services-category-list">
                        <li>Купля-продажа</li>
                        <li>Аренда</li>
                        <li>Споры</li>
                        <li>Приватизация</li>
                    </ul>
                    <div class="services-base-price" data-base-price="30000">
                        <p class="services-base-price__label">От</p>
                        <p class="services-base-price__value">30 000 ₽</p>
                    </div>
                </article>

                <article class="card services-category-card" data-category="corporate">
                    <h2>Корпоративное право</h2>
                    <ul class="services-category-list">
                        <li>Регистрация ООО</li>
                        <li>Договоры</li>
                        <li>Налоги</li>
                        <li>Реорганизация</li>
                    </ul>
                    <div class="services-base-price" data-base-price="40000">
                        <p class="services-base-price__label">От</p>
                        <p class="services-base-price__value">40 000 ₽</p>
                    </div>
                </article>

                <article class="card services-category-card" data-category="criminal">
                    <h2>Уголовная защита</h2>
                    <ul class="services-category-list">
                        <li>Защита в суде</li>
                        <li>Обжалование</li>
                        <li>Следствие</li>
                        <li>Консультации</li>
                    </ul>
                    <div class="services-base-price" data-base-price="50000">
                        <p class="services-base-price__label">От</p>
                        <p class="services-base-price__value">50 000 ₽</p>
                    </div>
                </article>
            </div>

            <div class="card services-process-card">
                <h2>Как мы работаем</h2>
                <div class="services-steps">
                    <div class="services-step">
                        <div class="services-step__number">1</div>
                        <h3 class="services-step__title">Консультация</h3>
                        <p class="services-step__desc">Бесплатная первичная консультация для анализа вашей ситуации.</p>
                    </div>
                    <div class="services-step">
                        <div class="services-step__number">2</div>
                        <h3 class="services-step__title">Анализ</h3>
                        <p class="services-step__desc">Изучаем документы и разрабатываем стратегию.</p>
                    </div>
                    <div class="services-step">
                        <div class="services-step__number">3</div>
                        <h3 class="services-step__title">Работа</h3>
                        <p class="services-step__desc">Ведём дело и защищаем ваши интересы.</p>
                    </div>
                    <div class="services-step">
                        <div class="services-step__number">4</div>
                        <h3 class="services-step__title">Результат</h3>
                        <p class="services-step__desc">Добиваемся положительного решения.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section section-accent">
        <div class="container section-flex">
            <div>
                <h2>Не нашли нужную услугу?</h2>
                <p>Задайте вопрос онлайн‑консультанту — он подберёт подходящее направление и предложит варианты.</p>
            </div>
            <div>
                <a href="chat.php" class="btn btn-primary">Спросить консультанта</a>
            </div>
        </div>
    </section>
</main>

<script>
    (function () {
        const filterSelect = document.getElementById('servicesFilterSelect');
        const calcSelect = document.getElementById('servicesCalcSelect');
        const budgetInput = document.getElementById('servicesBudgetInput');
        const urgentInput = document.getElementById('servicesUrgentInput');
        const calcBtn = document.getElementById('servicesCalcBtn');
        const resultEl = document.getElementById('servicesCalcResult');
        const categoriesWrap = document.getElementById('servicesCategories');

        const basePrices = {
            family: 25000,
            property: 30000,
            corporate: 40000,
            criminal: 50000
        };

        function formatRuble(n) {
            try { return Number(n).toLocaleString('ru-RU'); } catch (e) { return String(n); }
        }

        function applyFilter() {
            if (!categoriesWrap || !filterSelect) return;
            const selected = filterSelect.value;
            categoriesWrap.querySelectorAll('.services-category-card').forEach((card) => {
                const cat = card.getAttribute('data-category');
                const isVisible = selected === 'all' || selected === cat;
                card.style.display = isVisible ? '' : 'none';
            });
        }

        function syncCalcSelectFromFilter() {
            if (!filterSelect || !calcSelect) return;
            if (filterSelect.value === 'all') return;
            calcSelect.value = filterSelect.value;
        }

        function calculate() {
            if (!resultEl || !calcSelect || !urgentInput) return;
            const cat = calcSelect.value;

            let price = basePrices[cat];
            if (!price) {
                resultEl.style.display = 'block';
                resultEl.innerHTML = '<p class="services-calc-result__label">Примерная стоимость услуги:</p><p class="services-calc-result__value">15 000 ₽</p><p class="services-calc-result__hint">* Точная стоимость определяется после консультации с юристом</p>';
                return;
            }

            // На UI бюджет нужен только как подсказка/ввод, но расчёт имитируем как в макете.
            const isUrgent = !!urgentInput.checked;
            if (isUrgent) price = price * 1.3;

            const rounded = Math.round(price);
            resultEl.style.display = 'block';
            resultEl.innerHTML =
                '<p class="services-calc-result__label">Примерная стоимость услуги:</p>' +
                '<p class="services-calc-result__value">' + formatRuble(rounded) + ' ₽</p>' +
                '<p class="services-calc-result__hint">* Точная стоимость определяется после консультации с юристом</p>';
        }

        if (filterSelect) {
            filterSelect.addEventListener('change', function () {
                applyFilter();
                syncCalcSelectFromFilter();
            });
        }

        if (calcBtn) {
            calcBtn.addEventListener('click', calculate);
        }

        // init
        applyFilter();
    })();
</script>

<footer class="site-footer">
    <div class="container footer-inner">
        <div>© <?php echo date('Y'); ?> Юркрас. Все права защищены.</div>
        <div class="footer-links">
            <a href="services.php">Услуги</a>
            <a href="contacts.php">Контакты</a>
        </div>
    </div>
</footer>

<script src="assets/js/main.js"></script>
</body>
</html>

