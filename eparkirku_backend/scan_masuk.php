<?php
require 'config.php';

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $data['user_id'] ?? 0;
$jenis_kendaraan = $data['jenis_kendaraan'] ?? 'motor';

if ($user_id == 0) {
    echo json_encode(['status' => 'error', 'message' => 'User ID wajib diisi']);
    exit;
}

// Cek apakah user sudah parkir aktif
$check = $conn->prepare("SELECT id FROM transaksi_parkir WHERE user_id = ? AND status = 'aktif'");
$check->bind_param("i", $user_id);
$check->execute();
$checkResult = $check->get_result();

if ($checkResult->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Anda masih memiliki parkir aktif']);
    exit;
}

// Cari slot tersedia
$slotStmt = $conn->prepare("SELECT id FROM slot_parkir WHERE jenis_kendaraan = ? AND status = 'tersedia' LIMIT 1");
$slotStmt->bind_param("s", $jenis_kendaraan);
$slotStmt->execute();
$slotResult = $slotStmt->get_result();

if ($slotResult->num_rows == 0) {
    echo json_encode(['status' => 'error', 'message' => 'Parkir penuh untuk kendaraan ' . $jenis_kendaraan]);
    exit;
}

$slot = $slotResult->fetch_assoc();
$slot_id = $slot['id'];

// Generate QR Code unik
$qr_code = 'EPK-' . date('YmdHis') . '-' . $user_id . '-' . rand(1000, 9999);
$waktu_masuk = date('Y-m-d H:i:s');

// Insert transaksi
$transStmt = $conn->prepare("INSERT INTO transaksi_parkir (user_id, slot_id, qr_code, waktu_masuk) VALUES (?, ?, ?, ?)");
$transStmt->bind_param("iiss", $user_id, $slot_id, $qr_code, $waktu_masuk);

if ($transStmt->execute()) {
    // Update slot jadi terisi
    $updateSlot = $conn->prepare("UPDATE slot_parkir SET status = 'terisi' WHERE id = ?");
    $updateSlot->bind_param("i", $slot_id);
    $updateSlot->execute();
    
    // Insert log
    $logStmt = $conn->prepare("INSERT INTO log_parkir (transaksi_id, aksi, keterangan) VALUES (?, 'masuk', 'Kendaraan masuk parkir')");
    $logStmt->bind_param("i", $transStmt->insert_id);
    $logStmt->execute();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Parkir berhasil',
        'data' => [
            'qr_code' => $qr_code,
            'waktu_masuk' => $waktu_masuk,
            'slot_id' => $slot_id
        ]
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal mencatat parkir']);
}

$slotStmt->close();
$transStmt->close();
$conn->close();
?>