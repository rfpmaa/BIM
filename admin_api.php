<?php
header("Content-Type: application/json");
include 'koneksi.php';
include 'admin_guard.php';
requireTeacher($conn);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Metode tidak didukung."]);
    exit;
}

$sql = "SELECT u.id, u.nama, u.email, COALESCE(p.kelas, '') AS kelas, COALESCE(p.jurusan, '') AS jurusan,
    COALESCE(SUM(CASE WHEN ps.progress >= 100 THEN 1 ELSE 0 END), 0) AS selesai,
    COALESCE(ROUND(SUM(ps.progress) / 4), 0) AS rata_rata
    FROM user u
    LEFT JOIN profil_siswa p ON p.user_id = u.id
    LEFT JOIN progres_siswa ps ON ps.user_id = u.id
    WHERE u.role = 'siswa'
    GROUP BY u.id, u.nama, u.email, p.kelas, p.jurusan
    ORDER BY u.nama ASC";
$result = $conn->query($sql);
$students = [];
while ($row = $result->fetch_assoc()) $students[] = $row;

$countResult = $conn->query("SELECT COUNT(*) AS jumlah FROM materi");
$materialCount = $countResult ? (int) $countResult->fetch_assoc()['jumlah'] : 0;

echo json_encode(["status" => "success", "data" => ["students" => $students, "material_count" => $materialCount]]);
?>
