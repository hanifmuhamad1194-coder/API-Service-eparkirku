<?php
require 'config.php';

$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Email dan password wajib diisi']);
    exit;
}

$stmt = $conn->prepare("SELECT id, nim_nip, nama, email, role, plat_nomor, jenis_kendaraan FROM users WHERE email = ? AND password = MD5(?)");
$stmt->bind_param("ss", $email, $password);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo json_encode(['status' => 'success', 'data' => $user]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Email atau password salah']);
}

$stmt->close();
$conn->close();
?>