<?php
session_start();
include '../koneksi.php';
/** @var mysqli $koneksi */

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$error = "";
if (isset($_POST['simpan'])) {
    $username     = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password     = md5($_POST['password']);
    $nama_lengkap = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
    $role         = 'peminjam'; // Otomatis diset sebagai peminjam

    // Cek duplikasi username
    $cek = mysqli_query($koneksi, "SELECT * FROM users WHERE username = '$username'");
    if (mysqli_num_rows($cek) > 0) {
        $error = "Username sudah digunakan!";
    } else {
        $query = "INSERT INTO users (username, password, nama_lengkap, role) VALUES ('$username', '$password', '$nama_lengkap', '$role')";
        if (mysqli_query($koneksi, $query)) {
            // PERBAIKAN: Diarahkan kembali ke peminjam.php setelah berhasil
            echo "<script>alert('Peminjam berhasil ditambahkan!'); window.location='peminjam.php';</script>";
            exit();
        } else {
            $error = "Gagal menyimpan data: " . mysqli_error($koneksi);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Peminjam - Rental & Co.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="height: 100vh;">
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white" style="width: 450px;">
        <h4 class="fw-bold mb-3 text-dark"><i class="fas fa-user-plus text-success me-2"></i> Tambah Akun Peminjam</h4>
        
        <?php if (!empty($error)) { ?>
            <div class="alert alert-danger py-2 small"><?php echo $error; ?></div>
        <?php } ?>

        <form action="" method="POST">
            <div class="mb-3">
                <label class="form-label small fw-semibold">Username</label>
                <input type="text" class="form-control" name="username" required autocomplete="off" placeholder="Masukkan username peminjam...">
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">Password</label>
                <input type="password" class="form-control" name="password" required placeholder="Masukkan password...">
            </div>
            <div class="mb-4">
                <label class="form-label small fw-semibold">Nama Lengkap</label>
                <input type="text" class="form-control" name="nama_lengkap" required placeholder="Masukkan nama lengkap...">
            </div>
            <div class="d-flex justify-content-between">
                <!-- PERBAIKAN: Tombol kembali diarahkan ke peminjam.php -->
                <a href="peminjam.php" class="btn btn-secondary btn-sm px-4 rounded-pill">Kembali</a>
                <button type="submit" name="simpan" class="btn btn-success btn-sm px-4 rounded-pill fw-bold">Simpan Peminjam</button>
            </div>
        </form>
    </div>
</body>
</html>