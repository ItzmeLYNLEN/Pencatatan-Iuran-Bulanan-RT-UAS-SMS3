<?php
include 'templates/header.php';

$tahun_laporan = isset($_GET['tahun']) ? intval($_GET['tahun']) : date('Y');
$warga_result = $conn->query("SELECT id_warga, nama_lengkap, no_rumah FROM warga WHERE role='warga' ORDER BY no_rumah ASC");
$warga_list = [];
while ($row = $warga_result->fetch_assoc()) $warga_list[] = $row;

$pembayaran_result = $conn->query("SELECT id_warga, bulan FROM pembayaran WHERE tahun = $tahun_laporan");
$pembayaran_data = [];
while ($row = $pembayaran_result->fetch_assoc()) $pembayaran_data[$row['id_warga'] . '_' . $row['bulan']] = true;

$bulan_nama = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
?>
<h3>Laporan Iuran Bulanan</h3><hr>
<form method="GET" action="" class="mb-3">
    <div class="row">
        <div class="col-md-3">
            <label class="form-label">Pilih Tahun Laporan</label>
            <select name="tahun" class="form-select" onchange="this.form.submit()">
                <?php for ($i = date('Y'); $i >= date('Y') - 5; $i--): ?>
                <option value="<?php echo $i; ?>" <?php echo ($i == $tahun_laporan) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                <?php endfor; ?>
            </select>
        </div>
    </div>
</form>
<div class="table-responsive">
    <table class="table table-bordered table-striped text-center">
        <thead class="table-dark">
            <tr><th>Nama Warga (No. Rumah)</th><?php foreach ($bulan_nama as $nama) echo "<th>$nama</th>"; ?></tr>
        </thead>
        <tbody>
            <?php foreach ($warga_list as $warga): ?>
            <tr>
                <td class="text-start"><?php echo htmlspecialchars($warga['nama_lengkap']) . ' (' . htmlspecialchars($warga['no_rumah']) . ')'; ?></td>
                <?php for ($i = 1; $i <= 12; $i++):
                    $key_pembayaran = $warga['id_warga'] . '_' . $i;
                    if (isset($pembayaran_data[$key_pembayaran])) {
                        echo '<td class="bg-success text-white">Lunas</td>';
                    } else {
                        echo '<td class="bg-danger text-white">Belum</td>';
                    }
                endfor; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php include 'templates/footer.php'; ?>