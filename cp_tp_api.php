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
ensureApplicationTables($conn);

function respond($status, $message, $data = null) {
    http_response_code($status);
    $response = ["status" => $status >= 200 && $status < 300 ? "success" : "error"];
    if ($message !== '') $response["message"] = $message;
    if ($data !== null) $response["data"] = $data;
    echo json_encode($response);
    exit;
}

function validateLearningOutcome($input) {
    foreach (['no_soal', 'capaian_pembelajaran', 'tujuan_pembelajaran', 'kriteria_ketercapaian', 'kemampuan_kognitif', 'tingkat_kesukaran', 'skor'] as $field) {
        if (isset($input[$field]) && !is_scalar($input[$field])) {
            respond(422, "Format data tidak valid.");
        }
    }

    $noSoal = filter_var($input['no_soal'] ?? null, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
    $capaian = trim($input['capaian_pembelajaran'] ?? '');
    $tujuan = trim($input['tujuan_pembelajaran'] ?? '');
    $kriteria = trim($input['kriteria_ketercapaian'] ?? '');
    $cognitive = trim($input['kemampuan_kognitif'] ?? '');
    $difficulty = trim($input['tingkat_kesukaran'] ?? '');
    $scoreInput = trim((string) ($input['skor'] ?? ''));
    $score = filter_var($scoreInput, FILTER_VALIDATE_FLOAT);

    if ($noSoal === false || $capaian === '' || $tujuan === '' || $kriteria === '') {
        respond(422, "No soal, capaian pembelajaran, tujuan pembelajaran, dan kriteria ketercapaian wajib diisi.");
    }
    if (!in_array($cognitive, ['C1', 'C2', 'C3', 'C4', 'C5', 'C6'], true)) {
        respond(422, "Kemampuan kognitif harus dipilih dari C1 sampai C6.");
    }
    if (!in_array($difficulty, ['Md', 'Sd', 'Sr'], true)) {
        respond(422, "Tingkat kesukaran harus dipilih dari Md, Sd, atau Sr.");
    }
    if ($score === false || $score < 0 || $score > 999999.99 || !preg_match('/^\d+(?:\.\d{1,2})?$/', $scoreInput)) {
        respond(422, "Skor harus berupa angka antara 0 sampai 999999.99 dengan maksimal dua angka desimal.");
    }

    return [$noSoal, $capaian, $tujuan, $kriteria, $cognitive, $difficulty, $score];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $adminView = ($_GET['admin'] ?? '') === '1';
    if ($adminView) requireTeacher($conn);

    $sql = "SELECT id, no_soal, capaian_pembelajaran, tujuan_pembelajaran, kriteria_ketercapaian, kemampuan_kognitif, tingkat_kesukaran, skor, published
            FROM capaian_pembelajaran";
    if (!$adminView) $sql .= " WHERE published = 1";
    $sql .= " ORDER BY no_soal ASC, id ASC";
    $result = $conn->query($sql);
    if (!$result) respond(500, "Data capaian pembelajaran gagal dimuat.");

    $items = [];
    while ($row = $result->fetch_assoc()) $items[] = $row;
    respond(200, '', $items);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireTeacher($conn);
    if (isset($_POST['id']) && !is_scalar($_POST['id'])) respond(422, "ID data tidak valid.");
    $idInput = trim((string) ($_POST['id'] ?? ''));
    $id = $idInput === '' ? 0 : filter_var($idInput, FILTER_VALIDATE_INT);
    if ($id === false || $id < 0) respond(422, "ID data tidak valid.");
    $values = validateLearningOutcome($_POST);
    $published = isset($_POST['published']) ? 1 : 0;

    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE capaian_pembelajaran
            SET no_soal = ?, capaian_pembelajaran = ?, tujuan_pembelajaran = ?, kriteria_ketercapaian = ?,
                kemampuan_kognitif = ?, tingkat_kesukaran = ?, skor = ?, published = ?
            WHERE id = ?");
        if (!$stmt) respond(500, "Data capaian pembelajaran gagal disiapkan untuk diperbarui.");
        $stmt->bind_param("isssssdii", $values[0], $values[1], $values[2], $values[3], $values[4], $values[5], $values[6], $published, $id);
        $message = "Capaian dan tujuan pembelajaran diperbarui.";
    } else {
        $stmt = $conn->prepare("INSERT INTO capaian_pembelajaran
            (no_soal, capaian_pembelajaran, tujuan_pembelajaran, kriteria_ketercapaian, kemampuan_kognitif, tingkat_kesukaran, skor, published)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) respond(500, "Data capaian pembelajaran gagal disiapkan untuk disimpan.");
        $stmt->bind_param("isssssdi", $values[0], $values[1], $values[2], $values[3], $values[4], $values[5], $values[6], $published);
        $message = "Capaian dan tujuan pembelajaran ditambahkan.";
    }

    if (!$stmt->execute()) respond(500, "Capaian dan tujuan pembelajaran gagal disimpan.");
    respond(200, $message);
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    requireTeacher($conn);
    parse_str(file_get_contents('php://input'), $input);
    if (isset($_GET['id']) && !is_scalar($_GET['id'])) respond(422, "ID data tidak valid.");
    if (isset($input['id']) && !is_scalar($input['id'])) respond(422, "ID data tidak valid.");
    $id = filter_var($_GET['id'] ?? $input['id'] ?? 0, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
    if ($id === false) respond(422, "ID data tidak valid.");

    $stmt = $conn->prepare("DELETE FROM capaian_pembelajaran WHERE id = ?");
    if (!$stmt) respond(500, "Data gagal disiapkan untuk dihapus.");
    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) respond(500, "Capaian dan tujuan pembelajaran gagal dihapus.");
    if ($stmt->affected_rows === 0) respond(404, "Data tidak ditemukan.");
    respond(200, "Capaian dan tujuan pembelajaran dihapus.");
}

header("Allow: GET, POST, DELETE");
respond(405, "Metode tidak didukung.");
?>
