<?php
// ============================================================
// DB.PHP — Veritabanı Bağlantısı
// ============================================================
$host   = 'localhost';
$dbname = 'vanguard_db';
$user   = 'root';
$pass   = '';

$conn = new mysqli($host, $user, $pass, $dbname);
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    die('Veritabanı bağlantı hatası: ' . $conn->connect_error);
}
