<?php

// Параметры подключения к MySQL под XAMPP.
// При необходимости отредактируйте значения под свою конфигурацию.

$dbHost = 'localhost';
$dbName = 'yurkrass_db';
$dbUser = 'root';
$dbPass = ''; // по умолчанию в XAMPP пароль пустой, если вы его не меняли

$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

if ($mysqli->connect_error) {
    die('Ошибка подключения к базе данных: ' . $mysqli->connect_error);
}

$mysqli->set_charset('utf8mb4');

