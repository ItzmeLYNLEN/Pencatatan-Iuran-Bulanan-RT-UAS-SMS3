<?php
include 'templates/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_warga'])) {
    $nama = $_POST['nama_lengkap'];
    $no_rumah = $_POST['no_rumah'];
    $username = $_POST['username'];
    
    // !! PERUBAHAN KUNCI !!
    // Kita tidak lagi menggunakan password_hash()
    // Password dari form langsung disimpan ke database
    $password = $_POST['password']; 
    
    $role = 'warga';

    $stmt = $conn->prepare("INSERT INTO warga (nama_lengkap, no_rumah, username, password, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $nama, $no_rumah, $username, $password, $role);
    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>Warga baru berhasil ditambahkan.</div>";
    } else {
        echo "<div class='alert alert-danger'>Gagal menambahkan warga: " . $stmt->error . "</div>";
    }
}
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
            <thead><tr><th>No</th><th>Nama Lengkap</th><th>No. Rumah</th><th>Username</th></tr></thead>
            <tbody>
                <?php $no = 1; if ($result->num_rows > 0): while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                    <td><?php echo htmlspecialchars($row['no_rumah']); ?></td>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="4" class="text-center">Belum ada data warga.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include 'templates/footer.php'; ?>