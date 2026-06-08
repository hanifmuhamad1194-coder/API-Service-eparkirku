<?php
require 'config.php';

$data = json_decode(file_get_contents('php://input'), true);
$qr_code = $data['qr_code'] ?? '';

if (empty($qr_code)) {
    echo json_encode(['status' => 'error', 'message' => 'QR Code wajib diisi']);
    exit;
}

// Cari transaksi aktif
$stmt = $conn->prepare("SELECT t.id, t.user_id, t.slot_id, t.waktu_masuk, s.nama_slot, s.area, u.plat_nomor, u.nama 
                        FROM transaksi_parkir t 
                        JOIN slot_parkir s ON t.slot_id = s.id 
                        JOIN users u ON t.user_id = u.id 
                        WHERE t.qr_code = ? AND t.status = 'aktif'");
$stmt->bind_param("s", $qr_code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(['status' => 'error', 'message' => 'QR Code tidak valid atau parkir sudah selesai']);
    exit;
}

$transaksi = $result->fetch_assoc();
$waktu_keluar = date('Y-m-d H:i:s');

// Hitung durasi dan biaya
$masuk = new DateTime($transaksi['waktu_masuk']);
$keluar = new DateTime($waktu_keluar);
$diff = $masuk->diff($keluar);
$durasi_menit = ($diff->h * 60) + $diff->i;

// Tarif: Motor 2000/jam, Mobil 5000/jam
$tarif_per_jam = ($transaksi['plat_nomor']) ? 2000 : 5000; // Simplified
$jam_penuh = ceil($durasi_menit / 60);
$biaya = $jam_penuh * $tarif_per_jam;

// Update transaksi
$update = $conn->prepare("UPDATE transaksi_parkir SET waktu_keluar = ?, durasi_menit = ?, biaya = ?, status = 'selesai' WHERE id = ?");
$update->bind_param("sidi", $waktu_keluar, $durasi_menit, $biaya, $transaksi['id']);

if ($update->execute()) {
    // Update slot jadi tersedia
    $updateSlot = $conn->prepare("UPDATE slot_parkir SET status = 'tersedia' WHERE id = ?");
    $updateSlot->bind_param("i", $transaksi['slot_id']);
    $updateSlot->execute();
    
    // Insert log
    $logStmt = $conn->prepare("INSERT INTO log_parkir (transaksi_id, aksi, keterangan) VALUES (?, 'keluar', 'Kendaraan keluar parkir')");
    $logStmt->bind_param("i", $transaksi['id']);
    $logStmt->execute();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Parkir selesai',
        'data' => [
            'nama' => $transaksi['nama'],
            'plat_nomor' => $transaksi['plat_nomor'],
            'waktu_masuk' => $transaksi['waktu_masuk'],
            'waktu_keluar' => $waktu_keluar,
            'durasi_menit' => $durasi_menit,
            'biaya' => $biaya,
            'slot' => $transaksi['nama_slot'] . ' - ' . $transaksi['area']
        ]
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal memproses keluar']);
}

$stmt->close();
$update->close();
$conn->close();
?>