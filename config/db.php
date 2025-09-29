<?php
// config/db.php

$host = 'localhost';
$user = 'root';
$pass = ''; // Kosongkan jika tidak ada password
$db   = 'db_iuran_rt';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi ke database gagal: " . $conn->connect_error);
}

// Mulai session di sini agar tersedia di semua halaman
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>