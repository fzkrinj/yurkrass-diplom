<?php

// Обработчик AJAX-запросов чата.

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Метод не поддерживается']);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!is_array($data)) {
    $data = $_POST;
}

$action = trim($data['action'] ?? '');
$message = trim($data['message'] ?? '');
$sessionId = isset($data['session_id']) ? (int)$data['session_id'] : 0;
$afterId = isset($data['after_id']) ? (int)$data['after_id'] : 0;

// Если пользователь авторизован — привязываем сессию чата к нему
$clientId = auth_current_user_id();

function json_forbidden(string $message = 'Доступ запрещён'): void
{
    http_response_code(403);
    echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function require_consultant_role(): int
{
    if (empty($_SESSION['user_id'])) {
        json_forbidden('Требуется вход в аккаунт консультанта');
    }
    $role = $_SESSION['user_role'] ?? null;
    if ($role !== 'consultant') {
        json_forbidden('Требуются права консультанта');
    }
    return (int)$_SESSION['user_id'];
}

function set_active_client_chat_session(?int $sessionId): void
{
    if ($sessionId === null || $sessionId <= 0) {
        unset($_SESSION['active_chat_session_id']);
        return;
    }

    $_SESSION['active_chat_session_id'] = $sessionId;
}

// Действие: получить сессии, ожидающие консультанта (для уведомлений в кабинете)
if ($action === 'consultant_waiting_sessions') {
    require_consultant_role();

    $stmt = $mysqli->prepare('SELECT cs.id, cs.created_at, u.full_name
        FROM chat_sessions cs
        LEFT JOIN users u ON cs.client_id = u.id
        WHERE cs.status = "waiting_for_consultant"
        ORDER BY cs.created_at DESC');
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode([
        'count' => count($rows),
        'sessions' => $rows,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Действие: получить новые сообщения (клиент/юрист)
if ($action === 'fetch_messages') {
    if ($sessionId <= 0) {
        echo json_encode([
            'session_id' => null,
            'status' => null,
            'messages' => [],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $mysqli->prepare('SELECT status FROM chat_sessions WHERE id = ?');
    $stmt->bind_param('i', $sessionId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        echo json_encode(['error' => 'Сессия не найдена'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $status = $row['status'];

    $stmt = $mysqli->prepare('SELECT id, sender_type, message_text, created_at FROM chat_messages WHERE session_id = ? AND id > ? ORDER BY id ASC');
    $stmt->bind_param('ii', $sessionId, $afterId);
    $stmt->execute();
    $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode([
        'session_id' => $sessionId,
        'status' => $status,
        'messages' => $messages,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Действие: запросить живого консультанта
if ($action === 'request_consultant') {
    if ($clientId === null) {
        json_forbidden('Чтобы отправить запрос консультанту, войдите или зарегистрируйтесь.');
    }

    if ($sessionId > 0) {
        $stmt = $mysqli->prepare('UPDATE chat_sessions SET client_id = ?, status = "waiting_for_consultant" WHERE id = ?');
        $stmt->bind_param('ii', $clientId, $sessionId);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $mysqli->prepare('INSERT INTO chat_sessions (client_id, status) VALUES (?, "waiting_for_consultant")');
        $stmt->bind_param('i', $clientId);
        $stmt->execute();
        $sessionId = $stmt->insert_id;
        $stmt->close();
    }

    $botText = 'Хорошо. Я уже отправил запрос живому консультанту. Пожалуйста, ожидайте подключения специалиста в этом окне.';

    $stmt = $mysqli->prepare('INSERT INTO chat_messages (session_id, sender_type, sender_id, message_text) VALUES (?, "bot", NULL, ?)');
    $stmt->bind_param('is', $sessionId, $botText);
    $stmt->execute();
    $stmt->close();

    echo json_encode([
        'session_id' => $sessionId,
        'bot_message' => $botText,
        'service_link' => null,
        'mode' => 'waiting_for_consultant',
    ]);
    set_active_client_chat_session($sessionId);
    exit;
}

// Действие: юрист подключается к сессии
if ($action === 'consultant_connect') {
    require_consultant_role();

    // Подключиться можно только к сессии, которая еще ожидает консультанта.
    // Это предотвращает одновременное "подключение" нескольких консультантов.
    $stmt = $mysqli->prepare('UPDATE chat_sessions SET status = "consultant_connected" WHERE id = ? AND status = "waiting_for_consultant"');
    $stmt->bind_param('i', $sessionId);
    $stmt->execute();
    $affectedRows = $stmt->affected_rows;
    $stmt->close();

    if ($affectedRows <= 0) {
        echo json_encode([
            'error' => 'Сессия уже принята другим консультантом или недоступна.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $botText = 'К диалогу подключился живой консультант. Пожалуйста, опишите ситуацию и укажите, что именно хотите получить (консультацию, документы, представительство в суде).';
    $stmt = $mysqli->prepare('INSERT INTO chat_messages (session_id, sender_type, sender_id, message_text) VALUES (?, "bot", NULL, ?)');
    $stmt->bind_param('is', $sessionId, $botText);
    $stmt->execute();
    $msgId = $stmt->insert_id;
    $stmt->close();

    echo json_encode([
        'session_id' => $sessionId,
        'mode' => 'consultant_connected',
        'bot_message' => $botText,
        'message_id' => $msgId,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Действие: юрист закрывает диалог
if ($action === 'consultant_close') {
    require_consultant_role();

    $stmt = $mysqli->prepare('UPDATE chat_sessions SET status = "closed", closed_at = NOW() WHERE id = ?');
    $stmt->bind_param('i', $sessionId);
    $stmt->execute();
    $stmt->close();

    $botText = 'Диалог закрыт. Если появятся новые вопросы — вы можете написать в чат снова или оставить заявку на консультацию.';
    $stmt = $mysqli->prepare('INSERT INTO chat_messages (session_id, sender_type, sender_id, message_text) VALUES (?, "bot", NULL, ?)');
    $stmt->bind_param('is', $sessionId, $botText);
    $stmt->execute();
    $msgId = $stmt->insert_id;
    $stmt->close();

    echo json_encode([
        'session_id' => $sessionId,
        'mode' => 'closed',
        'bot_message' => $botText,
        'message_id' => $msgId,
    ], JSON_UNESCAPED_UNICODE);
    set_active_client_chat_session(null);
    exit;
}

// Действие: клиент завершает диалог
if ($action === 'client_close') {
    if ($clientId === null) {
        json_forbidden('Требуется вход в аккаунт клиента');
    }

    $stmt = $mysqli->prepare('UPDATE chat_sessions SET status = "closed", closed_at = NOW() WHERE id = ? AND client_id = ?');
    $stmt->bind_param('ii', $sessionId, $clientId);
    $stmt->execute();
    $stmt->close();

    $botText = 'Диалог завершён клиентом. Если потребуется помощь снова, вы можете повторно обратиться в чат.';
    $stmt = $mysqli->prepare('INSERT INTO chat_messages (session_id, sender_type, sender_id, message_text) VALUES (?, "bot", NULL, ?)');
    $stmt->bind_param('is', $sessionId, $botText);
    $stmt->execute();
    $msgId = $stmt->insert_id;
    $stmt->close();

    echo json_encode([
        'session_id' => $sessionId,
        'mode' => 'closed',
        'bot_message' => $botText,
        'message_id' => $msgId,
    ], JSON_UNESCAPED_UNICODE);
    set_active_client_chat_session(null);
    exit;
}

// Действие: сообщение от юриста
if ($action === 'consultant_send') {
    $consultantId = require_consultant_role();

    if ($message === '') {
        echo json_encode(['error' => 'Пустое сообщение'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $mysqli->prepare('UPDATE chat_sessions SET status = "consultant_connected" WHERE id = ?');
    $stmt->bind_param('i', $sessionId);
    $stmt->execute();
    $stmt->close();

    $stmt = $mysqli->prepare('INSERT INTO chat_messages (session_id, sender_type, sender_id, message_text) VALUES (?, "consultant", ?, ?)');
    $stmt->bind_param('iis', $sessionId, $consultantId, $message);
    $stmt->execute();
    $msgId = $stmt->insert_id;
    $stmt->close();

    echo json_encode([
        'session_id' => $sessionId,
        'message_id' => $msgId,
        'mode' => 'consultant_connected',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'client_send') {
    if ($clientId === null) {
        json_forbidden('Требуется вход в аккаунт клиента');
    }

    if ($message === '') {
        echo json_encode(['error' => 'Пустое сообщение'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $mysqli->prepare('SELECT status, client_id FROM chat_sessions WHERE id = ?');
    $stmt->bind_param('i', $sessionId);
    $stmt->execute();
    $session = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$session) {
        echo json_encode(['error' => 'Сессия не найдена'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ((int)$session['client_id'] !== $clientId) {
        json_forbidden('Эта сессия принадлежит другому клиенту');
    }

    if ($session['status'] === 'closed') {
        echo json_encode(['error' => 'Диалог уже завершён'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $mysqli->prepare('INSERT INTO chat_messages (session_id, sender_type, sender_id, message_text) VALUES (?, "client", ?, ?)');
    $stmt->bind_param('iis', $sessionId, $clientId, $message);
    $stmt->execute();
    $msgId = $stmt->insert_id;
    $stmt->close();

    echo json_encode([
        'session_id' => $sessionId,
        'message_id' => $msgId,
        'mode' => $session['status'],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($message === '') {
    echo json_encode(['error' => 'Пустое сообщение']);
    exit;
}

// Определяем текущий статус сессии, чтобы понять, должен ли отвечать бот
$stmt = $mysqli->prepare('SELECT status FROM chat_sessions WHERE id = ?');
$stmt->bind_param('i', $sessionId);
$stmt->execute();
$session = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$session) {
    echo json_encode(['error' => 'Сессия не найдена'], JSON_UNESCAPED_UNICODE);
    exit;
}

$sessionStatus = $session['status'];

// Сохраняем сообщение клиента
$stmt = $mysqli->prepare('INSERT INTO chat_messages (session_id, sender_type, sender_id, message_text) VALUES (?, "client", NULL, ?)');
$stmt->bind_param('is', $sessionId, $message);
$stmt->execute();
$clientMessageId = $stmt->insert_id;
$stmt->close();

if ($sessionStatus === 'closed') {
    echo json_encode([
        'session_id' => $sessionId,
        'message_id' => $clientMessageId,
        'mode' => 'closed',
        'bot_message' => null,
        'service_link' => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($sessionStatus === 'waiting_for_consultant') {
    $botText = 'Ваше сообщение передано. Пожалуйста, дождитесь подключения живого консультанта.';

    $stmt = $mysqli->prepare('INSERT INTO chat_messages (session_id, sender_type, sender_id, message_text) VALUES (?, "bot", NULL, ?)');
    $stmt->bind_param('is', $sessionId, $botText);
    $stmt->execute();
    $stmt->close();

    echo json_encode([
        'session_id' => $sessionId,
        'message_id' => $clientMessageId,
        'bot_message' => $botText,
        'service_link' => null,
        'mode' => 'waiting_for_consultant',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($sessionStatus === 'consultant_connected') {
    echo json_encode([
        'session_id' => $sessionId,
        'message_id' => $clientMessageId,
        'bot_message' => null,
        'service_link' => null,
        'mode' => 'consultant_connected',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Универсальное сообщение бота (без подбора ответов из базы).
$botText = 'Спасибо! Я зафиксировал ваше сообщение. Если нужен живой специалист, нажмите «Проконсультироваться» — мы подключим консультанта.';
$serviceLink = null;

// Сохраняем сообщение бота
$stmt = $mysqli->prepare('INSERT INTO chat_messages (session_id, sender_type, sender_id, message_text) VALUES (?, "bot", NULL, ?)');
$stmt->bind_param('is', $sessionId, $botText);
$stmt->execute();
$stmt->close();

echo json_encode([
    'session_id' => $sessionId,
    'message_id' => $clientMessageId,
    'bot_message' => $botText,
    'service_link' => $serviceLink,
    'mode' => 'bot',
]);

