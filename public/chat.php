<?php
// Страница онлайн-консультанта (чат)
$activePage = '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Онлайн‑консультант — Юркрас</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .chat-wrapper {
            max-width: 700px;
            margin: 24px auto;
        }
        .chat-window {
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
            display: flex;
            flex-direction: column;
            height: 430px;
            overflow: hidden;
        }
        .chat-messages {
            flex: 1;
            padding: 12px 14px;
            overflow-y: auto;
            background: #f4f6fb;
            position: relative;
            z-index: 1;
        }
        .chat-message {
            max-width: 80%;
            padding: 8px 10px;
            border-radius: 10px;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }
        .chat-message.client {
            background: #d7e7ff;
            margin-left: auto;
        }
        .chat-message.bot {
            background: #ffffff;
            border: 1px solid #e0e0e0;
            margin-right: auto;
        }
        .chat-message.consultant {
            background: #e4f7e6;
            border: 1px solid #c9e9cf;
            margin-right: auto;
        }
        .chat-system-note {
            text-align: center;
            font-size: 0.8rem;
            color: #777;
            margin: 6px 0;
        }
        .chat-input-row {
            display: flex;
            gap: 8px;
            padding: 10px;
            border-top: 1px solid #e0e0e0;
            background: #fafafa;
        }
        .chat-input-row input[type="text"] {
            flex: 1;
            padding: 8px 10px;
            border-radius: 6px;
            border: 1px solid #cccccc;
            font-size: 0.95rem;
        }
        .chat-toolbar {
            padding: 8px 10px;
            border-bottom: 1px solid #e0e0e0;
            background: #ffffff;
            font-size: 0.9rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .chat-toolbar small {
            color: #777;
        }
        .chat-links {
            margin-top: 4px;
            font-size: 0.85rem;
        }
        .quick-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            padding: 10px;
            border-top: 1px solid #e0e0e0;
            background: #ffffff;
            position: relative;
            z-index: 2;
        }
        .quick-actions .btn {
            padding: 6px 12px;
            font-size: 0.9rem;
        }
        .chat-inline-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 8px;
        }
        .chat-inline-actions .btn {
            padding: 6px 12px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/partials/header.php'; ?>

<main>
    <section class="page-header">
        <div class="container">
            <h1>Онлайн‑консультант</h1>
            <p>Задайте вопрос — бот предложит краткий ответ и подскажет подходящие услуги. При необходимости к диалогу подключится живой консультант.</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="chat-wrapper">
                <div class="chat-window" id="chatWindow">
                    <div class="chat-toolbar">
                        <div>
                            Онлайн‑консультант «Юркрас»
                            <div class="chat-links">
                                <a href="services.php">Перейти к услугам</a> ·
                                <a href="contacts.php">Оставить заявку</a>
                            </div>
                        </div>
                        <div id="chatModeLabel" class="badge badge--bot">Бот</div>
                    </div>
                    <div class="chat-messages" id="chatMessages">
                        <div class="chat-message bot">
                            Здравствуйте! Я онлайн‑консультант компании «Юркрас».
                            <br><br>
                            Чтобы вам было проще, нажмите одну из кнопок ниже или напишите 1–2 слова по теме (например: «развод», «квартира», «договор»).
                        </div>
                    </div>
                    <div class="quick-actions" id="quickActions">
                        <button type="button" class="btn btn-secondary" data-quick="Нужна консультация по разводу и алиментам">Развод / алименты</button>
                        <button type="button" class="btn btn-secondary" data-quick="Вопрос по покупке квартиры (недвижимость)">Недвижимость</button>
                        <button type="button" class="btn btn-secondary" data-quick="Нужно составить или проверить договор">Договор</button>
                        <button type="button" class="btn btn-secondary" data-quick="Проблема с возвратом товара, защита прав потребителей">Права потребителей</button>
                        <button type="button" class="btn btn-outline" id="requestConsultantBtn">Позвать консультанта</button>
                    </div>
                    <div class="chat-input-row">
                        <input type="text" id="chatInput" placeholder="Введите сообщение..." autocomplete="off">
                        <button id="chatSendBtn" class="btn btn-primary" type="button">Отправить</button>
                    </div>
                </div>
                <p class="note" style="margin-top: 8px;">
                    Если вы не знаете, что писать — используйте кнопки‑подсказки. Также можно переключить диалог на живого консультанта.
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
</script>
<script src="assets/js/chat.js"></script>
</body>
</html>

