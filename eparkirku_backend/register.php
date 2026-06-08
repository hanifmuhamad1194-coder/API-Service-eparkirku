<?php
require 'config.php';

$data = json_decode(file_get_contents('php://input'), true);
$nim_nip = $data['nim_nip'] ?? '';
$nama = $data['nama'] ?? '';
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';
$no_hp = $data['no_hp'] ?? '';
$plat_nomor = $data['plat_nomor'] ?? '';
$jenis_kendaraan = $data['jenis_kendaraan'] ?? 'motor';

if (empty($nim_nip) || empty($nama) || empty($email) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Semua field wajib diisi']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO users (nim_nip, nama, email, password, no_hp, plat_nomor, jenis_kendaraan) VALUES (?, ?, ?, MD5(?), ?, ?, ?)");
$stmt->bind_param("ssssss", $nim_nip, $nama, $email, $password, $no_hp, $plat_nomor, $jenis_kendaraan);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Registrasi berhasil']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Registrasi gagal: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>