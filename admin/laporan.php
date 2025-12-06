<?php
include 'templates/header.php';

$tahun_laporan = isset($_GET['tahun']) ? intval($_GET['tahun']) : date('Y');
$search = isset($_GET['search']) ? $_GET['search'] : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$where_clause = "WHERE 1=1";
if (!empty($search)) {
    $safe_search = $conn->real_escape_string($search);
    $where_clause .= " AND (w.nama_lengkap LIKE '%$safe_search%' OR w.no_rumah LIKE '%$safe_search%')";
}

$total_res = $conn->query("SELECT COUNT(*) as total FROM profil_warga w $where_clause");
$total_warga = $total_res->fetch_assoc()['total'];
$total_pages = ($limit == -1) ? 1 : ceil($total_warga / $limit);

$sql = "
    SELECT 
        w.id_warga, 
        w.nama_lengkap, 
        w.no_rumah, 
        GROUP_CONCAT(SUBSTRING(t.periode_tagihan, 6, 2)) as bulan_lunas 
    FROM 
        profil_warga w
    LEFT JOIN 
        transaksi t ON w.id_warga = t.id_warga AND t.periode_tagihan LIKE '$tahun_laporan-%'
    $where_clause
    GROUP BY 
        w.id_warga
    ORDER BY 
        w.no_rumah ASC
";

if ($limit != -1) {
    $sql .= " LIMIT $limit OFFSET $offset";
}

$warga_result = $conn->query($sql);
$bulan_nama = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
$start_entry = ($total_warga == 0) ? 0 : $offset + 1;
$end_entry = ($limit == -1) ? $total_warga : min($offset + $limit, $total_warga);
?>

<h3 class="mb-4">Laporan Tahunan Iuran Warga</h3>

<div class="card shadow-sm">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col-md-3">
                <form method="GET" action="" class="d-flex align-items-center">
                    <input type="hidden" name="tahun" value="<?php echo $tahun_laporan; ?>">
                    <label class="form-label me-2 mb-0">Show</label>
                    <select name="limit" class="form-select form-select-sm" style="width: 80px;" onchange="this.form.submit()">
                        <option value="10" <?php if ($limit == 10) echo 'selected'; ?>>10</option>
                        <option value="25" <?php if ($limit == 25) echo 'selected'; ?>>25</option>
                        <option value="50" <?php if ($limit == 50) echo 'selected'; ?>>50</option>
                        <option value="-1" <?php if ($limit == -1) echo 'selected'; ?>>All</option>
                    </select>
                </form>
            </div>
            <div class="col-md-9">
                <form method="GET">
                    <input type="hidden" name="tahun" value="<?php echo $tahun_laporan; ?>">
                    <input type="hidden" name="limit" value="<?php echo $limit; ?>">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Cari nama atau no rumah..." value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover text-center align-middle">
                <thead class="table-dark">
                    <tr>
                        <th class="text-start">Nama Warga (Rumah)</th>
                        <?php foreach ($bulan_nama as $nama) echo "<th>$nama</th>"; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($warga_result->num_rows > 0): ?>
                        <?php while ($row = $warga_result->fetch_assoc()): 
                            $lunas_arr = !empty($row['bulan_lunas']) ? explode(',', $row['bulan_lunas']) : [];
                        ?>
                        <tr>
                            <td class="text-start fw-bold">
                                <?php echo htmlspecialchars($row['nama_lengkap']); ?> <br>
                                <small class="text-muted"><?php echo htmlspecialchars($row['no_rumah']); ?></small>
                            </td>
                            <?php for ($i = 1; $i <= 12; $i++): 
                                $bulan_str = str_pad($i, 2, '0', STR_PAD_LEFT);
                                $is_lunas = in_array($bulan_str, $lunas_arr);
                            ?>
                                <td>
                                    <?php if ($is_lunas): ?>
                                        <i class="fas fa-check-circle text-success fs-5" title="Lunas"></i>
                                    <?php else: ?>
                                        <i class="fas fa-times-circle text-danger fs-5 opacity-25" title="Belum"></i>
                                    <?php endif; ?>
                                </td>
                            <?php endfor; ?>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="13">Data tidak ditemukan.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="row mt-3 align-items-center">
            <div class="col-md-6">
                <p class="text-muted mb-0">
                    Menampilkan <?php echo $start_entry; ?> - <?php echo $end_entry; ?> dari <?php echo $total_warga; ?> data
                </p>
            </div>
            <div class="col-md-6">
                <nav>
                    <ul class="pagination justify-content-end mb-0">
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?tahun=<?php echo $tahun_laporan; ?>&limit=<?php echo $limit; ?>&search=<?php echo $search; ?>&page=<?php echo $page - 1; ?>">Prev</a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?tahun=<?php echo $tahun_laporan; ?>&limit=<?php echo $limit; ?>&search=<?php echo $search; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?tahun=<?php echo $tahun_laporan; ?>&limit=<?php echo $limit; ?>&search=<?php echo $search; ?>&page=<?php echo $page + 1; ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>