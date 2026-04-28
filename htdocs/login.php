<?php
require 'db.php';

if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, nama, password, jenis_kelamin FROM admin WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password']) || $password === $row['password']) {
            $_SESSION['admin_id'] = $row['id'];
            $_SESSION['admin_nama'] = $row['nama'];
            $_SESSION['admin_jk'] = $row['jenis_kelamin'] ?? 'Laki-laki';
            
            if ($password === $row['password']) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $updateStmt = $conn->prepare("UPDATE admin SET password = ? WHERE id = ?");
                $updateStmt->bind_param("si", $newHash, $row['id']);
                $updateStmt->execute();
                $updateStmt->close();
            }
            
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Password yang Anda masukkan salah.";
        }
    } else {
        $error = "Email tidak terdaftar.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Portal Kepegawaian UNSERA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --navy: #1e293b;
        }
        body {
            background-color: #f8fafc;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background-image: radial-gradient(circle at 0% 0%, rgba(37, 99, 235, 0.05) 0%, transparent 50%),
                              radial-gradient(circle at 100% 100%, rgba(37, 99, 235, 0.05) 0%, transparent 50%);
        }
        .login-card {
            width: 100%;
            max-width: 450px;
            background: white;
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
            border: 1px solid #f1f5f9;
        }
        .logo-box {
            width: 70px;
            height: 70px;
            margin: 0 auto 25px;
            background: #f8fafc;
            padding: 12px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-header {
            text-align: center;
            margin-bottom: 35px;
        }
        .login-header h1 {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 1.75rem;
            color: var(--navy);
            margin-bottom: 8px;
        }
        .login-header p {
            color: #64748b;
            font-size: 0.95rem;
        }
        .form-label {
            font-weight: 600;
            color: #475569;
            font-size: 0.85rem;
            margin-bottom: 8px;
        }
        .input-group-custom {
            position: relative;
            margin-bottom: 20px;
        }
        .input-group-custom i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1.1rem;
            transition: all 0.3s;
        }
        .form-control {
            border-radius: 14px;
            padding: 13px 18px 13px 48px;
            border: 2px solid #f1f5f9;
            background: #f8fafc;
            transition: all 0.2s;
            font-size: 1rem;
            color: var(--navy);
        }
        .form-control:focus {
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }
        .form-control:focus + i {
            color: var(--primary);
        }
        .btn-login {
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 14px;
            padding: 14px;
            font-weight: 700;
            width: 100%;
            margin-top: 10px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .btn-login:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.2);
        }
        .divider {
            margin: 25px 0;
            display: flex;
            align-items: center;
            text-align: center;
            color: #cbd5e1;
            font-size: 0.85rem;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #f1f5f9;
        }
        .divider:not(:empty)::before { margin-right: .75em; }
        .divider:not(:empty)::after { margin-left: .75em; }
        
        @media (max-width: 480px) {
            .login-card {
                padding: 30px 20px;
                border-radius: 20px;
            }
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="logo-box">
        <img src="download.png" style="width:100%; height:auto;">
    </div>
    
    <div class="login-header">
        <h1>Selamat Datang</h1>
        <p>Silakan masuk ke akun Anda</p>
    </div>

    <?php if(isset($error)): ?>
        <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center gap-3 p-3">
            <i class="fas fa-exclamation-circle fs-5"></i>
            <div class="small fw-bold"><?= $error ?></div>
        </div>
    <?php endif; ?>

    <?php if(isset($_GET['registered'])): ?>
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center gap-3 p-3">
            <i class="fas fa-check-circle fs-5"></i>
            <div class="small fw-bold">Pendaftaran berhasil! Silakan login.</div>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Email</label>
            <div class="input-group-custom">
                <input type="email" name="email" class="form-control" placeholder="admin@unsera.ac.id" required>
                <i class="fas fa-envelope"></i>
            </div>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Password</label>
            <div class="input-group-custom">
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                <i class="fas fa-lock"></i>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4 px-1">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="remember">
                <label class="form-check-label small text-muted cursor-pointer" for="remember">Ingat Saya</label>
            </div>
            <a href="#" class="small text-primary fw-bold text-decoration-none">Lupa?</a>
        </div>

        <button type="submit" name="login" class="btn btn-login">
            MASUK <i class="fas fa-arrow-right"></i>
        </button>
    </form>

    <div class="divider">ATAU</div>

    <div class="text-center">
        <p class="small text-muted mb-0">Belum punya akun?</p>
        <a href="register.php" class="text-primary fw-bold text-decoration-none">Daftar Akun Baru</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
