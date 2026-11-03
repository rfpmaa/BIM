<?php
header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

if (session_status() === PHP_SESSION_NONE) {
    session_name('BIM_ADMIN_SESSION');
    session_start();
}

require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/admin_guard.php';

function quizError($message, $statusCode) {
    http_response_code($statusCode);
    echo json_encode(["status" => "error", "message" => $message]);
    exit;
}

function ensureQuizTables($conn) {
    $questionsTable = $conn->query("CREATE TABLE IF NOT EXISTS `latihan_soal` (
        id INT AUTO_INCREMENT PRIMARY KEY,
        modul TINYINT UNSIGNED NOT NULL,
        pertanyaan TEXT NOT NULL,
        opsi_json TEXT NOT NULL,
        jawaban TINYINT UNSIGNED NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_latihan_modul (modul)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    if (!$questionsTable) {
        quizError("Tabel soal latihan gagal disiapkan.", 500);
    }

    $statusTable = $conn->query("CREATE TABLE IF NOT EXISTS `latihan_soal_status` (
        id TINYINT UNSIGNED PRIMARY KEY,
        default_seeded TINYINT(1) NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    if (!$statusTable || !$conn->query("INSERT IGNORE INTO `latihan_soal_status` (id, default_seeded) VALUES (1, 0)")) {
        quizError("Status soal latihan gagal disiapkan.", 500);
    }

    if (!$conn->begin_transaction()) {
        quizError("Inisialisasi soal latihan gagal.", 500);
    }

    $statusResult = $conn->query("SELECT default_seeded FROM `latihan_soal_status` WHERE id = 1 FOR UPDATE");
    if (!$statusResult) {
        $conn->rollback();
        quizError("Status soal latihan gagal dibaca.", 500);
    }
    $status = $statusResult->fetch_assoc();

    if (!$status || (int) $status['default_seeded'] === 0) {
        $countResult = $conn->query("SELECT COUNT(*) AS total FROM `latihan_soal`");
        if (!$countResult) {
            $conn->rollback();
            quizError("Daftar soal latihan gagal dibaca.", 500);
        }

        if ((int) $countResult->fetch_assoc()['total'] === 0) {
            $defaultsJson = file_get_contents(__DIR__ . '/latihan_default.json');
            if ($defaultsJson === false) {
                $conn->rollback();
                quizError("Data soal bawaan tidak dapat dibaca.", 500);
            }
            $defaults = json_decode($defaultsJson, true);
            if (!is_array($defaults)) {
                $conn->rollback();
                quizError("Data soal bawaan tidak valid.", 500);
            }

            $insert = $conn->prepare("INSERT INTO `latihan_soal` (modul, pertanyaan, opsi_json, jawaban) VALUES (?, ?, ?, ?)");
            if (!$insert) {
                $conn->rollback();
                quizError("Soal bawaan gagal disiapkan.", 500);
            }
            foreach ($defaults as $question) {
                $modul = (int) ($question['modul'] ?? 0);
                $text = (string) ($question['pertanyaan'] ?? '');
                $options = json_encode($question['options'] ?? []);
                $answer = (int) ($question['answer'] ?? -1);
                if ($modul < 1 || $modul > 4 || $text === '' || !is_array($question['options'] ?? null) || count($question['options']) !== 4 || !is_string($options) || $answer < 0 || $answer > 3) {
                    $conn->rollback();
                    quizError("Data soal bawaan tidak valid.", 500);
                }
                $insert->bind_param("issi", $modul, $text, $options, $answer);
                if (!$insert->execute()) {
                    $conn->rollback();
                    quizError("Soal bawaan gagal disimpan.", 500);
                }
            }
            $insert->close();
        }

        if (!$conn->query("UPDATE `latihan_soal_status` SET default_seeded = 1 WHERE id = 1") || !$conn->commit()) {
            $conn->rollback();
            quizError("Inisialisasi soal latihan gagal disimpan.", 500);
        }
    } elseif (!$conn->commit()) {
        quizError("Inisialisasi soal latihan gagal.", 500);
    }
}

ensureQuizTables($conn);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $result = $conn->query("SELECT id, modul, pertanyaan, opsi_json, jawaban FROM `latihan_soal` ORDER BY modul ASC, id ASC");
    if (!$result) {
        quizError("Daftar soal gagal dimuat.", 500);
    }

    $questions = [];
    while ($row = $result->fetch_assoc()) {
        $options = json_decode($row['opsi_json'], true);
        if (!is_array($options)) {
            quizError("Data pilihan jawaban tidak valid.", 500);
        }
        $questions[] = [
            "id" => (int) $row['id'],
            "modul" => (int) $row['modul'],
            "pertanyaan" => $row['pertanyaan'],
            "options" => $options,
            "answer" => (int) $row['jawaban']
        ];
    }
    echo json_encode(["status" => "success", "data" => $questions]);
    exit;
}

requireTeacher($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawId = $_POST['id'] ?? '0';
    $rawModule = $_POST['modul'] ?? '';
    $rawQuestion = $_POST['pertanyaan'] ?? '';
    if (!is_string($rawId) || !is_string($rawModule) || !is_string($rawQuestion)) {
        quizError("Data soal tidak valid.", 422);
    }
    if (($rawId !== '' && !ctype_digit($rawId)) || !ctype_digit($rawModule)) {
        quizError("ID soal atau modul tidak valid.", 422);
    }
    $id = (int) $rawId;
    $modul = (int) $rawModule;
    $pertanyaan = trim($rawQuestion);
    $options = [];
    for ($index = 0; $index < 4; $index++) {
        $rawOption = $_POST['option_' . $index] ?? '';
        if (!is_string($rawOption)) {
            quizError("Pilihan jawaban tidak valid.", 422);
        }
        $options[] = trim($rawOption);
    }
    $rawAnswer = $_POST['answer'] ?? null;
    if (!is_string($rawAnswer)) {
        quizError("Jawaban benar tidak valid.", 422);
    }
    $answer = filter_var($rawAnswer, FILTER_VALIDATE_INT);

    if ($modul < 1 || $modul > 4) {
        quizError("Modul harus dipilih dari 1 sampai 4.", 422);
    }
    if ($pertanyaan === '' || strlen($pertanyaan) > 4000) {
        quizError("Pertanyaan wajib diisi dan maksimal 4000 karakter.", 422);
    }
    foreach ($options as $option) {
        if ($option === '' || strlen($option) > 1000) {
            quizError("Keempat pilihan wajib diisi dan maksimal 1000 karakter.", 422);
        }
    }
    if ($answer === false || $answer < 0 || $answer > 3) {
        quizError("Pilih jawaban yang benar.", 422);
    }

    $optionsJson = json_encode($options);
    if ($optionsJson === false) {
        quizError("Pilihan jawaban tidak dapat diproses.", 422);
    }
    if ($id > 0) {
        $statement = $conn->prepare("UPDATE `latihan_soal` SET modul = ?, pertanyaan = ?, opsi_json = ?, jawaban = ? WHERE id = ?");
        if (!$statement) {
            quizError("Soal gagal disiapkan untuk diperbarui.", 500);
        }
        $statement->bind_param("issii", $modul, $pertanyaan, $optionsJson, $answer, $id);
        $message = "Soal berhasil diperbarui.";
    } else {
        $statement = $conn->prepare("INSERT INTO `latihan_soal` (modul, pertanyaan, opsi_json, jawaban) VALUES (?, ?, ?, ?)");
        if (!$statement) {
            quizError("Soal gagal disiapkan untuk disimpan.", 500);
        }
        $statement->bind_param("issi", $modul, $pertanyaan, $optionsJson, $answer);
        $message = "Soal berhasil ditambahkan.";
    }
    if (!$statement->execute()) {
        quizError("Soal gagal disimpan.", 500);
    }
    echo json_encode(["status" => "success", "message" => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents('php://input'), $input);
    $rawId = $_GET['id'] ?? $input['id'] ?? '0';
    if (!is_string($rawId) || !ctype_digit($rawId) || (int) $rawId <= 0) {
        quizError("ID soal tidak valid.", 422);
    }
    $id = (int) $rawId;
    $statement = $conn->prepare("DELETE FROM `latihan_soal` WHERE id = ?");
    if (!$statement) {
        quizError("Soal gagal disiapkan untuk dihapus.", 500);
    }
    $statement->bind_param("i", $id);
    if (!$statement->execute()) {
        quizError("Soal gagal dihapus.", 500);
    }
    echo json_encode(["status" => "success", "message" => "Soal berhasil dihapus."]);
    exit;
}

quizError("Metode tidak didukung.", 405);
?>
