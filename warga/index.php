<?php
include 'templates/header.php';

$id_warga = $_SESSION['user_id'];
$tahun_sekarang = date('Y');

$stmt = $conn->prepare("SELECT bulan FROM pembayaran WHERE id_warga = ? AND tahun = ?");
$stmt->bind_param("is", $id_warga, $tahun_sekarang);
$stmt->execute();
$result = $stmt->get_result();
$pembayaran_lunas = [];
while ($row = $result->fetch_assoc()) {
    $pembayaran_lunas[] = $row['bulan'];
}

$bulan_nama = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$iuran_per_bulan = 50000;
?>
<h3>Status Pembayaran Iuran Tahun <?php echo $tahun_sekarang; ?></h3><hr>
<?php if(isset($_GET['status']) && $_GET['status'] == 'sukses'): ?>
<div class="alert alert-success">Pembayaran berhasil dikonfirmasi!</div>
<?php endif; ?>
<div class="row">
    <?php for ($bulan = 1; $bulan <= 12; $bulan++): ?>
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><?php echo $bulan_nama[$bulan]; ?></h5>
                <p class="card-text">Jumlah Iuran: Rp <?php echo number_format($iuran_per_bulan, 0, ',', '.'); ?></p>
                <?php
                if (in_array($bulan, $pembayaran_lunas)) {
                    echo '<span class="btn btn-success disabled w-100">Lunas</span>';
                } else {
                    echo '<a href="simulasi_bayar.php?bulan='.$bulan.'&tahun='.$tahun_sekarang.'" class="btn btn-primary w-100">Bayar Sekarang</a>';
                }
                ?>
            </div>
        </div>
    </div>
    <?php endfor; ?>
</div>
<?php include 'templates/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (isset($_SESSION['flash_message'])): ?>
        const flashMessage = <?php echo json_encode($_SESSION['flash_message']); ?>;
        
        Swal.fire({
            title: flashMessage.title,
            text: flashMessage.message,
            icon: flashMessage.type,
            confirmButtonText: 'OK'
        });

        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>
});
</script>