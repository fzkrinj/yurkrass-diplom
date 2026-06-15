<?php
// Страница онлайн-консультанта (чат)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../backend/config/db.php';

$activePage = '';
$isAuthenticated = !empty($_SESSION['user_id']);
$initialSessionId = 0;
$initialStatus = 'bot';

if ($isAuthenticated) {
    $currentUserId = (int)$_SESSION['user_id'];
    $sessionIdFromQuery = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
    if ($sessionIdFromQuery > 0) {
        // Подхватываем выбранную сессию из кабинета клиента.
        $_SESSION['active_chat_session_id'] = $sessionIdFromQuery;
    }
    $activeChatSessionId = isset($_SESSION['active_chat_session_id']) ? (int)$_SESSION['active_chat_session_id'] : 0;

    if ($activeChatSessionId > 0) {
        $stmt = $mysqli->prepare('SELECT id, status FROM chat_sessions WHERE id = ? AND client_id = ? AND status IN ("waiting_for_consultant", "consultant_connected") LIMIT 1');
        $stmt->bind_param('ii', $activeChatSessionId, $currentUserId);
        $stmt->execute();
        $sessionRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($sessionRow) {
            $initialSessionId = (int)$sessionRow['id'];
            $initialStatus = $sessionRow['status'];
        } else {
            unset($_SESSION['active_chat_session_id']);
        }
    }
}
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
            max-width: 760px;
            margin: 16px auto 0;
        }
        .chat-window {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #E5E7EB;
            box-shadow: 0 10px 24px rgba(26, 54, 93, 0.08);
            display: flex;
            flex-direction: column;
            height: 520px;
            overflow: hidden;
        }
        .chat-messages {
            flex: 1;
            padding: 12px 14px;
            overflow-y: auto;
            background: #F5F7FA;
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
            background: var(--color-accent);
            color: #ffffff;
            margin-left: auto;
        }
        .chat-message.bot {
            background: #ffffff;
            border: 1px solid #E5E7EB;
            margin-right: auto;
        }
        .chat-message.consultant {
            background: #E7F7EE;
            border: 1px solid #BEEACD;
            margin-right: auto;
        }
        .chat-message.system {
            background: #FFF4E6;
            border: 1px solid #FFD9A8;
            margin-right: auto;
        }
        .chat-system-note {
            text-align: center;
            font-size: 0.8rem;
            color: #777;
            margin: 6px 0;
        }
        .chat-toolbar {
            padding: 10px 12px;
            border-bottom: 1px solid #E5E7EB;
            background: #ffffff;
            font-size: 0.9rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }
        .chat-toolbar small {
            color: #777;
        }
        .chat-links {
            margin-top: 4px;
            font-size: 0.85rem;
        }
        .chat-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            padding: 10px 12px;
            border-top: 1px solid #E5E7EB;
            background: #ffffff;
            position: relative;
            z-index: 2;
        }
        .chat-actions .btn {
            padding: 6px 12px;
            font-size: 0.9rem;
        }
        .chat-input-row {
            display: none;
            gap: 8px;
            padding: 10px 12px;
            border-top: 1px solid #E5E7EB;
            background: #F5F7FA;
        }
        .chat-input-row.is-visible {
            display: flex;
        }
        .chat-input-row input[type="text"] {
            flex: 1;
            padding: 8px 10px;
            border-radius: 6px;
            border: 1px solid #D1D5DB;
            font-size: 0.95rem;
        }
        .chat-note-box {
            margin-top: 12px;
            padding: 12px 14px;
            border-radius: 8px;
            background: #FFF4E6;
            color: #7A5200;
            font-size: 0.92rem;
        }

        @media (max-width: 768px) {
            .chat-wrapper {
                margin-top: 8px;
            }

            .chat-window {
                height: 70vh;
                min-height: 420px;
                border-radius: 10px;
            }

            .chat-toolbar {
                flex-direction: column;
                align-items: flex-start;
            }

            .chat-actions .btn,
            .chat-input-row .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/partials/header.php'; ?>

<main>
    <section class="page-header">
        <div class="container">
            <h1>Онлайн‑консультант</h1>
            <p>Здесь можно запросить подключение живого консультанта. Сначала система подскажет, что нужно сделать, а затем передаст запрос специалисту.</p>
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
                            Здравствуйте! Я помощник онлайн‑консультанта компании «Юркрас».
                            <br><br>
                            Если вам нужна помощь юриста, нажмите кнопку <b>«Проконсультироваться»</b>. Я помогу передать ваш запрос живому специалисту.
                        </div>
                    </div>
                    <div class="chat-actions" id="chatActions">
                        <button id="startConsultationBtn" class="btn btn-primary" type="button">Проконсультироваться</button>
                        <button id="closeChatBtn" class="btn btn-outline" type="button" style="display:none;">Завершить разговор</button>
                    </div>
                    <div class="chat-input-row" id="chatInputRow">
                        <input type="text" id="chatInput" placeholder="Напишите сообщение консультанту..." autocomplete="off">
                        <button id="chatSendBtn" class="btn btn-primary" type="button">Отправить</button>
                    </div>
                </div>
                <div class="chat-note-box">
                    После входа в личный кабинет запрос будет отправлен онлайн‑консультанту, а статус диалога изменится на ожидание специалиста.
                </div>
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
    const CHAT_IS_AUTHENTICATED = <?php echo $isAuthenticated ? 'true' : 'false'; ?>;
    const CHAT_LOGIN_URL = 'login.php?redirect=chat.php';
    const CHAT_REGISTER_URL = 'register.php?redirect=chat.php';
    const CHAT_INITIAL_SESSION_ID = <?php echo $initialSessionId; ?>;
    const CHAT_INITIAL_STATUS = '<?php echo htmlspecialchars($initialStatus, ENT_QUOTES, 'UTF-8'); ?>';
</script>
<script src="assets/js/chat.js"></script>
</body>
</html>

