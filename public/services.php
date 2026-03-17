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
            <h1>Юридические услуги</h1>
            <p>Список основных направлений работы компании «Юркрас».</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="cards">
                <article class="card">
                    <h2>Гражданское право</h2>
                    <ul>
                        <li>Консультации по договорам и спорам</li>
                        <li>Защита прав потребителей</li>
                        <li>Подготовка претензий и исков</li>
                    </ul>
                </article>
                <article class="card">
                    <h2>Семейное право</h2>
                    <ul>
                        <li>Развод и раздел имущества</li>
                        <li>Алименты</li>
                        <li>Определение места жительства ребёнка</li>
                    </ul>
                </article>
                <article class="card">
                    <h2>Недвижимость и сделки</h2>
                    <ul>
                        <li>Сопровождение сделок купли‑продажи</li>
                        <li>Проверка правоустанавливающих документов</li>
                        <li>Подготовка договоров аренды</li>
                    </ul>
                </article>
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

