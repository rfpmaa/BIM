<?php
header("Content-Type: application/json");
if (session_status() === PHP_SESSION_NONE) {
    session_name('BIM_ADMIN_SESSION');
    session_start();
}
include 'koneksi.php';
include 'admin_guard.php';
requireTeacher($conn);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $result = $conn->query("SELECT id, modul, judul, ringkasan, kategori, isi, video_url, published, created_at FROM materi ORDER BY modul ASC, updated_at DESC");
    $items = [];
    while ($row = $result->fetch_assoc()) $items[] = $row;
    echo json_encode(["status" => "success", "data" => $items]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $modul = (int) ($_POST['modul'] ?? 1);
    $judul = trim($_POST['judul'] ?? '');
    $ringkasan = trim($_POST['ringkasan'] ?? '');
    $kategori = trim($_POST['kategori'] ?? 'BIM');
    $isi = trim($_POST['isi'] ?? '');
    $videoUrl = trim($_POST['video_url'] ?? '');
    $published = isset($_POST['published']) ? 1 : 0;

    if ($modul < 1 || $modul > 4) {
        http_response_code(422);
        echo json_encode(["status" => "error", "message" => "Modul harus dipilih dari 1 sampai 4."]);
        exit;
    }

    if ($judul === '' || $ringkasan === '' || $isi === '') {
        http_response_code(422);
        echo json_encode(["status" => "error", "message" => "Judul, ringkasan, dan isi materi wajib diisi."]);
        exit;
    }
    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE materi SET modul = ?, judul = ?, ringkasan = ?, kategori = ?, isi = ?, video_url = ?, published = ? WHERE id = ?");
        $stmt->bind_param("isssssii", $modul, $judul, $ringkasan, $kategori, $isi, $videoUrl, $published, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO materi (modul, judul, ringkasan, kategori, isi, video_url, published) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssi", $modul, $judul, $ringkasan, $kategori, $isi, $videoUrl, $published);
    }
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Materi gagal disimpan."]);
        exit;
    }
    echo json_encode(["status" => "success", "message" => $id > 0 ? "Materi diperbarui." : "Materi ditambahkan."]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents('php://input'), $input);
    $id = (int) ($_GET['id'] ?? $input['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(422);
        echo json_encode(["status" => "error", "message" => "ID materi tidak valid."]);
        exit;
    }
    $stmt = $conn->prepare("DELETE FROM materi WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    echo json_encode(["status" => "success", "message" => "Materi dihapus."]);
    exit;
}

http_response_code(405);
echo json_encode(["status" => "error", "message" => "Metode tidak didukung."]);
?>
