<?php
// Главная страница «Юркрас»
$activePage = 'home';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Юркрас — юридическая компания</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/partials/header.php'; ?>

<main>
    <section class="hero">
        <div class="container hero-inner">
            <div class="hero-text">
                <h1>Юридическая компания «Юркрас»</h1>
                <p>Квалифицированная правовая помощь для физических и юридических лиц. Онлайн‑консультации, сопровождение сделок, подготовка документов.</p>
                <div class="hero-actions">
                    <a href="services.php" class="btn btn-primary">Получить консультацию</a>
                    <a href="chat.php" class="btn btn-secondary">Онлайн‑консультант</a>
                </div>
            </div>
            <div class="hero-aside">
                <div class="hero-card">
                    <h2>Почему «Юркрас»?</h2>
                    <ul>
                        <li>Опытные юристы по ключевым направлениям права</li>
                        <li>Понятные условия и прозрачные тарифы</li>
                        <li>Онлайн‑поддержка и личный кабинет клиента</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <h2>Популярные услуги</h2>
            <p>Небольшой перечень основных направлений. Полный список доступен на странице «Услуги».</p>
            <div class="cards">
                <article class="card">
                    <h3>Консультация по гражданским делам</h3>
                    <p>Разрешение споров, защита прав потребителей, договорные отношения.</p>
                </article>
                <article class="card">
                    <h3>Семейные споры</h3>
                    <p>Развод, раздел имущества, алименты, определение места жительства ребёнка.</p>
                </article>
                <article class="card">
                    <h3>Сопровождение сделок</h3>
                    <p>Покупка и продажа недвижимости, проверка документов, подготовка договоров.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="section section-accent">
        <div class="container section-flex">
            <div>
                <h2>Онлайн‑консультант</h2>
                <p>Задайте вопрос в чате — система предложит краткий ответ и подберёт подходящие услуги. При необходимости к диалогу подключится живой консультант.</p>
            </div>
            <div>
                <a href="chat.php" class="btn btn-primary">Открыть чат</a>
            </div>
        </div>
    </section>
</main>

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

