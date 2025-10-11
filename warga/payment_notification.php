<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../config/db.php';
date_default_timezone_set('Asia/Jakarta');

if ($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$id_warga = (int)$_POST['id_warga'];
$bulan = (int)$_POST['bulan'];
$tahun = (int)$_POST['tahun'];
$jumlah = (int)$_POST['jumlah'];
$status = $_POST['payment_status'];

if ($id_warga != $_SESSION['user_id']) {
    header("Location: index.php");
    exit;
}

if ($status == 'success') {
    $cek_stmt = $conn->prepare("SELECT id_pembayaran FROM pembayaran WHERE id_warga = ? AND bulan = ? AND tahun = ?");
    $cek_stmt->bind_param("iii", $id_warga, $bulan, $tahun);
    $cek_stmt->execute();
    $result_cek = $cek_stmt->get_result();

    if ($result_cek->num_rows == 0) {
        $tanggal_bayar = date('Y-m-d', strtotime("$tahun-$bulan-01"));

        $stmt_insert = $conn->prepare("INSERT INTO pembayaran (id_warga, bulan, tahun, jumlah, tanggal_bayar) VALUES (?, ?, ?, ?, ?)");
        $stmt_insert->bind_param("iiids", $id_warga, $bulan, $tahun, $jumlah, $tanggal_bayar);
        
        if ($stmt_insert->execute()) {
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'title' => 'Pembayaran Berhasil!',
                'message' => 'Terima kasih, pembayaran Anda telah kami terima.'
            ];
        } else {
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'title' => 'Oops... Terjadi Kesalahan',
                'message' => 'Gagal menyimpan data pembayaran ke database.'
            ];
        }
    } else {
        $_SESSION['flash_message'] = [
            'type' => 'info',
            'title' => 'Informasi',
            'message' => 'Anda sudah membayar iuran untuk periode ini.'
        ];
    }
} else {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'title' => 'Pembayaran Gagal',
        'message' => 'Proses pembayaran Anda tidak berhasil diselesaikan.'
    ];
}

header("Location: index.php");
exit;
?>