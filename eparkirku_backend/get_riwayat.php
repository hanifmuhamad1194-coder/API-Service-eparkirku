<?php
require 'config.php';

$user_id = $_GET['user_id'] ?? 0;

if ($user_id == 0) {
    echo json_encode(['status' => 'error', 'message' => 'User ID wajib diisi']);
    exit;
}

$stmt = $conn->prepare("SELECT t.*, s.nama_slot, s.area 
                        FROM transaksi_parkir t 
                        JOIN slot_parkir s ON t.slot_id = s.id 
                        WHERE t.user_id = ? 
                        ORDER BY t.waktu_masuk DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$riwayat = [];
while ($row = $result->fetch_assoc()) {
    $riwayat[] = $row;
}

echo json_encode(['status' => 'success', 'data' => $riwayat]);

$stmt->close();
$conn->close();
?>