<?php
header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
if (session_status() === PHP_SESSION_NONE) {
    session_name('BIM_ADMIN_SESSION');
    session_start();
}
include 'koneksi.php';
include 'admin_guard.php';
requireTeacher($conn);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Metode tidak didukung."]);
    exit;
}

$sql = "SELECT pt.id, pt.modul, pt.original_name, pt.file_size, pt.submitted_at,
        u.nama, u.email, COALESCE(ps.kelas, '') AS kelas
    FROM pengumpulan_tugas pt
    INNER JOIN `user` u ON u.id = pt.user_id
    LEFT JOIN profil_siswa ps ON ps.user_id = u.id
    ORDER BY pt.submitted_at DESC";
$result = $conn->query($sql);
$items = [];
while ($row = $result->fetch_assoc()) $items[] = $row;
echo json_encode(["status" => "success", "data" => $items]);
?>