<?php

require_once __DIR__ . '/../lib/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../public/register.php');
    exit;
}

$fullName = $_POST['full_name'] ?? '';
$birthDate = $_POST['birth_date'] ?? null;
$phone = $_POST['phone'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$password2 = $_POST['password_confirm'] ?? '';

if ($password !== $password2) {
    $error = 'Пароли не совпадают.';
} else {
    $result = auth_register($fullName, $birthDate, $phone, $email, $password);
    if ($result['success']) {
        header('Location: ../../public/login.php?registered=1');
        exit;
    }
    $error = $result['error'] ?? 'Ошибка регистрации.';
}

session_start();
$_SESSION['register_error'] = $error;
header('Location: ../../public/register.php');
exit;

