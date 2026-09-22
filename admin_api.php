<?php
header("Content-Type: application/json");
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

$sql = "SELECT u.id, u.nama, u.email,
    COALESCE(p.kelas, '') AS kelas,
    COALESCE(p.jurusan, '') AS jurusan,
    COALESCE((SELECT COUNT(*) FROM progres_siswa ps WHERE ps.user_id = u.id AND ps.progress >= 100), 0) AS selesai,
    COALESCE((SELECT ROUND(SUM(ps2.progress) / 4) FROM progres_siswa ps2 WHERE ps2.user_id = u.id), 0) AS rata_rata
    FROM user u
    LEFT JOIN profil_siswa p ON p.user_id = u.id
    WHERE u.role = 'siswa'
    ORDER BY u.nama ASC";
$result = $conn->query($sql);
$students = [];
while ($row = $result->fetch_assoc()) $students[] = $row;

$countResult = $conn->query("SELECT COUNT(*) AS jumlah FROM materi");
$materialCount = $countResult ? (int) $countResult->fetch_assoc()['jumlah'] : 0;

echo json_encode(["status" => "success", "data" => ["students" => $students, "material_count" => $materialCount]]);
?>
