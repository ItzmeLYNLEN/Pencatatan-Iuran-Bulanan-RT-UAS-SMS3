<?php
include 'templates/header.php';

$tahun_laporan = isset($_GET['tahun']) ? intval($_GET['tahun']) : date('Y');
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$total_warga_result = $conn->query("SELECT COUNT(id_warga) as total FROM warga WHERE role='warga'");
$total_warga = $total_warga_result->fetch_assoc()['total'];
$total_pages = ($limit == -1) ? 1 : ceil($total_warga / $limit);

$sql_warga = "SELECT id_warga, nama_lengkap, no_rumah FROM warga WHERE role='warga' ORDER BY no_rumah ASC";
if ($limit != -1) {
    $sql_warga .= " LIMIT $limit OFFSET $offset";
}
$warga_result = $conn->query($sql_warga);
$warga_list = [];
while ($row = $warga_result->fetch_assoc()) $warga_list[] = $row;

$pembayaran_result = $conn->query("SELECT id_warga, bulan FROM pembayaran WHERE tahun = $tahun_laporan");
$pembayaran_data = [];
while ($row = $pembayaran_result->fetch_assoc()) {
    $pembayaran_data[$row['id_warga']][$row['bulan']] = true;
}

$bulan_nama = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];

$start_entry = ($total_warga == 0) ? 0 : $offset + 1;
$end_entry = ($limit == -1) ? $total_warga : min($offset + $limit, $total_warga);
?>

<h3 class="mb-4">Laporan Tahunan Iuran Warga</h3>

<div class="card shadow-sm">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col-md-3">
                <form method="GET" action="" id="limitForm" class="d-flex align-items-center">
                    <input type="hidden" name="tahun" value="<?php echo $tahun_laporan; ?>">
                    <label class="form-label me-2 mb-0">Tampilkan</label>
                    <select name="limit" class="form-select form-select-sm" style="width: 80px;" onchange="this.form.submit()">
                        <option value="5" <?php if($limit == 5) echo 'selected'; ?>>5</option>
                        <option value="10" <?php if($limit == 10) echo 'selected'; ?>>10</option>
                        <option value="25" <?php if($limit == 25) echo 'selected'; ?>>25</option>
                        <option value="50" <?php if($limit == 50) echo 'selected'; ?>>50</option>
                        <option value="-1" <?php if($limit == -1) echo 'selected'; ?>>Semua</option>
                    </select>
                    <span class="ms-2">data</span>
                </form>
            </div>
            <div class="col-md-9">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" id="searchInput" class="form-control" placeholder="Cari nama warga di halaman ini...">
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover text-center">
                <thead class="table-dark">
                    <tr>
                        <th class="text-start">Nama Warga (No. Rumah)</th>
                        <?php foreach ($bulan_nama as $nama) echo "<th>$nama</th>"; ?>
                    </tr>
                </thead>
                <tbody id="reportTableBody">
                    <?php if (empty($warga_list)): ?>
                        <tr><td colspan="13">Belum ada data warga.</td></tr>
                    <?php else: ?>
                        <?php foreach ($warga_list as $warga): ?>
                        <tr>
                            <td class="text-start fw-bold"><?php echo htmlspecialchars($warga['nama_lengkap']) . ' (' . htmlspecialchars($warga['no_rumah']) . ')'; ?></td>
                            <?php for ($i = 1; $i <= 12; $i++):
                                $is_lunas = isset($pembayaran_data[$warga['id_warga']][$i]);
                                if ($is_lunas) {
                                    echo '<td><span class="text-success" title="Lunas">✅</span></td>';
                                } else {
                                    echo '<td><span class="text-danger" title="Belum Lunas">❌</span></td>';
                                }
                            endfor; ?>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="row mt-3 align-items-center">
            <div class="col-md-6">
                <p class="text-muted mb-0">
                    Menampilkan <?php echo $start_entry; ?> sampai <?php echo $end_entry; ?> dari <?php echo $total_warga; ?> data
                </p>
            </div>
            <div class="col-md-6">
                <nav>
                    <ul class="pagination justify-content-end mb-0">
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?tahun=<?php echo $tahun_laporan; ?>&limit=<?php echo $limit; ?>&page=<?php echo $page - 1; ?>">Sebelumnya</a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?tahun=<?php echo $tahun_laporan; ?>&limit=<?php echo $limit; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?tahun=<?php echo $tahun_laporan; ?>&limit=<?php echo $limit; ?>&page=<?php echo $page + 1; ?>">Selanjutnya</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>

<script>
document.getElementById('searchInput').addEventListener('keyup', function() {
    const filter = this.value.toLowerCase();
    const tableBody = document.getElementById('reportTableBody');
    const rows = tableBody.getElementsByTagName('tr');
    for (let i = 0; i < rows.length; i++) {
        const firstCell = rows[i].getElementsByTagName('td')[0];
        if (firstCell) {
            if (firstCell.textContent.toLowerCase().indexOf(filter) > -1) {
                rows[i].style.display = "";
            } else {
                rows[i].style.display = "none";
            }
        }
    }
});
</script>