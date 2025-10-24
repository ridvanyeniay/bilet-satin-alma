<?php
session_start();
require_once __DIR__ . '/../src/db.php';

// admin yetkisi
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: /");
    exit();
}

$user_id = $_GET['id'] ?? null;
if (!$user_id) {
    header("Location: /admin_panel.php");
    exit();
}

try {
    // kendini silmemesi için ekledim
    if ($user_id === $_SESSION['user_id']) {
        header("Location: /admin_panel.php");
        exit();
    }
    
    $stmt = $pdo->prepare("DELETE FROM Users WHERE id = ?");
    $stmt->execute([$user_id]);

    header("Location: /admin_panel.php");
    exit();

} catch (PDOException $e) {
    die("Kullanıcı silinirken bir hata oluştu: " . $e->getMessage());
}