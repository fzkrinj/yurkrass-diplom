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
        <div class="logo">
            <a href="index.php" aria-label="Юркрас — на главную">
                <span class="logo-mark" aria-hidden="true">Ю</span>
                <span class="logo-text">ЮРКРАС</span>
            </a>
        </div>
        <div class="logo"><a href="index.php">Юркрас</a></div>
        <nav class="main-nav">
            <a href="index.php" class="<?php echo ($activePage === 'home') ? 'active' : ''; ?>">Главная</a>
            <a href="services.php" class="<?php echo ($activePage === 'services') ? 'active' : ''; ?>">Услуги</a>
            <a href="about.php" class="<?php echo ($activePage === 'about') ? 'active' : ''; ?>">О компании</a>
            <a href="contacts.php" class="<?php echo ($activePage === 'contacts') ? 'active' : ''; ?>">Контакты</a>
        </nav>

            <button
                id="mobileMenuToggle"
                class="mobile-menu-toggle"
                type="button"
                aria-label="Открыть меню"
            >
                ☰
            </button>

        <div class="header-actions">
            <a href="chat.php" class="btn btn-danger">Проконсультироваться</a>
            <?php if ($currentUserId): ?>
                <span class="header-user">
                    <span class="icon" aria-hidden="true">
                        <img src="assets/images/icons/user.svg" alt="">
                    </span>
                    <?php
                        $displayName = $currentUserName ?? 'Клиент';
                        $roleLabels = [
                            'consultant' => 'Консультант',
                            'client' => 'Клиент',
                        ];
                        $displayRole = $roleLabels[$currentUserRole ?? 'client'] ?? 'Клиент';
                        echo htmlspecialchars($displayName . ' · ' . $displayRole, ENT_QUOTES, 'UTF-8');
                    ?>
                </span>
                <?php if ($currentUserRole === 'consultant'): ?>
                    <a href="consultant-dashboard.php" class="btn btn-secondary">Личный кабинет</a>
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
        <nav id="mobileMenuNav" class="main-nav main-nav-mobile" aria-label="Мобильная навигация">
            <a href="index.php" class="<?php echo ($activePage === 'home') ? 'active' : ''; ?>">Главная</a>
            <a href="services.php" class="<?php echo ($activePage === 'services') ? 'active' : ''; ?>">Услуги</a>
            <a href="about.php" class="<?php echo ($activePage === 'about') ? 'active' : ''; ?>">О компании</a>
            <a href="contacts.php" class="<?php echo ($activePage === 'contacts') ? 'active' : ''; ?>">Контакты</a>
            <a href="chat.php">Проконсультироваться</a>

            <?php if ($currentUserId): ?>
                <?php if ($currentUserRole === 'consultant'): ?>
                    <a href="consultant-dashboard.php">Личный кабинет</a>
                <?php else: ?>
                    <a href="client-dashboard.php">Личный кабинет</a>
                <?php endif; ?>
                <a href="../backend/handlers/handle_logout.php">Выход</a>
            <?php else: ?>
                <a href="login.php">Войти</a>
                <a href="register.php">Регистрация</a>
            <?php endif; ?>
        </nav>
</header>
<?php
    $currentPageFile = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $hideWidgetOnPages = ['chat.php', 'consultant-chat.php'];
?>
<?php if (!in_array($currentPageFile, $hideWidgetOnPages, true)): ?>
    <a href="chat.php" class="floating-consultant-widget" aria-label="Открыть онлайн-консультант">
        <span class="floating-consultant-widget__title">Нужна помощь?</span>
        <span class="floating-consultant-widget__action">Проконсультироваться</span>
    </a>
<?php endif; ?>

<script>
    (function () {
        const toggleBtn = document.getElementById('mobileMenuToggle');
        const mobileNav = document.getElementById('mobileMenuNav');
        if (!toggleBtn || !mobileNav) return;

        const closeMenu = () => mobileNav.classList.remove('is-open');
        const toggleMenu = () => mobileNav.classList.toggle('is-open');

        toggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleMenu();
        });

        mobileNav.addEventListener('click', (e) => e.stopPropagation());

        mobileNav.querySelectorAll('a').forEach((a) => {
            a.addEventListener('click', closeMenu);
        });

        document.addEventListener('click', (e) => {
            const clickedToggle = e.target.closest('#mobileMenuToggle');
            if (!mobileNav.contains(e.target) && !clickedToggle) closeMenu();
        });
    })();
</script>

