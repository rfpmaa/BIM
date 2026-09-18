<?php
header("Content-Type: application/json");
include 'koneksi.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Sesi login tidak ditemukan."]);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$createTable = "CREATE TABLE IF NOT EXISTS profil_siswa (
    user_id INT PRIMARY KEY,
    nis VARCHAR(30) NOT NULL DEFAULT '',
    kelas VARCHAR(100) NOT NULL DEFAULT '',
    jurusan VARCHAR(150) NOT NULL DEFAULT '',
    jenis_kelamin VARCHAR(30) NOT NULL DEFAULT '',
    sekolah VARCHAR(200) NOT NULL DEFAULT '',
    telepon VARCHAR(30) NOT NULL DEFAULT '',
    alamat TEXT NOT NULL,
    foto MEDIUMTEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$conn->query($createTable)) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Tabel profil tidak dapat disiapkan."]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conn->prepare("SELECT u.nama, u.email, p.nis, p.kelas, p.jurusan, p.jenis_kelamin, p.sekolah, p.telepon, p.alamat, p.foto FROM user u LEFT JOIN profil_siswa p ON p.user_id = u.id WHERE u.id = ? LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc();
    echo json_encode(["status" => "success", "data" => $profile]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Metode tidak didukung."]);
    exit;
}

$nama = trim($_POST['nama'] ?? '');
$email = trim($_POST['email'] ?? '');
$nis = trim($_POST['nis'] ?? '');
$kelas = trim($_POST['kelas'] ?? '');
$jurusan = trim($_POST['jurusan'] ?? '');
$jenisKelamin = trim($_POST['jenis_kelamin'] ?? '');
$sekolah = trim($_POST['sekolah'] ?? '');
$telepon = trim($_POST['telepon'] ?? '');
$alamat = trim($_POST['alamat'] ?? '');
$foto = $_POST['foto'] ?? '';

if ($nama === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "Nama dan email wajib diisi dengan benar."]);
    exit;
}

if ($foto !== '' && strlen($foto) > 7000000) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "Ukuran foto terlalu besar."]);
    exit;
}

$updateUser = $conn->prepare("UPDATE user SET nama = ?, email = ? WHERE id = ?");
$updateUser->bind_param("ssi", $nama, $email, $userId);
if (!$updateUser->execute()) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "Nama atau email tidak dapat disimpan."]);
    exit;
}

$stmt = $conn->prepare("INSERT INTO profil_siswa (user_id, nis, kelas, jurusan, jenis_kelamin, sekolah, telepon, alamat, foto) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULLIF(?, '')) ON DUPLICATE KEY UPDATE nis = VALUES(nis), kelas = VALUES(kelas), jurusan = VALUES(jurusan), jenis_kelamin = VALUES(jenis_kelamin), sekolah = VALUES(sekolah), telepon = VALUES(telepon), alamat = VALUES(alamat), foto = COALESCE(VALUES(foto), foto)");
$stmt->bind_param("issssssss", $userId, $nis, $kelas, $jurusan, $jenisKelamin, $sekolah, $telepon, $alamat, $foto);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Data profil gagal disimpan."]);
    exit;
}

$_SESSION['nama'] = $nama;
$_SESSION['email'] = $email;
echo json_encode(["status" => "success", "message" => "Data profil berhasil disimpan."]);
?>
