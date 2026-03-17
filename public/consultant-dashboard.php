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

$activePage = '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Кабинет юриста — Юркрас</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/partials/header.php'; ?>

<main>
    <section class="page-header">
        <div class="container">
            <h1>Кабинет юриста</h1>
            <p>Обзор новых заявок на консультацию и активных сессий онлайн‑консультанта.</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
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
                                    <td><?php echo htmlspecialchars($r['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($r['comment'] ?? '—', ENT_QUOTES, 'UTF-8')); ?></td>
                                    <td><?php echo htmlspecialchars($r['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
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
                            <tbody>
                            <?php foreach ($waitingSessions as $s): ?>
                                <tr>
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

            <div class="cards" style="grid-template-columns: 1.4fr;">
                <article class="card">
                    <h2>Последние сессии онлайн‑чата</h2>
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
                                    <td><?php echo htmlspecialchars($s['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($s['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <a class="btn btn-outline" href="consultant-chat.php?session_id=<?php echo (int)$s['id']; ?>">Открыть чат</a>
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

