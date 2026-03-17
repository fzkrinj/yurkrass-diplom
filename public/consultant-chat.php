<?php
require_once __DIR__ . '/../backend/lib/auth.php';
auth_require_login('consultant');

$sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
$activePage = '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Чат с клиентом — Юркрас</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .chat-wrapper { max-width: 820px; margin: 24px auto; }
        .chat-window {
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
            display: flex;
            flex-direction: column;
            height: 520px;
            overflow: hidden;
        }
        .chat-messages { flex: 1; padding: 12px 14px; overflow-y: auto; background: #f4f6fb; }
        .chat-message { max-width: 85%; padding: 8px 10px; border-radius: 10px; margin-bottom: 8px; font-size: 0.95rem; }
        .chat-message.client { background: #d7e7ff; margin-left: auto; }
        .chat-message.bot { background: #ffffff; border: 1px solid #e0e0e0; margin-right: auto; }
        .chat-message.consultant { background: #e4f7e6; margin-right: auto; border: 1px solid #c9e9cf; }
        .chat-input-row { display: flex; gap: 8px; padding: 10px; border-top: 1px solid #e0e0e0; background: #fafafa; }
        .chat-input-row input[type="text"] { flex: 1; padding: 8px 10px; border-radius: 6px; border: 1px solid #cccccc; font-size: 0.95rem; }
        .chat-toolbar { padding: 8px 10px; border-bottom: 1px solid #e0e0e0; background: #ffffff; font-size: 0.9rem; display: flex; justify-content: space-between; align-items: center; gap: 10px; }
        .chat-toolbar small { color: #777; }
        .chat-toolbar .left { display: flex; flex-direction: column; gap: 4px; }
        .chat-toolbar .right { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/partials/header.php'; ?>

<main>
    <section class="page-header">
        <div class="container">
            <h1>Чат с клиентом</h1>
            <p>Сессия №<?php echo (int)$sessionId; ?>. Нажмите «Подключиться», чтобы перевести диалог в режим живого консультанта.</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="chat-wrapper">
                <div class="chat-window">
                    <div class="chat-toolbar">
                        <div class="left">
                            <div>Юрист — онлайн‑консультант «Юркрас»</div>
                            <small id="statusLabel">Статус: —</small>
                        </div>
                        <div class="right">
                            <button type="button" class="btn btn-outline" id="connectBtn">Подключиться</button>
                            <button type="button" class="btn btn-outline" id="closeBtn">Закрыть диалог</button>
                            <a class="btn btn-secondary" href="consultant-dashboard.php">Назад</a>
                        </div>
                    </div>

                    <div class="chat-messages" id="chatMessages">
                        <div class="chat-message bot">Откройте сессию и нажмите «Подключиться». Затем отвечайте клиенту внизу.</div>
                    </div>

                    <div class="chat-input-row">
                        <input type="text" id="chatInput" placeholder="Ваш ответ клиенту..." autocomplete="off">
                        <button id="chatSendBtn" class="btn btn-primary" type="button">Отправить</button>
                    </div>
                </div>

                <p class="note" style="margin-top: 8px;">
                    Подсказка: если клиент «плохо ориентируется», пишите коротко и по шагам: что сделать сейчас, какие документы подготовить, куда перейти на сайте.
                </p>
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

<script>
    const CHAT_HANDLER_URL = '../backend/handlers/handle_chat_message.php';
    const CONSULTANT_SESSION_ID = <?php echo (int)$sessionId; ?>;
</script>
<script src="assets/js/consultant-chat.js"></script>
</body>
</html>

