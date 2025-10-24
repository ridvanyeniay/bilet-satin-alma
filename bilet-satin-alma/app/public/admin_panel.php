<?php
session_start();
require_once __DIR__ . '/../src/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: /");
    exit();
}

$company_error = ''; $company_success = '';
$firma_admin_error = ''; $firma_admin_success = '';
$coupon_error = ''; $coupon_success = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_company'])) {
    $company_name = trim($_POST['company_name']);
    if (empty($company_name)) {
        $company_error = "Firma adı boş bırakılamaz.";
    } else {
        try {
            $company_id = uniqid('comp_');
            $stmt = $pdo->prepare("INSERT INTO Companies (id, name) VALUES (?, ?)");
            $stmt->execute([$company_id, $company_name]);
            $company_success = "'{$company_name}' firması başarıyla eklendi.";
        } catch (PDOException $e) {
            if ($e->getCode() == 19) { $company_error = "Hata: '{$company_name}' adında bir firma zaten mevcut."; } 
            else { $company_error = "Veritabanı hatası oluştu: " . $e->getMessage(); }
        }
    }
}

// firma eklem
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_firma_admin'])) {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $company_id = $_POST['company_id'];
    if (empty($fullname) || empty($email) || empty($password) || empty($company_id)) {
        $firma_admin_error = "Tüm alanların doldurulması zorunludur.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $firma_admin_error = "Geçerli bir e-posta adresi girin.";
    } else {
        try {
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM Users WHERE email = ?");
            $stmt_check->execute([$email]);
            if ($stmt_check->fetchColumn() > 0) {
                $firma_admin_error = "Bu e-posta adresi zaten başka bir kullanıcı tarafından kullanılıyor.";
            } else {
                $user_id = uniqid('fadmin_');
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt_insert = $pdo->prepare("INSERT INTO Users (id, fullname, email, password, role, company_id) VALUES (?, ?, ?, ?, 'FirmaAdmin', ?)");
                $stmt_insert->execute([$user_id, $fullname, $email, $hashed_password, $company_id]);
                $firma_admin_success = "'{$fullname}' adlı Firma Admin kullanıcısı başarıyla oluşturuldu.";
            }
        } catch (PDOException $e) {
            $firma_admin_error = "Kullanıcı oluşturulurken bir veritabanı hatası oluştu: " . $e->getMessage();
        }
    }
}

// kupon
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_coupon'])) {
    $code = strtoupper(trim($_POST['code']));
    $discount_rate = filter_input(INPUT_POST, 'discount_rate', FILTER_VALIDATE_INT);
    $usage_limit = filter_input(INPUT_POST, 'usage_limit', FILTER_VALIDATE_INT);
    $valid_until = $_POST['valid_until'];
    if (empty($code) || $discount_rate === false || $usage_limit === false || empty($valid_until)) {
        $coupon_error = "Tüm alanlar zorunludur ve doğru formatta olmalıdır.";
    } elseif ($discount_rate < 1 || $discount_rate > 99) {
        $coupon_error = "İndirim oranı 1 ile 99 arasında olmalıdır.";
    } else {
        try {
            $coupon_id = uniqid('coupon_');
            $db_discount_rate = $discount_rate / 100.0;
            $stmt = $pdo->prepare("INSERT INTO Coupons (id, code, discount_rate, usage_limit, valid_until, company_id) VALUES (?, ?, ?, ?, ?, NULL)");
            $stmt->execute([$coupon_id, $code, $db_discount_rate, $usage_limit, $valid_until]);
            $coupon_success = "'{$code}' kodlu kupon başarıyla oluşturuldu.";
        } catch (PDOException $e) {
            if ($e->getCode() == 19) { $coupon_error = "Hata: '{$code}' kodlu bir kupon zaten mevcut."; } 
            else { $coupon_error = "Veritabanı hatası: " . $e->getMessage(); }
        }
    }
}

// verileri çek
try {
    $companies = $pdo->query("SELECT * FROM Companies ORDER BY name ASC")->fetchAll();
    $firma_admins = $pdo->query("SELECT u.id, u.fullname, u.email, c.name AS company_name FROM Users u JOIN Companies c ON u.company_id = c.id WHERE u.role = 'FirmaAdmin' ORDER BY u.fullname ASC")->fetchAll();
    $coupons = $pdo->query("SELECT * FROM Coupons WHERE company_id IS NULL ORDER BY valid_until DESC")->fetchAll();
} catch (PDOException $e) {
    die("Veriler getirilirken bir hata oluştu: " . $e->getMessage());
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-container">
    <h1>Admin Paneli</h1>
    <p>Sistemi yönetmek için aşağıdaki araçları kullanabilirsiniz.</p>

    <div class="admin-section">
        <h2>Firma Yönetimi</h2>
        <form action="admin_panel.php" method="POST" class="admin-form">
            <h3>Yeni Firma Ekle</h3>
            <?php if ($company_error): ?><p class="error-box"><?= $company_error ?></p><?php endif; ?>
            <?php if ($company_success): ?><p class="success-box"><?= $company_success ?></p><?php endif; ?>
            <div class="form-group">
                <label for="company_name">Firma Adı:</label>
                <input type="text" id="company_name" name="company_name" required>
            </div>
            <button type="submit" name="add_company" class="btn-submit">Firma Ekle</button>
        </form>
        <h3 style="margin-top: 40px;">Mevcut Firmalar</h3>
        <?php if (empty($companies)): ?>
            <p>Sistemde kayıtlı firma bulunmamaktadır.</p>
        <?php else: ?>
            <table class="admin-table">
                <thead><tr><th>Firma Adı</th><th>İşlemler</th></tr></thead>
                <tbody>
                    <?php foreach ($companies as $company): ?>
                        <tr>
                            <td><?= htmlspecialchars($company['name']) ?></td>
                            <td>
                                <a href="edit_company.php?id=<?= htmlspecialchars($company['id']) ?>" class="btn-secondary">Düzenle</a> 
                                <a href="delete_company.php?id=<?= $company['id'] ?>" class="btn-danger" onclick="return confirm('Bu firmayı silmek istediğinizden emin misiniz?')">Sil</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="admin-section">
        <h2>Firma Admini Yönetimi</h2>
        <form action="admin_panel.php" method="POST" class="admin-form">
            <h3>Yeni Firma Admini Ekle</h3>
            <?php if ($firma_admin_error): ?><p class="error-box"><?= $firma_admin_error ?></p><?php endif; ?>
            <?php if ($firma_admin_success): ?><p class="success-box"><?= $firma_admin_success ?></p><?php endif; ?>
            <div class="form-group"><label for="fullname">Ad Soyad:</label><input type="text" id="fullname" name="fullname" required></div>
            <div class="form-group"><label for="email">E-posta:</label><input type="email" id="email" name="email" required></div>
            <div class="form-group"><label for="password">Parola:</label><input type="password" id="password" name="password" required></div>
            <div class="form-group">
                <label for="company_id">Atanacak Firma:</label>
                <select name="company_id" id="company_id" required>
                    <option value="">-- Firma Seçin --</option>
                    <?php foreach ($companies as $company): ?>
                        <option value="<?= htmlspecialchars($company['id']) ?>"><?= htmlspecialchars($company['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" name="add_firma_admin" class="btn-submit">Firma Admini Ekle</button>
        </form>
        <h3 style="margin-top: 40px;">Mevcut Firma Adminleri</h3>
        <?php if (empty($firma_admins)): ?>
            <p>Sistemde kayıtlı firma admini bulunmamaktadır.</p>
        <?php else: ?>
            <table class="admin-table">
                <thead><tr><th>Ad Soyad</th><th>E-posta</th><th>Firma</th><th>İşlemler</th></tr></thead>
                <tbody>
                    <?php foreach ($firma_admins as $f_admin): ?>
                        <tr>
                            <td><?= htmlspecialchars($f_admin['fullname']) ?></td>
                            <td><?= htmlspecialchars($f_admin['email']) ?></td>
                            <td><?= htmlspecialchars($f_admin['company_name']) ?></td>
                            <td><a href="delete_user.php?id=<?= $f_admin['id'] ?>" class="btn-danger" onclick="return confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz?')">Sil</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="admin-section">
        <h2>Genel Kupon Yönetimi</h2>
        <form action="admin_panel.php" method="POST" class="admin-form">
            <h3>Yeni Genel Kupon Ekle</h3>
            <?php if ($coupon_error): ?><p class="error-box"><?= $coupon_error ?></p><?php endif; ?>
            <?php if ($coupon_success): ?><p class="success-box"><?= $coupon_success ?></p><?php endif; ?>
            <div class="form-row">
                <div class="form-group"><label for="code">Kupon Kodu:</label><input type="text" id="code" name="code" required></div>
                <div class="form-group"><label for="discount_rate">İndirim Oranı (%):</label><input type="number" id="discount_rate" name="discount_rate" min="1" max="99" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label for="usage_limit">Kullanım Limiti:</label><input type="number" id="usage_limit" name="usage_limit" min="1" required></div>
                <div class="form-group"><label for="valid_until">Son Kullanma Tarihi:</label><input type="date" id="valid_until" name="valid_until" required></div>
            </div>
            <button type="submit" name="add_coupon" class="btn-submit">Kupon Ekle</button>
        </form>
        <h3 style="margin-top: 40px;">Mevcut Genel Kuponlar</h3>
        <?php if (empty($coupons)): ?>
            <p>Sistemde kayıtlı genel kupon bulunmamaktadır.</p>
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
                                <a href="edit_coupon.php?id=<?= htmlspecialchars($coupon['id']) ?>" class="btn-secondary">Düzenle</a>
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