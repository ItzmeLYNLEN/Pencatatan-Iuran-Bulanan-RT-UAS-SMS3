<?php
include 'templates/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'tambah_warga') {
        $nama = $_POST['nama_lengkap'];
        $no_rumah = $_POST['no_rumah'];
        $username = $_POST['username'];
        $password = $_POST['password'];
        $role = 'warga';
        $stmt = $conn->prepare("INSERT INTO warga (nama_lengkap, no_rumah, username, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $nama, $no_rumah, $username, $password, $role);
        if ($stmt->execute()) {
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Warga baru berhasil ditambahkan.'];
        } else {
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Gagal: ' . $stmt->error];
        }
    } elseif ($_POST['action'] == 'edit_warga') {
        $id_warga = $_POST['id_warga'];
        $nama = $_POST['nama_lengkap'];
        $no_rumah = $_POST['no_rumah'];
        $username = $_POST['username'];
        if (!empty($_POST['password'])) {
            $password = $_POST['password'];
            $stmt = $conn->prepare("UPDATE warga SET nama_lengkap = ?, no_rumah = ?, username = ?, password = ? WHERE id_warga = ?");
            $stmt->bind_param("ssssi", $nama, $no_rumah, $username, $password, $id_warga);
        } else {
            $stmt = $conn->prepare("UPDATE warga SET nama_lengkap = ?, no_rumah = ?, username = ? WHERE id_warga = ?");
            $stmt->bind_param("sssi", $nama, $no_rumah, $username, $id_warga);
        }
        if ($stmt->execute()) {
            $_SESSION['flash_message'] = ['type' => 'info', 'message' => 'Data warga berhasil diperbarui.'];
        } else {
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Gagal: ' . $stmt->error];
        }
    } elseif ($_POST['action'] == 'hapus_warga') {
        $id_warga = $_POST['id_warga'];
        $stmt = $conn->prepare("DELETE FROM warga WHERE id_warga = ?");
        $stmt->bind_param("i", $id_warga);
        if ($stmt->execute()) {
            $_SESSION['flash_message'] = ['type' => 'warning', 'message' => 'Data warga berhasil dihapus.'];
        } else {
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Gagal: ' . $stmt->error];
        }
    }
    header("Location: kelola_warga.php");
    exit();
}

$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$where_clause = "WHERE role='warga'";
if (!empty($search)) {
    $where_clause .= " AND (nama_lengkap LIKE '%$search%' OR no_rumah LIKE '%$search%' OR username LIKE '%$search%')";
}

$total_warga_result = $conn->query("SELECT COUNT(id_warga) as total FROM warga $where_clause");
$total_warga = $total_warga_result->fetch_assoc()['total'];
$total_pages = ($limit == -1) ? 1 : ceil($total_warga / $limit);

$sql_warga = "SELECT id_warga, nama_lengkap, no_rumah, username FROM warga $where_clause ORDER BY no_rumah ASC";
if ($limit != -1) {
    $sql_warga .= " LIMIT $limit OFFSET $offset";
}
$result = $conn->query($sql_warga);

$start_entry = ($total_warga == 0) ? 0 : $offset + 1;
$end_entry = ($limit == -1) ? $total_warga : min($offset + $limit, $total_warga);
?>

<h3 class="mb-4">Kelola Data Warga</h3>

<div class="card mb-4">
    <div class="card-header">Tambah Warga Baru</div>
    <div class="card-body">
        <form method="POST" action="">
            <input type="hidden" name="action" value="tambah_warga">
            <div class="row">
                <div class="col-md-4 mb-3"><label class="form-label">Nama Lengkap</label><input type="text" class="form-control" name="nama_lengkap" required></div>
                <div class="col-md-2 mb-3"><label class="form-label">No. Rumah</label><input type="text" class="form-control" name="no_rumah" required></div>
                <div class="col-md-3 mb-3"><label class="form-label">Username</label><input type="text" class="form-control" name="username" required></div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" name="password" id="addPassword" required>
                        <button class="btn btn-outline-secondary" type="button" id="toggleAddPassword"><i class="fas fa-eye"></i></button>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Tambah Warga</button>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col-md-4">
                <form method="GET" action="" class="d-flex align-items-center">
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
                <form method="GET" action="">
                    <input type="hidden" name="limit" value="<?php echo $limit; ?>">
                    <div class="input-group">
                        <input type="text" id="searchInput" name="search" class="form-control" placeholder="Cari nama, no. rumah, atau username..." value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-dark"><tr><th>No</th><th>Nama Lengkap</th><th>No. Rumah</th><th>Username</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php if ($result->num_rows > 0): $no = $offset + 1; ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                            <td><?php echo htmlspecialchars($row['no_rumah']); ?></td>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-info lihatBtn" data-bs-toggle="modal" data-bs-target="#lihatWargaModal" data-nama="<?php echo htmlspecialchars($row['nama_lengkap']); ?>" data-rumah="<?php echo htmlspecialchars($row['no_rumah']); ?>" data-user="<?php echo htmlspecialchars($row['username']); ?>">Lihat</button>
                                <button type="button" class="btn btn-sm btn-warning editBtn" data-bs-toggle="modal" data-bs-target="#editWargaModal" data-id="<?php echo $row['id_warga']; ?>" data-nama="<?php echo htmlspecialchars($row['nama_lengkap']); ?>" data-rumah="<?php echo htmlspecialchars($row['no_rumah']); ?>" data-user="<?php echo htmlspecialchars($row['username']); ?>">Edit</button>
                                <button type="button" class="btn btn-sm btn-danger hapusBtn" data-bs-toggle="modal" data-bs-target="#hapusWargaModal" data-id="<?php echo $row['id_warga']; ?>">Hapus</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center">Data tidak ditemukan.</td></tr>
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
                <nav><ul class="pagination justify-content-end mb-0">
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?limit=<?php echo $limit; ?>&search=<?php echo $search; ?>&page=<?php echo $page - 1; ?>">Sebelumnya</a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                        <a class="page-link" href="?limit=<?php echo $limit; ?>&search=<?php echo $search; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?limit=<?php echo $limit; ?>&search=<?php echo $search; ?>&page=<?php echo $page + 1; ?>">Selanjutnya</a>
                    </li>
                </ul></nav>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="lihatWargaModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Detail Data Warga</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="mb-3"><label class="form-label">Nama Lengkap</label><input type="text" class="form-control" id="lihat_nama_lengkap" readonly></div><div class="mb-3"><label class="form-label">No. Rumah</label><input type="text" class="form-control" id="lihat_no_rumah" readonly></div><div class="mb-3"><label class="form-label">Username</label><input type="text" class="form-control" id="lihat_username" readonly></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button></div></div></div>
</div>
<div class="modal fade" id="editWargaModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Edit Data Warga</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <form method="POST" action="">
        <input type="hidden" name="action" value="edit_warga">
        <div class="modal-body">
            <input type="hidden" name="id_warga" id="edit_id_warga">
            <div class="mb-3"><label class="form-label">Nama Lengkap</label><input type="text" class="form-control" name="nama_lengkap" id="edit_nama_lengkap" required></div>
            <div class="mb-3"><label class="form-label">No. Rumah</label><input type="text" class="form-control" name="no_rumah" id="edit_no_rumah" required></div>
            <div class="mb-3"><label class="form-label">Username</label><input type="text" class="form-control" name="username" id="edit_username" required></div>
            <div class="mb-3">
                <label class="form-label">Password Baru</label>
                <div class="input-group">
                    <input type="password" class="form-control" name="password" id="editPassword">
                    <button class="btn btn-outline-secondary" type="button" id="toggleEditPassword"><i class="fas fa-eye"></i></button>
                </div><small class="form-text text-muted">Kosongkan jika tidak ingin mengubah password.</small>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </div>
</div>
<div class="modal fade" id="hapusWargaModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Konfirmasi Hapus</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><form method="POST" action=""><input type="hidden" name="action" value="hapus_warga"><div class="modal-body"><p>Apakah Anda yakin ingin menghapus data warga ini?</p><input type="hidden" name="id_warga" id="hapus_id_warga"></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-danger">Ya, Hapus</button></div></form></div></div>
</div>

<?php include 'templates/footer.php'; ?>

<script>
function setupPasswordToggle(toggleButtonId, passwordInputId) {
    const toggleButton = document.getElementById(toggleButtonId);
    const passwordInput = document.getElementById(passwordInputId);
    const icon = toggleButton.querySelector('i');
    toggleButton.addEventListener('click', function () {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        icon.classList.toggle('fa-eye');
        icon.classList.toggle('fa-eye-slash');
    });
}
setupPasswordToggle('toggleAddPassword', 'addPassword');
setupPasswordToggle('toggleEditPassword', 'editPassword');

const lihatWargaModal = document.getElementById('lihatWargaModal');
lihatWargaModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const nama = button.getAttribute('data-nama');
    const rumah = button.getAttribute('data-rumah');
    const user = button.getAttribute('data-user');
    lihatWargaModal.querySelector('#lihat_nama_lengkap').value = nama;
    lihatWargaModal.querySelector('#lihat_no_rumah').value = rumah;
    lihatWargaModal.querySelector('#lihat_username').value = user;
});

const editWargaModal = document.getElementById('editWargaModal');
editWargaModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const id = button.getAttribute('data-id');
    const nama = button.getAttribute('data-nama');
    const rumah = button.getAttribute('data-rumah');
    const user = button.getAttribute('data-user');
    editWargaModal.querySelector('#edit_id_warga').value = id;
    editWargaModal.querySelector('#edit_nama_lengkap').value = nama;
    editWargaModal.querySelector('#edit_no_rumah').value = rumah;
    editWargaModal.querySelector('#edit_username').value = user;
});

const hapusWargaModal = document.getElementById('hapusWargaModal');
hapusWargaModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const id = button.getAttribute('data-id');
    hapusWargaModal.querySelector('#hapus_id_warga').value = id;
});

<?php if (isset($_SESSION['flash_message'])): ?>
    const flashMessage = <?php echo json_encode($_SESSION['flash_message']); ?>;
    Swal.fire({
        title: (flashMessage.type === 'success' || flashMessage.type === 'info') ? 'Berhasil!' : 'Oops...',
        text: flashMessage.message,
        icon: flashMessage.type,
        timer: 3000,
        showConfirmButton: false
    });
    <?php unset($_SESSION['flash_message']); ?>
<?php endif; ?>
</script>