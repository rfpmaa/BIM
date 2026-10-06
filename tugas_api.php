<?php
header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
include 'koneksi.php';
include 'admin_guard.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'siswa') {
    http_response_code(isset($_SESSION['user_id']) ? 403 : 401);
    echo json_encode(["status" => "error", "message" => "Silakan masuk sebagai siswa untuk mengirim tugas."]);
    exit;
}

$userId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conn->prepare("SELECT id, modul, original_name, file_size, submitted_at FROM pengumpulan_tugas WHERE user_id = ? ORDER BY submitted_at DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];
    while ($row = $result->fetch_assoc()) $items[] = $row;
    echo json_encode(["status" => "success", "data" => $items]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Metode tidak didukung."]);
    exit;
}

$module = (int) ($_POST['modul'] ?? 0);
if ($module < 1 || $module > 4) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "Modul tugas tidak valid."]);
    exit;
}

if (!isset($_FILES['file_tugas']) || !is_array($_FILES['file_tugas'])) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "Pilih file tugas terlebih dahulu."]);
    exit;
}

$file = $_FILES['file_tugas'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    $message = $file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE
        ? "Ukuran file maksimal 10 MB."
        : "File gagal diunggah. Silakan coba kembali.";
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => $message]);
    exit;
}

if ($file['size'] <= 0 || $file['size'] > 10 * 1024 * 1024 || !is_uploaded_file($file['tmp_name'])) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "File kosong atau melebihi batas 10 MB."]);
    exit;
}

$originalName = basename(str_replace('\\', '/', (string) $file['name']));
$originalName = preg_replace('/[\x00-\x1F\x7F]/', '', $originalName);
$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
$allowedTypes = [
    'pdf' => ['application/pdf'],
    'doc' => ['application/msword', 'application/octet-stream'],
    'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
    'xls' => ['application/vnd.ms-excel', 'application/octet-stream'],
    'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream'],
    'ppt' => ['application/vnd.ms-powerpoint', 'application/octet-stream'],
    'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip', 'application/octet-stream'],
    'zip' => ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'],
    'jpg' => ['image/jpeg'],
    'jpeg' => ['image/jpeg'],
    'png' => ['image/png']
];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);

if ($originalName === '' || strlen($originalName) > 255 || !isset($allowedTypes[$extension]) || !in_array($mimeType, $allowedTypes[$extension], true)) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "Format file tidak didukung. Gunakan PDF, Word, Excel, PowerPoint, ZIP, JPG, atau PNG."]);
    exit;
}

$storageDirectory = __DIR__ . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'tugas';
if (!is_dir($storageDirectory) && !mkdir($storageDirectory, 0755, true) && !is_dir($storageDirectory)) {
    http_response_code(500);
    error_log('Task upload directory could not be created.');
    echo json_encode(["status" => "error", "message" => "Folder penyimpanan tugas gagal dibuat. Pastikan folder proyek dapat ditulis oleh PHP."]);
    exit;
}

if (!is_writable($storageDirectory)) {
    http_response_code(500);
    error_log('Task upload directory is not writable.');
    echo json_encode(["status" => "error", "message" => "Folder storage/tugas tidak dapat ditulis. Atur izin folder agar PHP dapat menyimpan file."]);
    exit;
}

$storedName = bin2hex(random_bytes(24));
$destination = $storageDirectory . DIRECTORY_SEPARATOR . $storedName;
if (!move_uploaded_file($file['tmp_name'], $destination)) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "File gagal disimpan."]);
    exit;
}

$stmt = $conn->prepare("INSERT INTO pengumpulan_tugas (user_id, modul, original_name, stored_name, file_size) VALUES (?, ?, ?, ?, ?)");
$fileSize = (int) $file['size'];
$stmt->bind_param("iissi", $userId, $module, $originalName, $storedName, $fileSize);
if (!$stmt->execute()) {
    unlink($destination);
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Data tugas gagal disimpan."]);
    exit;
}

echo json_encode(["status" => "success", "message" => "File tugas berhasil dikirim."]);
?>