<?php

require_once __DIR__ . '/../lib/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../public/login.php');
    exit;
}

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

$result = auth_login($email, $password);

if ($result['success']) {
    $role = $result['role'] ?? 'client';
    if ($role === 'consultant' || $role === 'admin') {
        header('Location: ../../public/consultant-dashboard.php');
    } else {
        header('Location: ../../public/client-dashboard.php');
    }
    exit;
}

session_start();
$_SESSION['login_error'] = $result['error'] ?? 'Ошибка входа.';
header('Location: ../../public/login.php');
exit;

