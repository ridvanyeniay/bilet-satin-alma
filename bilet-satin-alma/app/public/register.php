<?php
session_start();
require_once __DIR__ . '/../src/db.php';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm']; 
    if (empty($fullname) || empty($email) || empty($password) || empty($password_confirm)) {
        $error_message = 'Tüm alanların doldurulması zorunludur.';
    } 
    // format kontrolü
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Lütfen geçerli bir e-posta adresi girin.';
    }
    // 2 parolada aynı mı
    elseif ($password !== $password_confirm) {
        $error_message = 'Girdiğiniz parolalar eşleşmiyor.';
    }
    else {
        try {
            // e posta varmı yokmu kontrol et
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM Users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                $error_message = 'Bu e-posta adresi zaten kayıtlı. Lütfen giriş yapmayı deneyin.';
            } else {
                // başarılı
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $user_id = uniqid('user_');

                $stmt = $pdo->prepare("INSERT INTO Users (id, fullname, email, password, role) VALUES (?, ?, ?, ?, 'User')");
                $stmt->execute([$user_id, $fullname, $email, $hashed_password]);
                $_SESSION['success_message'] = 'Kayıt işlemi başarıyla tamamlandı. Şimdi giriş yapabilirsiniz.';
                header("Location: /login.php");
                exit();
            }
        } catch (PDOException $e) {
            $error_message = "Bir veritabanı hatası oluştu. Lütfen daha sonra tekrar deneyin.";
        }
    }
}
include __DIR__ . '/../includes/header.php';
?>

<div class="auth-container">
    <form class="auth-form" action="register.php" method="POST">
        <h1>Kayıt Ol</h1>
        <?php if (!empty($error_message)): ?>
            <p class="error-box"><?= htmlspecialchars($error_message) ?></p>
        <?php endif; ?>
        <div class="form-group">
            <label for="fullname">Ad Soyad</label>
            <input type="text" id="fullname" name="fullname" required>
        </div>
        <div class="form-group">
            <label for="email">E-posta Adresi</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div class="form-group">
            <label for="password">Parola</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div class="form-group">
            <label for="password_confirm">Parolayı Doğrula</label>
            <input type="password" id="password_confirm" name="password_confirm" required>
        </div>
        <button type="submit" class="btn-submit">Kayıt Ol</button>
        <p class="toggle-auth">
            Zaten hesabın var mı? <a href="login.php">Giriş Yap</a>
        </p>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>