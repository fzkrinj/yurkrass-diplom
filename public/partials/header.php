<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUserId = $_SESSION['user_id'] ?? null;
$currentUserRole = $_SESSION['user_role'] ?? null;
$currentUserName = $_SESSION['user_name'] ?? null;

if (!isset($activePage)) {
    $activePage = '';
}
?>
<header class="site-header">
    <div class="container header-inner">
        <div class="logo">Юркрас</div>
        <nav class="main-nav">
            <a href="index.php" class="<?php echo ($activePage === 'home') ? 'active' : ''; ?>">Главная</a>
            <a href="services.php" class="<?php echo ($activePage === 'services') ? 'active' : ''; ?>">Услуги</a>
            <a href="about.php" class="<?php echo ($activePage === 'about') ? 'active' : ''; ?>">О компании</a>
            <a href="contacts.php" class="<?php echo ($activePage === 'contacts') ? 'active' : ''; ?>">Контакты</a>
        </nav>
        <div class="header-actions">
            <?php if ($currentUserId): ?>
                <span class="header-user">
                    <span class="icon" aria-hidden="true">
                        <img src="assets/images/icons/user.svg" alt="">
                    </span>
                    <?php echo htmlspecialchars($currentUserName ?? 'Клиент', ENT_QUOTES, 'UTF-8'); ?>
                </span>
                <?php if ($currentUserRole === 'consultant' || $currentUserRole === 'admin'): ?>
                    <a href="consultant-dashboard.php" class="btn btn-secondary">Кабинет юриста</a>
                <?php else: ?>
                    <a href="client-dashboard.php" class="btn btn-secondary">Личный кабинет</a>
                <?php endif; ?>
                <a href="../backend/handlers/handle_logout.php" class="btn btn-outline">Выход</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline">Войти</a>
                <a href="register.php" class="btn btn-primary">Регистрация</a>
            <?php endif; ?>
        </div>
    </div>
</header>

