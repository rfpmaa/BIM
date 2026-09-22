<?php
header("Content-Type: application/json");
include 'koneksi.php';
include 'admin_guard.php';
ensureApplicationTables($conn);

$nama     = $_POST['nama'] ?? '';
$email    = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

$nama = trim($nama);
$email = trim($email);

if ($nama === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
    echo json_encode(["status" => "error", "message" => "Nama wajib diisi, email harus valid, dan password minimal 8 karakter."]);
    exit;
}

$checkStmt = $conn->prepare("SELECT id FROM user WHERE email = ? LIMIT 1");
$checkStmt->bind_param("s", $email);
$checkStmt->execute();
if ($checkStmt->get_result()->num_rows > 0) {
    echo json_encode(["status" => "error", "message" => "Email sudah terdaftar."]);
    exit;
}

// Enkripsi password demi keamanan
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// Simpan ke database
$stmt = $conn->prepare("INSERT INTO user (nama, email, password, role) VALUES (?, ?, ?, 'siswa')");
$stmt->bind_param("sss", $nama, $email, $passwordHash);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Pendaftaran berhasil!"]);
} else {
    echo json_encode(["status" => "error", "message" => "Pendaftaran gagal. Silakan coba lagi."]);
}
?>