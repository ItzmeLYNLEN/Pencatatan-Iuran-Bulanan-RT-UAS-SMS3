<?php
// admin/index.php
include 'templates/header.php';

// --- LOGIKA PENGAMBILAN DATA UNTUK DASHBOARD ---
$bulan_sekarang = date('m');
$tahun_sekarang = date('Y');

// Total Pembayaran
$stmt_total = $conn->prepare("SELECT SUM(jumlah) AS total_bulanan FROM pembayaran WHERE tanggal_bayar IS NOT NULL AND bulan = ? AND tahun = ?");
$stmt_total->bind_param("is", $bulan_sekarang, $tahun_sekarang);
$stmt_total->execute();
$total_pembayaran_bulan_ini = $stmt_total->get_result()->fetch_assoc()['total_bulanan'] ?? 0;

// Statistik Warga
$total_warga = $conn->query("SELECT COUNT(*) AS jumlah_warga FROM warga WHERE role = 'warga'")->fetch_assoc()['jumlah_warga'] ?? 0;
$stmt_sudah_bayar = $conn->prepare("SELECT COUNT(*) AS sudah_bayar FROM pembayaran WHERE tanggal_bayar IS NOT NULL AND bulan = ? AND tahun = ?");
$stmt_sudah_bayar->bind_param("is", $bulan_sekarang, $tahun_sekarang);
$stmt_sudah_bayar->execute();
$warga_sudah_bayar = $stmt_sudah_bayar->get_result()->fetch_assoc()['sudah_bayar'] ?? 0;
$warga_belum_bayar = $total_warga - $warga_sudah_bayar;

// --- LOGIKA DATA UNTUK CHART (12 BULAN) ---
$data_per_bulan = array_fill_keys(['January','February','March','April','May','June','July','August','September','October','November','December'], 0);
$stmt_chart = $conn->prepare("SELECT MONTHNAME(tanggal_bayar) as bulan, SUM(jumlah) as total FROM pembayaran WHERE YEAR(tanggal_bayar) = ? AND tanggal_bayar IS NOT NULL GROUP BY MONTH(tanggal_bayar), MONTHNAME(tanggal_bayar)");
$stmt_chart->bind_param("s", $tahun_sekarang);
$stmt_chart->execute();
$result_chart = $stmt_chart->get_result();
while ($row = $result_chart->fetch_assoc()) {
    if (array_key_exists($row['bulan'], $data_per_bulan)) {
        $data_per_bulan[$row['bulan']] = (float) $row['total'];
    }
}
$chart_labels_json = json_encode(array_keys($data_per_bulan));
$chart_data_json = json_encode(array_values($data_per_bulan));
?>

<h3 class="mb-4">Dashboard</h3>
<div class="row">
    <div class="col-md-3 mb-4"><div class="card text-white bg-primary"><div class="card-body"><h5>Total Iuran Bulan Ini</h5><p class="card-text fs-4">Rp <?php echo number_format($total_pembayaran_bulan_ini); ?></p></div></div></div>
    <div class="col-md-3 mb-4"><div class="card text-white bg-info"><div class="card-body"><h5>Jumlah Warga</h5><p class="card-text fs-4"><?php echo $total_warga; ?> Orang</p></div></div></div>
    <div class="col-md-3 mb-4"><div class="card text-white bg-success"><div class="card-body"><h5>Sudah Bayar Bulan Ini</h5><p class="card-text fs-4"><?php echo $warga_sudah_bayar; ?> Orang</p></div></div></div>
    <div class="col-md-3 mb-4"><div class="card text-white bg-danger"><div class="card-body"><h5>Belum Bayar Bulan Ini</h5><p class="card-text fs-4"><?php echo $warga_belum_bayar; ?> Orang</p></div></div></div>
</div>
<div class="card">
    <div class="card-header">Grafik Pemasukan Iuran per Bulan (Tahun <?php echo $tahun_sekarang; ?>)</div>
    <div class="card-body">
        <canvas id="myBarChart" style="height: 320px;"></canvas>
    </div>
</div>

<?php include 'templates/footer.php'; ?>

<script>
new Chart(document.getElementById('myBarChart'),{type:'bar',data:{labels:<?php echo $chart_labels_json;?>,datasets:[{label:'Total Pemasukan',data:<?php echo $chart_data_json;?>,backgroundColor:'rgba(0, 123, 255, 0.8)'}]},options:{responsive:true,maintainAspectRatio:false,scales:{y:{beginAtZero:true,ticks:{callback:function(v){return'Rp '+new Intl.NumberFormat('id-ID').format(v);}}}}}});
</script>