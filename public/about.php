<?php
// Страница «О компании / Юристы»
$activePage = 'about';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>О компании — Юркрас</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/partials/header.php'; ?>

<main>
    <section class="page-header">
        <div class="container">
            <h1>О компании «Юркрас»</h1>
            <p>Юридическая компания «Юркрас» предоставляет квалифицированную правовую помощь на территории РФ.</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <h2>Наши принципы</h2>
            <ul class="list">
                <li>Профессионализм и актуальные знания законодательства РФ.</li>
                <li>Прозрачные и понятные условия сотрудничества.</li>
                <li>Конфиденциальность и уважение к каждому клиенту.</li>
            </ul>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <h2>Наши юристы</h2>
            <div class="cards cards-grid-3">
                <article class="card card-person">
                    <div class="avatar-placeholder">ИИ</div>
                    <h3>Иванов Иван Иванович</h3>
                    <p>Специализация: гражданское и семейное право. Опыт работы более 10 лет.</p>
                </article>
                <article class="card card-person">
                    <div class="avatar-placeholder">ПК</div>
                    <h3>Петрова Ксения Константиновна</h3>
                    <p>Специализация: недвижимость и сопровождение сделок.</p>
                </article>
                <article class="card card-person">
                    <div class="avatar-placeholder">СА</div>
                    <h3>Сидоров Алексей Андреевич</h3>
                    <p>Специализация: корпоративное право и договорная работа.</p>
                </article>
            </div>
            <p class="note">Фотографии могут быть добавлены позднее. Сейчас используются заготовки‑заглушки.</p>
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

