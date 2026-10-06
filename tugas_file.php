<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('BIM_ADMIN_SESSION');
    session_start();
}
include 'koneksi.php';
include 'admin_guard.php';
requireTeacher($conn);

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(404);
    exit('File tidak ditemukan.');
}

$stmt = $conn->prepare("SELECT original_name, stored_name FROM pengumpulan_tugas WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$submission = $stmt->get_result()->fetch_assoc();
if (!$submission || !preg_match('/^[a-f0-9]{48}$/', $submission['stored_name'])) {
    http_response_code(404);
    exit('File tidak ditemukan.');
}

$filePath = __DIR__ . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'tugas' . DIRECTORY_SEPARATOR . $submission['stored_name'];
if (!is_file($filePath)) {
    http_response_code(404);
    exit('File tidak ditemukan.');
}

$downloadName = str_replace(["\r", "\n", '"'], '', $submission['original_name']);
header('Content-Type: application/octet-stream');
header('Content-Length: ' . filesize($filePath));
header('Content-Disposition: attachment; filename="submission"; filename*=UTF-8\'\'' . rawurlencode($downloadName));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
readfile($filePath);
?>