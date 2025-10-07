<?php

// Файл users.php должен объявлять массив $users со списком пользователей и ХЭШАМИ их паролей.
require_once __DIR__ . '/users.php';

// Возвращает всех пользователей из файла users.php.
function getUsersList(): array
{
    global $users;
    return (isset($users) && is_array($users)) ? $users : [];
}

/**
 * Проверяет существование пользователя с указанным логином.
 * Возвращает true, если пользователь найден; иначе false.
 */
function existsUser(string $login): bool
{
    $login = trim($login);
    if ($login === '') return false;

    $users = getUsersList();
    foreach ($users as $user) {
        if ($user['login'] === $login) return true;
    }
    return false;
};

/**
 * Проверяет пароль пользователя.
 * Возвращает true, если логин существует и введённый пароль подходит к ХЭШУ в базе; иначе false.*/
function checkPassword(string $login, string $password): bool
{
    $login = trim($login);
    if ($login === '' || $password === '') return false;

    foreach (getUsersList() as $user) {
        if (($user['login'] ?? null) === $login) {
            return password_verify($password, $user['password']);
        }
    }
    return false;
};

/**
 * Возвращает логин текущего пользователя из сессии или null, если не авторизован.
 * Предполагается, что где-то до вызова уже был вызван session_start().
 */
function getCurrentUser(): ?string
{
    return $_SESSION['login'] ?? null;
};

// Выход пользователя: очищаем данные сессии и завершаем сессию.
function logout(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $_SESSION = [];
    session_destroy();
}
