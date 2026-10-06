<?php
if (!function_exists('ensureColumnExists')) {
function ensureColumnExists($conn, $table, $column, $definition) {
    $check = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($check && $check->num_rows === 0) {
        $conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
}

}

if (!function_exists('ensureApplicationTables')) {
function ensureApplicationTables($conn) {
    if (!$conn || !($conn instanceof mysqli)) {
        return;
    }

    $tables = [
        'admin' => "CREATE TABLE IF NOT EXISTS `admin` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nama VARCHAR(120) NOT NULL,
            email VARCHAR(160) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        'progres_siswa' => "CREATE TABLE IF NOT EXISTS `progres_siswa` (
            user_id INT NOT NULL,
            modul INT NOT NULL,
            progress TINYINT UNSIGNED NOT NULL DEFAULT 0,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id, modul)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];

    foreach ($tables as $table => $sql) {
        $conn->query($sql);
    }

    $modulTableCheck = $conn->query("SHOW TABLES LIKE 'materi'");
    if ($modulTableCheck && $modulTableCheck->num_rows > 0) {
        $legacyColumns = ['deskripsi', 'file_path', 'modul'];
        foreach ($legacyColumns as $column) {
            $exists = $conn->query("SHOW COLUMNS FROM `materi` LIKE '$column'");
            if ($exists && $exists->num_rows === 0) {
                $definitions = [
                    'deskripsi' => 'TEXT NULL',
                    'file_path' => 'VARCHAR(255) NOT NULL DEFAULT ""',
                    'modul' => 'INT NOT NULL DEFAULT 1'
                ];
                if (isset($definitions[$column])) {
                    $conn->query("ALTER TABLE `materi` ADD COLUMN `$column` {$definitions[$column]}");
                }
            }
        }
    }

    $userCheck = $conn->query("SHOW TABLES LIKE 'user'");
    if ($userCheck && $userCheck->num_rows === 0) {
        $conn->query("CREATE TABLE IF NOT EXISTS `user` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nama VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(20) NOT NULL DEFAULT 'siswa'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    ensureColumnExists($conn, 'user', 'role', "VARCHAR(20) NOT NULL DEFAULT 'siswa'");
    $conn->query("UPDATE `user` SET role = 'siswa' WHERE role IS NULL OR TRIM(role) = ''");

    $conn->query("CREATE TABLE IF NOT EXISTS `pengumpulan_tugas` (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        modul TINYINT UNSIGNED NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        stored_name CHAR(48) NOT NULL UNIQUE,
        file_size BIGINT UNSIGNED NOT NULL,
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_pengumpulan_user (user_id),
        INDEX idx_pengumpulan_modul (modul)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $materiExists = $conn->query("SHOW TABLES LIKE 'materi'");
    if ($materiExists && $materiExists->num_rows === 0) {
        $conn->query("CREATE TABLE IF NOT EXISTS `materi` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            judul VARCHAR(255) NOT NULL,
            ringkasan TEXT NOT NULL,
            kategori VARCHAR(80) NOT NULL DEFAULT 'BIM',
            isi TEXT NOT NULL,
            video_url VARCHAR(500) NOT NULL DEFAULT '',
            published TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    ensureColumnExists($conn, 'materi', 'modul', "INT NOT NULL DEFAULT 1");
    ensureColumnExists($conn, 'materi', 'ringkasan', "TEXT NOT NULL");
    ensureColumnExists($conn, 'materi', 'kategori', "VARCHAR(80) NOT NULL DEFAULT 'BIM'");
    ensureColumnExists($conn, 'materi', 'isi', "TEXT NOT NULL");
    ensureColumnExists($conn, 'materi', 'video_url', "VARCHAR(500) NOT NULL DEFAULT ''");
    ensureColumnExists($conn, 'materi', 'published', "TINYINT(1) NOT NULL DEFAULT 1");
    ensureColumnExists($conn, 'materi', 'updated_at', "TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");

    $legacyDeskripsi = $conn->query("SHOW COLUMNS FROM `materi` LIKE 'deskripsi'");
    if ($legacyDeskripsi && $legacyDeskripsi->num_rows > 0) {
        $conn->query("UPDATE `materi` SET `ringkasan` = COALESCE(NULLIF(TRIM(`deskripsi`), ''), `ringkasan`) WHERE `ringkasan` IS NULL OR TRIM(`ringkasan`) = ''");
        $conn->query("UPDATE `materi` SET `isi` = COALESCE(NULLIF(TRIM(`isi`), ''), COALESCE(NULLIF(TRIM(`deskripsi`), ''), '')) WHERE `isi` IS NULL OR TRIM(`isi`) = ''");
    }

    $legacyFilePath = $conn->query("SHOW COLUMNS FROM `materi` LIKE 'file_path'");
    if ($legacyFilePath && $legacyFilePath->num_rows > 0) {
        $conn->query("UPDATE `materi` SET `video_url` = COALESCE(NULLIF(TRIM(`video_url`), ''), COALESCE(NULLIF(TRIM(`file_path`), ''), '')) WHERE `video_url` IS NULL OR TRIM(`video_url`) = ''");
    }

    $conn->query("UPDATE `materi` SET `modul` = 1 WHERE `modul` IS NULL OR `modul` < 1 OR `modul` > 4");
    $conn->query("UPDATE `materi` SET `ringkasan` = 'Ringkasan belum diisi' WHERE `ringkasan` IS NULL OR TRIM(`ringkasan`) = ''");
    $conn->query("UPDATE `materi` SET `kategori` = 'BIM' WHERE `kategori` IS NULL OR TRIM(`kategori`) = ''");
    $conn->query("UPDATE `materi` SET `isi` = `ringkasan` WHERE `isi` IS NULL OR TRIM(`isi`) = ''");
    $conn->query("UPDATE `materi` SET `video_url` = '' WHERE `video_url` IS NULL");
    $conn->query("UPDATE `materi` SET `published` = 1 WHERE `published` IS NULL");

    $profileCheck = $conn->query("SHOW TABLES LIKE 'profil_siswa'");
    if ($profileCheck && $profileCheck->num_rows === 0) {
        $conn->query("CREATE TABLE IF NOT EXISTS `profil_siswa` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL UNIQUE,
            nis VARCHAR(40) DEFAULT '',
            kelas VARCHAR(40) DEFAULT '',
            jurusan VARCHAR(80) DEFAULT '',
            jenis_kelamin VARCHAR(20) DEFAULT '',
            sekolah VARCHAR(120) DEFAULT '',
            telepon VARCHAR(30) DEFAULT '',
            alamat TEXT DEFAULT NULL,
            foto VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_profil_user FOREIGN KEY (user_id) REFERENCES `user`(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    ensureColumnExists($conn, 'profil_siswa', 'nis', "VARCHAR(40) NOT NULL DEFAULT ''");
    ensureColumnExists($conn, 'profil_siswa', 'kelas', "VARCHAR(40) NOT NULL DEFAULT ''");
    ensureColumnExists($conn, 'profil_siswa', 'jurusan', "VARCHAR(80) NOT NULL DEFAULT ''");
    ensureColumnExists($conn, 'profil_siswa', 'jenis_kelamin', "VARCHAR(20) NOT NULL DEFAULT ''");
    ensureColumnExists($conn, 'profil_siswa', 'sekolah', "VARCHAR(120) NOT NULL DEFAULT ''");
    ensureColumnExists($conn, 'profil_siswa', 'telepon', "VARCHAR(30) NOT NULL DEFAULT ''");
    ensureColumnExists($conn, 'profil_siswa', 'foto', "VARCHAR(255) DEFAULT NULL");
    ensureColumnExists($conn, 'profil_siswa', 'updated_at', "TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");

    $legacyNisn = $conn->query("SHOW COLUMNS FROM `profil_siswa` LIKE 'nisn'");
    if ($legacyNisn && $legacyNisn->num_rows > 0) {
        $conn->query("UPDATE `profil_siswa` SET `nis` = COALESCE(NULLIF(TRIM(`nis`), ''), `nisn`) WHERE `nis` IS NULL OR TRIM(`nis`) = ''");
    }

    $conn->query("UPDATE `profil_siswa` SET `nis` = '' WHERE `nis` IS NULL");
    $conn->query("UPDATE `profil_siswa` SET `kelas` = '' WHERE `kelas` IS NULL");
    $conn->query("UPDATE `profil_siswa` SET `jurusan` = '' WHERE `jurusan` IS NULL");
    $conn->query("UPDATE `profil_siswa` SET `jenis_kelamin` = '' WHERE `jenis_kelamin` IS NULL");
    $conn->query("UPDATE `profil_siswa` SET `sekolah` = '' WHERE `sekolah` IS NULL");
    $conn->query("UPDATE `profil_siswa` SET `telepon` = '' WHERE `telepon` IS NULL");
}

}

if (!function_exists('requireTeacher')) {
function requireTeacher($conn) {
    ensureApplicationTables($conn);
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'guru') {
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Akses khusus guru diperlukan."]);
        exit;
    }
}

}
?>
