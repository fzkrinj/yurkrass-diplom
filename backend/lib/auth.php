<?php

// Функции для аутентификации пользователей

require_once __DIR__ . '/../config/db.php';

session_start();

function auth_register(string $fullName, ?string $birthDate, string $phone, string $email, string $password): array
{
    global $mysqli;

    $fullName = trim($fullName);
    $phone = trim($phone);
    $email = trim($email);

    if ($fullName === '' || $email === '' || $password === '') {
        return ['success' => false, 'error' => 'Заполните обязательные поля.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Некорректный формат E‑mail.'];
    }

    $stmt = $mysqli->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        return ['success' => false, 'error' => 'Пользователь с таким E‑mail уже существует.'];
    }
    $stmt->close();

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $mysqli->prepare('INSERT INTO users (role, full_name, birth_date, phone, email, password_hash) VALUES (\'client\', ?, ?, ?, ?, ?)');
    $stmt->bind_param('sssss', $fullName, $birthDate, $phone, $email, $passwordHash);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        return ['success' => false, 'error' => 'Ошибка при регистрации. Попробуйте позже.'];
    }

    return ['success' => true];
}

function auth_login(string $email, string $password): array
{
    global $mysqli;

    $email = trim($email);

    $stmt = $mysqli->prepare('SELECT id, role, full_name, password_hash FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        return ['success' => false, 'error' => 'Неверный E‑mail или пароль.'];
    }

    if (!password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'error' => 'Неверный E‑mail или пароль.'];
    }

    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_name'] = $user['full_name'];

    return ['success' => true, 'role' => $user['role']];
}

function auth_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

function auth_require_login(string $requiredRole = null): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
    if ($requiredRole !== null) {
        $role = $_SESSION['user_role'] ?? null;
        // Админ имеет доступ к кабинету юриста
        if ($requiredRole === 'consultant' && ($role === 'consultant' || $role === 'admin')) {
            return;
        }
        if ($role !== $requiredRole) {
            header('Location: index.php');
            exit;
        }
    }
}

function auth_current_user_id(): ?int
{
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

