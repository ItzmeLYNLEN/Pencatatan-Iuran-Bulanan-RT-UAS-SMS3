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

$bulan_nama = [1=>'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
$jumlah_lunas = count($pembayaran_lunas);
$progress_percentage = round(($jumlah_lunas / 12) * 100);
?>
<style>
    .main-container {
        padding: 40px;
        background: #ffffff;
        border-radius: 20px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.05);
        border: 1px solid #e9ecef;
    }
    .progress-meter { width: 180px; height: 180px; position: relative; }
    .progress-meter .progress-text { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 2.5rem; font-weight: 600; color: #343a40; }
    .progress-meter .progress-subtext { font-size: 0.9rem; font-weight: 400; color: #6c757d; }
    
    
    .month-grid { 
        display: grid; 
        grid-template-columns: repeat(6, 1fr); 
        gap: 15px; 
    }
    
    .month-card { background: #f8f9fa; border-radius: 15px; text-align: center; padding: 15px; border: 1px solid #e9ecef; transition: all 0.3s ease; cursor: pointer; color: #6c757d; }
    .month-card.lunas { background: #d1e7dd; border-color: #b2dfc8; color: #0a3622; cursor: default; }
    .month-card.tunggakan:hover { background: #e9ecef; transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
    .month-card .month-name { font-weight: 600; color: #343a40; }
    .month-card .status-icon { font-size: 2rem; margin: 10px 0; }
    .month-card .status-text { font-size: 0.8rem; font-weight: 500; text-transform: uppercase; }

    
    @media (max-width: 768px) {
        .month-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

<div class="main-container">
    <div class="row align-items-center">
        <div class="col-md-4 text-center mb-4 mb-md-0">
            <h4>Progres Tahunan</h4>
            <div class="progress-meter mx-auto">
                <svg width="180" height="180" viewBox="0 0 120 120">
                    <circle cx="60" cy="60" r="54" fill="none" stroke="#e9ecef" stroke-width="12" />
                    <circle cx="60" cy="60" r="54" fill="none" stroke="#0d6efd" stroke-width="12"
                            stroke-dasharray="<?php echo 2 * pi() * 54; ?>"
                            stroke-dashoffset="<?php echo (2 * pi() * 54) * (1 - ($progress_percentage / 100)); ?>"
                            transform="rotate(-90 60 60)" style="transition: stroke-dashoffset 1s ease-out;" />
                </svg>
                <div class="progress-text">
                    <?php echo $jumlah_lunas; ?><span class="progress-subtext">/12</span>
                </div>
            </div>
             <p class="mt-2 mb-0 text-muted">Bulan Telah Lunas</p>
        </div>
        <div class="col-md-8">
            <h4>Status Pembayaran <?php echo $tahun_sekarang; ?></h4>
            <div class="month-grid">
                <?php for ($bulan = 1; $bulan <= 12; $bulan++): 
                    $is_lunas = in_array($bulan, $pembayaran_lunas);
                ?>
                    <a href="<?php echo $is_lunas ? 'javascript:void(0)' : 'simulasi_bayar.php?bulan='.$bulan.'&tahun='.$tahun_sekarang; ?>" 
                       class="text-decoration-none">
                        <div class="month-card <?php echo $is_lunas ? 'lunas' : 'tunggakan'; ?>">
                            <div class="month-name"><?php echo $bulan_nama[$bulan]; ?></div>
                            <?php if ($is_lunas): ?>
                                <div class="status-icon"><i class="fas fa-check-circle"></i></div>
                                <div class="status-text">LUNAS</div>
                            <?php else: ?>
                                <div class="status-icon"><i class="fas fa-money-bill-wave"></i></div>
                                <div class="status-text">BAYAR</div>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endfor; ?>
            </div>
        </div>
    </div>
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