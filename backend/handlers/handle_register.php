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
$redirect = trim((string)($_POST['redirect'] ?? ''));

function build_public_redirect(string $target, string $fallback): string
{
    if ($target === '' || preg_match('/^[a-z]+:/i', $target) || str_contains($target, '..') || str_starts_with($target, '/')) {
        return $fallback;
    }

    return '../../public/' . ltrim($target, '\\/');
}

if ($password !== $password2) {
    $error = 'Пароли не совпадают.';
} else {
    $result = auth_register($fullName, $birthDate, $phone, $email, $password);
    if ($result['success']) {
        $loginTarget = 'login.php?registered=1';
        if ($redirect !== '') {
            $loginTarget .= '&redirect=' . urlencode($redirect);
        }
        header('Location: ' . build_public_redirect($loginTarget, '../../public/login.php?registered=1'));
        exit;
    }
    $error = $result['error'] ?? 'Ошибка регистрации.';
}

session_start();
$_SESSION['register_error'] = $error;
$registerTarget = 'register.php';
if ($redirect !== '') {
    $registerTarget .= '?redirect=' . urlencode($redirect);
}
header('Location: ' . build_public_redirect($registerTarget, '../../public/register.php'));
exit;

