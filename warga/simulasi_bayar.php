<?php
// Mengatur zona waktu default ke Waktu Indonesia Barat (WIB)
date_default_timezone_set('Asia/Jakarta');

include 'templates/header.php';

// Memeriksa apakah parameter bulan dan tahun ada di URL
if (!isset($_GET['bulan']) || !isset($_GET['tahun'])) {
    die("Error: Periode pembayaran tidak valid.");
}

// Mengambil dan membersihkan data dari URL
$bulan = (int)$_GET['bulan'];
$tahun = (int)$_GET['tahun'];
$id_warga = $_SESSION['user_id'];
$iuran_per_bulan = 50000;
$bulan_nama = date('F', mktime(0, 0, 0, $bulan, 10)); // Mendapatkan nama bulan dalam bahasa Inggris

// Blok ini dieksekusi ketika form dikirim (tombol konfirmasi diklik)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Mengambil data dari form yang disubmit
    $post_bulan = (int)$_POST['bulan'];
    $post_tahun = (int)$_POST['tahun'];
    
    // Mengambil tanggal hari ini (sudah sesuai zona waktu Asia/Jakarta)
    $tanggal_bayar = date('Y-m-d');

    // 1. Cek apakah warga sudah pernah membayar untuk periode ini sebelumnya
    $cek_stmt = $conn->prepare("SELECT id_pembayaran FROM pembayaran WHERE id_warga = ? AND bulan = ? AND tahun = ?");
    $cek_stmt->bind_param("iii", $id_warga, $post_bulan, $post_tahun);
    $cek_stmt->execute();
    $result_cek = $cek_stmt->get_result();

    // 2. Jika belum ada data pembayaran (num_rows == 0), maka proses insert
    if ($result_cek->num_rows == 0) {
        $stmt_insert = $conn->prepare("INSERT INTO pembayaran (id_warga, bulan, tahun, jumlah, tanggal_bayar) VALUES (?, ?, ?, ?, ?)");
        $stmt_insert->bind_param("iiids", $id_warga, $post_bulan, $post_tahun, $iuran_per_bulan, $tanggal_bayar);
        
        // Jika insert berhasil, redirect ke halaman utama dengan status sukses
        if ($stmt_insert->execute()) {
            header("Location: index.php?status=sukses");
            exit;
        } else {
            // Jika gagal, tampilkan pesan error
            $error_message = "Terjadi kesalahan saat menyimpan data.";
        }
    } else {
        // Jika data pembayaran sudah ada, tampilkan pesan error
        $error_message = "Iuran untuk periode ini sudah pernah dibayar.";
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h4>Konfirmasi Pembayaran Iuran</h4></div>
            <div class="card-body">
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <p>Anda akan melakukan pembayaran untuk:</p>
                <table class="table">
                    <tr><td>Periode</td><td><strong><?php echo $bulan_nama . ' ' . $tahun; ?></strong></td></tr>
                    <tr><td>Atas Nama</td><td><strong><?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></strong></td></tr>
                    <tr><td>Jumlah</td><td><strong>Rp <?php echo number_format($iuran_per_bulan, 0, ',', '.'); ?></strong></td></tr>
                </table>

                <p class="text-center">Klik tombol di bawah untuk mengkonfirmasi bahwa Anda telah melakukan pembayaran.</p>

                <form method="POST" action="">
                    <input type="hidden" name="bulan" value="<?php echo $bulan; ?>">
                    <input type="hidden" name="tahun" value="<?php echo $tahun; ?>">
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">Konfirmasi Pembayaran</button>
                        <a href="index.php" class="btn btn-secondary">Batal</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>