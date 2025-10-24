<?php
try {
    // Veritabanı dosyasının yolu
    $db_path = __DIR__ . '/../data/database.db';
    
    // PDO ile SQLite veritabanına bağlanma
    $pdo = new PDO('sqlite:' . $db_path);

    // Hata modunu ayarlama
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Veritabanı bağlantı hatası durumunda işlemi sonlandır
    die("Veritabanı bağlantısı kurulamadı: " . $e->getMessage());
}