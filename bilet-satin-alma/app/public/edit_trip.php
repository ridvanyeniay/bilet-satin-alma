<?php
session_start();
require_once __DIR__ . '/../src/db.php';

//rol kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'FirmaAdmin' || !isset($_SESSION['company_id'])) {
    header("Location: /");
    exit();
}

$trip_id = $_GET['id'] ?? null;
if (!$trip_id) {
    header("Location: /firma_admin_panel.php");
    exit();
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $departure_city = trim($_POST['departure_city']);
    $destination_city = trim($_POST['destination_city']);
    $departure_time = $_POST['departure_time'];
    $arrival_time = $_POST['arrival_time'];
    $seat_count = filter_input(INPUT_POST, 'seat_count', FILTER_VALIDATE_INT);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);

    if (empty($departure_city) || empty($destination_city) || empty($departure_time) || empty($arrival_time) || !$seat_count || !$price) {
        $error_message = "Lütfen tüm alanları doğru bir şekilde doldurun.";
    } else {
        try {
            // sadece kendi firmasını düznleyebilesi için
            $stmt = $pdo->prepare("
                UPDATE Trips SET 
                    departure_city = ?, 
                    destination_city = ?, 
                    departure_time = ?, 
                    arrival_time = ?, 
                    seat_count = ?, 
                    price = ? 
                WHERE id = ? AND company_id = ?
            ");
            $stmt->execute([$departure_city, $destination_city, $departure_time, $arrival_time, $seat_count, $price, $trip_id, $_SESSION['company_id']]);
            
            $success_message = "Sefer başarıyla güncellendi.";
        } catch (PDOException $e) {
            $error_message = "Sefer güncellenirken bir hata oluştu: " . $e->getMessage();
        }
    }
}


// bilgileri çek
try {
    $stmt = $pdo->prepare("SELECT * FROM Trips WHERE id = ? AND company_id = ?");
    $stmt->execute([$trip_id, $_SESSION['company_id']]);
    $trip = $stmt->fetch();

    if (!$trip) {
        header("Location: /firma_admin_panel.php");
        exit();
    }
} catch (PDOException $e) {
    die("Sefer bilgileri getirilirken bir hata oluştu: " . $e->getMessage());
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-container">
    <h1>Sefer Düzenle</h1>
    <p><a href="/firma_admin_panel.php">&larr; Firma Paneline Geri Dön</a></p>

    <div class="admin-section">
        <form action="edit_trip.php?id=<?= htmlspecialchars($trip_id) ?>" method="POST" class="admin-form trip-form">
            <?php if ($error_message): ?><p class="error-box"><?= $error_message ?></p><?php endif; ?>
            <?php if ($success_message): ?><p class="success-box"><?= $success_message ?></p><?php endif; ?>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="departure_city">Kalkış Şehri:</label>
                    <input type="text" id="departure_city" name="departure_city" value="<?= htmlspecialchars($trip['departure_city']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="destination_city">Varış Şehri:</label>
                    <input type="text" id="destination_city" name="destination_city" value="<?= htmlspecialchars($trip['destination_city']) ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="departure_time">Kalkış Zamanı:</label>
                    <input type="datetime-local" id="departure_time" name="departure_time" value="<?= date('Y-m-d\TH:i', strtotime($trip['departure_time'])) ?>" required>
                </div>
                <div class="form-group">
                    <label for="arrival_time">Tahmini Varış Zamanı:</label>
                    <input type="datetime-local" id="arrival_time" name="arrival_time" value="<?= date('Y-m-d\TH:i', strtotime($trip['arrival_time'])) ?>" required>
                </div>
            </div>

             <div class="form-row">
                <div class="form-group">
                    <label for="seat_count">Koltuk Sayısı:</label>
                    <input type="number" id="seat_count" name="seat_count" min="1" value="<?= htmlspecialchars($trip['seat_count']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="price">Fiyat (TL):</label>
                    <input type="number" id="price" name="price" min="0.01" step="0.01" value="<?= htmlspecialchars($trip['price']) ?>" required>
                </div>
            </div>

            <button type="submit" name="edit_trip" class="btn-submit">Değişiklikleri Kaydet</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>