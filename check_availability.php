<?php
require __DIR__ . '/config/db.php';

header('Content-Type: application/json');

$roomId = (int)($_GET['room_id'] ?? 0);
$checkIn = trim($_GET['check_in'] ?? '');
$checkOut = trim($_GET['check_out'] ?? '');

if (!$roomId || !$checkIn || !$checkOut) {
    echo json_encode(['available' => true]);
    exit;
}

$parseDate = function($s) {
    if (!preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $s)) return null;
    $d = DateTime::createFromFormat('d.m.Y', $s);
    return ($d && $d->format('d.m.Y') === $s) ? $d : null;
};

$dIn = $parseDate($checkIn);
$dOut = $parseDate($checkOut);

if (!$dIn || !$dOut || $dOut <= $dIn) {
    echo json_encode(['available' => true]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id FROM bookings 
    WHERE room_id = ? 
      AND status = 'approved'
      AND check_in < ? 
      AND check_out > ?
");
$stmt->execute([
    $roomId,
    $dOut->format('Y-m-d'),
    $dIn->format('Y-m-d')
]);

$booked = $stmt->fetch();

echo json_encode(['available' => !$booked]);
