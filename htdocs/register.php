<?php
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO admin (nama, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $nama, $email, $password);

    if ($stmt->execute()) {
        header("Location: login.php?registered=1");
        exit;
    } else {
        $error = "Terjadi kesalahan saat mendaftar. Email mungkin sudah digunakan.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Admin | UNSERA</title>
    <link rel="stylesheet" href="style.css?v=4">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: #f1f5f9;
            margin: 0;
            overflow: hidden;
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            background: #ffffff;
            border: 1px solid var(--border-color);
            padding: 50px 40px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .logo-area {
            text-align: center;
            margin-bottom: 40px;
        }
        .logo-text {
            font-family: 'Outfit', sans-serif;
            font-size: 2.8rem;
            font-weight: 800;
            background: linear-gradient(135deg, #38bdf8, #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -2px;
            margin-bottom: 5px;
        }
        .login-card label {
            color: #94a3b8;
            font-weight: 500;
            margin-bottom: 10px;
            display: block;
            font-size: 0.9rem;
        }
        .login-card .form-control {
            background: #ffffff;
            border-color: var(--border-color);
            color: var(--text-main);
            padding: 14px 18px;
            border-radius: var(--radius-md);
        }
        .login-card .form-control:focus {
            background: #ffffff;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-soft);
        }
        .alert {
            padding: 14px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #fca5a5;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="logo-area">
        <img src="download.png" alt="Logo UNSERA" style="width: 80px; height: auto; margin-bottom: 5px; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));">
        <div class="logo-text" style="font-size: 1.8rem; letter-spacing: 0; margin-bottom: 6px;">UNIVERSITAS SERANG RAYA</div>
        <p style="color: #64748b; font-size: 0.85rem; font-weight: 500; letter-spacing: 1px;">REGISTRASI ADMINISTRATOR BARU</p>
    </div>

    <?php if(isset($error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?= $error ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group" style="margin-bottom: 20px;">
            <label>Nama Lengkap</label>
            <div style="position: relative;">
                <i class="fas fa-user" style="position: absolute; left: 18px; top: 16px; color: #475569;"></i>
                <input type="text" name="nama" class="form-control" placeholder="Administrator Name" required style="padding-left: 48px;">
            </div>
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label>Email Universitas</label>
            <div style="position: relative;">
                <i class="fas fa-envelope" style="position: absolute; left: 18px; top: 16px; color: #475569;"></i>
                <input type="email" name="email" class="form-control" placeholder="admin@unsera.ac.id" required style="padding-left: 48px;">
            </div>
        </div>

        <div class="form-group" style="margin-bottom: 35px;">
            <label>Password Akun</label>
            <div style="position: relative;">
                <i class="fas fa-lock" style="position: absolute; left: 18px; top: 16px; color: #475569;"></i>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required style="padding-left: 48px;">
            </div>
        </div>

        <button type="submit" name="register" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 1rem; font-weight: 700; border-radius: 12px; background: linear-gradient(135deg, #0ea5e9, #6366f1); border: none;">
            DAFTAR SEKARANG
        </button>
    </form>

    <div style="margin-top: 35px; text-align: center; color: #64748b; font-size: 0.9rem;">
        Sudah memiliki akun? <a href="login.php" style="color: #38bdf8; text-decoration: none; font-weight: 700; border-bottom: 2px solid rgba(56, 189, 248, 0.2);">Masuk ke Dashboard</a>
    </div>
</div>

</body>
</html>
