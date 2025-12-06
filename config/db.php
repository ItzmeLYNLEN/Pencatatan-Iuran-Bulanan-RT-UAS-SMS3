<?php


$host = 'localhost';
$user = 'root';
$pass = ''; 
$db   = 'db_iuran_rt';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi ke database gagal: " . $conn->connect_error);
}


if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>