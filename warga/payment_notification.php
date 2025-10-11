<?php
session_start();
include '../config/db.php';
date_default_timezone_set('Asia/Jakarta');

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: index.php?status=error_akses");
    exit;
}

$id_warga = (int)$_POST['id_warga'];
$bulan = (int)$_POST['bulan'];
$tahun = (int)$_POST['tahun'];
$jumlah = (int)$_POST['jumlah'];
$status = $_POST['payment_status'];
$tanggal_bayar = date('Y-m-d');

if ($status == 'success') {

    $cek_stmt = $conn->prepare("SELECT id_pembayaran FROM pembayaran WHERE id_warga = ? AND bulan = ? AND tahun = ?");
    $cek_stmt->bind_param("iii", $id_warga, $bulan, $tahun);
    $cek_stmt->execute();
    $result_cek = $cek_stmt->get_result();

    if ($result_cek->num_rows == 0) {
        $stmt_insert = $conn->prepare("INSERT INTO pembayaran (id_warga, bulan, tahun, jumlah, tanggal_bayar) VALUES (?, ?, ?, ?, ?)");
        $stmt_insert->bind_param("iiids", $id_warga, $bulan, $tahun, $jumlah, $tanggal_bayar);
        
        if ($stmt_insert->execute()) {
            header("Location: index.php?status=sukses_bayar");
            exit;
        } else {
            header("Location: index.php?status=gagal_db");
            exit;
        }
    } else {
        header("Location: index.php?status=sudah_dibayar");
        exit;
    }

} else {
    header("Location: index.php?status=gagal_bayar");
    exit;
}
?>