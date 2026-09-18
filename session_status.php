<?php
header("Content-Type: application/json");
include 'koneksi.php';

if (isset($_SESSION['user_id'])) {
    echo json_encode([
        "status" => "success",
        "authenticated" => true,
        "data" => [
            "id" => $_SESSION['user_id'],
            "nama" => $_SESSION['nama'] ?? '',
            "email" => $_SESSION['email'] ?? '',
            "role" => $_SESSION['role'] ?? 'siswa'
        ]
    ]);
    exit;
}

echo json_encode(["status" => "success", "authenticated" => false]);
?>
