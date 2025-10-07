<?php
// admin/templates/header.php
// Saya memindahkan session_start() ke sini agar ada di setiap halaman
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php"); // Arahkan ke login utama, bukan admin
    exit;
}

// Ambil nama file saat ini untuk menandai menu aktif
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">Admin Iuran RT</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>" href="index.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo ($current_page == 'kelola_warga.php') ? 'active' : ''; ?>" href="kelola_warga.php">Kelola Warga</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo ($current_page == 'catat_pembayaran.php') ? 'active' : ''; ?>" href="catat_pembayaran.php">Catat Pembayaran</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo ($current_page == 'laporan.php') ? 'active' : ''; ?>" href="laporan.php">Laporan Iuran</a></li>
                </ul>
                <span class="navbar-text text-white me-3">
                    Halo, <?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?>
                </span>
                <a href="../logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </nav>
    <div class="container mt-4">