<?php
date_default_timezone_set('Asia/Jakarta');
include 'templates/header.php';

if (!isset($_GET['bulan']) || !isset($_GET['tahun'])) {
    die("Error: Periode pembayaran tidak valid.");
}

$bulan = (int)$_GET['bulan'];
$tahun = (int)$_GET['tahun'];
$id_warga = $_SESSION['user_id'];
$nama_lengkap = $_SESSION['nama_lengkap'];
$iuran_per_bulan = 50000;
$bulan_nama = date('F', mktime(0, 0, 0, $bulan, 10));
$order_id = "INV-" . $tahun . str_pad($bulan, 2, '0', STR_PAD_LEFT) . "-" . $id_warga . "-" . time();


$dummy_va_number = "78108" . mt_rand(100000000, 999999999); 
$dummy_qris_image_url = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=PEMBAYARAN-" . $order_id; 
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h4>Detail Tagihan Iuran</h4></div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr><td>ID Pesanan</td><td><strong><?php echo $order_id; ?></strong></td></tr>
                    <tr><td>Periode</td><td><strong><?php echo $bulan_nama . ' ' . $tahun; ?></strong></td></tr>
                    <tr><td>Atas Nama</td><td><strong><?php echo htmlspecialchars($nama_lengkap); ?></strong></td></tr>
                    <tr class="table-primary"><td><strong>Jumlah</strong></td><td><strong>Rp <?php echo number_format($iuran_per_bulan, 0, ',', '.'); ?></strong></td></tr>
                </table>
                <p class="text-center">Silakan lanjutkan ke halaman pembayaran yang aman untuk menyelesaikan transaksi Anda.</p>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#paymentModal">
                        Lanjutkan ke Pembayaran
                    </button>
                    <a href="index.php" class="btn btn-secondary">Batal</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="paymentModalLabel"><i class="fas fa-shield-alt"></i>Halaman Pembayaran</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-5">
                        <h6>Pilih Metode Pembayaran:</h6>
                        <div class="list-group">
                            <label class="list-group-item list-group-item-action">
                                <input type="radio" name="payment_method" value="va" checked> 
                                <i class="fas fa-university"></i> Virtual Account
                            </label>
                            <label class="list-group-item list-group-item-action">
                                <input type="radio" name="payment_method" value="qris"> 
                                <i class="fas fa-qrcode"></i> QRIS
                            </label>
                        </div>
                        <hr>
                        <h5>Total Bayar:</h5>
                        <h3>Rp <?php echo number_format($iuran_per_bulan, 0, ',', '.'); ?></h3>
                    </div>

                    <div class="col-md-7">
                        <div id="va-details" class="payment-details">
                            <h5>Pembayaran via Virtual Account</h5>
                            <p class="small text-muted">Selesaikan pembayaran Anda ke nomor Virtual Account di bawah ini.</p>
                            <div class="card bg-light">
                                <div class="card-body">
                                    <p class="mb-1"><strong>BANK MANDIRI</strong></p>
                                    <h4 class="font-monospace"><?php echo $dummy_va_number; ?></h4>
                                    <p class="mb-0">Tambah Keterangan Atas Nama: <?php echo htmlspecialchars($nama_lengkap); ?></p>
                                </div>
                            </div>
                        </div>

                        <div id="qris-details" class="payment-details" style="display: none;">
                            <h5>Pembayaran via QRIS</h5>
                            <p class="small text-muted">Scan QR Code di bawah ini menggunakan aplikasi e-wallet atau mobile banking Anda.</p>
                            <div class="text-center">
                                <img src="<?php echo $dummy_qris_image_url; ?>" alt="QRIS Code" class="img-fluid border rounded">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <form method="POST" action="payment_notification.php" class="w-100">
                    <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                    <input type="hidden" name="id_warga" value="<?php echo $id_warga; ?>">
                    <input type="hidden" name="bulan" value="<?php echo $bulan; ?>">
                    <input type="hidden" name="tahun" value="<?php echo $tahun; ?>">
                    <input type="hidden" name="jumlah" value="<?php echo $iuran_per_bulan; ?>">
                    <input type="hidden" name="payment_status" value="success">
                    <div class="d-grid">
                        <button type="submit" class="btn btn-success btn-lg">Selesaikan Pembayaran</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const paymentRadios = document.querySelectorAll('input[name="payment_method"]');
    const paymentDetails = document.querySelectorAll('.payment-details');

    paymentRadios.forEach(radio => {
        radio.addEventListener('change', function () {
            
            paymentDetails.forEach(detail => {
                detail.style.display = 'none';
            });

            
            const selectedDetail = document.getElementById(this.value + '-details');
            if (selectedDetail) {
                selectedDetail.style.display = 'block';
            }
        });
    });
});
</script>

<?php include 'templates/footer.php'; ?>