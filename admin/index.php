<?php
include 'templates/header.php';
$total_warga = $conn->query("SELECT COUNT(*) as total FROM warga WHERE role='warga'")->fetch_assoc()['total'];
$total_pembayaran_bulan_ini = $conn->query("SELECT SUM(jumlah) as total FROM pembayaran WHERE MONTH(tanggal_bayar) = MONTH(CURDATE()) AND YEAR(tanggal_bayar) = YEAR(CURDATE())")->fetch_assoc()['total'];
?>
<h3>Dashboard Admin</h3>
<hr>
<p>Selamat datang di halaman dashboard admin. Di sini Anda dapat mengelola data iuran RT 04 Perum. Kahuripan Mas.</p>
<div class="row">
    <div class="col-md-6">
        <div class="card text-white bg-primary mb-3">
            <div class="card-header">Total Warga RT 04 Yang Terdaftar</div>
            <div class="card-body">
                <h5 class="card-title"><?php echo $total_warga; ?> Warga</h5>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card text-white bg-success mb-3">
            <div class="card-header">Total Iuran Bulan Ini</div>
            <div class="card-body">
                <h5 class="card-title">Rp <?php echo number_format($total_pembayaran_bulan_ini ?? 0, 0, ',', '.'); ?></h5>
            </div>
        </div>
    </div>
</div>
<?php include 'templates/footer.php'; ?>