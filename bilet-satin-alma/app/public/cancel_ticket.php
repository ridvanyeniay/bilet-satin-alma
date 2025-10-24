<?php
session_start();
require_once __DIR__ . '/../src/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}
$booking_id_from_url = $_GET['booking_id'] ?? null;
if (!$booking_id_from_url) {
    header("Location: /my_tickets.php");
    exit();
}

try {
    // iptal bilet bilgisi çek
    $stmt = $pdo->prepare("
        SELECT b.user_id, b.price_paid, t.departure_time 
        FROM Bookings b
        JOIN Trips t ON b.trip_id = t.id
        WHERE b.id = ?
    ");
    $stmt->execute([$booking_id_from_url]);
    $booking = $stmt->fetch();

    // --- KONTROLLER ---
    // 1. Bilet bulunamadıysa VEYA bilet bu kullanıcıya ait değilse
    if (!$booking || $booking['user_id'] !== $_SESSION['user_id']) {
        $_SESSION['cancel_error'] = "Geçersiz işlem veya bu bileti iptal etme yetkiniz yok.";
        header("Location: /my_tickets.php");
        exit();
    }

    // 1 saat kontrolü
    $departure_datetime = new DateTime($booking['departure_time']);
    $now = new DateTime();
    $interval = $now->diff($departure_datetime);
    $hours_until_departure = ($interval->days * 24) + $interval->h;
    
    if ($departure_datetime <= $now || $hours_until_departure < 1) {
        $_SESSION['cancel_error'] = "Kalkışa 1 saatten az kaldığı için bu bilet iptal edilemez.";
        header("Location: /my_tickets.php");
        exit();
    }

    // iptall
    $pdo->beginTransaction(); 

    // para iadesi
    $stmt_refund = $pdo->prepare("UPDATE Users SET balance = balance + ? WHERE id = ?");
    $stmt_refund->execute([$booking['price_paid'], $_SESSION['user_id']]);

    $stmt_delete = $pdo->prepare("DELETE FROM Bookings WHERE id = ?");
    $stmt_delete->execute([$booking_id_from_url]);

    $pdo->commit();

    $_SESSION['cancel_success'] = "Biletiniz başarıyla iptal edildi. " . number_format($booking['price_paid'], 2) . " TL hesabınıza iade edildi.";
    header("Location: /my_tickets.php");
    exit();

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['cancel_error'] = "İptal işlemi sırasında bir veritabanı hatası oluştu.";
    header("Location: /my_tickets.php");
    exit();
}