<?php
// Страница «Контакты»
$activePage = 'contacts';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Контакты — Юркрас</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/partials/header.php'; ?>

<main>
    <section class="page-header">
        <div class="container">
            <h1>Контакты</h1>
            <p>Свяжитесь с нами удобным для вас способом.</p>
        </div>
    </section>

    <section class="section">
        <div class="container contacts-layout">
            <div class="contacts-info">
                <h2>Реквизиты и контактные данные</h2>
                <p><strong>Юридическая компания «Юркрас»</strong></p>
                <p class="contact-line">
                    <span class="icon" aria-hidden="true"><img src="assets/images/icons/phone.svg" alt=""></span>
                    <strong>Телефон:</strong> +7 (000) 000‑00‑00
                </p>
                <p class="contact-line">
                    <span class="icon" aria-hidden="true"><img src="assets/images/icons/mail.svg" alt=""></span>
                    <strong>E‑mail:</strong> info@yurkrass.example
                </p>
                <p><strong>Адрес:</strong> г. Краснодар, примерная улица, дом 1</p>
                <p class="note">Адрес и контакты являются демонстрационными и могут быть изменены под реальные данные.</p>

                <div class="map-placeholder">
                    Карта офиса будет размещена здесь.
                </div>
            </div>

            <div class="contacts-form">
                <h2>Форма обратной связи</h2>
                <?php
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $contactError = $_SESSION['contact_error'] ?? null;
                $contactSuccess = $_SESSION['contact_success'] ?? null;
                unset($_SESSION['contact_error'], $_SESSION['contact_success']);
                ?>
                <?php if ($contactError): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($contactError, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
                <?php if ($contactSuccess): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($contactSuccess, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
                <form method="post" action="../backend/handlers/handle_consultation_request.php">
                    <label>
                        Имя
                        <input type="text" name="name" required>
                    </label>
                    <label>
                        Телефон
                        <input type="tel" name="phone" required>
                    </label>
                    <label>
                        E‑mail
                        <input type="email" name="email">
                    </label>
                    <label>
                        Ваш вопрос
                        <textarea name="message" rows="4" required></textarea>
                    </label>
                    <button type="submit" class="btn btn-primary">Отправить</button>
                </form>
            </div>
        </div>
    </section>

    <section class="section section-accent">
        <div class="container section-flex">
            <div>
                <h2>Нужна быстрая консультация?</h2>
                <p>Задайте вопрос онлайн‑консультанту и получите первичный ответ в чате.</p>
            </div>
            <div>
                <a href="chat.php" class="btn btn-primary">Перейти к онлайн‑консультанту</a>
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

