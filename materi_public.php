<?php
header("Content-Type: application/json");
include 'koneksi.php';
include 'admin_guard.php';
ensureApplicationTables($conn);

$result = $conn->query("SELECT id, judul, ringkasan, kategori, isi, video_url FROM materi WHERE published = 1 ORDER BY updated_at DESC");
$items = [];
while ($row = $result->fetch_assoc()) $items[] = $row;
echo json_encode(["status" => "success", "data" => $items]);
?>
