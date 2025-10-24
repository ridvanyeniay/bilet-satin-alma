<?php
session_start();
require_once __DIR__ . '/../src/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}

$success_message = '';
$error_message = '';

// başarılı
if (isset($_SESSION['success_message_ticket'])) {
    $success_message = $_SESSION['success_message_ticket'];
    unset($_SESSION['success_message_ticket']);
}
if (isset($_SESSION['cancel_success'])) {
    $success_message = $_SESSION['cancel_success'];
    unset($_SESSION['cancel_success']);
}
//hatalı mesaj
if (isset($_SESSION['cancel_error'])) {
    $error_message = $_SESSION['cancel_error'];
    unset($_SESSION['cancel_error']);
}

// verileri çekme kısmı
$my_tickets = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            b.id AS booking_id,
            b.seat_number,
            t.departure_city,
            t.destination_city,
            t.departure_time,
            t.price,
            c.name AS company_name
        FROM Bookings b
        JOIN Trips t ON b.trip_id = t.id
        JOIN Companies c ON t.company_id = c.id
        WHERE b.user_id = ?
        ORDER BY t.departure_time DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $my_tickets = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Biletler getirilirken bir hata oluştu: " . $e->getMessage());
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-container">
    <h1>Biletlerim</h1>

    <?php if ($success_message): ?>
        <p class="success-box"><?= htmlspecialchars($success_message) ?></p>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <p class="error-box"><?= htmlspecialchars($error_message) ?></p>
    <?php endif; ?>
    
    <p>Daha önce satın almış olduğunuz tüm biletleri burada görebilirsiniz.</p>

    <?php if (empty($my_tickets)): ?>
        <p>Henüz satın alınmış bir biletiniz bulunmamaktadır.</p>
    <?php else: ?>
        <table class="tickets-table">
            <thead>
                <tr>
                    <th>Firma</th>
                    <th>Güzergah</th>
                    <th>Kalkış Tarihi</th>
                    <th>Koltuk No</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($my_tickets as $ticket): ?>
                    <?php
                        $departure_datetime = new DateTime($ticket['departure_time']);
                        $now = new DateTime();
                        $interval = $now->diff($departure_datetime);
                        $hours_until_departure = ($interval->days * 24) + $interval->h;
                        
                        $can_cancel = ($departure_datetime > $now) && ($hours_until_departure >= 1);
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($ticket['company_name']) ?></td>
                        <td><?= htmlspecialchars($ticket['departure_city']) ?> &rarr; <?= htmlspecialchars($ticket['destination_city']) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($ticket['departure_time'])) ?></td>
                        <td><?= htmlspecialchars($ticket['seat_number']) ?></td>
                        <td>
                            <?php if ($can_cancel): ?>
                                <a href="cancel_ticket.php?booking_id=<?= htmlspecialchars($ticket['booking_id']) ?>" class="btn-secondary" onclick="return confirm('Bu bileti iptal etmek istediğinizden emin misiniz? Ücret iade edilecektir.')">İptal Et</a>
                            <?php else: ?>
                                <a href="#" class="btn-disabled" disabled>İptal Edilemez</a>
                            <?php endif; ?>
                            
                            <a href="download_pdf.php?booking_id=<?= htmlspecialchars($ticket['booking_id']) ?>" class="btn-primary">PDF İndir</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php
// ?düzenle
echo '
<style>
.btn-disabled {
    background-color: #ccc;
    color: #666;
    padding: 6px 12px;
    font-size: 0.9rem;
    text-decoration: none;
    border-radius: 5px;
    cursor: not-allowed;
    pointer-events: none;
}
</style>
';

include __DIR__ . '/../includes/footer.php'; 
?>