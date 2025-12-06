<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../config/db.php';
date_default_timezone_set('Asia/Jakarta');

if ($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_SESSION['id_warga'])) {
    header("Location: index.php");
    exit;
}

$id_warga = $_SESSION['id_warga'];
$bulan = (int)$_POST['bulan'];
$tahun = (int)$_POST['tahun'];
$periode = $tahun . '-' . str_pad($bulan, 2, '0', STR_PAD_LEFT);
$status = $_POST['payment_status'];

if ($status == 'success') {
    $cek = $conn->prepare("SELECT id_transaksi FROM transaksi WHERE id_warga = ? AND periode_tagihan = ?");
    $cek->bind_param("is", $id_warga, $periode);
    $cek->execute();
    
    if ($cek->get_result()->num_rows == 0) {
        $kode = 'INV-ON-' . date('YmdHis') . '-' . $id_warga;
        
        $total = 50000; 

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO transaksi (kode_transaksi, id_warga, id_admin, periode_tagihan, total_tagihan, metode_pembayaran, tanggal_bayar) VALUES (?, ?, NULL, ?, ?, 'online', NOW())");
            $stmt->bind_param("sisd", $kode, $id_warga, $periode, $total);
            $stmt->execute();
            $id_transaksi = $conn->insert_id;

            $nama_item = 'Iuran Warga (Keamanan & Sampah)';
            $stmt_d = $conn->prepare("INSERT INTO detail_transaksi (id_transaksi, nama_item, jumlah_biaya) VALUES (?, ?, ?)");
            $stmt_d->bind_param("isd", $id_transaksi, $nama_item, $total);
            $stmt_d->execute();

            $conn->commit();
            $_SESSION['flash_message'] = ['type' => 'success','title' => 'Berhasil','message' => 'Pembayaran Online Diterima.'];
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['flash_message'] = ['type' => 'error','title' => 'Gagal','message' => 'Error database.'];
        }
    } else {
        $_SESSION['flash_message'] = ['type' => 'info','title' => 'Info','message' => 'Tagihan sudah lunas sebelumnya.'];
    }
}
header("Location: index.php");
exit;
?>