<?php
session_start();

$host = "localhost";
$user = "root";
$pass = "";
$db   = "bim";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Koneksi database gagal"]));
}
?>