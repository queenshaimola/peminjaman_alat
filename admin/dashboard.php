<?php
session_start();
include '../koneksi.php';
/** @var mysqli $koneksi */

// Proteksi Session & Role
if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login') {
    header("Location: ../login.php");
    exit();
}

// Cek Role (Izinkan Admin dan Petugas)
$role = strtolower(trim($_SESSION['role'] ?? ''));
if ($role !== 'admin' && $role !== 'petugas') {
    echo "<script>alert('Akses ditolak! Anda tidak memiliki akses ke halaman ini.'); window.location='../index.php';</script>";
    exit();
}

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

$role_user = strtolower($_SESSION['role']);

// AMBIL STATISTIK JUMLAH DATA UNTUK KARTU INFORMASI
$jml_user     = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM users"));
$jml_alat     = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM alat"));
$jml_pinjam   = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM peminjaman"));
$jml_kategori = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM kategori"));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Rental & Co.</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Ikon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    /* Ini wajib ada di setiap halaman utama supaya kontennya tidak nabrak sidebar */
    .main-content {
        margin-left: 260px;
        padding: 30px;
        transition: margin-left 0.3s ease;
    }
    .main-content.expanded {
        margin-left: 0;
    }
</style>
</head>
<body>

    <!-- MEMANGGIL SIDEBAR DARI FILE TERPISAH -->
    <?php include 'sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="main-content" id="mainContent">
        <!-- TOPBAR / HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
            <div class="d-flex align-items-center">
                <!-- Tombol Toggle untuk Memunculkan/Menyembunyikan Sidebar -->
                <button class="btn btn-outline-secondary me-3" id="sidebarToggle" title="Sembunyikan/Tampilkan Sidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <div>
                    <h3 class="fw-bold text-dark mb-1">Dashboard <?php echo ucfirst($role_user); ?></h3>
                    <p class="text-muted small mb-0">Selamat datang kembali, <strong><?php echo htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Pengguna'); ?></strong>!</p>
                </div>
            </div>
            <div class="dropdown">
                <button class="btn btn-white border shadow-sm dropdown-toggle rounded-pill px-3" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-user-shield text-primary me-2"></i> <?php echo ucfirst($role_user); ?> Sistem
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="../index.php" target="_blank"><i class="fas fa-globe me-2"></i> Lihat Website</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Keluar</a></li>
                </ul>
            </div>
        </div>

        <!-- STATISTIK KARTU (QUICK OVERVIEW) -->
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="card card-stat bg-primary text-white p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase small fw-semibold opacity-75">Total Pengguna</h6>
                            <h2 class="fw-bold mb-0"><?php echo $jml_user; ?></h2>
                        </div>
                        <i class="fas fa-users fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stat bg-success text-white p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase small fw-semibold opacity-75">Total Jenis Alat</h6>
                            <h2 class="fw-bold mb-0"><?php echo $jml_alat; ?></h2>
                        </div>
                        <i class="fas fa-toolbox fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stat bg-warning text-dark p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase small fw-semibold opacity-75">Total Kategori</h6>
                            <h2 class="fw-bold mb-0"><?php echo $jml_kategori; ?></h2>
                        </div>
                        <i class="fas fa-tags fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stat bg-info text-white p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase small fw-semibold opacity-75">Total Transaksi</h6>
                            <h2 class="fw-bold mb-0"><?php echo $jml_pinjam; ?></h2>
                        </div>
                        <i class="fas fa-file-invoice fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- KONTEN TAMBAHAN / SELAMAT DATANG -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h4 class="fw-bold text-dark mb-2">Panel Kontrol Utama Rental & Co.</h4>
                    <p class="text-muted">Gunakan menu di sebelah kiri untuk mengelola transaksi peminjaman dan pengembalian alat secara cepat dan terpantau dengan baik.</p>
                    <?php if ($role_user === 'admin') { ?>
                        <a href="alat.php" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold"><i class="fas fa-plus-circle me-1"></i> Kelola Data Alat</a>
                    <?php } else { ?>
                        <a href="peminjaman.php" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold"><i class="fas fa-file-invoice-dollar me-1"></i> Kelola Peminjaman</a>
                    <?php } ?>
                </div>
                <div class="col-md-4 text-center d-none d-md-block">
                    <i class="fas fa-cogs fa-7x text-primary opacity-25"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script untuk Toggle Sidebar (Muncul / Sembunyi) -->
    <script>
        const sidebar = document.getElementById('sidebarNav');
        const mainContent = document.getElementById('mainContent');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarClose = document.getElementById('sidebarClose');

        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('hidden');
            mainContent.classList.toggle('expanded');
        });

        if(sidebarClose) {
            sidebarClose.addEventListener('click', () => {
                sidebar.classList.add('hidden');
                mainContent.classList.add('expanded');
            });
        }
    </script>
</body>
</html>