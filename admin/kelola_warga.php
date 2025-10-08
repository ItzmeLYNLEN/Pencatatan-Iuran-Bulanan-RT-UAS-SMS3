<?php
include 'templates/header.php';

// --- LOGIKA UNTUK PROSES DATA (Tidak ada perubahan signifikan) ---

// 1. PROSES TAMBAH WARGA
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_warga'])) {
    $nama = $_POST['nama_lengkap'];
    $no_rumah = $_POST['no_rumah'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = 'warga';
    $stmt = $conn->prepare("INSERT INTO warga (nama_lengkap, no_rumah, username, password, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $nama, $no_rumah, $username, $password, $role);
    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>Warga baru berhasil ditambahkan.</div>";
    } else {
        echo "<div class='alert alert-danger'>Gagal: " . $stmt->error . "</div>";
    }
}

// 2. PROSES EDIT WARGA
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_warga'])) {
    $id_warga = $_POST['id_warga'];
    $nama = $_POST['nama_lengkap'];
    $no_rumah = $_POST['no_rumah'];
    $username = $_POST['username'];
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE warga SET nama_lengkap = ?, no_rumah = ?, username = ?, password = ? WHERE id_warga = ?");
        $stmt->bind_param("ssssi", $nama, $no_rumah, $username, $password, $id_warga);
    } else {
        $stmt = $conn->prepare("UPDATE warga SET nama_lengkap = ?, no_rumah = ?, username = ? WHERE id_warga = ?");
        $stmt->bind_param("sssi", $nama, $no_rumah, $username, $id_warga);
    }
    if ($stmt->execute()) {
        echo "<div class='alert alert-info'>Data warga berhasil diperbarui.</div>";
    } else {
        echo "<div class='alert alert-danger'>Gagal: " . $stmt->error . "</div>";
    }
}

// 3. PROSES HAPUS WARGA
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['hapus_warga'])) {
    $id_warga = $_POST['id_warga'];
    $stmt = $conn->prepare("DELETE FROM warga WHERE id_warga = ?");
    $stmt->bind_param("i", $id_warga);
    if ($stmt->execute()) {
        echo "<div class='alert alert-warning'>Data warga berhasil dihapus.</div>";
    } else {
        echo "<div class='alert alert-danger'>Gagal: " . $stmt->error . "</div>";
    }
}

// Ambil data warga
$result = $conn->query("SELECT id_warga, nama_lengkap, no_rumah, username FROM warga WHERE role = 'warga' ORDER BY no_rumah ASC");
?>

<h3>Kelola Data Warga</h3>
<hr>

<div class="card mb-4">
    <div class="card-header">Tambah Warga Baru</div>
    <div class="card-body">
        <form method="POST" action="">
            <div class="row">
                <div class="col-md-4 mb-3"><label class="form-label">Nama Lengkap</label><input type="text" class="form-control" name="nama_lengkap" required></div>
                <div class="col-md-2 mb-3"><label class="form-label">No. Rumah</label><input type="text" class="form-control" name="no_rumah" required></div>
                <div class="col-md-3 mb-3"><label class="form-label">Username</label><input type="text" class="form-control" name="username" required></div>
                
                <div class="col-md-3 mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" name="password" id="addPassword" required>
                        <button class="btn btn-outline-secondary" type="button" id="toggleAddPassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

            </div>
            <button type="submit" name="tambah_warga" class="btn btn-primary">Tambah Warga</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">Daftar Warga</div>
    <div class="card-body">
        <table class="table table-bordered table-striped">
            <thead><tr><th>No</th><th>Nama Lengkap</th><th>No. Rumah</th><th>Aksi</th></tr></thead>
            <tbody>
                <?php $no = 1; if ($result->num_rows > 0): while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                    <td><?php echo htmlspecialchars($row['no_rumah']); ?></td>
                    <td>
                        <button type="button" class="btn btn-sm btn-info lihatBtn" data-bs-toggle="modal" data-bs-target="#lihatWargaModal" data-nama="<?php echo htmlspecialchars($row['nama_lengkap']); ?>" data-rumah="<?php echo htmlspecialchars($row['no_rumah']); ?>" data-user="<?php echo htmlspecialchars($row['username']); ?>">Lihat</button>
                        <button type="button" class="btn btn-sm btn-warning editBtn" data-bs-toggle="modal" data-bs-target="#editWargaModal" data-id="<?php echo $row['id_warga']; ?>" data-nama="<?php echo htmlspecialchars($row['nama_lengkap']); ?>" data-rumah="<?php echo htmlspecialchars($row['no_rumah']); ?>" data-user="<?php echo htmlspecialchars($row['username']); ?>">Edit</button>
                        <button type="button" class="btn btn-sm btn-danger hapusBtn" data-bs-toggle="modal" data-bs-target="#hapusWargaModal" data-id="<?php echo $row['id_warga']; ?>">Hapus</button>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="4" class="text-center">Belum ada data warga.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
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
        <div class="modal-body">
            <input type="hidden" name="id_warga" id="edit_id_warga">
            <div class="mb-3"><label class="form-label">Nama Lengkap</label><input type="text" class="form-control" name="nama_lengkap" id="edit_nama_lengkap" required></div>
            <div class="mb-3"><label class="form-label">No. Rumah</label><input type="text" class="form-control" name="no_rumah" id="edit_no_rumah" required></div>
            <div class="mb-3"><label class="form-label">Username</label><input type="text" class="form-control" name="username" id="edit_username" required></div>
            
            <div class="mb-3">
                <label class="form-label">Password Baru</label>
                <div class="input-group">
                    <input type="password" class="form-control" name="password" id="editPassword">
                    <button class="btn btn-outline-secondary" type="button" id="toggleEditPassword">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <small class="form-text text-muted">Kosongkan jika tidak ingin mengubah password.</small>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" name="edit_warga" class="btn btn-primary">Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="hapusWargaModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Konfirmasi Hapus</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><form method="POST" action=""><div class="modal-body"><p>Apakah Anda yakin ingin menghapus data warga ini?</p><input type="hidden" name="id_warga" id="hapus_id_warga"></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" name="hapus_warga" class="btn btn-danger">Ya, Hapus</button></div></form></div></div>
</div>

<?php include 'templates/footer.php'; ?>

<script>
// SCRIPT BARU UNTUK TOGGLE PASSWORD
function setupPasswordToggle(toggleButtonId, passwordInputId) {
    const toggleButton = document.getElementById(toggleButtonId);
    const passwordInput = document.getElementById(passwordInputId);
    const icon = toggleButton.querySelector('i');

    toggleButton.addEventListener('click', function () {
        // Ganti tipe input
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        
        // Ganti ikon mata
        icon.classList.toggle('fa-eye');
        icon.classList.toggle('fa-eye-slash');
    });
}

// Terapkan fungsi ke kedua form password
setupPasswordToggle('toggleAddPassword', 'addPassword');
setupPasswordToggle('toggleEditPassword', 'editPassword');


// Script lama untuk mengisi data modal (Tidak berubah)
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
</script>