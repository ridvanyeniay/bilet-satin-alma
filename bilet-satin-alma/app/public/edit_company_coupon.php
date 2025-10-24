<?php
session_start();
require_once __DIR__ . '/../src/db.php';

// kontrol
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'FirmaAdmin' || !isset($_SESSION['company_id'])) {
    header("Location: /");
    exit();
}

$coupon_id = $_GET['id'] ?? null;
if (!$coupon_id) {
    header("Location: /firma_admin_panel.php");
    exit();
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['code']));
    $discount_rate = filter_input(INPUT_POST, 'discount_rate', FILTER_VALIDATE_INT);
    $usage_limit = filter_input(INPUT_POST, 'usage_limit', FILTER_VALIDATE_INT);
    $valid_until = $_POST['valid_until'];

    if (empty($code) || $discount_rate === false || $usage_limit === false || empty($valid_until)) {
        $error_message = "Tüm alanlar zorunludur ve doğru formatta olmalıdır.";
    } elseif ($discount_rate < 1 || $discount_rate > 99) {
        $error_message = "İndirim oranı 1 ile 99 arasında olmalıdır.";
    } else {
        try {
            $db_discount_rate = $discount_rate / 100.0;
            
            $stmt = $pdo->prepare("
                UPDATE Coupons SET 
                    code = ?, 
                    discount_rate = ?, 
                    usage_limit = ?, 
                    valid_until = ? 
                WHERE id = ? AND company_id = ? 
            ");
            $stmt->execute([$code, $db_discount_rate, $usage_limit, $valid_until, $coupon_id, $_SESSION['company_id']]);
            
            $success_message = "Kupon başarıyla güncellendi.";
        } catch (PDOException $e) {
            $error_message = "Kupon güncellenirken bir hata oluştu: " . $e->getMessage();
        }
    }
}


try {
    $stmt = $pdo->prepare("SELECT * FROM Coupons WHERE id = ? AND company_id = ?");
    $stmt->execute([$coupon_id, $_SESSION['company_id']]);
    $coupon = $stmt->fetch();

    if (!$coupon) {
        header("Location: /firma_admin_panel.php");
        exit();
    }
} catch (PDOException $e) {
    die("Kupon bilgileri getirilirken bir hata oluştu: " . $e->getMessage());
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-container">
    <h1>Firma Kuponunu Düzenle</h1>
    <p><a href="/firma_admin_panel.php">&larr; Firma Paneline Geri Dön</a></p>

    <div class="admin-section">
        <form action="edit_company_coupon.php?id=<?= htmlspecialchars($coupon_id) ?>" method="POST" class="admin-form">
            <?php if ($error_message): ?><p class="error-box"><?= $error_message ?></p><?php endif; ?>
            <?php if ($success_message): ?><p class="success-box"><?= $success_message ?></p><?php endif; ?>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="code">Kupon Kodu:</label>
                    <input type="text" id="code" name="code" value="<?= htmlspecialchars($coupon['code']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="discount_rate">İndirim Oranı (%):</label>
                    <input type="number" id="discount_rate" name="discount_rate" min="1" max="99" value="<?= htmlspecialchars($coupon['discount_rate'] * 100) ?>" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="usage_limit">Kullanım Limiti:</label>
                    <input type="number" id="usage_limit" name="usage_limit" min="1" value="<?= htmlspecialchars($coupon['usage_limit']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="valid_until">Son Kullanma Tarihi:</label>
                    <input type="date" id="valid_until" name="valid_until" value="<?= htmlspecialchars($coupon['valid_until']) ?>" required>
                </div>
            </div>
            <button type="submit" class="btn-submit">Değişiklikleri Kaydet</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>