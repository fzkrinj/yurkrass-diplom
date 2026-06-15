<?php
require_once __DIR__ . '/../backend/lib/auth.php';
auth_require_login('consultant');

$userId = auth_current_user_id();

global $mysqli;

$stmt = $mysqli->prepare('SELECT full_name FROM users WHERE id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt = $mysqli->prepare('SELECT id, name, phone, status, created_at, comment FROM consultation_requests ORDER BY created_at DESC LIMIT 10');
$stmt->execute();
$requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $mysqli->prepare('SELECT cs.id, cs.status, cs.created_at, u.full_name 
    FROM chat_sessions cs 
    LEFT JOIN users u ON cs.client_id = u.id 
    ORDER BY (cs.status = "waiting_for_consultant") DESC, cs.created_at DESC 
    LIMIT 10');
$stmt->execute();
$sessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $mysqli->prepare('SELECT cs.id, cs.status, cs.created_at, u.full_name 
    FROM chat_sessions cs 
    LEFT JOIN users u ON cs.client_id = u.id 
    WHERE cs.status = "waiting_for_consultant"
    ORDER BY cs.created_at DESC');
$stmt->execute();
$waitingSessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$sessionDeleteSuccess = null;
$sessionDeleteError = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_chat_session') {
    $deleteSessionId = isset($_POST['session_id']) ? (int)$_POST['session_id'] : 0;
    if ($deleteSessionId <= 0) {
        $sessionDeleteError = 'Некорректный идентификатор сессии.';
    } else {
        $stmt = $mysqli->prepare('DELETE FROM chat_messages WHERE session_id = ?');
        $stmt->bind_param('i', $deleteSessionId);
        $stmt->execute();
        $stmt->close();

        $stmt = $mysqli->prepare('DELETE FROM chat_sessions WHERE id = ?');
        $stmt->bind_param('i', $deleteSessionId);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        if ($affected > 0) {
            $sessionDeleteSuccess = 'Сессия чата удалена.';
        } else {
            $sessionDeleteError = 'Сессия не найдена или уже удалена.';
        }

        // Обновляем списки после удаления.
        $stmt = $mysqli->prepare('SELECT cs.id, cs.status, cs.created_at, u.full_name 
            FROM chat_sessions cs 
            LEFT JOIN users u ON cs.client_id = u.id 
            ORDER BY (cs.status = "waiting_for_consultant") DESC, cs.created_at DESC 
            LIMIT 10');
        $stmt->execute();
        $sessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $stmt = $mysqli->prepare('SELECT cs.id, cs.status, cs.created_at, u.full_name 
            FROM chat_sessions cs 
            LEFT JOIN users u ON cs.client_id = u.id 
            WHERE cs.status = "waiting_for_consultant"
            ORDER BY cs.created_at DESC');
        $stmt->execute();
        $waitingSessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

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

function chat_status_badge(string $status): string
{
    $map = [
        'bot' => ['Бот', 'badge badge--bot'],
        'waiting_for_consultant' => ['Ожидает консультанта', 'badge badge--waiting'],
        'consultant_connected' => ['Консультант на связи', 'badge badge--connected'],
        'closed' => ['Закрыт', 'badge badge--closed'],
    ];
    $label = $map[$status][0] ?? $status;
    $class = $map[$status][1] ?? 'badge';
    return '<span class="' . $class . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>';
}

$activeTab = $_GET['tab'] ?? ($_POST['tab'] ?? 'requests');
$allowedTabs = ['requests', 'sessions'];
if (!in_array($activeTab, $allowedTabs, true)) {
    $activeTab = 'requests';
}
$initialWaitingSessionIds = array_map(static function (array $row): int {
    return (int)($row['id'] ?? 0);
}, $waitingSessions);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Кабинет консультанта — Юркрас</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/partials/header.php'; ?>

<main class="dashboard-page">
    <div class="dashboard-shell" data-active-tab="<?php echo htmlspecialchars($activeTab, ENT_QUOTES, 'UTF-8'); ?>">
        <div id="dashboardOverlay" class="dashboard-overlay" aria-hidden="true"></div>

        <aside id="dashboardSidebar" class="dashboard-sidebar" aria-label="Навигация кабинета консультанта">
            <div class="dashboard-sidebar-header">
                <h2 class="dashboard-sidebar-title">Кабинет консультанта</h2>
                <button type="button" id="dashboardSidebarCloseBtn" class="mobile-menu-toggle" aria-label="Закрыть меню" style="display:none;">×</button>
            </div>

            <div class="dashboard-sidebar-profile">
                <div class="dashboard-profile-row">
                    <div class="dashboard-avatar" aria-hidden="true">К</div>
                    <div>
                        <p class="dashboard-profile-name"><?php echo htmlspecialchars($user['full_name'] ?? 'Консультант', ENT_QUOTES, 'UTF-8'); ?></p>
                        <p class="dashboard-profile-email">consultant</p>
                    </div>
                </div>
            </div>

            <nav class="dashboard-nav" aria-label="Меню кабинета консультанта">
                <button type="button" class="dashboard-tab-button <?php echo ($activeTab === 'requests') ? 'is-active' : ''; ?>" data-tab="requests" data-label="Заявки">
                    <span class="dashboard-icon-pill" aria-hidden="true">1</span>
                    <span>Заявки</span>
                    <span id="waitingCountBadge" class="waiting-count-badge<?php echo empty($waitingSessions) ? '' : ' is-visible'; ?>">
                        <?php echo (int)count($waitingSessions); ?>
                    </span>
                </button>
                <button type="button" class="dashboard-tab-button <?php echo ($activeTab === 'sessions') ? 'is-active' : ''; ?>" data-tab="sessions" data-label="Сессии">
                    <span class="dashboard-icon-pill" aria-hidden="true">2</span>
                    <span>Сессии</span>
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
                <section id="tab-requests" class="dashboard-tab-panel <?php echo ($activeTab === 'requests') ? 'is-active' : ''; ?>" aria-label="Вкладка Заявки">
                    <h2 class="dashboard-panel-title">Заявки</h2>
                    <div id="consultantChatNotification" class="consultant-chat-notification" style="display:none;"></div>

                    <div class="cards" style="grid-template-columns: 1.4fr 1.4fr;">
                        <article class="card">
                            <h2>Последние заявки на консультацию</h2>
                            <?php if (empty($requests)): ?>
                                <p class="note">Заявок пока нет.</p>
                            <?php else: ?>
                                <table class="table-simple">
                                    <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Имя</th>
                                        <th>Телефон</th>
                                        <th>Статус</th>
                                        <th>Кратко о вопросе</th>
                                        <th>Создана</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($requests as $r): ?>
                                        <tr>
                                            <td><?php echo (int)$r['id']; ?></td>
                                            <td><?php echo htmlspecialchars($r['name'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($r['phone'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo request_status_badge($r['status']); ?></td>
                                            <td><?php echo nl2br(htmlspecialchars($r['comment'] ?? '—', ENT_QUOTES, 'UTF-8')); ?></td>
                                            <td><?php echo htmlspecialchars(substr($r['created_at'] ?? '', 0, 16), ENT_QUOTES, 'UTF-8'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </article>

                        <article class="card">
                            <h2>Ожидают консультанта</h2>
                            <?php if (empty($waitingSessions)): ?>
                                <p class="note">Сейчас нет сессий, ожидающих подключение.</p>
                            <?php else: ?>
                                <table class="table-simple">
                                    <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Клиент</th>
                                        <th>Создана</th>
                                        <th>Действие</th>
                                    </tr>
                                    </thead>
                                    <tbody id="waitingSessionsTbody">
                                    <?php foreach ($waitingSessions as $s): ?>
                                        <tr data-session-id="<?php echo (int)$s['id']; ?>">
                                            <td><?php echo (int)$s['id']; ?></td>
                                            <td><?php echo htmlspecialchars($s['full_name'] ?? 'Гость', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($s['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>
                                                <a class="btn btn-primary" href="consultant-chat.php?session_id=<?php echo (int)$s['id']; ?>">Подключиться</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </article>
                    </div>
                </section>

                <section id="tab-sessions" class="dashboard-tab-panel <?php echo ($activeTab === 'sessions') ? 'is-active' : ''; ?>" aria-label="Вкладка Сессии">
                    <h2 class="dashboard-panel-title">Сессии онлайн‑чата</h2>
                    <div class="cards" style="grid-template-columns: 1.4fr;">
                        <article class="card">
                            <h2>Последние сессии онлайн‑чата</h2>
                            <?php if ($sessionDeleteSuccess): ?>
                                <div class="alert alert-success"><?php echo htmlspecialchars($sessionDeleteSuccess, ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endif; ?>
                            <?php if ($sessionDeleteError): ?>
                                <div class="alert alert-error"><?php echo htmlspecialchars($sessionDeleteError, ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endif; ?>
                            <?php if (empty($sessions)): ?>
                                <p class="note">Сессий чата пока нет.</p>
                            <?php else: ?>
                                <table class="table-simple">
                                    <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Клиент</th>
                                        <th>Статус</th>
                                        <th>Создана</th>
                                        <th>Действие</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($sessions as $s): ?>
                                        <tr>
                                            <td><?php echo (int)$s['id']; ?></td>
                                            <td><?php echo htmlspecialchars($s['full_name'] ?? 'Гость', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo chat_status_badge($s['status']); ?></td>
                                            <td><?php echo htmlspecialchars(substr($s['created_at'] ?? '', 0, 16), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>
                                                <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                                    <a class="btn btn-outline" href="consultant-chat.php?session_id=<?php echo (int)$s['id']; ?>">Открыть чат</a>
                                                    <form method="post" onsubmit="return confirm('Удалить эту сессию чата? Это действие нельзя отменить.');" style="margin:0;">
                                                        <input type="hidden" name="action" value="delete_chat_session">
                                                        <input type="hidden" name="session_id" value="<?php echo (int)$s['id']; ?>">
                                                        <input type="hidden" name="tab" value="sessions">
                                                        <button type="submit" class="btn btn-danger">Удалить</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                            <p class="note" style="margin-top: 8px;">
                                Для сессий со статусом <b>waiting_for_consultant</b> можно открыть чат и подключиться к диалогу.
                            </p>
                        </article>
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
        const CHAT_HANDLER_URL = '../backend/handlers/handle_chat_message.php';
        const initialKnownWaitingIds = <?php echo json_encode(array_values(array_filter($initialWaitingSessionIds))); ?>;
        const knownWaitingIds = new Set(initialKnownWaitingIds.map((v) => Number(v)).filter((v) => Number.isFinite(v) && v > 0));
        const waitingCountBadgeEl = document.getElementById('waitingCountBadge');
        const consultantNotificationEl = document.getElementById('consultantChatNotification');
        const waitingSessionsTbodyEl = document.getElementById('waitingSessionsTbody');
        let notificationPermissionAsked = false;

        const activeTabInit = shell.getAttribute('data-active-tab') || 'requests';
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

            const activeBtn = shell.querySelector('.dashboard-tab-button[data-tab="' + tab + '"]');
            if (mobileTitleEl && activeBtn) {
                mobileTitleEl.textContent = activeBtn.getAttribute('data-label') || tab;
            }
        }

        setTab(activeTabInit);

        tabButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                const tab = btn.getAttribute('data-tab') || 'requests';
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

        function renderWaitingCount(count) {
            if (!waitingCountBadgeEl) return;
            waitingCountBadgeEl.textContent = String(count);
            waitingCountBadgeEl.classList.toggle('is-visible', count > 0);
        }

        function showInPageNotification(text, sessionId) {
            if (!consultantNotificationEl) return;
            consultantNotificationEl.style.display = 'flex';
            consultantNotificationEl.innerHTML =
                '<span>' + text + '</span>' +
                '<a class="btn btn-primary" href="consultant-chat.php?session_id=' + Number(sessionId) + '">Подключиться</a>';
        }

        function showBrowserNotification(text, sessionId) {
            if (!('Notification' in window)) return;
            if (Notification.permission === 'granted') {
                const n = new Notification('Новая сессия чата', { body: text });
                n.onclick = function () {
                    window.focus();
                    window.location.href = 'consultant-chat.php?session_id=' + Number(sessionId);
                };
                return;
            }

            if (Notification.permission === 'default' && !notificationPermissionAsked) {
                notificationPermissionAsked = true;
                Notification.requestPermission().then(function (permission) {
                    if (permission === 'granted') {
                        showBrowserNotification(text, sessionId);
                    }
                });
            }
        }

        function renderWaitingSessionsTable(sessions) {
            if (!waitingSessionsTbodyEl) return;
            waitingSessionsTbodyEl.innerHTML = '';

            sessions.forEach(function (s) {
                const sid = Number(s.id);
                if (!Number.isFinite(sid) || sid <= 0) return;
                const tr = document.createElement('tr');
                tr.setAttribute('data-session-id', String(sid));

                const tdId = document.createElement('td');
                tdId.textContent = String(sid);

                const tdName = document.createElement('td');
                tdName.textContent = s.full_name ? String(s.full_name) : 'Гость';

                const tdCreated = document.createElement('td');
                tdCreated.textContent = s.created_at ? String(s.created_at) : '—';

                const tdAction = document.createElement('td');
                const link = document.createElement('a');
                link.className = 'btn btn-primary';
                link.href = 'consultant-chat.php?session_id=' + sid;
                link.textContent = 'Подключиться';
                tdAction.appendChild(link);

                tr.appendChild(tdId);
                tr.appendChild(tdName);
                tr.appendChild(tdCreated);
                tr.appendChild(tdAction);
                waitingSessionsTbodyEl.appendChild(tr);
            });
        }

        async function pollWaitingSessions() {
            try {
                const response = await fetch(CHAT_HANDLER_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json; charset=utf-8' },
                    body: JSON.stringify({ action: 'consultant_waiting_sessions' })
                });
                if (!response.ok) return;
                const data = await response.json();
                if (data.error) return;

                const sessions = Array.isArray(data.sessions) ? data.sessions : [];
                renderWaitingCount(Number(data.count) || sessions.length);
                renderWaitingSessionsTable(sessions);

                let newestSession = null;
                sessions.forEach(function (s) {
                    const sid = Number(s.id);
                    if (!Number.isFinite(sid) || sid <= 0) return;
                    if (!knownWaitingIds.has(sid)) {
                        knownWaitingIds.add(sid);
                        newestSession = s;
                    }
                });

                if (newestSession) {
                    const name = newestSession.full_name ? String(newestSession.full_name) : 'Клиент';
                    const notifyText = 'Новая ожидающая сессия: ' + name + ' (№' + newestSession.id + ').';
                    showInPageNotification(notifyText, newestSession.id);
                    showBrowserNotification(notifyText, newestSession.id);
                }
            } catch (e) {
                // молча, чтобы не мешать работе консультанта
            }
        }

        setInterval(pollWaitingSessions, 8000);
    })();
</script>
</body>
</html>

