<?php
// Memulai session untuk mendeteksi apakah user sudah login atau belum
session_start();
include 'koneksi.php';

// Cek koneksi
if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// 1. AMBIL DATA KATEGORI UNTUK DROPDOWN FILTER
$query_kategori = "SELECT * FROM kategori ORDER BY nama_kategori ASC";
$result_kategori = mysqli_query($koneksi, $query_kategori);

// 2. TANGKAP INPUT PENCARIAN & FILTER KATEGORI (METHOD GET)
$keyword = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, $_GET['cari']) : '';
$filter_kategori = isset($_GET['id_kategori']) ? $_GET['id_kategori'] : '';

// 3. QUERY AMBIL DATA ALAT & KATEGORI (DINAMIS DENGAN FILTER)
$query = "SELECT alat.*, kategori.nama_kategori 
          FROM alat 
          JOIN kategori ON alat.id_kategori = kategori.id_kategori 
          WHERE 1=1";

if (!empty($keyword)) {
    $query .= " AND (alat.nama_alat LIKE '%$keyword%' OR alat.merk LIKE '%$keyword%')";
}

if (!empty($filter_kategori)) {
    $query .= " AND alat.id_kategori = '$filter_kategori'";
}

$query .= " ORDER BY alat.id_alat DESC";
$result = mysqli_query($koneksi, $query);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rental & Co. - Beranda Peminjaman Alat</title>
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome untuk ikon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 60px 0;
            border-radius: 0 0 30px 30px;
        }

        .card-alat {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            border-radius: 15px;
        }

        .card-alat:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body class="bg-light d-flex flex-column min-vh-100">

    <!-- NAVBAR DINAMIS -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php"><i class="fas fa-tools text-warning me-2"></i>Rental & Co.</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav align-items-center">
                    <li class="nav-item"><a class="nav-link active" href="index.php">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="#daftar-alat">Daftar Alat</a></li>
                    <li class="nav-item ms-lg-3">
                        <a class="nav-link text-warning fw-semibold" href="peminjam/keranjang.php">
                            <i class="fas fa-shopping-cart me-1"></i> Keranjang
                            <?php
                            $jml_cart = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
                            if ($jml_cart > 0) {
                                echo '<span class="badge bg-danger rounded-pill">' . $jml_cart . '</span>';
                            }
                            ?>
                        </a>
                    </li>

                    <!-- CEK APAKAH USER SUDAH LOGIN ATAU BELUM -->
                    <?php if (isset($_SESSION['status']) && $_SESSION['status'] === 'login') { ?>

                        <!-- Jika sudah login sebagai peminjam/user biasa -->
                        <li class="nav-item ms-lg-3">
                            <a class="nav-link text-info fw-semibold" href="peminjam/riwayat.php">
                                <i class="fas fa-clipboard-list me-1"></i> Peminjaman Saya
                            </a>
                        </li>

                        <!-- Dropdown Nama User & Logout -->
                        <li class="nav-item dropdown ms-lg-3">
                            <a class="btn btn-outline-light dropdown-toggle rounded-pill px-3 py-1 text-white" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['nama_lengkap'] ?? 'User'); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <?php
                                $role_session = strtolower(trim($_SESSION['role'] ?? ''));
                                if ($role_session === 'admin' || $role_session === 'petugas') {
                                ?>
                                    <li>
                                        <a class="dropdown-item fw-bold text-primary" href="admin/dashboard.php">
                                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard Management
                                        </a>
                                    </li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                <?php } ?>
                                <li>
                                    <a class="dropdown-item text-danger fw-semibold" href="logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i> Keluar
                                    </a>
                                </li>
                            </ul>
                        </li>

                    <?php } else { ?>

                        <!-- Jika BELUM login, tampilkan tombol Login biasa -->
                        <li class="nav-item ms-lg-3">
                            <a href="login.php" class="btn btn-outline-light btn-sm px-3 rounded-pill">
                                <i class="fas fa-sign-in-alt me-1"></i> Login
                            </a>
                        </li>

                    <?php } ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- HERO SECTION (Banner Utama) -->
    <header class="hero-section text-center shadow-sm mb-5">
        <div class="container">
            <h1 class="display-4 fw-bold mb-3">Selamat Datang di Rental & Co.</h1>
            <p class="lead mb-4">Sistem peminjaman alat profesional, cepat, dan terpercaya untuk kebutuhan praktikum & kerja lapangan Anda.</p>
            <a href="#daftar-alat" class="btn btn-warning btn-lg fw-bold text-dark px-4 rounded-pill shadow"><i class="fas fa-box-open me-2"></i> Lihat Alat Tersedia</a>
        </div>
    </header>

    <!-- KONTEN DAFTAR ALAT YANG BISA DIPINJAM (DINAMIS DARI DATABASE) -->
    <main class="container mb-5" id="daftar-alat">
        <div class="row mb-4">
            <div class="col-md-12 text-center">
                <h2 class="fw-bold text-dark">Katalog Alat Tersedia</h2>
                <p class="text-muted">Pilih alat yang ingin Anda pinjam sesuai dengan kebutuhan.</p>
            </div>
        </div>

        <!-- FITUR PENCARIAN & FILTER KATEGORI -->
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
            <form method="GET" action="#daftar-alat" class="row g-3 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" class="form-control border-start-0" name="cari" value="<?php echo htmlspecialchars($keyword); ?>" placeholder="Cari nama alat atau merk...">
                    </div>
                </div>
                <div class="col-md-4">
                    <select class="form-select" name="id_kategori">
                        <option value="">-- Semua Kategori --</option>
                        <?php while ($kat = mysqli_fetch_assoc($result_kategori)) { ?>
                            <option value="<?php echo $kat['id_kategori']; ?>" <?php if ($filter_kategori == $kat['id_kategori']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($kat['nama_kategori']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary fw-bold w-100 rounded-pill"><i class="fas fa-filter me-1"></i> Cari & Filter</button>
                    <a href="index.php#daftar-alat" class="btn btn-outline-secondary rounded-circle" title="Reset Filter"><i class="fas fa-sync-alt"></i></a>
                </div>
            </form>
        </div>

        <!-- KATALOG GRID ALAT -->
        <div class="row g-4">
            <?php
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
            ?>
                    <div class="col-md-4">
                        <div class="card card-alat h-100 shadow-sm overflow-hidden">

                            <!-- TAMPILKAN GAMBAR CUSTOM JIKA ADA, ATAU PLACEHOLDER JIKA KOSONG -->
                            <?php if (!empty($row['gambar']) && file_exists('uploads/' . $row['gambar'])) { ?>
                                <img src="uploads/<?php echo $row['gambar']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($row['nama_alat']); ?>" style="height: 200px; object-fit: cover;">
                            <?php } else { ?>
                                <div class="bg-secondary text-white text-center py-5">
                                    <i class="fas fa-toolbox fa-3x"></i>
                                </div>
                            <?php } ?>

                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($row['nama_kategori']); ?></span>
                                    <!-- Menampilkan Harga Sewa per Hari -->
                                    <span class="fw-bold text-success">Rp <?php echo number_format($row['harga'] ?? 0, 0, ',', '.'); ?> <small class="text-muted fw-normal">/hari</small></span>
                                </div>

                                <!-- Nama alat dari database -->
                                <h5 class="card-title fw-bold text-dark"><?php echo htmlspecialchars($row['nama_alat']); ?></h5>

                                <!-- Merk jika ada -->
                                <?php if (!empty($row['merk'])) { ?>
                                    <p class="card-text text-muted small mb-1">Merk: <span class="fw-semibold text-dark"><?php echo htmlspecialchars($row['merk']); ?></span></p>
                                <?php } ?>

                                <!-- Kondisi alat -->
                                <p class="card-text text-muted small mb-3">Kondisi: <span class="fw-semibold text-dark"><?php echo htmlspecialchars($row['kondisi']); ?></span></p>

                                <div class="d-flex justify-content-between align-items-center mt-auto pt-2 border-top">
                                    <!-- Stok dari database -->
                                    <span class="text-secondary small fw-semibold">Stok: <?php echo $row['stok']; ?> Unit</span>

                                    <?php if (isset($_SESSION['status']) && $_SESSION['status'] === 'login') { ?>
                                        <!-- Jika sudah login, arahkan ke tambah_keranjang.php dengan membawa parameter id -->
                                        <a href="peminjam/tambah_keranjang.php?id=<?php echo $row['id_alat']; ?>" class="btn btn-primary btn-sm rounded-pill px-3">
                                            <i class="fas fa-cart-plus me-1"></i> Masukkan Keranjang
                                        </a>
                                    <?php } else { ?>
                                        <!-- Jika belum login, arahkan ke halaman login -->
                                        <a href="login.php" class="btn btn-primary btn-sm rounded-pill px-3">
                                            Pinjam Alat
                                        </a>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
            <?php
                }
            } else {
                echo '<div class="col-12 text-center py-5"><i class="fas fa-box-open fa-3x text-muted mb-3 d-block"></i><p class="text-muted fw-semibold">Tidak ada data alat yang sesuai dengan pencarian Anda.</p></div>';
            }
            ?>
        </div>
    </main>

    <!-- FOOTER -->
    <footer class="bg-dark text-white text-center py-3 mt-auto">
        <div class="container">
            <p class="mb-0 small">&copy; 2026 <strong>Rental & Co.</strong> Aplikasi Peminjaman Alat. Hak Cipta Dilindungi.</p>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>