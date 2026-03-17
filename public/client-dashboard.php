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

<main>
    <section class="page-header">
        <div class="container">
            <h1>Личный кабинет клиента</h1>
            <p>Здесь отображаются ваши основные данные и история обращений.</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="cards" style="grid-template-columns: 1.2fr 1.5fr;">
                <article class="card">
                    <h2>Личные данные</h2>
                    <p><strong>ФИО:</strong> <?php echo htmlspecialchars($user['full_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                    <p><strong>Дата рождения:</strong> <?php echo htmlspecialchars($user['birth_date'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></p>
                    <p><strong>Телефон:</strong> <?php echo htmlspecialchars($user['phone'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></p>
                    <p><strong>E‑mail:</strong> <?php echo htmlspecialchars($user['email'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></p>
                    <p><strong>Клиент с:</strong> <?php echo htmlspecialchars(substr($user['created_at'] ?? '', 0, 10), ENT_QUOTES, 'UTF-8'); ?></p>
                </article>

                <article class="card">
                    <h2>Последние заявки на консультацию</h2>
                    <?php if (empty($requests)): ?>
                        <p class="note">У вас ещё нет заявок. Вы можете оставить заявку через раздел «Контакты» или через онлайн‑консультанта.</p>
                    <?php else: ?>
                        <table class="table-simple">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Статус</th>
                                <th>Дата</th>
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

