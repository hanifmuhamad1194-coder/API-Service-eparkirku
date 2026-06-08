<?php
require 'config.php';

// Total parkir hari ini
$today = date('Y-m-d');
$totalHariIni = $conn->query("SELECT COUNT(*) as total FROM transaksi_parkir WHERE DATE(waktu_masuk) = '$today'")->fetch_assoc()['total'];

// Parkir aktif
$aktif = $conn->query("SELECT COUNT(*) as total FROM transaksi_parkir WHERE status = 'aktif'")->fetch_assoc()['total'];

// Total pendapatan hari ini
$pendapatan = $conn->query("SELECT COALESCE(SUM(biaya), 0) as total FROM transaksi_parkir WHERE DATE(waktu_keluar) = '$today' AND status = 'selesai'")->fetch_assoc()['total'];

// Slot tersedia vs terisi
$slotMotor = $conn->query("SELECT COUNT(*) as total FROM slot_parkir WHERE jenis_kendaraan = 'motor' AND status = 'tersedia'")->fetch_assoc()['total'];
$slotMobil = $conn->query("SELECT COUNT(*) as total FROM slot_parkir WHERE jenis_kendaraan = 'mobil' AND status = 'tersedia'")->fetch_assoc()['total'];

// 5 transaksi terakhir
$recent = $conn->query("SELECT t.*, u.nama, u.plat_nomor, s.nama_slot 
                        FROM transaksi_parkir t 
                        JOIN users u ON t.user_id = u.id 
                        JOIN slot_parkir s ON t.slot_id = s.id 
                        ORDER BY t.waktu_masuk DESC LIMIT 5");

$recentTrans = [];
while ($row = $recent->fetch_assoc()) {
    $recentTrans[] = $row;
}

echo json_encode([
    'status' => 'success',
    'data' => [
        'total_hari_ini' => $totalHariIni,
        'parkir_aktif' => $aktif,
        'pendapatan_hari_ini' => $pendapatan,
        'slot_motor_tersedia' => $slotMotor,
        'slot_mobil_tersedia' => $slotMobil,
        'transaksi_terakhir' => $recentTrans
    ]
]);

$conn->close();
?>