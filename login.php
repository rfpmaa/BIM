<?php
header("Content-Type: application/json");
include 'koneksi.php';
include 'admin_guard.php';
ensureApplicationTables($conn);

$email    = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    echo json_encode(["status" => "error", "message" => "Email dan password wajib diisi dengan benar."]);
    exit;
}

$stmt = $conn->prepare("SELECT id, nama, email, password, role FROM user WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    // Verifikasi password
    if (password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'] ?? 'siswa';

        echo json_encode([
            "status" => "success",
            "message" => "Login berhasil!",
            "data" => [
                "id" => $user['id'],
                "nama" => $user['nama'],
                "email" => $user['email']
            ]
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Password salah. Pastikan password yang dimasukkan benar."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Email tidak ditemukan. Silakan buat akun terlebih dahulu."]);
}
?>