<?php
session_start();
require_once __DIR__ . '/../src/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'FirmaAdmin') {
    header("Location: /");
    exit();
}
$company_id = $_SESSION['company_id'] ?? null;
if (!$company_id) {
    header("Location: /logout.php");
    exit();
}

$trip_error = ''; $trip_success = '';
$coupon_error = ''; $coupon_success = '';

//yeni sefer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_trip'])) {
    $departure_city = trim($_POST['departure_city']);
    $destination_city = trim($_POST['destination_city']);
    $departure_time = $_POST['departure_time'];
    $arrival_time = $_POST['arrival_time'];
    $seat_count = filter_input(INPUT_POST, 'seat_count', FILTER_VALIDATE_INT);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);

    if (empty($departure_city) || empty($destination_city) || empty($departure_time) || empty($arrival_time) || !$seat_count || !$price || $seat_count <= 0 || $price <= 0) {
        $trip_error = "Lütfen tüm sefer alanlarını doğru ve geçerli bir şekilde doldurun.";
    } else {
        try {
            $trip_id = uniqid('trip_');
            $stmt = $pdo->prepare("INSERT INTO Trips (id, company_id, departure_city, destination_city, departure_time, arrival_time, seat_count, price) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$trip_id, $company_id, $departure_city, $destination_city, $departure_time, $arrival_time, $seat_count, $price]);
            $trip_success = "Yeni sefer başarıyla eklendi.";
        } catch (PDOException $e) {
            $trip_error = "Sefer eklenirken bir veritabanı hatası oluştu: " . $e->getMessage();
        }
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_company_coupon'])) {
    $code = strtoupper(trim($_POST['code']));
    $discount_rate = filter_input(INPUT_POST, 'discount_rate', FILTER_VALIDATE_INT);
    $usage_limit = filter_input(INPUT_POST, 'usage_limit', FILTER_VALIDATE_INT);
    $valid_until = $_POST['valid_until'];

    if (empty($code) || $discount_rate === false || $usage_limit === false || empty($valid_until)) {
        $coupon_error = "Tüm kupon alanları zorunludur ve doğru formatta olmalıdır.";
    } elseif ($discount_rate < 1 || $discount_rate > 99) {
        $coupon_error = "İndirim oranı 1 ile 99 arasında olmalıdır.";
    } else {
        try {
            $coupon_id = uniqid('coupon_');
            $db_discount_rate = $discount_rate / 100.0;
            $stmt = $pdo->prepare("INSERT INTO Coupons (id, code, discount_rate, usage_limit, valid_until, company_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$coupon_id, $code, $db_discount_rate, $usage_limit, $valid_until, $company_id]);
            $coupon_success = "'{$code}' kodlu kupon başarıyla oluşturuldu.";
        } catch (PDOException $e) {
            if ($e->getCode() == 19) { $coupon_error = "Hata: '{$code}' kodlu bir kupon zaten mevcut."; }
            else { $coupon_error = "Veritabanı hatası: " . $e->getMessage(); }
        }
    }
}

// verileri çekmek için
try {
    $stmt_company = $pdo->prepare("SELECT name FROM Companies WHERE id = ?");
    $stmt_company->execute([$company_id]);
    $company_name = $stmt_company->fetchColumn();

    $stmt_trips = $pdo->prepare("SELECT * FROM Trips WHERE company_id = ? ORDER BY departure_time DESC");
    $stmt_trips->execute([$company_id]);
    $trips = $stmt_trips->fetchAll();

    $stmt_coupons = $pdo->prepare("SELECT * FROM Coupons WHERE company_id = ? ORDER BY valid_until DESC");
    $stmt_coupons->execute([$company_id]);
    $coupons = $stmt_coupons->fetchAll();
} catch (PDOException $e) {
    die("Veriler getirilirken bir hata oluştu: " . $e->getMessage());
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-container">
    <h1>Firma Admin Paneli: <?= htmlspecialchars($company_name) ?></h1>
    <p>Firmanıza ait seferleri ve kuponları buradan yönetebilirsiniz.</p>

    <div class="admin-section">
        <h2>Yeni Sefer Ekle</h2>
        <form action="firma_admin_panel.php" method="POST" class="admin-form trip-form">
            <?php if ($trip_error): ?><p class="error-box"><?= $trip_error ?></p><?php endif; ?>
            <?php if ($trip_success): ?><p class="success-box"><?= $trip_success ?></p><?php endif; ?>
            <div class="form-row">
                <div class="form-group autocomplete-container">
                    <label for="departure_city">Kalkış Şehri:</label>
                    <input type="text" id="departure_city" name="departure_city" autocomplete="off" required>
                    <div class="suggestions-list" id="fap_departure_suggestions"></div>
                </div>
                <div class="form-group autocomplete-container">
                    <label for="destination_city">Varış Şehri:</label>
                    <input type="text" id="destination_city" name="destination_city" autocomplete="off" required>
                    <div class="suggestions-list" id="fap_destination_suggestions"></div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group"><label for="departure_time">Kalkış Zamanı:</label><input type="datetime-local" id="departure_time" name="departure_time" required></div>
                <div class="form-group"><label for="arrival_time">Tahmini Varış Zamanı:</label><input type="datetime-local" id="arrival_time" name="arrival_time" required></div>
            </div>
             <div class="form-row">
                <div class="form-group"><label for="seat_count">Koltuk Sayısı:</label><input type="number" id="seat_count" name="seat_count" min="1" required></div>
                <div class="form-group"><label for="price">Fiyat (TL):</label><input type="number" id="price" name="price" min="0.01" step="0.01" required></div>
            </div>
            <button type="submit" name="add_trip" class="btn-submit">Seferi Ekle</button>
        </form>
    </div>

    <div class="admin-section">
        <h2>Mevcut Seferler</h2>
        <?php if (empty($trips)): ?>
            <p>Firmanıza ait kayıtlı sefer bulunmamaktadır.</p>
        <?php else: ?>
            <table class="admin-table">
                <thead><tr><th>Kalkış Şehri</th><th>Varış Şehri</th><th>Kalkış Zamanı</th><th>Koltuk</th><th>Fiyat</th><th>İşlemler</th></tr></thead>
                <tbody>
                    <?php foreach ($trips as $trip): ?>
                        <tr>
                            <td><?= htmlspecialchars($trip['departure_city']) ?></td>
                            <td><?= htmlspecialchars($trip['destination_city']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($trip['departure_time'])) ?></td>
                            <td><?= htmlspecialchars($trip['seat_count']) ?></td>
                            <td><?= htmlspecialchars(number_format($trip['price'], 2)) ?> TL</td>
                            <td>
                                <a href="edit_trip.php?id=<?= $trip['id'] ?>" class="btn-secondary">Düzenle</a>
                                <a href="delete_trip.php?id=<?= $trip['id'] ?>" class="btn-danger" onclick="return confirm('Bu seferi silmek istediğinizden emin misiniz?')">Sil</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="admin-section">
        <h2>Firma Kupon Yönetimi</h2>
        <form action="firma_admin_panel.php" method="POST" class="admin-form">
            <h3>Yeni Firma Kuponu Ekle</h3>
            <?php if ($coupon_error): ?><p class="error-box"><?= $coupon_error ?></p><?php endif; ?>
            <?php if ($coupon_success): ?><p class="success-box"><?= $coupon_success ?></p><?php endif; ?>
            <div class="form-row">
                <div class="form-group"><label for="coupon_code">Kupon Kodu:</label><input type="text" id="coupon_code" name="code" required></div>
                <div class="form-group"><label for="coupon_discount_rate">İndirim Oranı (%):</label><input type="number" id="coupon_discount_rate" name="discount_rate" min="1" max="99" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label for="coupon_usage_limit">Kullanım Limiti:</label><input type="number" id="coupon_usage_limit" name="usage_limit" min="1" required></div>
                <div class="form-group"><label for="coupon_valid_until">Son Kullanma Tarihi:</label><input type="date" id="coupon_valid_until" name="valid_until" required></div>
            </div>
            <button type="submit" name="add_company_coupon" class="btn-submit">Firma Kuponu Ekle</button>
        </form>
        <h3 style="margin-top: 40px;">Firmanıza Ait Mevcut Kuponlar</h3>
        <?php if (empty($coupons)): ?>
            <p>Firmanıza ait kayıtlı kupon bulunmamaktadır.</p>
        <?php else: ?>
            <table class="admin-table">
                <thead><tr><th>Kod</th><th>İndirim Oranı</th><th>Kullanım Limiti</th><th>Geçerlilik Tarihi</th><th>İşlemler</th></tr></thead>
                <tbody>
                    <?php foreach ($coupons as $coupon): ?>
                        <tr>
                            <td><?= htmlspecialchars($coupon['code']) ?></td>
                            <td>%<?= htmlspecialchars($coupon['discount_rate'] * 100) ?></td>
                            <td><?= htmlspecialchars($coupon['usage_limit']) ?></td>
                            <td><?= date('d/m/Y', strtotime($coupon['valid_until'])) ?></td>
                            <td>
                                <a href="edit_company_coupon.php?id=<?= htmlspecialchars($coupon['id']) ?>" class="btn-secondary">Düzenle</a>
                                <a href="delete_coupon.php?id=<?= $coupon['id'] ?>" class="btn-danger" onclick="return confirm('Bu kuponu silmek istediğinizden emin misiniz?')">Sil</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>