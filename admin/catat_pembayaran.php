<?php
ob_start();
include 'templates/header.php';


$list_standar = [
    ['nama' => 'Iuran Warga (Keamanan & Sampah)', 'harga' => 50000]
];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'bayar_iuran') {
    $id_warga = $_POST['warga_id'];
    $bulan = $_POST['bulan_iuran'];
    $tahun = $_POST['tahun_iuran'];
    $id_admin = $_SESSION['id_admin'] ?? NULL; 
    
    $items = $_POST['nama_item']; 
    $biaya = $_POST['jumlah_biaya'];
    
    $total_tagihan = 0;
    foreach ($biaya as $b) {
        $total_tagihan += (int)$b;
    }

    $periode = $tahun . '-' . str_pad($bulan, 2, '0', STR_PAD_LEFT);
    $kode = 'INV-' . date('YmdHis') . '-' . $id_warga;

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO transaksi (kode_transaksi, id_warga, id_admin, periode_tagihan, total_tagihan, metode_pembayaran, tanggal_bayar) VALUES (?, ?, ?, ?, ?, 'manual', NOW())");
        $stmt->bind_param("siisd", $kode, $id_warga, $id_admin, $periode, $total_tagihan);
        $stmt->execute();
        $id_transaksi = $conn->insert_id;

        $stmt_detail = $conn->prepare("INSERT INTO detail_transaksi (id_transaksi, nama_item, jumlah_biaya) VALUES (?, ?, ?)");
        
        for ($i = 0; $i < count($items); $i++) {
            $nama = $items[$i];
            $jumlah = (int)$biaya[$i];
            
            if (!empty($nama) && $jumlah > 0) {
                $stmt_detail->bind_param("isd", $id_transaksi, $nama, $jumlah);
                $stmt_detail->execute();
            }
        }

        $conn->commit();
        $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Pembayaran berhasil dicatat! Total: Rp ' . number_format($total_tagihan)];
    } catch (Exception $e) {
        $conn->rollback();
        $errorMessage = ($conn->errno == 1062) ? 'Gagal: Warga ini sudah tercatat lunas untuk periode tersebut.' : 'Gagal: ' . $stmt->error;
        $_SESSION['flash_message'] = ['type' => 'error', 'message' => $errorMessage];
    }
    
    session_write_close();
    header("Location: catat_pembayaran.php?bulan=$bulan&tahun=$tahun");
    exit();
}

$filter_bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$filter_tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
$periode_filter = $filter_tahun . '-' . str_pad($filter_bulan, 2, '0', STR_PAD_LEFT);

$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$total_warga_result = $conn->query("SELECT COUNT(id_warga) as total FROM profil_warga");
$total_warga = $total_warga_result->fetch_assoc()['total'];
$total_pages = ($limit == -1) ? 1 : ceil($total_warga / $limit);

$sql_join = "
    SELECT 
        w.id_warga, 
        w.nama_lengkap, 
        w.no_rumah,
        t.id_transaksi
    FROM 
        profil_warga w
    LEFT JOIN 
        transaksi t ON w.id_warga = t.id_warga 
                     AND t.periode_tagihan = ?
    ORDER BY 
        w.no_rumah ASC
";

if ($limit != -1) {
    $sql_join .= " LIMIT ? OFFSET ?";
    $stmt_warga = $conn->prepare($sql_join);
    $stmt_warga->bind_param("sii", $periode_filter, $limit, $offset);
} else {
    $stmt_warga = $conn->prepare($sql_join);
    $stmt_warga->bind_param("s", $periode_filter);
}

$stmt_warga->execute();
$result_warga = $stmt_warga->get_result();

$start_entry = ($total_warga == 0) ? 0 : $offset + 1;
$end_entry = ($limit == -1) ? $total_warga : min($offset + $limit, $total_warga);
?>

<h3 class="mb-4">Catat Pembayaran Iuran</h3>

<div class="card mb-4">
    <div class="card-header">Pilih Periode Tagihan</div>
    <div class="card-body">
        <form method="GET" action="" class="row align-items-end">
             <div class="col-md-4">
                 <label class="form-label">Bulan</label>
                 <select name="bulan" class="form-select">
                    <?php for ($i=1; $i<=12; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php if ($i==$filter_bulan) echo 'selected'; ?>>
                            <?php echo date('F', mktime(0,0,0,$i,10)); ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
             <div class="col-md-3">
                 <label class="form-label">Tahun</label>
                 <select name="tahun" class="form-select">
                    <?php for ($i=date('Y'); $i>=date('Y')-5; $i--): ?>
                        <option value="<?php echo $i; ?>" <?php if ($i==$filter_tahun) echo 'selected'; ?>><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
             <div class="col-md-2">
                 <button type="submit" class="btn btn-primary w-100 mt-3 mt-md-0">Tampilkan</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col-md-4">
                <form method="GET" action="" class="d-flex align-items-center">
                    <input type="hidden" name="bulan" value="<?php echo $filter_bulan; ?>">
                    <input type="hidden" name="tahun" value="<?php echo $filter_tahun; ?>">
                    <label class="form-label me-2 mb-0">Tampil</label>
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
            <div class="col-md-8">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" id="searchInput" class="form-control" placeholder="Ketik untuk mencari warga di halaman ini...">
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead><tr><th>No</th><th>Nama Lengkap</th><th>No. Rumah</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody id="paymentTableBody">
                    <?php if ($result_warga->num_rows > 0): $no = $offset + 1; ?>
                        <?php while($warga = $result_warga->fetch_assoc()): ?>
                        <?php $is_lunas = ($warga['id_transaksi'] !== null); ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo htmlspecialchars($warga['nama_lengkap']); ?></td>
                            <td><?php echo htmlspecialchars($warga['no_rumah']); ?></td>
                            <td>
                                <?php if($is_lunas): ?>
                                    <span class="badge bg-success">Lunas</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Belum Lunas</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if(!$is_lunas): ?>
                                    <button type="button" class="btn btn-sm btn-primary btn-bayar" 
                                        data-bs-toggle="modal" data-bs-target="#modalBayar"
                                        data-id="<?php echo $warga['id_warga']; ?>"
                                        data-nama="<?php echo htmlspecialchars($warga['nama_lengkap']); ?>">Bayar</button>
                                <?php else: echo "-"; endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                         <tr><td colspan="5" class="text-center">Tidak ada data warga.</td></tr>
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
                            <a class="page-link" href="?bulan=<?php echo $filter_bulan; ?>&tahun=<?php echo $filter_tahun; ?>&limit=<?php echo $limit; ?>&page=<?php echo $page - 1; ?>">Sebelumnya</a>
                        </li>
                        <li class="page-item disabled"><a class="page-link"><?php echo $page; ?></a></li>
                        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?bulan=<?php echo $filter_bulan; ?>&tahun=<?php echo $filter_tahun; ?>&limit=<?php echo $limit; ?>&page=<?php echo $page + 1; ?>">Selanjutnya</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalBayar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rincian Pembayaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="bayar_iuran">
                    <input type="hidden" name="warga_id" id="modal_warga_id">
                    <input type="hidden" name="bulan_iuran" value="<?php echo $filter_bulan; ?>">
                    <input type="hidden" name="tahun_iuran" value="<?php echo $filter_tahun; ?>">

                    <div class="mb-3">
                        <label class="fw-bold">Nama Warga:</label>
                        <span id="modal_nama_warga"></span>
                    </div>

                    <div class="mb-2">
                        <label class="fw-bold">Item Tagihan</label>
                    </div>
                    
                    <div id="container-items">
                        <?php foreach ($list_standar as $item): ?>
                            <div class="input-group mb-2">
                                <input type="text" readonly class="form-control" name="nama_item[]" value="<?php echo htmlspecialchars($item['nama']); ?>">
                                <span class="input-group-text">Rp</span>
                                <input type="number" readonly class="form-control" name="jumlah_biaya[]" value="<?php echo (int)$item['harga']; ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Transaksi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    const modalBayar = document.getElementById('modalBayar');
    modalBayar.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const id = button.getAttribute('data-id');
        const nama = button.getAttribute('data-nama');
        
        document.getElementById('modal_warga_id').value = id;
        document.getElementById('modal_nama_warga').textContent = nama;
    });
    
    <?php if (isset($_SESSION['flash_message'])): ?>
        Swal.fire({
            title: "<?php echo $_SESSION['flash_message']['type'] == 'success' ? 'Berhasil!' : 'Oops...'; ?>",
            text: "<?php echo $_SESSION['flash_message']['message']; ?>",
            icon: "<?php echo $_SESSION['flash_message']['type']; ?>",
            timer: 3000,
            showConfirmButton: false
        });
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    const searchInput = document.getElementById('searchInput');
    const tableBody = document.getElementById('paymentTableBody');
    const tableRows = tableBody.getElementsByTagName('tr');

    searchInput.addEventListener('keyup', function() {
        const filter = searchInput.value.toLowerCase();
        for (let i = 0; i < tableRows.length; i++) {
            const namaCell = tableRows[i].getElementsByTagName('td')[1];
            const rumahCell = tableRows[i].getElementsByTagName('td')[2];
            
            if (namaCell && rumahCell) {
                const namaText = namaCell.textContent || namaCell.innerText;
                const rumahText = rumahCell.textContent || rumahCell.innerText;
                
                if (namaText.toLowerCase().indexOf(filter) > -1 || rumahText.toLowerCase().indexOf(filter) > -1) {
                    tableRows[i].style.display = "";
                } else {
                    tableRows[i].style.display = "none";
                }
            }
        }
    });
});
</script>

<?php
ob_end_flush();
?>