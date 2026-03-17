<?php

// Обработчик AJAX-запросов чата.

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/chat_logic.php';
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
        json_forbidden('Требуется вход в аккаунт юриста');
    }
    $role = $_SESSION['user_role'] ?? null;
    if ($role !== 'consultant' && $role !== 'admin') {
        json_forbidden('Требуются права юриста');
    }
    return (int)$_SESSION['user_id'];
}

// Создаём новую сессию, если не передана существующая
if ($sessionId <= 0) {
    $stmt = $mysqli->prepare('INSERT INTO chat_sessions (client_id, status) VALUES (?, "bot")');
    if ($clientId === null) {
        $null = null;
        $stmt->bind_param('i', $null);
    } else {
        $stmt->bind_param('i', $clientId);
    }
    $stmt->execute();
    $sessionId = $stmt->insert_id;
    $stmt->close();
}

// Действие: получить новые сообщения (клиент/юрист)
if ($action === 'fetch_messages') {
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
    // Меняем статус сессии на ожидание консультанта
    $stmt = $mysqli->prepare('UPDATE chat_sessions SET status = "waiting_for_consultant" WHERE id = ?');
    $stmt->bind_param('i', $sessionId);
    $stmt->execute();
    $stmt->close();

    $botText = 'Хорошо. Я передал запрос — подключаем живого консультанта. Пожалуйста, опишите ситуацию одним предложением и оставьте ваш контакт (телефон), если это удобно.';

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
    exit;
}

// Действие: юрист подключается к сессии
if ($action === 'consultant_connect') {
    require_consultant_role();

    $stmt = $mysqli->prepare('UPDATE chat_sessions SET status = "consultant_connected" WHERE id = ? AND status IN ("waiting_for_consultant", "bot", "consultant_connected")');
    $stmt->bind_param('i', $sessionId);
    $stmt->execute();
    $stmt->close();

    $botText = 'К диалогу подключился живой консультант. Пожалуйста, опишите ситуацию и укажите, что именно хотите получить (консультацию, документы, представительство в суде).';
    $stmt = $mysqli->prepare('INSERT INTO chat_messages (session_id, sender_type, sender_id, message_text) VALUES (?, "bot", NULL, ?)');
    $stmt->bind_param('is', $sessionId, $botText);
    $stmt->execute();
    $stmt->close();

    echo json_encode([
        'session_id' => $sessionId,
        'mode' => 'consultant_connected',
        'bot_message' => $botText,
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
    $stmt->close();

    echo json_encode([
        'session_id' => $sessionId,
        'mode' => 'closed',
        'bot_message' => $botText,
    ], JSON_UNESCAPED_UNICODE);
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

if ($message === '') {
    echo json_encode(['error' => 'Пустое сообщение']);
    exit;
}

// Сохраняем сообщение клиента
$stmt = $mysqli->prepare('INSERT INTO chat_messages (session_id, sender_type, sender_id, message_text) VALUES (?, "client", NULL, ?)');
$stmt->bind_param('is', $sessionId, $message);
$stmt->execute();
$stmt->close();

// Ищем ответ бота
$botAnswer = bot_find_answer($message);

if ($botAnswer === null) {
    $botText = 'Я не нашёл точного ответа на ваш вопрос. Наш специалист сможет помочь вам подробнее. Вы можете оставить заявку на консультацию или уточнить вопрос.';
    $serviceLink = null;
} else {
    $botText = $botAnswer['answer_text'];
    $serviceLink = $botAnswer['service_link'];
}

// Сохраняем сообщение бота
$stmt = $mysqli->prepare('INSERT INTO chat_messages (session_id, sender_type, sender_id, message_text) VALUES (?, "bot", NULL, ?)');
$stmt->bind_param('is', $sessionId, $botText);
$stmt->execute();
$stmt->close();

echo json_encode([
    'session_id' => $sessionId,
    'bot_message' => $botText,
    'service_link' => $serviceLink,
    'mode' => 'bot',
]);

