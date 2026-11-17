<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth.php';

// Смена статуса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    $id = (int)$_POST['id'];
    $action = $_POST['action'];
    if (in_array($action, ['approved', 'rejected'])) {
        $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->execute([$action, $id]);
    }
    header('Location: /admin/bookings.php');
    exit;
}

// Удаление заявки
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
    $stmt->execute([(int)$_GET['delete']]);
    header('Location: /admin/bookings.php');
    exit;
}

$bookings = $pdo->query("SELECT b.*, r.price, c.name AS category_name
                         FROM bookings b
                         JOIN rooms r ON r.id = b.room_id
                         JOIN categories c ON c.id = r.category_id
                         ORDER BY b.created_at DESC")->fetchAll();

$statusLabels = [
    'new'      => ['Новая',    'warning'],
    'approved' => ['Одобрена', 'success'],
    'rejected' => ['Отклонена','danger'],
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Заявки — Админ</title>
    <link rel="stylesheet" href="/css/bootstrap.min.css">
    <link rel="stylesheet" href="/css/index.css">
</head>
<body>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Панель администратора — Заявки</h2>
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

    <?php if (!$bookings): ?>
        <div class="alert alert-info">Заявок пока нет.</div>
    <?php else: ?>
        <table class="table table-bordered bg-white">
            <thead>
                <tr>
                    <th>#</th><th>Клиент</th><th>Телефон</th><th>Email</th>
                    <th>Номер</th><th>Заезд</th><th>Выезд</th><th>Статус</th><th>Действия</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($bookings as $b): ?>
                <tr>
                    <td><?= $b['id'] ?></td>
                    <td><?= htmlspecialchars($b['last_name'].' '.$b['first_name']) ?></td>
                    <td><?= htmlspecialchars($b['phone']) ?></td>
                    <td><?= htmlspecialchars($b['email']) ?></td>
                    <td><?= htmlspecialchars($b['category_name']) ?>, <?= $b['price'] ?> ₽</td>
                    <td><?= date('d.m.Y', strtotime($b['check_in'])) ?></td>
                    <td><?= date('d.m.Y', strtotime($b['check_out'])) ?></td>
                    <td>
                        <span class="badge bg-<?= $statusLabels[$b['status']][1] ?>">
                            <?= $statusLabels[$b['status']][0] ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($b['status'] === 'new'): ?>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                <button name="action" value="approved" class="btn btn-sm btn-success">Одобрить</button>
                                <button name="action" value="rejected" class="btn btn-sm btn-danger">Отклонить</button>
                            </form>
                        <?php endif; ?>
                        <a href="?delete=<?= $b['id'] ?>" class="btn btn-sm btn-outline-danger"
                           onclick="return confirm('Удалить заявку?')">Удалить</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
