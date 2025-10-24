<?php
session_start();
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../lib/fpdf/fpdf.php'; // pdf indirmek için, lib/fpdf altına yerleştirdim

// giriş yapmış kullanıcının kullanabilmesi için
if (!isset($_SESSION['user_id'])) {
    exit('Bu sayfaya erişim yetkiniz yok.');
}

$booking_id = $_GET['booking_id'] ?? null;
if (!$booking_id) {
    exit('Geçersiz bilet ID.');
}

// bilgileri çek
try {
    $stmt = $pdo->prepare("
        SELECT 
            u.fullname AS passenger_name,
            t.departure_city,
            t.destination_city,
            t.departure_time,
            t.arrival_time,
            b.seat_number,
            b.id AS booking_id,
            c.name AS company_name
        FROM Bookings b
        JOIN Users u ON b.user_id = u.id
        JOIN Trips t ON b.trip_id = t.id
        JOIN Companies c ON t.company_id = c.id
        WHERE b.id = ? AND b.user_id = ?
    ");
    $stmt->execute([$booking_id, $_SESSION['user_id']]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        exit('Bilet bulunamadı veya bu bileti görüntüleme yetkiniz yok.');
    }
} catch (PDOException $e) {
    exit('Veritabanı hatası: ' . $e->getMessage());
}




// türkçe için
function fix_turkish_chars($text) {
    return iconv('UTF-8', 'ISO-8859-9//TRANSLIT', $text);
}

$pdf = new FPDF();
$pdf->AddPage(); 
$pdf->SetFont('Arial', 'B', 16);

$pdf->Cell(0, 10, fix_turkish_chars('Elektronik Otobüs Biletiniz'), 0, 1, 'C');
$pdf->Ln(10); 

$pdf->SetFont('Arial', '', 12);
$pdf->Cell(50, 10, fix_turkish_chars('Firma:'), 0, 0);
$pdf->Cell(0, 10, fix_turkish_chars($ticket['company_name']), 0, 1);

$pdf->Cell(50, 10, fix_turkish_chars('Yolcu Adı:'), 0, 0);
$pdf->Cell(0, 10, fix_turkish_chars($ticket['passenger_name']), 0, 1);

$pdf->Ln(5);

$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, fix_turkish_chars('Sefer Bilgileri'), 0, 1);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(50, 10, fix_turkish_chars('Güzergah:'), 0, 0);
$pdf->Cell(0, 10, fix_turkish_chars($ticket['departure_city'] . ' -> ' . $ticket['destination_city']), 0, 1);

$pdf->Cell(50, 10, fix_turkish_chars('Kalkış Tarihi:'), 0, 0);
$pdf->Cell(0, 10, date('d/m/Y H:i', strtotime($ticket['departure_time'])), 0, 1);

$pdf->Cell(50, 10, fix_turkish_chars('Tahmini Varış:'), 0, 0);
$pdf->Cell(0, 10, date('d/m/Y H:i', strtotime($ticket['arrival_time'])), 0, 1);

$pdf->Ln(5);

// koltuk numarası 
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(50, 10, fix_turkish_chars('Koltuk No:'), 0, 0);
$pdf->SetFont('Arial', 'B', 18);
$pdf->Cell(0, 10, $ticket['seat_number'], 0, 1);

$pdf->Ln(15);

$pdf->SetFont('Arial', 'I', 10);
$pdf->Cell(0, 10, fix_turkish_chars('İyi yolculuklar dileriz!'), 0, 1, 'C');

//indirme için
$filename = "bilet-" . $ticket['booking_id'] . ".pdf";
$pdf->Output('D', $filename);