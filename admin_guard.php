<?php
function ensureApplicationTables($conn) {
    $columns = $conn->query("SHOW COLUMNS FROM user LIKE 'role'");
    if ($columns && $columns->num_rows === 0) {
        $conn->query("ALTER TABLE user ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'siswa'");
    }

    $conn->query("CREATE TABLE IF NOT EXISTS materi (
        id INT AUTO_INCREMENT PRIMARY KEY,
        judul VARCHAR(180) NOT NULL,
        ringkasan TEXT NOT NULL,
        kategori VARCHAR(80) NOT NULL DEFAULT 'BIM',
        isi TEXT NOT NULL,
        video_url VARCHAR(500) NOT NULL DEFAULT '',
        published TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS progres_siswa (
        user_id INT NOT NULL,
        modul INT NOT NULL,
        progress TINYINT UNSIGNED NOT NULL DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id, modul)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function requireTeacher($conn) {
    ensureApplicationTables($conn);
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'guru') {
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Akses khusus guru diperlukan."]);
        exit;
    }
}
?>
