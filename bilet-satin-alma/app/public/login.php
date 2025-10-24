<?php
session_start();
require_once __DIR__ . '/../src/db.php';

$error_message = '';
$success_message = '';

if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error_message = 'E-posta ve parola alanları zorunludur.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM Users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['fullname'] = $user['fullname'];
                $_SESSION['role'] = $user['role'];
                if ($user['role'] === 'FirmaAdmin') {
                    $_SESSION['company_id'] = $user['company_id'];
                }

                // yönlendirme
                if ($user['role'] === 'Admin') {
                    header("Location: /admin_panel.php");
                } elseif ($user['role'] === 'FirmaAdmin') {
                    header("Location: /firma_admin_panel.php");
                } else {
                    header("Location: /"); 
                }
                exit();
            } else {
                $error_message = 'E-posta veya parola hatalı.';
            }
        } catch (PDOException $e) {
            $error_message = "Veritabanı hatası. Lütfen daha sonra tekrar deneyin.";
        }
    }
}

// html ekle -- düzenlenecek
include __DIR__ . '/../includes/header.php';
?>

<div class="auth-container">
    <form class="auth-form" action="login.php" method="POST">
        <h1>Giriş Yap</h1>
        <?php if (!empty($error_message)): ?>
            <p class="error-box"><?= htmlspecialchars($error_message) ?></p>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
            <p class="success-box"><?= htmlspecialchars($success_message) ?></p>
        <?php endif; ?>
        <div class="form-group">
            <label for="email">E-posta Adresi</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div class="form-group">
            <label for="password">Parola</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="btn-submit">Giriş Yap</button>
        <p class="toggle-auth">
            Hesabın yok mu? <a href="register.php">Hemen Kayıt Ol</a>
        </p>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>