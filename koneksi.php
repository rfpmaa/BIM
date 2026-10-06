<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/admin_guard.php';

$host = "sql108.infinityfree.com";
$user = "if0_43104263";
$pass = "bimjosjis";
$db   = "if0_43104263_bim";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Koneksi database gagal"]));
}

ensureApplicationTables($conn);
?>