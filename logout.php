<?php
header("Content-Type: application/json");
if (($_GET['scope'] ?? '') === 'admin' && session_status() === PHP_SESSION_NONE) {
    session_name('BIM_ADMIN_SESSION');
    session_start();
}
include 'koneksi.php';

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}
session_destroy();

echo json_encode(["status" => "success", "message" => "Anda telah keluar dari akun."]);
?>
