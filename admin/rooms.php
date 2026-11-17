<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth.php';

$message = '';

// Удаление
if (isset($_GET['delete'])) {
    try {
        $pdo->prepare("DELETE FROM rooms WHERE id = ?")->execute([(int)$_GET['delete']]);
        $message = 'Номер удалён';
    } catch (PDOException $e) {
        $message = 'Нельзя удалить: есть связанные заявки';
    }
}

// Добавление / редактирование
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id         = (int)($_POST['id'] ?? 0);
    $categoryId = (int)$_POST['category_id'];
    $price      = (int)$_POST['price'];
    $capacity   = (int)$_POST['capacity'];
    $area       = (float)$_POST['area'];
    $image      = trim($_POST['image']);
    $desc       = trim($_POST['description']);
    $amenities  = $_POST['amenities'] ?? [];

    $err = [];
    if ($price <= 0) $err[] = 'Цена должна быть > 0';
    if ($capacity <= 0) $err[] = 'Количество мест должно быть > 0';
    if ($area <= 0) $err[] = 'Площадь должна быть > 0';
    if (!$image) $err[] = 'Укажите изображение';

    if (!$err) {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE rooms SET category_id=?, price=?, capacity=?, area=?, image=?, description=? WHERE id=?");
            $stmt->execute([$categoryId, $price, $capacity, $area, $image, $desc, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO rooms (category_id, price, capacity, area, image, description) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$categoryId, $price, $capacity, $area, $image, $desc]);
            $id = $pdo->lastInsertId();
        }
        // Обновляем удобства
        $pdo->prepare("DELETE FROM room_amenities WHERE room_id = ?")->execute([$id]);
        foreach ($amenities as $aId) {
            $pdo->prepare("INSERT INTO room_amenities (room_id, amenity_id) VALUES (?, ?)")
                ->execute([$id, (int)$aId]);
        }
        header('Location: /admin/rooms.php?ok=1');
        exit;
    }
    $message = implode(', ', $err);
}

$rooms = $pdo->query("SELECT r.*, c.name AS category_name FROM rooms r 
                      JOIN categories c ON c.id=r.category_id ORDER BY r.id")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories ORDER BY id")->fetchAll();
$amenities  = $pdo->query("SELECT * FROM amenities ORDER BY id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Номера — Админ</title>
    <link rel="stylesheet" href="/css/bootstrap.min.css">
    <link rel="stylesheet" href="/css/index.css">
</head>
<body>
<div class="container mt-4">
    <div class="d-flex justify-content-between mb-4">
        <h2>Управление номерами</h2>
        <div class="d-flex gap-2">
    <a href="/index.php" class="btn btn-outline-success" target="_blank">
        🌐 На сайт
    </a>
    <?php if (basename($_SERVER['PHP_SELF']) !== 'bookings.php'): ?>
        <a href="/admin/bookings.php" class="btn btn-secondary">Заявки</a>
    <?php endif; ?>
    <?php if (basename($_SERVER['PHP_SELF']) !== 'rooms.php'): ?>
        <a href="/admin/rooms.php" class="btn btn-secondary">Номера</a>
    <?php endif; ?>
    <a href="/admin/logout.php" class="btn btn-outline-danger">Выйти</a>
</div>
    </div>

    <?php if ($message): ?><div class="alert alert-info"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if (isset($_GET['ok'])): ?><div class="alert alert-success">Сохранено</div><?php endif; ?>

    <div class="row">
        <div class="col-md-5">
            <h4>Добавить / Редактировать</h4>
            <form method="post" class="bg-white p-3 rounded">
                <input type="hidden" name="id" id="room_id" value="">

                <label class="form-label">Категория</label>
                <select name="category_id" class="form-select mb-2" required>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <label class="form-label">Цена, ₽</label>
                <input type="number" name="price" class="form-control mb-2" min="1" required>

                <label class="form-label">Количество мест</label>
                <input type="number" name="capacity" class="form-control mb-2" min="1" required>

                <label class="form-label">Площадь, м²</label>
                <input type="number" step="0.01" name="area" class="form-control mb-2" min="0.01" required>

                <label class="form-label">Изображение (имя файла)</label>
                <input type="text" name="image" class="form-control mb-2" required>

                <label class="form-label">Описание</label>
                <textarea name="description" class="form-control mb-2" rows="2"></textarea>

                <label class="form-label">Удобства</label>
                <?php foreach ($amenities as $a): ?>
                    <div class="form-check">
                        <input type="checkbox" name="amenities[]" value="<?= $a['id'] ?>"
                               class="form-check-input" id="am_<?= $a['id'] ?>">
                        <label class="form-check-label" for="am_<?= $a['id'] ?>"><?= htmlspecialchars($a['name']) ?></label>
                    </div>
                <?php endforeach; ?>

                <button class="btn btn-success mt-3 w-100">Сохранить</button>
                <button type="button" class="btn btn-secondary mt-2 w-100" onclick="resetForm()">Очистить</button>
            </form>
        </div>

        <div class="col-md-7">
            <h4>Существующие номера</h4>
            <table class="table table-bordered bg-white">
                <thead>
                    <tr><th>#</th><th>Категория</th><th>Цена</th><th>Мест</th><th>Площадь</th><th>Действия</th></tr>
                </thead>
                <tbody>
                <?php foreach ($rooms as $r): ?>
                    <tr>
                        <td><?= $r['id'] ?></td>
                        <td><?= htmlspecialchars($r['category_name']) ?></td>
                        <td><?= $r['price'] ?> ₽</td>
                        <td><?= $r['capacity'] ?></td>
                        <td><?= $r['area'] ?> м²</td>
                        <td>
                            <button class="btn btn-sm btn-primary"
                                onclick='editRoom(<?= json_encode($r, JSON_UNESCAPED_UNICODE) ?>)'>Ред.</button>
                            <a href="?delete=<?= $r['id'] ?>" class="btn btn-sm btn-danger"
                               onclick="return confirm('Удалить номер?')">Удалить</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
function editRoom(r) {
    document.getElementById('room_id').value = r.id;
    const f = document.querySelector('form');
    f.category_id.value = r.category_id;
    f.price.value = r.price;
    f.capacity.value = r.capacity;
    f.area.value = r.area;
    f.image.value = r.image;
    f.description.value = r.description || '';
    window.scrollTo(0,0);
}
function resetForm() {
    document.querySelector('form').reset();
    document.getElementById('room_id').value = '';
}
</script>
</body>
</html>
