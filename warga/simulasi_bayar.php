<?php
include 'templates/header.php';

if (!isset($_GET['bulan']) || !isset($_GET['tahun'])) {
    die("Error: Periode pembayaran tidak valid.");
}

$bulan = (int)$_GET['bulan'];
$tahun = (int)$_GET['tahun'];
$id_warga = $_SESSION['id_warga'];
$nama_lengkap = $_SESSION['nama_lengkap'];

$list_tagihan = [
    ['nama' => 'Iuran Warga (Keamanan & Sampah)', 'jumlah' => 50000]
];

$iuran_per_bulan = 0;
foreach ($list_tagihan as $item) {
    $iuran_per_bulan += $item['jumlah'];
}

$bulan_nama = date('F', mktime(0, 0, 0, $bulan, 10));
$order_id = "INV-" . $tahun . str_pad($bulan, 2, '0', STR_PAD_LEFT) . "-" . $id_warga;
$dummy_va_number = "78108" . substr(str_shuffle("0123456789"), 0, 10);
$dummy_qris_image_url = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=PEMBAYARAN-" . $order_id;
$expiration_time = time() + (15 * 60);
?>
<style>
    .payment-method-card { padding: 15px; border-radius: 10px; border: 2px solid #eee; cursor: pointer; transition: all 0.3s ease; }
    .payment-method-card:hover { border-color: #0d6efd; }
    .payment-method-card.active { border-color: #0d6efd; background-color: #e7f1ff; }
    .payment-method-card input[type="radio"] { display: none; }
</style>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0" style="border-radius: 1rem;">
            <div class="card-body p-4">
                <div class="row">
                    <div class="col-lg-5 mb-4 mb-lg-0 border-end-lg">
                        <h3 class="mb-4">Ringkasan Tagihan</h3>
                        <ul class="list-group list-group-flush mb-3">
                            <li class="list-group-item d-flex justify-content-between px-0"><span>ID Pesanan</span><strong><?php echo $order_id; ?></strong></li>
                            <li class="list-group-item d-flex justify-content-between px-0"><span>Atas Nama</span><strong><?php echo htmlspecialchars($nama_lengkap); ?></strong></li>
                            <li class="list-group-item d-flex justify-content-between px-0"><span>Periode</span><strong><?php echo $bulan_nama . ' ' . $tahun; ?></strong></li>
                        </ul>

                        <h6 class="text-muted mb-2">RINCIAN ITEM:</h6>
                        <ul class="list-group list-group-flush small mb-3">
                            <?php foreach ($list_tagihan as $item): ?>
                            <li class="list-group-item d-flex justify-content-between px-0 bg-transparent py-1">
                                <span><?php echo htmlspecialchars($item['nama']); ?></span>
                                <span>Rp <?php echo number_format($item['jumlah'], 0, ',', '.'); ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>

                        <div class="bg-light p-3 rounded-3 text-center mt-3">
                            <h6 class="text-muted mb-1">TOTAL PEMBAYARAN</h6>
                            <h2 class="display-6 fw-bold text-primary">Rp <?php echo number_format($iuran_per_bulan, 0, ',', '.'); ?></h2>
                        </div>
                        <div class="d-grid gap-2 mt-4">
                            <button id="btn-pilih-bayar" class="btn btn-primary btn-lg"><i class="fas fa-credit-card me-2"></i>Pilih Metode Pembayaran</button>
                            <a href="index.php" class="btn btn-outline-secondary btn-sm">Batal</a>
                        </div>
                    </div>
                    
                    <div class="col-lg-7" id="payment-options-panel" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="mb-0">Pilih Metode Pembayaran</h4>
                            <div class="fw-bold text-danger"><i class="fas fa-clock"></i> <span id="countdown">15:00</span></div>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-6">
                                <label class="payment-method-card text-center active" id="label-va">
                                    <input type="radio" name="payment_method" value="va" checked><i class="fas fa-university fa-2x mb-2 text-primary"></i><div>Virtual Account</div>
                                </label>
                            </div>
                            <div class="col-6">
                                <label class="payment-method-card text-center" id="label-qris">
                                    <input type="radio" name="payment_method" value="qris"><i class="fas fa-qrcode fa-2x mb-2 text-primary"></i><div>QRIS</div>
                                </label>
                            </div>
                        </div>
                        <div id="va-details" class="payment-details">
                            <h5 class="fw-normal"><i class="fas fa-file-invoice-dollar"></i> Pembayaran via Virtual Account</h5>
                            <div class="card bg-light border-0 p-3 rounded-3 font-monospace text-center mt-3">
                                <small>BANK MANDIRI</small>
                                <h3 class="fw-bold mb-0 text-primary"><?php echo chunk_split($dummy_va_number, 4, ' '); ?></h3>
                            </div>
                        </div>
                        <div id="qris-details" class="payment-details" style="display: none;">
                            <h5 class="fw-normal"><i class="fas fa-camera"></i> Pembayaran via QRIS</h5>
                            <div class="text-center bg-white p-3 rounded-3 border mt-3">
                                <img src="<?php echo $dummy_qris_image_url; ?>" alt="QRIS Code" class="img-fluid">
                            </div>
                        </div>
                        <form method="POST" action="payment_notification.php" class="mt-4">
                            <input type="hidden" name="id_warga" value="<?php echo $id_warga; ?>">
                            <input type="hidden" name="bulan" value="<?php echo $bulan; ?>">
                            <input type="hidden" name="tahun" value="<?php echo $tahun; ?>">
                            <input type="hidden" name="payment_status" value="success">
                            <div class="d-grid"><button id="btn-selesaikan" type="submit" class="btn btn-success btn-lg fw-bold">Selesaikan Pembayaran</button></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function(){
    const btnPilihBayar=document.getElementById('btn-pilih-bayar');
    const paymentOptionsPanel=document.getElementById('payment-options-panel');
    const countdownEl=document.getElementById('countdown');
    const btnSelesaikan=document.getElementById('btn-selesaikan');
    const expirationTime=<?php echo $expiration_time; ?>*1000;
    let timerInterval;
    btnPilihBayar.addEventListener('click',function(){
        paymentOptionsPanel.style.display='block';
        this.style.display='none';
        timerInterval=setInterval(updateCountdown,1000);
        updateCountdown();
    });
    const paymentRadios=document.querySelectorAll('input[name="payment_method"]');
    paymentRadios.forEach(radio=>{
        radio.addEventListener('change',function(){
            document.querySelectorAll('.payment-method-card').forEach(c=>c.classList.remove('active'));
            document.getElementById('label-'+this.value).classList.add('active');
            document.querySelectorAll('.payment-details').forEach(d=>d.style.display='none');
            document.getElementById(this.value+'-details').style.display='block';
        });
    });
    function updateCountdown(){
        const now=new Date().getTime();
        const distance=expirationTime-now;
        if(distance<0){
            clearInterval(timerInterval);
            countdownEl.textContent="Waktu Habis";
            btnSelesaikan.disabled=true;
            return;
        }
        const minutes=Math.floor((distance%(1000*60*60))/(1000*60));
        const seconds=Math.floor((distance%(1000*60))/1000);
        countdownEl.textContent=`${minutes.toString().padStart(2,'0')}:${seconds.toString().padStart(2,'0')}`;
    }
});
</script>