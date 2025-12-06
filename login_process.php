<?php
require_once 'config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        header("Location: index.php?error=Username dan password tidak boleh kosong!");
        exit;
    }

    $stmt = $conn->prepare("SELECT id_pengguna, password, role FROM pengguna WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if ($password === $user['password']) {
            $_SESSION['user_id'] = $user['id_pengguna'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['nama_lengkap'] = $username; 

            $stmt_warga = $conn->prepare("SELECT id_warga, nama_lengkap, no_rumah FROM profil_warga WHERE id_pengguna = ?");
            $stmt_warga->bind_param("i", $user['id_pengguna']);
            $stmt_warga->execute();
            $res_warga = $stmt_warga->get_result();
            
            if ($row_warga = $res_warga->fetch_assoc()) {
                $_SESSION['id_warga'] = $row_warga['id_warga'];
                $_SESSION['nama_lengkap'] = $row_warga['nama_lengkap'];
                $_SESSION['no_rumah'] = $row_warga['no_rumah'];
            }

            if ($user['role'] == 'admin') {
                $stmt_admin = $conn->prepare("SELECT id_admin, nama_lengkap FROM profil_admin WHERE id_pengguna = ?");
                $stmt_admin->bind_param("i", $user['id_pengguna']);
                $stmt_admin->execute();
                $res_admin = $stmt_admin->get_result();
                
                if ($row_admin = $res_admin->fetch_assoc()) {
                    $_SESSION['id_admin'] = $row_admin['id_admin'];
                    $_SESSION['nama_lengkap'] = $row_admin['nama_lengkap']; 
                }
                header("Location: admin/index.php");
            } else {
                header("Location: warga/index.php");
            }
            exit;
        }
    }
    
    header("Location: index.php?error=Username atau password salah!");
    exit;
}
?>