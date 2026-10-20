<?php
session_start();
include '../koneksi.php';
/** @var mysqli $koneksi */

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$id = $_GET['id'] ?? $_GET['id_user'] ?? '';
$data = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM users WHERE id_user = '$id'"));

$error = "";
if (isset($_POST['update'])) {
    $username     = mysqli_real_escape_string($koneksi, $_POST['username']);
    $nama_lengkap = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);

    // Jika password baru diisi, update dengan MD5
    if (!empty($_POST['password'])) {
        $password = md5($_POST['password']);
        $query = "UPDATE users SET username='$username', password='$password', nama_lengkap='$nama_lengkap' WHERE id_user='$id'";
    } else {
        $query = "UPDATE users SET username='$username', nama_lengkap='$nama_lengkap' WHERE id_user='$id'";
    }

    if (mysqli_query($koneksi, $query)) {
        echo "<script>alert('Data peminjam berhasil diperbarui!'); window.location='peminjam.php';</script>";
        exit();
    } else {
        $error = "Gagal memperbarui data: " . mysqli_error($koneksi);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Peminjam - Rental & Co.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="height: 100vh;">
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white" style="width: 450px;">
        <h4 class="fw-bold mb-3 text-dark"><i class="fas fa-user-edit text-warning me-2"></i> Edit Data Peminjam</h4>
        
        <?php if (!empty($error)) { ?>
            <div class="alert alert-danger py-2 small"><?php echo $error; ?></div>
        <?php } ?>

        <form action="" method="POST">
            <div class="mb-3">
                <label class="form-label small fw-semibold">Username</label>
                <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($data['username']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">Password Baru <span class="text-muted fw-normal">(Kosongkan jika tidak ingin diubah)</span></label>
                <input type="password" class="form-control" name="password" placeholder="Masukkan password baru...">
            </div>
            <div class="mb-4">
                <label class="form-label small fw-semibold">Nama Lengkap</label>
                <input type="text" class="form-control" name="nama_lengkap" value="<?php echo htmlspecialchars($data['nama_lengkap']); ?>" required>
            </div>
            <div class="d-flex justify-content-between">
                <a href="peminjam.php" class="btn btn-secondary btn-sm px-4 rounded-pill">Kembali</a>
                <button type="submit" name="update" class="btn btn-warning btn-sm px-4 rounded-pill fw-bold text-white">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</body>
</html>