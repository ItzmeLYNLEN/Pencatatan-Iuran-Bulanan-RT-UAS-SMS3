<?php
include 'templates/header.php';

$bulan_sekarang = date('m');
$tahun_sekarang = date('Y');
$periode_sekarang = $tahun_sekarang . '-' . $bulan_sekarang;

$stmt_total = $conn->prepare("SELECT SUM(total_tagihan) AS total FROM transaksi WHERE periode_tagihan = ?");
$stmt_total->bind_param("s", $periode_sekarang);
$stmt_total->execute();
$total_pembayaran_bulan_ini = $stmt_total->get_result()->fetch_assoc()['total'] ?? 0;

$res_warga = $conn->query("SELECT COUNT(*) AS total FROM profil_warga");
$total_warga = $res_warga->fetch_assoc()['total'] ?? 0;

$stmt_bayar = $conn->prepare("SELECT COUNT(*) AS total FROM transaksi WHERE periode_tagihan = ?");
$stmt_bayar->bind_param("s", $periode_sekarang);
$stmt_bayar->execute();
$warga_sudah_bayar = $stmt_bayar->get_result()->fetch_assoc()['total'] ?? 0;

$warga_belum_bayar = $total_warga - $warga_sudah_bayar;

$data_per_bulan = array_fill_keys(range(1, 12), 0);
$like_tahun = $tahun_sekarang . '-%';
$stmt_chart = $conn->prepare("
    SELECT SUBSTRING(periode_tagihan, 6, 2) as bulan_angka, SUM(total_tagihan) as total 
    FROM transaksi 
    WHERE periode_tagihan LIKE ?
    GROUP BY bulan_angka
");
$stmt_chart->bind_param("s", $like_tahun);
$stmt_chart->execute();
$result_chart = $stmt_chart->get_result();

while ($row = $result_chart->fetch_assoc()) {
    $bulan_int = (int)$row['bulan_angka'];
    $data_per_bulan[$bulan_int] = (float) $row['total'];
}

$chart_labels = [];
for ($i = 1; $i <= 12; $i++) {
    $chart_labels[] = date('F', mktime(0, 0, 0, $i, 10));
}

$chart_labels_json = json_encode($chart_labels);
$chart_data_json = json_encode(array_values($data_per_bulan));
?>

<h3 class="mb-4">Dashboard</h3>
<div class="row">
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h5>Total Iuran Bulan Ini</h5>
                <p class="card-text fs-4">Rp <?php echo number_format($total_pembayaran_bulan_ini, 0, ',', '.'); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-info">
            <div class="card-body">
                <h5>Jumlah Warga</h5>
                <p class="card-text fs-4"><?php echo $total_warga; ?> Orang</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h5>Sudah Bayar Bulan Ini</h5>
                <p class="card-text fs-4"><?php echo $warga_sudah_bayar; ?> Orang</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-danger">
            <div class="card-body">
                <h5>Belum Bayar Bulan Ini</h5>
                <p class="card-text fs-4"><?php echo $warga_belum_bayar; ?> Orang</p>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">Grafik Pemasukan Iuran per Bulan (Tahun <?php echo $tahun_sekarang; ?>)</div>
    <div class="card-body">
        <canvas id="myBarChart" style="height: 320px;"></canvas>
    </div>
</div>

<?php include 'templates/footer.php'; ?>

<script>
new Chart(document.getElementById('myBarChart'), {
    type: 'bar',
    data: {
        labels: <?php echo $chart_labels_json; ?>,
        datasets: [{
            label: 'Total Pemasukan',
            data: <?php echo $chart_data_json; ?>,
            backgroundColor: 'rgba(13, 110, 253, 0.8)'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                    }
                }
            }
        }
    }
});
</script>