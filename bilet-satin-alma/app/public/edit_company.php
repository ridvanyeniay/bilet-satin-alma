<?php
session_start();
require_once __DIR__ . '/../src/db.php';

// kontrol
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: /");
    exit();
}

$company_id = $_GET['id'] ?? null;
if (!$company_id) {
    header("Location: /admin_panel.php");
    exit();
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_company_name = trim($_POST['company_name']);

    if (empty($new_company_name)) {
        $error_message = "Firma adı boş bırakılamaz.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE Companies SET name = ? WHERE id = ?");
            $stmt->execute([$new_company_name, $company_id]);
            
            $success_message = "Firma adı başarıyla güncellendi.";

        } catch (PDOException $e) {
             if ($e->getCode() == 19) { // SQLite UNIQUE constraint error code
                $error_message = "Hata: '{$new_company_name}' adında bir firma zaten mevcut.";
            } else {
                $error_message = "Firma güncellenirken bir veritabanı hatası oluştu: " . $e->getMessage();
            }
        }
    }
}

try {
    $stmt = $pdo->prepare("SELECT * FROM Companies WHERE id = ?");
    $stmt->execute([$company_id]);
    $company = $stmt->fetch();

    if (!$company) {
        // firma yoksa ana panele yönlendir
        header("Location: /admin_panel.php");
        exit();
    }
} catch (PDOException $e) {
    die("Firma bilgileri getirilirken bir hata oluştu: " . $e->getMessage());
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-container">
    <h1>Firma Adını Düzemle</h1>
    <p><a href="/admin_panel.php">&larr; Admin Paneline Geri Dön</a></p>

    <div class="admin-section">
        <form action="edit_company.php?id=<?= htmlspecialchars($company_id) ?>" method="POST" class="admin-form">
            <?php if ($error_message): ?><p class="error-box"><?= $error_message ?></p><?php endif; ?>
            <?php if ($success_message): ?><p class="success-box"><?= $success_message ?></p><?php endif; ?>
            
            <div class="form-group">
                <label for="company_name">Yeni Firma Adı:</label>
                <input type="text" id="company_name" name="company_name" value="<?= htmlspecialchars($company['name']) ?>" required>
            </div>
            
            <button type="submit" class="btn-submit">Değişiklikleri Kaydet</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>