<?php
session_start();
require_once __DIR__ . '/../src/db.php';
$error_message = '';
$success_message = '';

// rol kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'User') {
    header("Location: /");
    exit();
}

$trip_id = $_GET['trip_id'] ?? null;
if (!$trip_id) { header("Location: /"); exit(); }

try {
    $stmt_trip = $pdo->prepare("SELECT t.*, c.name AS company_name FROM Trips t JOIN Companies c ON t.company_id = c.id WHERE t.id = ?");
    $stmt_trip->execute([$trip_id]);
    $trip = $stmt_trip->fetch();

    //bakiye kontrol
    $stmt_user = $pdo->prepare("SELECT balance FROM Users WHERE id = ?");
    $stmt_user->execute([$_SESSION['user_id']]);
    $user_balance = $stmt_user->fetchColumn();

    if (!$trip) {
        die("Sefer bulunamadı.");
    }

    //disable koltuklar için
    $stmt_booked = $pdo->prepare("SELECT seat_number FROM Bookings WHERE trip_id = ?");
    $stmt_booked->execute([$trip_id]);
    $booked_seats = $stmt_booked->fetchAll(PDO::FETCH_COLUMN) ?: [];

} catch (PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}

//bilet satma
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_seat = $_POST['seat_number'] ?? null;
    $coupon_code = strtoupper(trim($_POST['coupon_code'] ?? ''));
    $final_price = $trip['price']; // normal fiyat
    $discount_amount = 0;
    $coupon_id_to_update = null;

    if (!$selected_seat) {
        $error_message = "Lütfen bir koltuk seçin.";
    } else {
        if (!empty($coupon_code)) {
            $stmt_coupon = $pdo->prepare("
                SELECT * FROM Coupons 
                WHERE code = ? AND valid_until >= date('now') AND usage_limit > 0
                AND (company_id IS NULL OR company_id = ?)
            ");
            $stmt_coupon->execute([$coupon_code, $trip['company_id']]);
            $coupon = $stmt_coupon->fetch();

            if ($coupon) {
                $discount_amount = $trip['price'] * $coupon['discount_rate'];
                $final_price = $trip['price'] - $discount_amount;
                $coupon_id_to_update = $coupon['id']; 
                $success_message = "'{$coupon_code}' kuponu uygulandı! " . number_format($discount_amount, 2) . " TL indirim kazandınız.";
            } else {
                $error_message = "Geçersiz, süresi dolmuş veya bu firma için geçerli olmayan bir kupon kodu girdiniz.";
            }
        }
        
        if (empty($error_message)) {
            if (in_array($selected_seat, $booked_seats)) {
                $error_message = "Bu koltuk siz işlem yaparken doldu. Lütfen başka bir koltuk seçin.";
            } elseif ($user_balance < $final_price) {
                $error_message = "Yetersiz bakiye. Gerekli tutar: " . number_format($final_price, 2) . " TL, Bakiyeniz: {$user_balance} TL.";
            } else {
                
                try {
                    $pdo->beginTransaction();

                    //bakiye-bilet
                    $new_balance = $user_balance - $final_price;
                    $stmt_update_balance = $pdo->prepare("UPDATE Users SET balance = ? WHERE id = ?");
                    $stmt_update_balance->execute([$new_balance, $_SESSION['user_id']]);

                    // kayıt yap
                    $booking_id = uniqid('booking_');
                    $stmt_insert_booking = $pdo->prepare("INSERT INTO Bookings (id, user_id, trip_id, seat_number, price_paid) VALUES (?, ?, ?, ?, ?)");
                    $stmt_insert_booking->execute([$booking_id, $_SESSION['user_id'], $trip_id, $selected_seat, $final_price]);
                    
                    // kupon-1
                    if ($coupon_id_to_update) {
                        $stmt_update_coupon = $pdo->prepare("UPDATE Coupons SET usage_limit = usage_limit - 1 WHERE id = ?");
                        $stmt_update_coupon->execute([$coupon_id_to_update]);
                    }

                    $pdo->commit();

                    $_SESSION['success_message_ticket'] = "Tebrikler! {$selected_seat} numaralı koltuğu başarıyla satın aldınız.";
                    header("Location: /my_tickets.php");
                    exit();

                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $error_message = "Satın alma sırasında beklenmedik bir hata oluştu: " . $e->getMessage();
                }
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-container">
    <h1>Bilet Satın Alma</h1>
    
    <div class="trip-details-card">
        <h2>Sefer Detayları</h2>
        <p><strong>Firma:</strong> <?= htmlspecialchars($trip['company_name']) ?></p>
        <p><strong>Güzergah:</strong> <?= htmlspecialchars($trip['departure_city']) ?> &rarr; <?= htmlspecialchars($trip['destination_city']) ?></p>
        <p><strong>Kalkış Tarihi:</strong> <?= date('d/m/Y H:i', strtotime($trip['departure_time'])) ?></p>
        <p><strong>Fiyat:</strong> <span class="price"><?= htmlspecialchars(number_format($trip['price'], 2)) ?> TL</span></p>
        <p><strong>Mevcut Bakiyeniz:</strong> <span class="price"><?= htmlspecialchars(number_format($user_balance, 2)) ?> TL</span></p>
    </div>

    <div class="seat-selection-container">
        <h2>Koltuk Seçimi</h2>

        <?php if ($error_message): ?><p class="error-box"><?= $error_message ?></p><?php endif; ?>
        <?php if ($success_message && empty($error_message)): ?><p class="success-box"><?= $success_message ?></p><?php endif; ?>

        <form method="POST" action="buy_ticket.php?trip_id=<?= htmlspecialchars($trip_id) ?>">
            
            <div class="bus-layout">
                <?php for ($i = 1; $i <= $trip['seat_count']; $i++): ?>
                    <?php
                        $is_booked = in_array($i, $booked_seats);
                        $seat_id = "seat-" . $i;
                    ?>
                    <div class="seat-wrapper">
                        <input 
                            type="radio" 
                            name="seat_number" 
                            id="<?= $seat_id ?>" 
                            value="<?= $i ?>"
                            <?= $is_booked ? 'disabled' : '' ?>
                        >
                        <label for="<?= $seat_id ?>" class="seat <?= $is_booked ? 'booked' : '' ?>">
                            <?= $i ?>
                        </label>
                    </div>
                <?php endfor; ?>
            </div>
            <div class="coupon-section">
                <label for="coupon_code">İndirim Kuponu:</label>
                <input type="text" name="coupon_code" id="coupon_code" placeholder="Kupon kodunu girin" value="<?= htmlspecialchars($_POST['coupon_code'] ?? '') ?>">
            </div>
            <div class="purchase-actions">
                <button type="submit" class="btn-submit">Satın Al</button>
            </div>
        </form>
    </div>
</div>

<?php 

echo '
<style>
.coupon-section {
    max-width: 400px;
    margin: 20px auto;
    text-align: center;
}
.coupon-section label {
    font-weight: bold;
    margin-right: 10px;
}
.coupon-section input {
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
}
</style>
';
include __DIR__ . '/../includes/footer.php'; 
?>