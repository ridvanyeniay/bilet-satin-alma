<?php
// Oturumu her sayfa başında başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bilet Satın Alma Platformu</title>
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
    <header class="navbar">
        <div class="container">
            <a href="/" class="navbar-brand">BiletGO</a>
            <nav class="navbar-nav">
                <a href="/">Ana Sayfa</a>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="/my_tickets.php">Biletlerim</a>

                    <?php // === YENİ EKLENEN ROL KONTROLÜ === ?>
                    <?php if ($_SESSION['role'] === 'Admin'): ?>
                        <a href="/admin_panel.php">Admin Paneli</a>
                    <?php elseif ($_SESSION['role'] === 'FirmaAdmin'): ?>
                        <a href="/firma_admin_panel.php">Firma Paneli</a> 
                        <?php // Henüz bu sayfayı oluşturmadık ama linki ekleyebiliriz ?>
                    <?php endif; ?>
                    <?php // === YENİ KONTROL BİTTİ === ?>

                    <a href="/logout.php" class="btn-nav btn-nav-secondary">
                        Çıkış Yap (<?= htmlspecialchars($_SESSION['fullname'] ?? 'Kullanıcı') ?>)
                    </a>
                <?php else: ?>
                    <a href="/login.php" class="btn-nav btn-nav-secondary">Giriş Yap</a>
                    <a href="/register.php" class="btn-nav btn-nav-primary">Kayıt Ol</a>
                <?php endif; ?>

            </nav>
        </div>
    </header>

    <main class="container page-content">