<?php

require_once __DIR__ . '/../lib/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../public/login.php');
    exit;
}

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$redirect = trim((string)($_POST['redirect'] ?? ''));

function build_public_redirect(string $target, string $fallback): string
{
    if ($target === '' || preg_match('/^[a-z]+:/i', $target) || str_contains($target, '..') || str_starts_with($target, '/')) {
        return $fallback;
    }

    return '../../public/' . ltrim($target, '\\/');
}

$result = auth_login($email, $password);

if ($result['success']) {
    $role = $result['role'] ?? 'client';
    if ($role === 'consultant') {
        header('Location: ../../public/consultant-dashboard.php');
    } else {
        header('Location: ' . build_public_redirect($redirect, '../../public/client-dashboard.php'));
    }
    exit;
}

session_start();
$_SESSION['login_error'] = $result['error'] ?? 'Ошибка входа.';
header('Location: ' . build_public_redirect('login.php' . ($redirect !== '' ? '?redirect=' . urlencode($redirect) : ''), '../../public/login.php'));
exit;

