<?php

include 'templates/header.php';


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $id_admin = $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT password FROM warga WHERE id_warga = ? AND role = 'admin'");
    $stmt->bind_param("i", $id_admin);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

 
    if ($user && $current_password === $user['password']) {
        if ($new_password === $confirm_password) {
            if (strlen($new_password) >= 6) {
              
                $update_stmt = $conn->prepare("UPDATE warga SET password = ? WHERE id_warga = ?");
                $update_stmt->bind_param("si", $new_password, $id_admin);

                if ($update_stmt->execute()) {
                    $_SESSION['flash_message'] = ['type' => 'success', 'title' => 'Berhasil!', 'message' => 'Password Anda telah berhasil diperbarui.'];
                } else {
                    $_SESSION['flash_message'] = ['type' => 'error', 'title' => 'Gagal', 'message' => 'Terjadi kesalahan saat memperbarui password.'];
                }
            } else {
                 $_SESSION['flash_message'] = ['type' => 'error', 'title' => 'Gagal', 'message' => 'Password baru minimal harus 6 karakter.'];
            }
        } else {
            $_SESSION['flash_message'] = ['type' => 'error', 'title' => 'Gagal', 'message' => 'Password baru dan konfirmasi tidak cocok.'];
        }
    } else {
        $_SESSION['flash_message'] = ['type' => 'error', 'title' => 'Gagal', 'message' => 'Password saat ini yang Anda masukkan salah.'];
    }
    header("Location: profile.php");
    exit();
}
?>

<head>
    <title>Profile Admin - Iuran RT</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f2f5;
        }
        .profile-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 56px); 
        }
        .profile-card {
            width: 100%;
            max-width: 500px;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border: none;
        }
        .profile-card-header {
            background-color: #343a40; 
            color: white;
            padding: 20px 30px;
        }
        .profile-card-header h4 {
            margin: 0;
            font-weight: 600;
        }
        .profile-card-body {
            padding: 30px;
        }
        .input-group-custom {
            position: relative;
        }
        .input-group-custom .form-control {
            border: none;
            border-bottom: 2px solid #eee;
            border-radius: 0;
            padding-left: 35px;
            transition: border-color 0.3s;
        }
        .input-group-custom .form-control:focus {
            box-shadow: none;
            border-bottom-color: #343a40;
        }
        .input-group-custom .input-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
        }
        .input-group-custom .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #aaa;
        }
    </style>
</head>

<div class="profile-wrapper">
    <div class="profile-card">
        <div class="profile-card-header">
            <h4><i class="fas fa-user-cog me-2"></i>Profile Admin</h4>
        </div>
        <div class="profile-card-body">
            <form method="POST" action="">
                <div class="mb-4 input-group-custom">
                    <i class="fas fa-lock-open input-icon"></i>
                    <input type="password" class="form-control" id="current_password" name="current_password" placeholder="Password Saat Ini" required>
                    <i class="fas fa-eye-slash toggle-password" data-target="current_password"></i>
                </div>
                <div class="mb-4 input-group-custom">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Password Baru (min. 6 karakter)" required>
                    <i class="fas fa-eye-slash toggle-password" data-target="new_password"></i>
                </div>
                <div class="mb-4 input-group-custom">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Konfirmasi Password Baru" required>
                    <i class="fas fa-eye-slash toggle-password" data-target="confirm_password"></i>
                </div>
                <div class="d-grid gap-2 mt-4">
                    <button type="submit" name="change_password" class="btn btn-dark btn-lg">Simpan Perubahan</button>
                    <a href="index.php" class="btn btn-outline-secondary">Kembali ke Dashboard</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (isset($_SESSION['flash_message'])): ?>
        const flashMessage = <?php echo json_encode($_SESSION['flash_message']); ?>;
        Swal.fire({
            title: flashMessage.title,
            text: flashMessage.message,
            icon: flashMessage.type,
            confirmButtonText: 'OK'
        });
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function() {
            const targetInputId = this.getAttribute('data-target');
            const passwordInput = document.getElementById(targetInputId);
            
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            this.classList.toggle('fa-eye-slash');
            this.classList.toggle('fa-eye');
        });
    });
});
</script>