<?php
require_once __DIR__ . '/../backend/lib/auth.php';
auth_require_login('client');

$userId = auth_current_user_id();

global $mysqli;

$stmt = $mysqli->prepare('SELECT full_name, birth_date, phone, email, created_at FROM users WHERE id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt = $mysqli->prepare('SELECT id, status, created_at FROM consultation_requests WHERE client_id = ? ORDER BY created_at DESC LIMIT 10');
$stmt->bind_param('i', $userId);
$stmt->execute();
$requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$activePage = '';

function request_status_badge(string $status): string
{
    $map = [
        'new' => ['Новая', 'badge badge--new'],
        'in_progress' => ['В работе', 'badge badge--in-progress'],
        'completed' => ['Завершена', 'badge badge--completed'],
        'cancelled' => ['Отменена', 'badge badge--cancelled'],
    ];
    $label = $map[$status][0] ?? $status;
    $class = $map[$status][1] ?? 'badge';
    return '<span class="' . $class . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>';
}

$activeTab = $_GET['tab'] ?? 'cases';
$allowedTabs = ['cases', 'documents', 'settings'];
if (!in_array($activeTab, $allowedTabs, true)) {
    $activeTab = 'cases';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет — Юркрас</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/partials/header.php'; ?>

<main class="dashboard-page">
    <div class="dashboard-shell" data-active-tab="<?php echo htmlspecialchars($activeTab, ENT_QUOTES, 'UTF-8'); ?>">
        <div id="dashboardOverlay" class="dashboard-overlay" aria-hidden="true"></div>

        <aside id="dashboardSidebar" class="dashboard-sidebar" aria-label="Навигация личного кабинета">
            <div class="dashboard-sidebar-header">
                <h2 class="dashboard-sidebar-title">Личный кабинет</h2>
                <button type="button" id="dashboardSidebarCloseBtn" class="mobile-menu-toggle" aria-label="Закрыть меню" style="display:none;">×</button>
            </div>

            <div class="dashboard-sidebar-profile">
                <div class="dashboard-profile-row">
                    <div class="dashboard-avatar" aria-hidden="true">КЛ</div>
                    <div>
                        <p class="dashboard-profile-name"><?php echo htmlspecialchars($user['full_name'] ?? 'Клиент', ENT_QUOTES, 'UTF-8'); ?></p>
                        <p class="dashboard-profile-email"><?php echo htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
            </div>

            <nav class="dashboard-nav" aria-label="Меню личного кабинета">
                <button type="button" class="dashboard-tab-button <?php echo ($activeTab === 'cases') ? 'is-active' : ''; ?>" data-tab="cases" data-label="Мои дела">
                    <span class="dashboard-icon-pill" aria-hidden="true">1</span>
                    <span>Мои дела</span>
                </button>
                <button type="button" class="dashboard-tab-button <?php echo ($activeTab === 'documents') ? 'is-active' : ''; ?>" data-tab="documents" data-label="Документы">
                    <span class="dashboard-icon-pill" aria-hidden="true">2</span>
                    <span>Документы</span>
                </button>
                <button type="button" class="dashboard-tab-button <?php echo ($activeTab === 'settings') ? 'is-active' : ''; ?>" data-tab="settings" data-label="Настройки">
                    <span class="dashboard-icon-pill" aria-hidden="true">3</span>
                    <span>Настройки</span>
                </button>
            </nav>
        </aside>

        <div class="dashboard-content">
            <div class="dashboard-mobile-topbar">
                <button type="button" id="dashboardSidebarToggle" class="dashboard-mobile-menu-btn" aria-label="Открыть меню">☰</button>
                <p class="dashboard-mobile-title" id="dashboardMobileTitle"></p>
                <span aria-hidden="true" style="width:42px;"></span>
            </div>

            <div class="container">
                <section id="tab-cases" class="dashboard-tab-panel <?php echo ($activeTab === 'cases') ? 'is-active' : ''; ?>" aria-label="Вкладка Мои дела">
                    <h2 class="dashboard-panel-title">Мои дела</h2>
                    <div class="card">
                        <h2 style="margin-top:0;">Последние заявки на консультацию</h2>
                        <?php if (empty($requests)): ?>
                            <p class="note">У вас ещё нет заявок. Вы можете оставить заявку через раздел «Контакты» или через онлайн‑консультанта.</p>
                        <?php else: ?>
                            <table class="table-simple">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Статус</th>
                                    <th>Дата</th>
                                    <td><?php echo (int)$r['id']; ?></td>
                                    <td><?php echo request_status_badge($r['status']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($r['created_at'] ?? '', 0, 16), ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($requests as $r): ?>
                                    <tr>
                                        <td><?php echo (int)$r['id']; ?></td>
                                        <td><?php echo request_status_badge($r['status']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($r['created_at'] ?? '', 0, 16), ENT_QUOTES, 'UTF-8'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </section>

                <section id="tab-documents" class="dashboard-tab-panel <?php echo ($activeTab === 'documents') ? 'is-active' : ''; ?>" aria-label="Вкладка Документы">
                    <h2 class="dashboard-panel-title">Документы</h2>
                    <div class="card">
                        <p class="note">Раздел документов пока не заполнен. Скоро добавим сюда файлы по вашим делам.</p>
                    </div>
                </section>

                <section id="tab-settings" class="dashboard-tab-panel <?php echo ($activeTab === 'settings') ? 'is-active' : ''; ?>" aria-label="Вкладка Настройки">
                    <h2 class="dashboard-panel-title">Настройки</h2>
                    <div class="card">
                        <h2 style="margin-top:0;">Личные данные</h2>
                        <p><strong>ФИО:</strong> <?php echo htmlspecialchars($user['full_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><strong>Дата рождения:</strong> <?php echo htmlspecialchars($user['birth_date'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><strong>Телефон:</strong> <?php echo htmlspecialchars($user['phone'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><strong>E‑mail:</strong> <?php echo htmlspecialchars($user['email'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><strong>Клиент с:</strong> <?php echo htmlspecialchars(substr($user['created_at'] ?? '', 0, 10), ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </section>
            </div>
        </div>
    </div>
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
<script>
    (function () {
        const shell = document.querySelector('.dashboard-shell');
        if (!shell) return;

        const activeTabInit = shell.getAttribute('data-active-tab') || 'cases';
        const tabButtons = shell.querySelectorAll('.dashboard-tab-button');
        const panels = shell.querySelectorAll('.dashboard-tab-panel');
        const mobileTitleEl = document.getElementById('dashboardMobileTitle');

        function setTab(tab) {
            tabButtons.forEach((btn) => {
                const isActive = btn.getAttribute('data-tab') === tab;
                btn.classList.toggle('is-active', isActive);
            });

            panels.forEach((panel) => {
                const panelTabId = panel.getAttribute('id') || '';
                panel.classList.toggle('is-active', panelTabId === ('tab-' + tab));
            });

            const activeBtn = shell.querySelector('.dashboard-tab-button[data-tab=\"' + tab + '\"]');
            if (mobileTitleEl && activeBtn) {
                mobileTitleEl.textContent = activeBtn.getAttribute('data-label') || tab;
            }
        }

        // init title + active tab
        setTab(activeTabInit);

        tabButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                const tab = btn.getAttribute('data-tab') || 'cases';
                setTab(tab);

                // закрываем сайдбар на мобилке
                const sidebar = document.getElementById('dashboardSidebar');
                const overlay = document.getElementById('dashboardOverlay');
                if (sidebar && overlay) {
                    sidebar.classList.remove('is-open');
                    overlay.classList.remove('is-open');
                }
            });
        });

        // mobile open/close
        const toggleBtn = document.getElementById('dashboardSidebarToggle');
        const closeBtn = document.getElementById('dashboardSidebarCloseBtn');
        const sidebar = document.getElementById('dashboardSidebar');
        const overlay = document.getElementById('dashboardOverlay');

        function openSidebar() {
            if (!sidebar || !overlay) return;
            sidebar.classList.add('is-open');
            overlay.classList.add('is-open');
        }

        function closeSidebar() {
            if (!sidebar || !overlay) return;
            sidebar.classList.remove('is-open');
            overlay.classList.remove('is-open');
        }

        if (toggleBtn) toggleBtn.addEventListener('click', openSidebar);
        if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
        if (overlay) overlay.addEventListener('click', closeSidebar);
    })();
</script>
</body>
</html>

