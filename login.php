<?php

declare(strict_types=1);

// Запускаем сессию — понадобится для хранения логина, даты рождения и времени входа
session_start();

// Подключаем файл с функциями
require_once __DIR__ . "/user_functions.php";

// Массив для ошибок валидации/аутентификации
$errors = [];

// Если пользователь уже залогинен (в сессии есть логин) — отправляем на главную
if (getCurrentUser() !==  null) {
    header('Location: index.php');
    exit;
}

// Обработка отправки формы (метод POST)
if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    $birthday = $_POST['birthday'] ?? '';

    // Валидвция полей
    if ($login === '') {
        $errors['login'] = 'Заполните логин.';
    }
    if ($password === '') {
        $errors['password'] = 'Заполните пароль.';
    }
    if ($birthday === '') {
        $errors['birthday'] = 'Введите дату рождения.';
    }

    // Если ошибок пока нет — проверяем логин/пароль
    if (empty($errors)) {
        // checkPassword() должен сверить логин и пароль (через password_verify) и вернуть true/false
        if (checkPassword($login, $password)) {
            // Аутентификация успешна — пишем данные в сессию
            $_SESSION['login'] = $login;
            $_SESSION['birthday'] = $birthday;
            $_SESSION['login_time'] = time();

            // После успешного входа перенаправляем на главную страницу
            header('Location: index.php');
            exit;
        } else {
            // Неверные логин и/или пароль
            $errors['password'] = 'Неверный логин и пароль.';
        }
    }
}

// Экранирование для безопасного вывода
function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в SPA Aphrodite</title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">
    <link rel="stylesheet" href="styles/style_form.css">
</head>

<body>

    <h2 class="h4 mb-3">Вход в Личный Кабинет</h2>

    <!-- Небольшая подсказка по требованиям к логину/паролю -->
    <div class="login_description">
        Введите ваше имя как Логин, содержащий от 4 до 8 букв и символов.
        <br>
        Введите пароль, содержащий от 8 до 12 букв и символов.
    </div>

    <!-- Форма авторизации. Отправляем данные методом POST на эту же страницу (action не задан) -->
    <form id="login_form" class="container mt-5" method="POST" novalidate>
        <div class="row justify-content-center">
            <div class="col-12 col-sm-8 col-md-6 col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-body">

                        <!-- Поле логина.
                        Если есть ошибка по логину — добавляем класс is-invalid для красной рамки. -->
                        <div class="mb-3">
                            <label for="login" class="form-label">Логин: </label>
                            <input id="login" type="text" name="login"
                                class="form-control <?= !empty($errors['login']) ? 'is-invalid' : '' ?>"
                                minlength="4" maxlength="8" placeholder="admin" required>
                            <?php if (!empty($errors['login'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= e($errors['login']) ?>
                                <?php endif; ?>
                                </div>

                                <!-- Поле пароля -->
                                <div class="mb-3">
                                    <label for="password" class="form-label">Пароль: </label>
                                    <input id="password" type="password" name="password"
                                        class="form-control <?= !empty($errors['password']) ? 'is-invalid' : '' ?>"
                                        minlength="8" maxlength="12" value="" required>
                                    <?php if (!empty($errors['password'])): ?>
                                        <div class="invalid-feedback d-block">
                                            <?= $errors['password'] ?>
                                        <?php endif; ?>
                                        </div>

                                        <!-- Поле даты рождения -->
                                        <div class="mb-3">
                                            <label for="birthday" class="form-label">Дата рождения: </label>
                                            <input id="birthday" type="date" name="birthday"
                                                class="form-control <?= !empty($errors['birthday']) ? 'is-invalid' : '' ?>" required>
                                            <?php if (!empty($errors['birthday'])): ?>
                                                <div class="invalid-feedback d-block">
                                                    <?= e($errors['birthday']) ?>
                                                <?php endif; ?>
                                                </div>

                                                <!-- Кнопка отправки формы -->
                                                <button type="submit" class="btn btn-primary w-100">Вход</button>

                                        </div>
                                </div>
                        </div>
                    </div>
    </form>


</body>

</html>