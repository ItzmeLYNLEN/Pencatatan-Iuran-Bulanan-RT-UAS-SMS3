<?php
ob_start();
include 'templates/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    
    // --- 1. TAMBAH WARGA ---
    if ($_POST['action'] == 'tambah_warga') {
        $nama = $_POST['nama_lengkap'];
        $no_rumah = $_POST['no_rumah'];
        $no_hp = $_POST['no_telepon']; 
        $username = $_POST['username'];
        $password = $_POST['password'];

        if (empty($username) || empty($password)) {
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Gagal: Username dan Password wajib diisi!'];
            header("Location: kelola_warga.php");
            exit();
        }

        $conn->begin_transaction();
        try {
            $stmt_user = $conn->prepare("INSERT INTO pengguna (username, password, role) VALUES (?, ?, 'warga')");
            $stmt_user->bind_param("ss", $username, $password);
            $stmt_user->execute();
            $id_pengguna = $conn->insert_id;

            $stmt_profil = $conn->prepare("INSERT INTO profil_warga (id_pengguna, nama_lengkap, no_rumah, no_telepon) VALUES (?, ?, ?, ?)");
            $stmt_profil->bind_param("isss", $id_pengguna, $nama, $no_rumah, $no_hp);
            $stmt_profil->execute();

            $conn->commit();
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Warga dan akun berhasil ditambahkan.'];
        } catch (Exception $e) {
            $conn->rollback();
            if ($conn->errno == 1062) {
                $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Gagal: Username sudah terdaftar.'];
            } else {
                $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Gagal: ' . $e->getMessage()];
            }
        }
    } 
    
    // --- 2. EDIT WARGA (UPDATE ROLE & DATA) ---
    elseif ($_POST['action'] == 'edit_warga') {
        $id_warga = $_POST['id_warga'];
        $nama = $_POST['nama_lengkap'];
        $no_rumah = $_POST['no_rumah'];
        $no_hp = $_POST['no_telepon'];
        $role_input = $_POST['role']; // Input Role Baru
        
        $id_pengguna = !empty($_POST['id_pengguna']) ? $_POST['id_pengguna'] : NULL;
        
        $username_input = $_POST['username'];
        $password_input = $_POST['password'];

        $conn->begin_transaction();
        try {
            // A. Update Profil Warga
            $stmt = $conn->prepare("UPDATE profil_warga SET nama_lengkap = ?, no_rumah = ?, no_telepon = ? WHERE id_warga = ?");
            $stmt->bind_param("sssi", $nama, $no_rumah, $no_hp, $id_warga);
            $stmt->execute();

            // B. Cek/Buat Akun Login
            if (empty($id_pengguna) && !empty($username_input) && !empty($password_input)) {
                // Buat akun baru
                $stmt_new = $conn->prepare("INSERT INTO pengguna (username, password, role) VALUES (?, ?, ?)");
                $stmt_new->bind_param("sss", $username_input, $password_input, $role_input);
                $stmt_new->execute();
                $new_id_pengguna = $conn->insert_id;

                $stmt_link = $conn->prepare("UPDATE profil_warga SET id_pengguna = ? WHERE id_warga = ?");
                $stmt_link->bind_param("ii", $new_id_pengguna, $id_warga);
                $stmt_link->execute();
                
                $id_pengguna = $new_id_pengguna; 
            }
            elseif (!empty($id_pengguna)) {
                // Update akun lama
                if (!empty($username_input)) {
                    $stmt_un = $conn->prepare("UPDATE pengguna SET username = ? WHERE id_pengguna = ?");
                    $stmt_un->bind_param("si", $username_input, $id_pengguna);
                    $stmt_un->execute();
                }
                if (!empty($password_input)) {
                    $stmt_pw = $conn->prepare("UPDATE pengguna SET password = ? WHERE id_pengguna = ?");
                    $stmt_pw->bind_param("si", $password_input, $id_pengguna);
                    $stmt_pw->execute();
                }
                // Update Role di tabel pengguna
                $stmt_role = $conn->prepare("UPDATE pengguna SET role = ? WHERE id_pengguna = ?");
                $stmt_role->bind_param("si", $role_input, $id_pengguna);
                $stmt_role->execute();
            }

            // C. Sinkronisasi Profil Admin (INI YANG DIPERBAIKI)
            if (!empty($id_pengguna)) {
                if ($role_input == 'admin') {
                    // Jika jadi ADMIN: Tambahkan ke profil_admin jika belum ada
                    $cek_admin = $conn->query("SELECT id_admin FROM profil_admin WHERE id_pengguna = $id_pengguna");
                    if ($cek_admin->num_rows == 0) {
                        $stmt_add = $conn->prepare("INSERT INTO profil_admin (id_pengguna, nama_lengkap) VALUES (?, ?)");
                        $stmt_add->bind_param("is", $id_pengguna, $nama);
                        $stmt_add->execute();
                    } else {
                        // Update nama juga biar sinkron
                        $stmt_upd = $conn->prepare("UPDATE profil_admin SET nama_lengkap = ? WHERE id_pengguna = ?");
                        $stmt_upd->bind_param("si", $nama, $id_pengguna);
                        $stmt_upd->execute();
                    }
                } 
                elseif ($role_input == 'warga') {
                    // Jika jadi WARGA: HAPUS dari profil_admin (Bersih-bersih)
                    $stmt_del = $conn->prepare("DELETE FROM profil_admin WHERE id_pengguna = ?");
                    $stmt_del->bind_param("i", $id_pengguna);
                    $stmt_del->execute();
                }
            }

            $conn->commit();
            $_SESSION['flash_message'] = ['type' => 'info', 'message' => 'Data warga dan role berhasil diperbarui.'];

        } catch (Exception $e) {
            $conn->rollback();
            $msg = ($conn->errno == 1062) ? 'Username sudah digunakan.' : $e->getMessage();
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Gagal: ' . $msg];
        }
    } 
    
    // --- 3. HAPUS WARGA ---
    elseif ($_POST['action'] == 'hapus_warga') {
        $id_warga = $_POST['id_warga'];
        
        $get_user = $conn->query("SELECT id_pengguna FROM profil_warga WHERE id_warga = $id_warga");
        $row = $get_user->fetch_assoc();
        $id_pengguna = $row['id_pengguna'];

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("DELETE FROM profil_warga WHERE id_warga = ?");
            $stmt->bind_param("i", $id_warga);
            $stmt->execute();

            if ($id_pengguna) {
                $conn->query("DELETE FROM pengguna WHERE id_pengguna = $id_pengguna");
            }

            $conn->commit();
            $_SESSION['flash_message'] = ['type' => 'warning', 'message' => 'Data warga dihapus.'];
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Gagal hapus: ' . $e->getMessage()];
        }
    }
    header("Location: kelola_warga.php");
    exit();
}

// --- TAMPILAN DATA ---
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$where_clause = "";
if (!empty($search)) {
    $where_clause = "WHERE w.nama_lengkap LIKE '%$search%' OR w.no_rumah LIKE '%$search%' OR p.username LIKE '%$search%'";
}

$count_query = "SELECT COUNT(w.id_warga) as total FROM profil_warga w LEFT JOIN pengguna p ON w.id_pengguna = p.id_pengguna $where_clause";
$total_warga_result = $conn->query($count_query);
$total_warga = $total_warga_result->fetch_assoc()['total'];
$total_pages = ($limit == -1) ? 1 : ceil($total_warga / $limit);

$sql_warga = "SELECT w.*, p.username, p.role FROM profil_warga w LEFT JOIN pengguna p ON w.id_pengguna = p.id_pengguna $where_clause ORDER BY w.no_rumah ASC";
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
                <div class="col-md-3 mb-3">
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" class="form-control" name="nama_lengkap" required>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">No. Rumah</label>
                    <input type="text" class="form-control" name="no_rumah" required>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">No. HP</label>
                    <input type="text" class="form-control" name="no_telepon" required>
                </div>
                
                <div class="col-md-2 mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" name="username" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" name="password" id="addPassword" required>
                        <button class="btn btn-outline-secondary" type="button" id="toggleAddPassword"><i class="fas fa-eye"></i></button>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Simpan</button>
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
                        <option value="5" <?php if ($limit == 5) echo 'selected'; ?>>5</option>
                        <option value="10" <?php if ($limit == 10) echo 'selected'; ?>>10</option>
                        <option value="25" <?php if ($limit == 25) echo 'selected'; ?>>25</option>
                        <option value="50" <?php if ($limit == 50) echo 'selected'; ?>>50</option>
                        <option value="-1" <?php if ($limit == -1) echo 'selected'; ?>>Semua</option>
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
                <thead class="table-dark">
                    <tr><th>No</th><th>Nama</th><th>Rumah</th><th>HP</th><th>Akun/Role</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0) : $no = $offset + 1; ?>
                        <?php while ($row = $result->fetch_assoc()) : ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                                <td><?php echo htmlspecialchars($row['no_rumah']); ?></td>
                                <td><?php echo htmlspecialchars($row['no_telepon']); ?></td>
                                <td>
                                    <?php 
                                        if($row['username']) {
                                            echo htmlspecialchars($row['username']);
                                            if($row['role'] == 'admin') echo ' <span class="badge bg-danger">Admin</span>';
                                            else echo ' <span class="badge bg-secondary">Warga</span>';
                                        } else {
                                            echo '-';
                                        }
                                    ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info lihatBtn" 
                                        data-bs-toggle="modal" data-bs-target="#lihatWargaModal" 
                                        data-nama="<?php echo htmlspecialchars($row['nama_lengkap']); ?>" 
                                        data-rumah="<?php echo htmlspecialchars($row['no_rumah']); ?>" 
                                        data-hp="<?php echo htmlspecialchars($row['no_telepon']); ?>" 
                                        data-user="<?php echo htmlspecialchars($row['username'] ?? '-'); ?>">Lihat</button>
                                    
                                    <button type="button" class="btn btn-sm btn-warning editBtn" 
                                        data-bs-toggle="modal" data-bs-target="#editWargaModal" 
                                        data-id="<?php echo $row['id_warga']; ?>" 
                                        data-iduser="<?php echo $row['id_pengguna']; ?>" 
                                        data-nama="<?php echo htmlspecialchars($row['nama_lengkap']); ?>" 
                                        data-rumah="<?php echo htmlspecialchars($row['no_rumah']); ?>" 
                                        data-hp="<?php echo htmlspecialchars($row['no_telepon']); ?>" 
                                        data-user="<?php echo htmlspecialchars($row['username'] ?? ''); ?>"
                                        data-role="<?php echo htmlspecialchars($row['role'] ?? 'warga'); ?>">Edit</button>
                                    
                                    <button type="button" class="btn btn-sm btn-danger hapusBtn" 
                                        data-bs-toggle="modal" data-bs-target="#hapusWargaModal" 
                                        data-id="<?php echo $row['id_warga']; ?>">Hapus</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <tr><td colspan="6" class="text-center">Data tidak ditemukan.</td></tr>
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
                            <a class="page-link" href="?limit=<?php echo $limit; ?>&search=<?php echo $search; ?>&page=<?php echo $page - 1; ?>">Sebelumnya</a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++) : ?>
                            <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                <a class="page-link" href="?limit=<?php echo $limit; ?>&search=<?php echo $search; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?limit=<?php echo $limit; ?>&search=<?php echo $search; ?>&page=<?php echo $page + 1; ?>">Selanjutnya</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="lihatWargaModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Detail Data Warga</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Nama Lengkap</label><input type="text" class="form-control" id="lihat_nama_lengkap" readonly></div>
                <div class="mb-3"><label class="form-label">No. Rumah</label><input type="text" class="form-control" id="lihat_no_rumah" readonly></div>
                <div class="mb-3"><label class="form-label">No. HP</label><input type="text" class="form-control" id="lihat_no_hp" readonly></div>
                <div class="mb-3"><label class="form-label">Username</label><input type="text" class="form-control" id="lihat_username" readonly></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button></div>
        </div>
    </div>
</div>

<div class="modal fade" id="editWargaModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Edit Data Warga</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="edit_warga">
                <div class="modal-body">
                    <input type="hidden" name="id_warga" id="edit_id_warga">
                    <input type="hidden" name="id_pengguna" id="edit_id_pengguna">
                    
                    <div class="mb-3"><label class="form-label">Nama Lengkap</label><input type="text" class="form-control" name="nama_lengkap" id="edit_nama_lengkap" required></div>
                    <div class="mb-3"><label class="form-label">No. Rumah</label><input type="text" class="form-control" name="no_rumah" id="edit_no_rumah" required></div>
                    <div class="mb-3"><label class="form-label">No. HP</label><input type="text" class="form-control" name="no_telepon" id="edit_hp"></div>
                    
                    <hr>
                    <p class="text-muted small">Pengaturan Akun</p>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Role</label>
                            <select name="role" id="edit_role" class="form-select">
                                <option value="warga">Warga</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" id="edit_username">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password (Kosongkan jika tidak diubah)</label>
                        <div class="input-group">
                            <input type="password" class="form-control" name="password" id="editPassword">
                            <button class="btn btn-outline-secondary" type="button" id="toggleEditPassword"><i class="fas fa-eye"></i></button>
                        </div>
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
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Konfirmasi Hapus</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="hapus_warga">
                <input type="hidden" name="id_warga" id="hapus_id_warga">
                <div class="modal-body"><p>Yakin hapus warga ini? Data akun login dan history transaksi terkait juga mungkin akan terpengaruh.</p></div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-danger">Ya, Hapus</button></div>
            </form>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>

<script>
    function setupPasswordToggle(toggleButtonId, passwordInputId) {
        const toggleButton = document.getElementById(toggleButtonId);
        const passwordInput = document.getElementById(passwordInputId);
        if(toggleButton && passwordInput){
            toggleButton.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                const icon = toggleButton.querySelector('i');
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            });
        }
    }
    setupPasswordToggle('toggleAddPassword', 'addPassword');
    setupPasswordToggle('toggleEditPassword', 'editPassword');

    const lihatModal = document.getElementById('lihatWargaModal');
    lihatModal.addEventListener('show.bs.modal', function(event) {
        const btn = event.relatedTarget;
        lihatModal.querySelector('#lihat_nama_lengkap').value = btn.getAttribute('data-nama');
        lihatModal.querySelector('#lihat_no_rumah').value = btn.getAttribute('data-rumah');
        lihatModal.querySelector('#lihat_no_hp').value = btn.getAttribute('data-hp');
        lihatModal.querySelector('#lihat_username').value = btn.getAttribute('data-user');
    });

    const editModal = document.getElementById('editWargaModal');
    editModal.addEventListener('show.bs.modal', function(event) {
        const btn = event.relatedTarget;
        editModal.querySelector('#edit_id_warga').value = btn.getAttribute('data-id');
        editModal.querySelector('#edit_id_pengguna').value = btn.getAttribute('data-iduser');
        editModal.querySelector('#edit_nama_lengkap').value = btn.getAttribute('data-nama');
        editModal.querySelector('#edit_no_rumah').value = btn.getAttribute('data-rumah');
        editModal.querySelector('#edit_hp').value = btn.getAttribute('data-hp');
        editModal.querySelector('#edit_username').value = btn.getAttribute('data-user');
        editModal.querySelector('#edit_role').value = btn.getAttribute('data-role'); // Load Role
        editModal.querySelector('#editPassword').value = ''; 
    });

    const hapusModal = document.getElementById('hapusWargaModal');
    hapusModal.addEventListener('show.bs.modal', function(event) {
        hapusModal.querySelector('#hapus_id_warga').value = event.relatedTarget.getAttribute('data-id');
    });

    <?php if (isset($_SESSION['flash_message'])) : ?>
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

<?php
ob_end_flush();
?>