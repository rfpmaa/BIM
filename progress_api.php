<?php
header("Content-Type: application/json");
include 'koneksi.php';
include 'admin_guard.php';
ensureApplicationTables($conn);

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Sesi login tidak ditemukan."]);
    exit;
}

$userId = (int) $_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conn->prepare("SELECT modul, progress FROM progres_siswa WHERE user_id = ? ORDER BY modul");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) $data[] = $row;
    echo json_encode(["status" => "success", "data" => $data]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $modul = (int) ($payload['modul'] ?? 0);
    $progress = max(0, min(100, (int) ($payload['progress'] ?? 0)));
    if ($modul < 1 || $modul > 4) {
        http_response_code(422);
        echo json_encode(["status" => "error", "message" => "Modul tidak valid."]);
        exit;
    }
    $stmt = $conn->prepare("INSERT INTO progres_siswa (user_id, modul, progress) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE progress = GREATEST(progress, VALUES(progress))");
    $stmt->bind_param("iii", $userId, $modul, $progress);
    $stmt->execute();
    echo json_encode(["status" => "success", "message" => "Progress tersimpan."]);
    exit;
}

http_response_code(405);
echo json_encode(["status" => "error", "message" => "Metode tidak didukung."]);
?>