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

    // Валидация дат (формат YYYY-MM-DD от input type="date")
$parseDate = function($s) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) return null;
    $d = DateTime::createFromFormat('Y-m-d', $s);
    return ($d && $d->format('Y-m-d') === $s) ? $d : null;
};

$dIn  = $parseDate($checkIn);
$dOut = $parseDate($checkOut);

if (!$dIn)  $errors['check_in']  = 'Выберите корректную дату заезда';
if (!$dOut) $errors['check_out'] = 'Выберите корректную дату выезда';

if ($dIn && $dOut && $dOut <= $dIn) {
    $errors['check_out'] = 'Дата выезда должна быть позже даты заезда';
}
if ($dIn && $dIn < new DateTime('today')) {
    $errors['check_in'] = 'Дата заезда не может быть в прошлом';
}

// Пересечение с одобренными бронями
if ($dIn && $dOut && !$errors) {
    $stmt = $pdo->prepare("
        SELECT id, check_in, check_out 
        FROM bookings 
        WHERE room_id = ? 
          AND status = 'approved'
          AND (
            (check_in < ? AND check_out > ?) OR
            (check_in < ? AND check_out > ?) OR
            (check_in >= ? AND check_out <= ?)
          )
    ");
    $stmt->execute([
        $roomId,
        $dIn->format('Y-m-d'), $dIn->format('Y-m-d'),  // новый заезд внутри существующего
        $dOut->format('Y-m-d'), $dOut->format('Y-m-d'), // новый выезд внутри существующего
        $dIn->format('Y-m-d'), $dOut->format('Y-m-d')   // новый период полностью внутри
    ]);
    
    if ($stmt->fetch()) {
        $errors['dates'] = 'Выбранный период уже забронирован. Пожалуйста, выберите другие даты.';
    }
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
    <label class="form-label">Дата заезда *</label>
    <input type="date" name="check_in" class="form-control"
           value="<?= htmlspecialchars($old['check_in'] ?? '') ?>"
           min="<?= date('Y-m-d') ?>"
           id="check_in"
           required>
    <?php if (isset($errors['check_in'])): ?>
        <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['check_in']) ?></div>
    <?php endif; ?>
</div>
<div class="col-md-6">
    <label class="form-label">Дата выезда *</label>
    <input type="date" name="check_out" class="form-control"
           value="<?= htmlspecialchars($old['check_out'] ?? '') ?>"
           id="check_out"
           required>
    <?php if (isset($errors['check_out'])): ?>
        <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['check_out']) ?></div>
    <?php endif; ?>
</div>

                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-success btn-lg w-100">Отправить заявку</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
	<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkIn = document.getElementById('check_in');
    const checkOut = document.getElementById('check_out');
    
    // При изменении даты заезда — обновляем минимальную дату выезда
    checkIn.addEventListener('change', function() {
        if (this.value) {
            const nextDay = new Date(this.value);
            nextDay.setDate(nextDay.getDate() + 1);
            const yyyy = nextDay.getFullYear();
            const mm = String(nextDay.getMonth() + 1).padStart(2, '0');
            const dd = String(nextDay.getDate()).padStart(2, '0');
            checkOut.min = `${yyyy}-${mm}-${dd}`;
            
            // Если дата выезда раньше новой минимальной — сбрасываем
            if (checkOut.value && checkOut.value <= this.value) {
                checkOut.value = '';
            }
        }
    });
});
// Проверка доступности дат
function checkAvailability() {
    const roomId = document.querySelector('input[name="room_id"]').value;
    const checkIn = document.querySelector('input[name="check_in"]').value;
    const checkOut = document.querySelector('input[name="check_out"]').value;
    
    if (checkIn && checkOut) {
        fetch(`/check_availability.php?room_id=${roomId}&check_in=${encodeURIComponent(checkIn)}&check_out=${encodeURIComponent(checkOut)}`)
            .then(r => r.json())
            .then(data => {
                const alert = document.getElementById('date-availability-alert');
                if (!data.available) {
                    if (!alert) {
                        const div = document.createElement('div');
                        div.id = 'date-availability-alert';
                        div.className = 'alert alert-warning mt-2';
                        div.textContent = '⚠️ Этот период уже забронирован. Выберите другие даты.';
                        document.querySelector('button[type="submit"]').before(div);
                    }
                } else if (alert) {
                    alert.remove();
                }
            });
    }
}

document.querySelector('input[name="check_in"]').addEventListener('change', checkAvailability);
document.querySelector('input[name="check_out"]').addEventListener('change', checkAvailability);
</script>
    <?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
