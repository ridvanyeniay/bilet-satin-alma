<?php
session_start();
require_once __DIR__ . '/../src/db.php';

// giriş olmamışsa, kupon silinmez
if (!isset($_SESSION['user_id'])) {
    header("Location: /");
    exit();
}

$coupon_id = $_GET['id'] ?? null;
if (!$coupon_id) {
    // role göre yere yönlendirme
    $redirect_url = ($_SESSION['role'] === 'Admin') ? '/admin_panel.php' : '/firma_admin_panel.php';
    header("Location: $redirect_url");
    exit();
}

try {
    if ($_SESSION['role'] === 'Admin') {
        $stmt = $pdo->prepare("DELETE FROM Coupons WHERE id = ? AND company_id IS NULL");
        $stmt->execute([$coupon_id]);
        $redirect_url = '/admin_panel.php';

    } elseif ($_SESSION['role'] === 'FirmaAdmin' && isset($_SESSION['company_id'])) {
        $stmt = $pdo->prepare("DELETE FROM Coupons WHERE id = ? AND company_id = ?");
        $stmt->execute([$coupon_id, $_SESSION['company_id']]);
        $redirect_url = '/firma_admin_panel.php';
        
    } else {
        $redirect_url = '/';
    }

    header("Location: $redirect_url");
    exit();

} catch (PDOException $e) {
    die("Kupon silinirken bir hata oluştu: " . $e->getMessage());
}