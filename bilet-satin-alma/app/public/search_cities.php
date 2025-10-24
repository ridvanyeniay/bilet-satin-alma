<?php
require_once __DIR__ . '/../src/db.php';

header('Content-Type: application/json; charset=utf-8');

$term = $_GET['term'] ?? '';

// **düzenle
$normalized_term = str_replace(
    ['ı', 'İ', 'I', 'ğ', 'Ğ', 'ü', 'Ü', 'ş', 'Ş', 'ö', 'Ö', 'ç', 'Ç'],
    ['i', 'i', 'i', 'g', 'g', 'u', 'u', 's', 's', 'o', 'o', 'c', 'c'],
    mb_strtolower(trim($term), 'UTF-8')
);

if (empty($normalized_term)) {
    echo json_encode([]);
    exit();
}

//i ve ı harfi sorunu için
try {
    $sql = "SELECT name FROM Cities WHERE name_normalized LIKE ?";
    
    $stmt = $pdo->prepare($sql);
    $like_term = $normalized_term . '%';
    $stmt->execute([$like_term]);
    
    $results = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

    echo json_encode($results);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Veritabanı hatası.']);
}