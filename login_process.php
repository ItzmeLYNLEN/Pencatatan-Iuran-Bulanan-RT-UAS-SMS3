<?php
// login_process.php (VERSI TANPA HASH - PERBAIKAN)
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT id_warga, nama_lengkap, password, role FROM warga WHERE username = ?");
    
    // !! INI PERBAIKANNYA !!
    // Sebelumnya: $stmt->bind_param("s", "s", $username); (SALAH)
    // Seharusnya hanya ada satu "s" karena hanya ada satu tanda tanya (?) di query SQL.
    $stmt->bind_param("s", $username); // (BENAR)

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Membandingkan teks password secara langsung
        if ($password === $user['password']) {
            $_SESSION['user_id'] = $user['id_warga'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] == 'admin') {
                header("Location: admin/index.php");
            } else {
                header("Location: warga/index.php");
            }
            exit;
        } else {
            header("Location: index.php?error=Username atau password salah!");
            exit;
        }
    } else {
        header("Location: index.php?error=Username atau password salah!");
        exit;
    }

    $stmt->close();
    $conn->close();
}
?>