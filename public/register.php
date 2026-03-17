<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$error = $_SESSION['register_error'] ?? null;
unset($_SESSION['register_error']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация — Юркрас</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php
$activePage = '';
require_once __DIR__ . '/partials/header.php';
?>

<main>
    <section class="page-header">
        <div class="container">
            <h1>Регистрация клиента</h1>
            <p>Создайте личный кабинет для удобного общения с юристами и просмотра истории обращений.</p>
        </div>
    </section>

    <section class="section">
        <div class="container" style="max-width: 520px;">
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <form method="post" action="../backend/handlers/handle_register.php" class="form-card">
                <label>
                    ФИО *
                    <input type="text" name="full_name" required>
                </label>
                <label>
                    Дата рождения
                    <input type="date" name="birth_date">
                </label>
                <label>
                    Телефон
                    <input type="tel" name="phone">
                </label>
                <label>
                    E‑mail *
                    <input type="email" name="email" required>
                </label>
                <label>
                    Пароль *
                    <input type="password" name="password" required>
                </label>
                <label>
                    Повторите пароль *
                    <input type="password" name="password_confirm" required>
                </label>
                <button type="submit" class="btn btn-primary" style="margin-top: 8px;">Зарегистрироваться</button>
            </form>
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

