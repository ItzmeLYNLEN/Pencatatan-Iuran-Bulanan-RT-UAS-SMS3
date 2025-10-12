<?php
include 'templates/header.php';


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'bayar_iuran') {
    $warga_id = $_POST['warga_id'];
    $bulan_iuran = $_POST['bulan_iuran'];
    $tahun_iuran = $_POST['tahun_iuran'];
    $jumlah = 50000;
    $tanggal_pembayaran = date('Y-m-d');
    $stmt = $conn->prepare("INSERT INTO pembayaran (id_warga, bulan, tahun, jumlah, tanggal_bayar) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iiids", $warga_id, $bulan_iuran, $tahun_iuran, $jumlah, $tanggal_pembayaran);
    
    if ($stmt->execute()) {
        $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Pembayaran berhasil dicatat!'];
    } else {
        $errorMessage = ($conn->errno == 1062) ? 'Gagal: Warga ini sudah tercatat lunas.' : 'Gagal: ' . $stmt->error;
        $_SESSION['flash_message'] = ['type' => 'error', 'message' => $errorMessage];
    }
    header("Location: catat_pembayaran.php?bulan=$bulan_iuran&tahun=$tahun_iuran");
    exit();
}


$filter_bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$filter_tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');

$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$total_warga_result = $conn->query("SELECT COUNT(id_warga) as total FROM warga WHERE role='warga'");
$total_warga = $total_warga_result->fetch_assoc()['total'];
$total_pages = ($limit == -1) ? 1 : ceil($total_warga / $limit);

$sql_warga = "SELECT id_warga, nama_lengkap, no_rumah FROM warga WHERE role = 'warga' ORDER BY no_rumah ASC";
if ($limit != -1) {
    $sql_warga .= " LIMIT $limit OFFSET $offset";
}
$result_warga = $conn->query($sql_warga);

$stmt_paid = $conn->prepare("SELECT id_warga FROM pembayaran WHERE bulan = ? AND tahun = ?");
$stmt_paid->bind_param("is", $filter_bulan, $filter_tahun);
$stmt_paid->execute();
$result_paid = $stmt_paid->get_result();
$paid_warga_ids = [];
while ($row = $result_paid->fetch_assoc()) {
    $paid_warga_ids[] = $row['id_warga'];
}

$start_entry = ($total_warga == 0) ? 0 : $offset + 1;
$end_entry = ($limit == -1) ? $total_warga : min($offset + $limit, $total_warga);
?>

<h3 class="mb-4">Catat Pembayaran Iuran</h3>

<div class="card mb-4">
    <div class="card-header">Pilih Periode</div>
    <div class="card-body">
        <form method="GET" action="" class="row align-items-end">
             <div class="col-md-4"><label class="form-label">Bulan</label><select name="bulan" class="form-select">
                <?php for ($i=1; $i<=12; $i++): ?><option value="<?php echo $i; ?>" <?php if ($i==$filter_bulan) echo 'selected'; ?>><?php echo date('F', mktime(0,0,0,$i,10)); ?></option><?php endfor; ?>
            </select></div>
            <div class="col-md-3"><label class="form-label">Tahun</label><select name="tahun" class="form-select">
                <?php for ($i=date('Y'); $i>=date('Y')-5; $i--): ?><option value="<?php echo $i; ?>" <?php if ($i==$filter_tahun) echo 'selected'; ?>><?php echo $i; ?></option><?php endfor; ?>
            </select></div>
            <div class="col-md-2"><button type="submit" class="btn btn-primary w-100 mt-3 mt-md-0">Tampilkan</button></div>
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
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo htmlspecialchars($warga['nama_lengkap']); ?></td>
                            <td><?php echo htmlspecialchars($warga['no_rumah']); ?></td>
                            <td>
                                <?php if(in_array($warga['id_warga'], $paid_warga_ids)): ?>
                                    <span class="badge bg-success">Lunas</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Belum Lunas</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if(!in_array($warga['id_warga'], $paid_warga_ids)): ?>
                                    <form method="POST" action="?bulan=<?php echo $filter_bulan; ?>&tahun=<?php echo $filter_tahun; ?>&limit=<?php echo $limit; ?>&page=<?php echo $page; ?>">
                                        <input type="hidden" name="warga_id" value="<?php echo $warga['id_warga']; ?>">
                                        <input type="hidden" name="bulan_iuran" value="<?php echo $filter_bulan; ?>">
                                        <input type="hidden" name="tahun_iuran" value="<?php echo $filter_tahun; ?>">
                                        <input type="hidden" name="action" value="bayar_iuran">
                                        <button type="button" class="btn btn-sm btn-primary btn-bayar" data-nama-warga="<?php echo htmlspecialchars($warga['nama_lengkap']); ?>">Bayar</button>
                                    </form>
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
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?bulan=<?php echo $filter_bulan; ?>&tahun=<?php echo $filter_tahun; ?>&limit=<?php echo $limit; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?bulan=<?php echo $filter_bulan; ?>&tahun=<?php echo $filter_tahun; ?>&limit=<?php echo $limit; ?>&page=<?php echo $page + 1; ?>">Selanjutnya</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const payButtons = document.querySelectorAll('.btn-bayar');
    payButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault(); 
            const form = this.closest('form');
            const namaWarga = this.getAttribute('data-nama-warga');
            Swal.fire({
                title: 'Konfirmasi Pembayaran',
                html: `Anda yakin ingin mencatat pembayaran untuk <br><b>${namaWarga}</b>?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Catat!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
    
    <?php if (isset($_SESSION['flash_message'])): ?>
        const flashMessage = <?php echo json_encode($_SESSION['flash_message']); ?>;
        Swal.fire({
            title: (flashMessage.type === 'success') ? 'Berhasil!' : 'Oops...',
            text: flashMessage.message,
            icon: flashMessage.type,
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