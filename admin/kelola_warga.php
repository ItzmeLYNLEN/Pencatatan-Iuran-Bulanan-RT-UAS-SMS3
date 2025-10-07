<?php
include 'templates/header.php';

// --- LOGIKA UNTUK PROSES DATA ---

// 1. PROSES TAMBAH WARGA (dengan password hashing)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_warga'])) {
    $nama = $_POST['nama_lengkap'];
    $no_rumah = $_POST['no_rumah'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Amankan password
    $role = 'warga';

    $stmt = $conn->prepare("INSERT INTO warga (nama_lengkap, no_rumah, username, password, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $nama, $no_rumah, $username, $password, $role);
    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>Warga baru berhasil ditambahkan.</div>";
    } else {
        echo "<div class='alert alert-danger'>Gagal menambahkan warga: " . $stmt->error . "</div>";
    }
}

// 2. PROSES EDIT WARGA
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_warga'])) {
    $id_warga = $_POST['id_warga'];
    $nama = $_POST['nama_lengkap'];
    $no_rumah = $_POST['no_rumah'];
    $username = $_POST['username'];

    // Cek apakah password diisi atau tidak
    if (!empty($_POST['password'])) {
        // Jika diisi, update password dengan hash baru
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE warga SET nama_lengkap = ?, no_rumah = ?, username = ?, password = ? WHERE id_warga = ?");
        $stmt->bind_param("ssssi", $nama, $no_rumah, $username, $password, $id_warga);
    } else {
        // Jika password kosong, jangan update password
        $stmt = $conn->prepare("UPDATE warga SET nama_lengkap = ?, no_rumah = ?, username = ? WHERE id_warga = ?");
        $stmt->bind_param("sssi", $nama, $no_rumah, $username, $id_warga);
    }

    if ($stmt->execute()) {
        echo "<div class='alert alert-info'>Data warga berhasil diperbarui.</div>";
    } else {
        echo "<div class='alert alert-danger'>Gagal memperbarui data: " . $stmt->error . "</div>";
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
        echo "<div class='alert alert-danger'>Gagal menghapus data: " . $stmt->error . "</div>";
    }
}

// Ambil data warga untuk ditampilkan di tabel
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
                <div class="col-md-3 mb-3"><label class="form-label">Password</label><input type="password" class="form-control" name="password" required></div>
            </div>
            <button type="submit" name="tambah_warga" class="btn btn-primary">Tambah Warga</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">Daftar Warga</div>
    <div class="card-body">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Lengkap</th>
                    <th>No. Rumah</th>
                    <th>Username</th>
                    <th>Aksi</th> </tr>
            </thead>
            <tbody>
                <?php $no = 1; if ($result->num_rows > 0): while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                    <td><?php echo htmlspecialchars($row['no_rumah']); ?></td>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td>
                        <button type="button" class="btn btn-sm btn-warning editBtn" 
                                data-bs-toggle="modal" data-bs-target="#editWargaModal"
                                data-id="<?php echo $row['id_warga']; ?>"
                                data-nama="<?php echo htmlspecialchars($row['nama_lengkap']); ?>"
                                data-rumah="<?php echo htmlspecialchars($row['no_rumah']); ?>"
                                data-user="<?php echo htmlspecialchars($row['username']); ?>">
                            Edit
                        </button>

                        <button type="button" class="btn btn-sm btn-danger hapusBtn"
                                data-bs-toggle="modal" data-bs-target="#hapusWargaModal"
                                data-id="<?php echo $row['id_warga']; ?>">
                            Hapus
                        </button>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="5" class="text-center">Belum ada data warga.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="editWargaModal" tabindex="-1" aria-labelledby="editWargaModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editWargaModalLabel">Edit Data Warga</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="">
        <div class="modal-body">
            <input type="hidden" name="id_warga" id="edit_id_warga">
            <div class="mb-3">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" class="form-control" name="nama_lengkap" id="edit_nama_lengkap" required>
            </div>
            <div class="mb-3">
                <label class="form-label">No. Rumah</label>
                <input type="text" class="form-control" name="no_rumah" id="edit_no_rumah" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" name="username" id="edit_username" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password Baru</label>
                <input type="password" class="form-control" name="password" id="edit_password">
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

<div class="modal fade" id="hapusWargaModal" tabindex="-1" aria-labelledby="hapusWargaModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="hapusWargaModalLabel">Konfirmasi Hapus</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="">
        <div class="modal-body">
            <p>Apakah Anda yakin ingin menghapus data warga ini?</p>
            <input type="hidden" name="id_warga" id="hapus_id_warga">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" name="hapus_warga" class="btn btn-danger">Ya, Hapus</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include 'templates/footer.php'; ?>

<script>
// Script untuk Modal Edit
const editWargaModal = document.getElementById('editWargaModal');
editWargaModal.addEventListener('show.bs.modal', function (event) {
    // Tombol yang memicu modal
    const button = event.relatedTarget;
    
    // Ekstrak data dari atribut data-*
    const id = button.getAttribute('data-id');
    const nama = button.getAttribute('data-nama');
    const rumah = button.getAttribute('data-rumah');
    const user = button.getAttribute('data-user');
    
    // Perbarui konten modal
    const modalBodyInputId = editWargaModal.querySelector('#edit_id_warga');
    const modalBodyInputNama = editWargaModal.querySelector('#edit_nama_lengkap');
    const modalBodyInputRumah = editWargaModal.querySelector('#edit_no_rumah');
    const modalBodyInputUser = editWargaModal.querySelector('#edit_username');
    
    modalBodyInputId.value = id;
    modalBodyInputNama.value = nama;
    modalBodyInputRumah.value = rumah;
    modalBodyInputUser.value = user;
});

// Script untuk Modal Hapus
const hapusWargaModal = document.getElementById('hapusWargaModal');
hapusWargaModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const id = button.getAttribute('data-id');
    const modalBodyInputId = hapusWargaModal.querySelector('#hapus_id_warga');
    modalBodyInputId.value = id;
});
</script>