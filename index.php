<?php
session_start();
require_once 'config/db.php'; 


if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: admin/index.php");
    } else {
        header("Location: warga/index.php");
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Aplikasi Iuran RT</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

    <div class="login-container">
        <div class="form-panel">
            <div class="form-content">
                <h2>Login</h2>
                
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
                <?php endif; ?>

                <form action="login_process.php" method="POST">
                    <div class="input-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" placeholder="Masukkan username Anda" required>
                    </div>
                    
                    <div class="input-group">
                        <label for="password">Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="password" name="password" placeholder="••••••••" required>
                            <i class="fa-solid fa-eye-slash" id="togglePassword"></i>
                        </div>
                    </div>
                    
                    
                    
                    <button type="submit" class="btn-login">Login</button>
                </form>

                <div class="social-login">
                    <p>Aplikasi Iuran Warga RT 04</p>
                    <p style="font-size: 0.8em; color: #aaa;">Klapanunggal, Kab. Bogor</p>
                </div>
            </div>
        </div>

        <div class="overlay-panel">
            <div class="overlay-content">
                <h2>Selamat Datang!</h2>
                <p>Sistem informasi untuk mempermudah pengelolaan data iuran warga RT 04.</p>
            </div>
        </div>
    </div>

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        togglePassword.addEventListener('click', function (e) {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            
            if (this.classList.contains('fa-eye-slash')) {
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            } else {
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            }
        });
    </script>

</body>
</html>