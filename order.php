<?php
require __DIR__ . '/config/db.php';
$pageTitle = 'Бронирование номера';

$roomId = (int)($_GET['room_id'] ?? $_POST['room_id'] ?? 0);

// Получаем номер из БД
$stmt = $pdo->prepare("SELECT r.*, c.name AS category_name,
    (SELECT GROUP_CONCAT(a.name SEPARATOR ', ')
       FROM room_amenities ra
       JOIN amenities a ON a.id = ra.amenity_id
       WHERE ra.room_id = r.id) AS amenities_list
    FROM rooms r
    JOIN categories c ON c.id = r.category_id
    WHERE r.id = ?");
$stmt->execute([$roomId]);
$room = $stmt->fetch();

$errors = [];
$success = false;
$old = $_POST;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $checkIn   = trim($_POST['check_in'] ?? '');
    $checkOut  = trim($_POST['check_out'] ?? '');

    // Валидация имени
    if ($firstName === '') {
        $errors['first_name'] = 'Введите имя';
    } elseif (!preg_match('/^[А-Яа-яЁё\s.\-]+$/u', $firstName)) {
        $errors['first_name'] = 'Имя может содержать только кириллицу, пробелы, точки и тире';
    }

    // Валидация фамилии
    if ($lastName === '') {
        $errors['last_name'] = 'Введите фамилию';
    } elseif (!preg_match('/^[А-Яа-яЁё\s.\-]+$/u', $lastName)) {
        $errors['last_name'] = 'Фамилия может содержать только кириллицу, пробелы, точки и тире';
    }

    // Валидация телефона
    if ($phone === '') {
        $errors['phone'] = 'Введите телефон';
    } elseif (!preg_match('/^\+7\(\d{3}\)\d{3}-\d{2}-\d{2}$/', $phone)) {
        $errors['phone'] = 'Телефон должен быть в формате +7(XXX)XXX-XX-XX';
    }

    // Валидация email
    if ($email === '') {
        $errors['email'] = 'Введите email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Некорректный email';
    }

    // Валидация дат
    $parseDate = function($s) {
        if (!preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $s)) return null;
        $d = DateTime::createFromFormat('d.m.Y', $s);
        return ($d && $d->format('d.m.Y') === $s) ? $d : null;
    };

    $dIn  = $parseDate($checkIn);
    $dOut = $parseDate($checkOut);

    if (!$dIn)  $errors['check_in']  = 'Дата заезда должна быть в формате ДД.ММ.ГГГГ';
    if (!$dOut) $errors['check_out'] = 'Дата выезда должна быть в формате ДД.ММ.ГГГГ';

    if ($dIn && $dOut && $dOut <= $dIn) {
        $errors['check_out'] = 'Дата выезда должна быть позже даты заезда';
    }
    if ($dIn && $dIn < new DateTime('today')) {
        $errors['check_in'] = 'Дата заезда не может быть в прошлом';
    }

    if (!$room) {
        $errors['room'] = 'Выбранный номер не найден';
    }

    // Если ошибок нет — сохраняем заявку
    if (!$errors && $room) {
        $stmt = $pdo->prepare("INSERT INTO bookings 
            (room_id, first_name, last_name, phone, email, check_in, check_out, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'new')");
        $stmt->execute([
            $roomId,
            $firstName,
            $lastName,
            $phone,
            $email,
            $dIn->format('Y-m-d'),
            $dOut->format('Y-m-d'),
        ]);
        $success = true;
        $old = [];
    }
}

include __DIR__ . '/includes/header.php';
?>

<h2 class="my-4">Бронирование номера</h2>

<?php if ($success): ?>
    <div class="alert alert-success">
        ✅ Заявка успешно отправлена! Мы свяжемся с вами в ближайшее время.
    </div>
    <a href="/index.php" class="btn btn-secondary">← Вернуться в каталог</a>
<?php else: ?>
    <?php if (!$room): ?>
        <div class="alert alert-danger">
            Номер не найден. <a href="/index.php">Вернуться в каталог</a>
        </div>
    <?php else: ?>
        <?php if ($errors): ?>
            <div class="alert alert-danger">
                <strong>Пожалуйста, исправьте ошибки:</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- ЛЕВАЯ КОЛОНКА: Картинка и информация о номере -->
            <div class="col-md-5">
                <div class="card h-100">
                    <img src="/img/<?= htmlspecialchars($room['image']) ?>" 
                         class="card-img-top" 
                         alt="<?= htmlspecialchars($room['category_name']) ?>"
                         style="max-height: 350px; object-fit: cover;">
                    <div class="card-body">
                        <h4 class="card-title"><?= htmlspecialchars($room['category_name']) ?></h4>
                        <p class="mb-2">
                            <strong style="color: #24B771; font-size: 1.25rem;">
                                <?= number_format($room['price'], 0, '.', ' ') ?> ₽ / сутки
                            </strong>
                        </p>
                        <hr>
                        <p class="mb-1"><strong>Площадь:</strong> <?= $room['area'] ?> м²</p>
                        <p class="mb-1"><strong>Количество мест:</strong> <?= $room['capacity'] ?></p>
                        <?php if (!empty($room['amenities_list'])): ?>
                            <p class="mb-1"><strong>Удобства:</strong></p>
                            <ul class="mb-0 small">
                                <?php foreach (explode(', ', $room['amenities_list']) as $am): ?>
                                    <li><?= htmlspecialchars($am) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <?php if (!empty($room['description'])): ?>
                            <hr>
                            <p class="mb-0 small text-muted"><?= htmlspecialchars($room['description']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ПРАВАЯ КОЛОНКА: Форма бронирования -->
            <div class="col-md-7">
                <form method="post" novalidate class="bg-white p-4 rounded shadow-sm">
                    <input type="hidden" name="room_id" value="<?= $room['id'] ?>">

                    <h4 class="mb-3">Данные для бронирования</h4>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Имя *</label>
                            <input type="text" name="first_name" class="form-control"
                                   value="<?= htmlspecialchars($old['first_name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Фамилия *</label>
                            <input type="text" name="last_name" class="form-control"
                                   value="<?= htmlspecialchars($old['last_name'] ?? '') ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Телефон *</label>
                            <input type="text" id="validationCustomPhone" name="phone" class="form-control"
                                   value="<?= htmlspecialchars($old['phone'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">E-mail *</label>
                            <input type="text" name="email" class="form-control"
                                   value="<?= htmlspecialchars($old['email'] ?? '') ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Дата заезда (ДД.ММ.ГГГГ) *</label>
                            <input type="text" name="check_in" class="form-control" placeholder="17.11.2026"
                                   value="<?= htmlspecialchars($old['check_in'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Дата выезда (ДД.ММ.ГГГГ) *</label>
                            <input type="text" name="check_out" class="form-control" placeholder="20.11.2026"
                                   value="<?= htmlspecialchars($old['check_out'] ?? '') ?>" required>
                        </div>

                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-success btn-lg w-100">Отправить заявку</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
