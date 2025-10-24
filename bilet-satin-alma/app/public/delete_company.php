<?php
session_start();
require_once __DIR__ . '/../src/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: /");
    exit();
}

$company_id = $_GET['id'] ?? null;
if (!$company_id) {
    header("Location: /admin_panel.php");
    exit();
}

try {

    $stmt = $pdo->prepare("DELETE FROM Companies WHERE id = ?");
    $stmt->execute([$company_id]);
    header("Location: /admin_panel.php");
    exit();
} catch (PDOException $e) {
    die("Firma silinirken bir hata oluştu: " . $e->getMessage());
}