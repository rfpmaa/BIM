<?php
header("Content-Type: application/json");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
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
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Query progress gagal dibuat."]);
        exit;
    }
    $stmt->bind_param("i", $userId);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Progress gagal dibaca dari database."]);
        exit;
    }
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
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Query progress gagal dibuat."]);
        exit;
    }
    $stmt->bind_param("iii", $userId, $modul, $progress);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Progress gagal disimpan ke database."]);
        exit;
    }
    echo json_encode(["status" => "success", "message" => "Progress tersimpan.", "data" => ["user_id" => $userId, "modul" => $modul, "progress" => $progress]]);
    exit;
}

http_response_code(405);
echo json_encode(["status" => "error", "message" => "Metode tidak didukung."]);
?>