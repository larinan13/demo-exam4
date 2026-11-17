<?php
require __DIR__ . '/config/db.php';
$pageTitle = 'Каталог номеров';

// Параметры фильтра
$catFilter   = $_GET['category'] ?? '';
$priceMin    = (int)($_GET['price_min'] ?? 0);
$priceMax    = (int)($_GET['price_max'] ?? 0);
$capacityMin = (int)($_GET['capacity_min'] ?? 0);
$capacityMax = (int)($_GET['capacity_max'] ?? 0);
$applyFilter = isset($_GET['apply']);

// SQL + условия
$sql = "SELECT r.*, c.name AS category_name,
        (SELECT GROUP_CONCAT(a.name SEPARATOR ', ')
           FROM room_amenities ra
           JOIN amenities a ON a.id = ra.amenity_id
           WHERE ra.room_id = r.id) AS amenities_list
        FROM rooms r
        JOIN categories c ON c.id = r.category_id";
$where = [];
$params = [];

if ($applyFilter) {
    if ($catFilter !== '') {
        $where[] = "r.category_id = ?";
        $params[] = (int)$catFilter;
    }
    if ($priceMin > 0) {
        $where[] = "r.price >= ?";
        $params[] = $priceMin;
    }
    if ($priceMax > 0) {
        $where[] = "r.price <= ?";
        $params[] = $priceMax;
    }
    if ($capacityMin > 0) {
        $where[] = "r.capacity >= ?";
        $params[] = $capacityMin;
    }
    if ($capacityMax > 0) {
        $where[] = "r.capacity <= ?";
        $params[] = $capacityMax;
    }
} else {
    // Без фильтра — 6 случайных
    $sql .= " ORDER BY RAND() LIMIT 6";
}

if ($where) {
    $sql .= " WHERE " . implode(' AND ', $where);
    $sql .= " ORDER BY r.id";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rooms = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM categories ORDER BY id")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<h2 class="my-4">Каталог номеров</h2>

<form method="get" class="row g-2 mb-4">
    <div class="col-md-3">
        <label class="form-label">Категория</label>
        <select name="category" class="form-select">
            <option value="">— любая —</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $catFilter==$c['id']?'selected':'' ?>>
                    <?= htmlspecialchars($c['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label">Цена от</label>
        <input type="number" name="price_min" class="form-control" value="<?= $priceMin?:'' ?>" min="0">
    </div>
    <div class="col-md-2">
        <label class="form-label">Цена до</label>
        <input type="number" name="price_max" class="form-control" value="<?= $priceMax?:'' ?>" min="0">
    </div>
    <div class="col-md-2">
        <label class="form-label">Мест от</label>
        <input type="number" name="capacity_min" class="form-control" value="<?= $capacityMin?:'' ?>" min="0">
    </div>
    <div class="col-md-2">
        <label class="form-label">Мест до</label>
        <input type="number" name="capacity_max" class="form-control" value="<?= $capacityMax?:'' ?>" min="0">
    </div>
    <div class="col-md-1 d-flex align-items-end gap-1">
        <button type="submit" name="apply" class="btn btn-success">Применить</button>
    </div>
    <div class="col-12">
        <a href="/index.php" class="btn btn-secondary">Сбросить фильтр</a>
    </div>
</form>

<?php if (!$rooms): ?>
    <div class="alert alert-warning">По вашему запросу номеров не найдено.</div>
<?php else: ?>
    <div class="row">
        <?php foreach ($rooms as $r): ?>
        <div class="col-md-4">
            <div class="card h-100">
                <img src="/img/<?= htmlspecialchars($r['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($r['category_name']) ?>">
                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($r['category_name']) ?></h5>
                    <p class="mb-1"><strong><?= number_format($r['price'], 0, '.', ' ') ?> ₽ / сутки</strong></p>
                    <p class="mb-1">Площадь: <?= $r['area'] ?> м²</p>
                    <p class="mb-1">Количество мест: <?= $r['capacity'] ?></p>
                    <p class="mb-2 small text-muted"><?= htmlspecialchars($r['amenities_list']) ?></p>
                    <a href="/order.php?room_id=<?= $r['id'] ?>" class="btn btn-success">Забронировать</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
