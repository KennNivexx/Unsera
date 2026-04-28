<?php
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $jenis_kelamin = $_POST['jenis_kelamin'];

    // Check if email already exists
    $checkStmt = $conn->prepare("SELECT id FROM admin WHERE email = ?");
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        $error = "Email sudah terdaftar. Silakan gunakan email lain.";
    } else {
        $stmt = $conn->prepare("INSERT INTO admin (nama, email, password, jenis_kelamin) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $nama, $email, $password, $jenis_kelamin);

        if ($stmt->execute()) {
            header("Location: login.php?registered=1");
            exit;
        } else {
            $error = "Terjadi kesalahan saat mendaftar. Silakan coba lagi.";
        }
        $stmt->close();
    }
    $checkStmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun | Portal Kepegawaian UNSERA</title>
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
        .register-card {
            width: 100%;
            max-width: 500px;
            background: white;
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
            border: 1px solid #f1f5f9;
        }
        .logo-box {
            width: 60px;
            height: 60px;
            margin: 0 auto 20px;
            background: #f8fafc;
            padding: 10px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .register-header h1 {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 1.6rem;
            color: var(--navy);
            margin-bottom: 8px;
        }
        .register-header p {
            color: #64748b;
            font-size: 0.9rem;
        }
        .form-label {
            font-weight: 600;
            color: #475569;
            font-size: 0.8rem;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .input-group-custom {
            position: relative;
            margin-bottom: 18px;
        }
        .input-group-custom i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1rem;
            transition: all 0.3s;
        }
        .form-control, .form-select {
            border-radius: 12px;
            padding: 12px 18px 12px 45px;
            border: 2px solid #f1f5f9;
            background: #f8fafc;
            transition: all 0.2s;
            font-size: 0.95rem;
            color: var(--navy);
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }
        .form-control:focus + i, .form-select:focus + i {
            color: var(--primary);
        }
        .btn-register {
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-weight: 700;
            width: 100%;
            margin-top: 5px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .btn-register:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.2);
        }
        
        @media (max-width: 480px) {
            .register-card {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>

<div class="register-card">
    <div class="logo-box">
        <img src="download.png" style="width:100%; height:auto;">
    </div>
    
    <div class="register-header">
        <h1>Daftar Akun Baru</h1>
        <p>Lengkapi formulir untuk mendaftar admin</p>
    </div>

    <?php if(isset($error)): ?>
        <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center gap-3 p-3">
            <i class="fas fa-exclamation-circle fs-5"></i>
            <div class="small fw-bold"><?= $error ?></div>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Nama Lengkap</label>
            <div class="input-group-custom">
                <input type="text" name="nama" class="form-control" placeholder="Masukkan nama" required>
                <i class="fas fa-user"></i>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Email Address</label>
            <div class="input-group-custom">
                <input type="email" name="email" class="form-control" placeholder="admin@unsera.ac.id" required>
                <i class="fas fa-envelope"></i>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Jenis Kelamin</label>
            <div class="input-group-custom">
                <select name="jenis_kelamin" class="form-select" required>
                    <option value="Laki-laki">Laki-laki</option>
                    <option value="Perempuan">Perempuan</option>
                </select>
                <i class="fas fa-venus-mars"></i>
            </div>
        </div>
        
        <div class="mb-4">
            <label class="form-label">Password</label>
            <div class="input-group-custom">
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                <i class="fas fa-lock"></i>
            </div>
        </div>

        <button type="submit" name="register" class="btn btn-register">
            DAFTAR SEKARANG <i class="fas fa-user-plus"></i>
        </button>
    </form>

    <div class="text-center mt-4">
        <p class="small text-muted mb-0">Sudah punya akun?</p>
        <a href="login.php" class="text-primary fw-bold text-decoration-none">Masuk di sini</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
