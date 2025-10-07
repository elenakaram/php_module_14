<?php

// Запускаем сессию: будет хранить логин, дату рождения и время входа
session_start();

// Подключаем файл с функциями
require_once __DIR__ . "/user_functions.php";

// Экранирование для безопасного вывода
function e(string $s): string
{
  return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Склонение слова "день/дня/дней" */
function ruPluralDays(int $n): string
{
  $n = abs($n) % 100;
  $n1 = $n % 10;
  if ($n > 10 && $n < 20) return 'дней';
  if ($n1 === 1) return 'день';
  if ($n1 >= 2 && $n1 <= 4) return 'дня';
  return 'дней';
}

// Текущий пользователь из сессии (или null)
$user = getCurrentUser();

// Данные для приветствия/акции
$secondsLeft = null;          // сколько секунд осталось персональной акции
$loginTimeHIS = null;        // форматированное время входа
$leftHms = null;             // осталось в формате HH:MM:SS
$greetingMessage = '';       // сообщение о ДР/скидке
$daysToBirthday = null;      // дней до ДР


// Расчет времени для персональная акция на 24 часа с момента входа
if ($user) {
   // Достаём момент входа из сессии (Unix timestamp)
  $loginTime = $_SESSION['login_time'] ?? null;
  
  if ($loginTime !== null && is_numeric($loginTime)) {
    $passed = time() - $loginTime;                // Сколько прошло секунд с момента входа
    $secondsLeft = max(0, 24 * 3600 - $passed);   // Сколько осталось до конца 24-часовой акции (не меньше нуля)
    $leftHms = gmdate('H:i:s', $secondsLeft);     // Для первичного отображения — формат вида HH:MM:SS
    $loginTimeHIS = date('H:i:s', $loginTime);    // Форматируем время входа
  }

  // Если в сессии есть дата рождения — считаем сколько дней до следующего ДР
  if (!empty($_SESSION['birthday'])) {
    // Парсим дату 'YYYY-MM-DD'
    $birthday = DateTime::createFromFormat('Y-m-d', $_SESSION['birthday']);
    $now = new DateTime('today');   // сегодняшняя дата (без времени)
    
    // День рождения в текущем году
    $nextBirthday = new DateTime($now->format('Y') . '-' . $birthday->format('m-d'));
    
    // Если в этом году уже прошёл — сдвигаем на следующий год
    if ($nextBirthday < $now) {
      $nextBirthday->modify('+1 year');
    }
    
    // Считаем, сколько дней до следующего ДР
    $daysToBirthday = (int)$now->diff($nextBirthday)->days;
  }

  // Сообщение для шапки: если сегодня ДР — поздравляем, иначе показываем сколько осталось
  if ($daysToBirthday === 0) {
    $greetingMessage = "Поздравляем с днем рождения! Ваша скидка 5% на все услуги салона!";
  } else {
    $greetingMessage = "Ваш день рождения наступит через {$daysToBirthday}" . ruPluralDays($daysToBirthday) . ".";
  }
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SPA Салон Aphrodite</title>
  <link rel="stylesheet" href="styles/style.css">
</head>

<body>

  <div class="container">
    <header class="hero-header">
      <div class="hero-header__inner">
        <h1>SPA Салон Aphrodite</h1>
        <br>

        <?php if ($user): ?>
          <!-- Блок приветствия виден только авторизованным юзерам-->
          <div class="greeting-line">
            Здравствуйте, <?= e($user) ?> !
            <br><br>

            <?php if ($greetingMessage !== ''): ?>
              <span class="greeting-msg"><?= e($greetingMessage) ?></span>
            <?php endif; ?>
            <br><br>
            
            <!-- Время входа (заводим на него таймер ниже) -->
            <p class="lead">Время входа на сайт: <?= e($loginTimeHIS ?? '-') ?></p>
          </div>
        <?php endif; ?>
      </div>
    </header>
  </div>

  <div class="layout">
    <!-- Боковое меню с якорями: ведут к секциям на этой же странице -->
    <nav class="sidebar">
      <div class="menu-block">
        <ul class="menu">
          <li><a href="#about">О нас</a></li>
          <li><a href="#services">Услуги</a></li>
          <li><a href="#contacts">Контакты</a></li>
        </ul>

        <!-- В зависимости от того, вошёл ли пользователь, показываем "ЛК/Войти" и "Выход" -->
        <?php if ($user): ?>
          <a href="/account" class="personal-account">Личный кабинет</a>
          <br><br>
          <a href="logout.php">Выйти</a>
        <?php else: ?>
          <a href="login.php" class="personal-account">Войти</a>
        <?php endif; ?>
      </div>
    </nav>

    <main class="content">
      <!-- О нас -->
      <section id="about">
        <h2>О нас</h2>
        <p class="lead">Уютный SPA-салон с персональными программами отдыха и восстановления.</p>
        <br>
        <div class="grid">
          <div class="card">Ароматерапия и релакс-зоны</div>
          <div class="card">Профессиональные мастера</div>
          <div class="card">Подарочные сертификаты</div>
        </div>
      </section>

      <!-- Услуги + персональная акция 24 часа -->
      <section id="services">
        <h2>Услуги</h2>
        <br>

        <?php if ($user && isset($secondsLeft) &&  $secondsLeft > 0): ?>
          <!-- Пользователь авторизован и акция ещё действует: показываем таймер -->
          <div class="promo promo--inline">
            <strong>Ваша персональная скидка 15%</strong> действует еще:
            <span id="promo-timer"><?= e($leftHms ?? '00:00:00') ?></span>
          </div>
        <?php elseif ($user && isset($secondsLeft) && $secondsLeft <= 0): ?>
          <!-- Авторизован, но акция завершилась -->
          <div class="promo promo--inline promo--expired">
            Персональная скидка истекла.
          </div>
        <?php else: ?>
          <!-- Гость (не авторизован): предлагаем войти -->
          <div class="promo promo--inline">
            <strong>Персональная скидка 15% авторизованным пользователям.</strong>
            <a href="login.php" class="promo-link">Войти</a>
          </div>
        <?php endif; ?>

        <!-- Карточки услуг (картинка снизу, текст сверху) -->
        <div class="services-grid">
          <div class="service-card">
            <div class="service-body">
              <h3 class="card-title">Аромамассаж</h3>
              <p class="card-text">60 мин • Расслабление и восстановление.</p>
              <span class="price">3&nbsp;500 ₽</span>
            </div>
            <div class="service-media">
              <img src="images/aromamassage.jpg" alt="SPA-комплекс">
            </div>
          </div>

          <div class="service-card">
            <div class="service-body">
              <h3 class="card-title">Стоун-терапия</h3>
              <p class="card-text">75 мин • Глубокое прогревание мышц.</p>
              <span class="price">4&nbsp;200 ₽</span>
            </div>
            <div class="service-media">
              <img src="images/stonetherapy.jpg" alt="SPA-комплекс">
            </div>
          </div>

          <div class="service-card">
            <div class="service-body">
              <h3 class="card-title">SPA-комплекс</h3>
              <p class="card-text">120 мин • Полная перезагрузка.</p>
              <span class="price">7&nbsp;900 ₽</span>
            </div>
            <div class="service-media">
              <img src="images/spacomplex.jpg" alt="SPA-комплекс">
            </div>
          </div>

          <div class="service-card">
            <div class="service-body">
              <h3 class="card-title">Талассо терапия</h3>
              <p class="card-text">120 мин • Антистресс и оздоровление.</p>
              <span class="price">15&nbsp;300 ₽</span>
            </div>
            <div class="service-media">
              <img src="images/talassotherapy.jpg" alt="SPA-комплекс">
            </div>
          </div>
        </div>
      </section>

      <!-- Контакты -->
      <section id="contacts">
        <h2>Контакты</h2>
        <p>г. Москва, ул. Приморская, 1<br>+7 (999) 123-45-67</p>
        <p>Часы работы: ежедневно 10:00–22:00</p>
      </section>
    </main>
  </div>

  <?php if ($user && isset($secondsLeft) && $secondsLeft > 0): ?>
    <script>
      // Кол-во секунд до конца акции (берём серверное значение из PHP)
      let left = <?= (int)$secondsLeft ?>;

      // Элемент, где рисуем таймер <span id="promo-timer">
      const el = document.getElementById('promo-timer'); 

      // Дополняем числа нулём слева: 9 -> '09'
      function pad(n) {
        return (n < 10 ? '0' : '') + n;
      } 
 
      // Перерисовка таймера в формате HH:MM:SS
      function render() {
        const h = Math.floor(left / 3600);
        const m = Math.floor((left % 3600) / 60);
        const s = left % 60;
        el.textContent = pad(h) + ':' + pad(m) + ':' + pad(s);
      }

      // Тик раз в секунду: уменьшаем left и перерисовываем
      function tick() {
        if (left <= 0) {
          el.textContent = '00:00:00';
          clearInterval(timer); // Останавливаем интервал, чтобы не тикаал дальше
          return;
        }
        left--; // уменьшаем секунду
        render(); // перерисовываем
      }

      // показать стартовое значение сразу, не ждать 1 секунду
      render(); 

      // Раз в секунду обновлять таймер
      const timer = setInterval(tick, 1000); 
    </script>
  <?php endif; ?>
</body>

</html>