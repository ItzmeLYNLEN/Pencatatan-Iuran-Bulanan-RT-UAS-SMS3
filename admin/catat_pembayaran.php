<?php
include 'templates/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['catat_bayar'])) {
    $id_warga = $_POST['id_warga'];
    $bulan = $_POST['bulan'];
    $tahun = $_POST['tahun'];
    $jumlah = $_POST['jumlah'];
    $tanggal_bayar = date('Y-m-d');

    $cek = $conn->query("SELECT * FROM pembayaran WHERE id_warga = $id_warga AND bulan = $bulan AND tahun = $tahun");
    if($cek->num_rows > 0) {
        echo "<div class='alert alert-danger'>Warga ini sudah membayar iuran untuk bulan dan tahun yang dipilih.</div>";
    } else {
        $stmt = $conn->prepare("INSERT INTO pembayaran (id_warga, bulan, tahun, jumlah, tanggal_bayar) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiids", $id_warga, $bulan, $tahun, $jumlah, $tanggal_bayar);
        if($stmt->execute()){
            echo "<div class='alert alert-success'>Pembayaran berhasil dicatat.</div>";
        } else {
            echo "<div class='alert alert-danger'>Gagal mencatat pembayaran: " . $stmt->error . "</div>";
        }
    }
}
$warga_list = $conn->query("SELECT id_warga, nama_lengkap, no_rumah FROM warga WHERE role = 'warga' ORDER BY nama_lengkap ASC");
?>
<h3>Catat Pembayaran Iuran (Manual)</h3><hr>
<div class="card">
    <div class="card-body">
        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Pilih Warga</label>
                <select name="id_warga" class="form-select" required>
                    <option value="">-- Pilih Warga --</option>
                    <?php while($warga = $warga_list->fetch_assoc()): ?>
                    <option value="<?php echo $warga['id_warga']; ?>"><?php echo htmlspecialchars($warga['nama_lengkap']) . ' (Rumah: ' . htmlspecialchars($warga['no_rumah']) . ')'; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Untuk Bulan</label>
                    <select name="bulan" class="form-select" required>
                        <?php for($i = 1; $i <= 12; $i++): ?><option value="<?php echo $i; ?>" <?php echo ($i == date('n')) ? 'selected' : ''; ?>><?php echo date('F', mktime(0, 0, 0, $i, 10)); ?></option><?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Untuk Tahun</label>
                    <input type="number" name="tahun" class="form-control" value="<?php echo date('Y'); ?>" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Jumlah Bayar (Rp)</label>
                    <input type="number" name="jumlah" class="form-control" value="50000" required>
                </div>
            </div>
            <button type="submit" name="catat_bayar" class="btn btn-primary">Catat Pembayaran</button>
        </form>
    </div>
</div>
<?php include 'templates/footer.php'; ?>