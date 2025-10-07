<?php
// admin/catat_pembayaran.php
include 'templates/header.php';

// PERBAIKAN DI SINI: Mengecek 'action'
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'bayar_iuran') {
    $warga_id = $_POST['warga_id'];
    $bulan_iuran = $_POST['bulan_iuran'];
    $tahun_iuran = $_POST['tahun_iuran'];
    $jumlah = 50000; // Ganti dengan jumlah iuran default Anda

    $tanggal_pembayaran = date('Y-m-d', strtotime("$tahun_iuran-$bulan_iuran-01"));

    $stmt = $conn->prepare("INSERT INTO pembayaran (id_warga, bulan, tahun, jumlah, tanggal_bayar) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iiids", $warga_id, $bulan_iuran, $tahun_iuran, $jumlah, $tanggal_pembayaran);
    
    if ($stmt->execute()) {
        $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Pembayaran berhasil dicatat!'];
    } else {
        $errorMessage = ($conn->errno == 1062) 
            ? 'Gagal: Warga ini sudah tercatat lunas untuk periode ini.'
            : 'Gagal mencatat pembayaran: ' . $stmt->error;
        $_SESSION['flash_message'] = ['type' => 'error', 'message' => $errorMessage];
    }
    header("Location: catat_pembayaran.php?bulan=$bulan_iuran&tahun=$tahun_iuran");
    exit();
}

// --- LOGIKA PENGAMBILAN DATA UNTUK DITAMPILKAN (Tidak berubah) ---
$filter_bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$filter_tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');

$result_warga = $conn->query("SELECT id_warga, nama_lengkap, no_rumah FROM warga WHERE role = 'warga' ORDER BY no_rumah ASC");
$stmt_paid = $conn->prepare("SELECT id_warga FROM pembayaran WHERE bulan = ? AND tahun = ?");
$stmt_paid->bind_param("is", $filter_bulan, $filter_tahun);
$stmt_paid->execute();
$result_paid = $stmt_paid->get_result();
$paid_warga_ids = [];
while ($row = $result_paid->fetch_assoc()) {
    $paid_warga_ids[] = $row['id_warga'];
}
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
    <div class="card-header">Status Iuran Bulan: <strong><?php echo date('F', mktime(0, 0, 0, $filter_bulan, 10)) . ' ' . $filter_tahun; ?></strong></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead><tr><th>No</th><th>Nama Lengkap</th><th>No. Rumah</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php $no=1; while($warga = $result_warga->fetch_assoc()): ?>
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
                                <form method="POST" action="?bulan=<?php echo $filter_bulan; ?>&tahun=<?php echo $filter_tahun; ?>">
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
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Script untuk konfirmasi pembayaran
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

    // 2. Script untuk menampilkan notifikasi (flash message)
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
});
</script>