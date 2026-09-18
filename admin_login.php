<?php
header("Content-Type: application/json");
include 'koneksi.php';
include 'admin_guard.php';

ensureApplicationTables($conn);
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    echo json_encode(["status" => "error", "message" => "Email dan password wajib diisi."]);
    exit;
}

$stmt = $conn->prepare("SELECT id, nama, email, password, role FROM user WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || !password_verify($password, $user['password'])) {
    echo json_encode(["status" => "error", "message" => "Email atau password guru salah."]);
    exit;
}
if ($user['role'] !== 'guru') {
    echo json_encode(["status" => "error", "message" => "Akun ini belum memiliki akses guru."]);
    exit;
}

session_regenerate_id(true);
$_SESSION['user_id'] = $user['id'];
$_SESSION['nama'] = $user['nama'];
$_SESSION['email'] = $user['email'];
$_SESSION['role'] = 'guru';

echo json_encode(["status" => "success", "message" => "Login guru berhasil."]);
?>
