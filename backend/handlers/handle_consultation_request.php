<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../public/contacts.php');
    exit;
}

$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');

session_start();

if ($name === '' || $phone === '' || $message === '') {
    $_SESSION['contact_error'] = 'Пожалуйста, заполните все обязательные поля.';
    header('Location: ../../public/contacts.php');
    exit;
}

$clientId = auth_current_user_id();
$serviceId = null;
$preferredTime = null;
$status = 'new';

$comment = $message;
if ($email !== '') {
    $comment .= "\nE-mail для связи: " . $email;
}

$stmt = $mysqli->prepare('INSERT INTO consultation_requests (client_id, name, phone, email, service_id, preferred_time, status, comment) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
$stmt->bind_param(
    'ississss',
    $clientId,
    $name,
    $phone,
    $email,
    $serviceId,
    $preferredTime,
    $status,
    $comment
);
$ok = $stmt->execute();
$stmt->close();

if ($ok) {
    $_SESSION['contact_success'] = 'Ваша заявка отправлена. Мы свяжемся с вами в ближайшее время.';
} else {
    $_SESSION['contact_error'] = 'Произошла ошибка при отправке заявки. Попробуйте позже.';
}

header('Location: ../../public/contacts.php');
exit;

