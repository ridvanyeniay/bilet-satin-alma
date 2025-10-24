<?php
include __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../src/db.php'; 

// ı harfini i yapar ama biraz sorunlu çalışıyor
function turkce_karakter_duzenle($metin) {
    if (empty($metin)) { return ''; }
    $kucuk_metin = mb_strtolower($metin, 'UTF-8');
    return str_replace('ı', 'i', $kucuk_metin);
}

$kalkis_sehri = $_GET['departure_city'] ?? '';
$varis_sehri = $_GET['destination_city'] ?? '';

try {
    // i harf sorunu--düzenle
    $sorgu_metni = "SELECT t.*, c.name AS company_name
            FROM Trips t
            JOIN Companies c ON t.company_id = c.id
            WHERE t.departure_time >= datetime('now')";

    $sartlar = [];
    $parametreler = [];

    if (!empty($kalkis_sehri)) {
        $sartlar[] = "LOWER(t.departure_city) LIKE LOWER(?)";
        $parametreler[] = '%' . $kalkis_sehri . '%';
    }

    if (!empty($varis_sehri)) {
        $sartlar[] = "LOWER(t.destination_city) LIKE LOWER(?)";
        $parametreler[] = '%' . $varis_sehri . '%';
    }

    if (!empty($sartlar)) {
        $sorgu_metni .= " AND " . implode(' AND ', $sartlar);
    }

    $sorgu_metni .= " ORDER BY t.departure_time ASC";

    $hazirlanan_sorgu = $pdo->prepare($sorgu_metni);
    $hazirlanan_sorgu->execute($parametreler);
    $ne_olur_ol_tum_seferler = $hazirlanan_sorgu->fetchAll();
    $gosterilecek_seferler = [];

    $aranan_kalkis_norm = turkce_karakter_duzenle($kalkis_sehri);
    $aranan_varis_norm = turkce_karakter_duzenle($varis_sehri);

    if (empty($aranan_kalkis_norm) && empty($aranan_varis_norm)) {
        $gosterilecek_seferler = $ne_olur_ol_tum_seferler;
    } else {
        foreach ($ne_olur_ol_tum_seferler as $tek_sefer) {
            $vt_kalkis_norm = turkce_karakter_duzenle($tek_sefer['departure_city']);
            $vt_varis_norm = turkce_karakter_duzenle($tek_sefer['destination_city']);

            $kalkis_eslesme = empty($aranan_kalkis_norm) || str_contains($vt_kalkis_norm, $aranan_kalkis_norm);
            $varis_eslesme = empty($aranan_varis_norm) || str_contains($vt_varis_norm, $aranan_varis_norm);

            if ($kalkis_eslesme && $varis_eslesme) {
                $gosterilecek_seferler[] = $tek_sefer;
            }
        }
    }

} catch (PDOException $e) {
    echo '<p style="color: red;">Veritabanindan seferler getirilemedi: ' . $e->getMessage() . '</p>';
    $gosterilecek_seferler = [];
}
?>

<section class="search-hero">
    <h1>Hayalindeki Yolculugu Kesfet</h1>
    <p>Turkiye'nin dort bir yanina en uygun fiyatlarla seyahat edin.</p>
    <form action="/" method="GET" class="search-form">
        <div class="form-group autocomplete-container">
            <label for="departure">Nereden</label>
            <input type="text" id="departure" name="departure_city" placeholder="Orn: Istanbul" value="<?= htmlspecialchars($kalkis_sehri) ?>" autocomplete="off">
            <div class="suggestions-list" id="departure-suggestions"></div>
        </div>
        <div class="form-group autocomplete-container">
            <label for="destination">Nereye</label>
            <input type="text" id="destination" name="destination_city" placeholder="Orn: Ankara" value="<?= htmlspecialchars($varis_sehri) ?>" autocomplete="off">
            <div class="suggestions-list" id="destination-suggestions"></div>
        </div>
        <button type="submit" class="btn-search">Sefer Bul</button>
    </form>
</section>

<h2 class="section-title">Arama Sonuclari</h2>

<div class="trips-grid">
    <?php if (!empty($gosterilecek_seferler)): ?>
        <?php foreach ($gosterilecek_seferler as $tek_sefer): ?>
            <div class="trip-card">
                <div class="trip-card-header">
                    <h3><?= htmlspecialchars($tek_sefer['company_name']) ?></h3>
                </div>
                <div class="trip-card-body">
                    <div class="trip-route">
                        <span><?= htmlspecialchars($tek_sefer['departure_city']) ?></span>
                        <span class="route-arrow">&rarr;</span>
                        <span><?= htmlspecialchars($tek_sefer['destination_city']) ?></span>
                    </div>
                    <div class="trip-time">
                        <p><strong>Kalkis:</strong> <?= date('d/m/Y H:i', strtotime($tek_sefer['departure_time'])) ?></p>
                        <p><strong>Tahmini Varis:</strong> <?= date('d/m/Y H:i', strtotime($tek_sefer['arrival_time'])) ?></p>
                    </div>
                </div>
                <div class="trip-footer">
                    <span class="trip-price"><?= htmlspecialchars(number_format($tek_sefer['price'], 2)) ?> TL</span>

                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'User'): ?>
                        <a href="buy_ticket.php?trip_id=<?= htmlspecialchars($tek_sefer['id']) ?>" class="btn-primary">Bilet Al</a>
                    <?php elseif (!isset($_SESSION['user_id'])): ?>
                        <a href="login.php" class="btn-primary">Bilet Al</a>
                    <?php endif; ?>

                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Aradiginiz kriterlere uygun sefer bulunamadi.</p>
    <?php endif; ?>
</div>

<?php
include __DIR__ . '/../includes/footer.php';
?>