<?php
require 'config.php';

$jenis = $_GET['jenis'] ?? '';

$sql = "SELECT id, nama_slot, area, jenis_kendaraan, status FROM slot_parkir";
if (!empty($jenis)) {
    $sql .= " WHERE jenis_kendaraan = ?";
}

$stmt = $conn->prepare($sql);
if (!empty($jenis)) {
    $stmt->bind_param("s", $jenis);
}
$stmt->execute();
$result = $stmt->get_result();

$slots = [];
while ($row = $result->fetch_assoc()) {
    $slots[] = $row;
}

// Hitung statistik
$total = count($slots);
$terisi = count(array_filter($slots, fn($s) => $s['status'] == 'terisi'));
$tersedia = $total - $terisi;

echo json_encode([
    'status' => 'success',
    'data' => [
        'slots' => $slots,
        'statistik' => [
            'total' => $total,
            'terisi' => $terisi,
            'tersedia' => $tersedia
        ]
    ]
]);

$stmt->close();
$conn->close();
?>