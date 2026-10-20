<?php
session_start();
include '../koneksi.php';
/** @var mysqli $koneksi */

// Proteksi halaman khusus admin
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    echo "<script>alert('Akses ditolak! Halaman ini khusus Admin.'); window.location='dashboard.php';</script>";
    exit();
}

// QUERY KHUSUS MENAMPILKAN USER DENGAN ROLE 'peminjam' SAJA
$query = "SELECT * FROM users WHERE role = 'peminjam' ORDER BY id_user DESC";
$result = mysqli_query($koneksi, $query);

$role_user = strtolower($_SESSION['role']);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Data Peminjam - Rental & Co.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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

    <!-- MEMANGGIL SIDEBAR LENGKAP TERPISAH -->
    <?php include 'sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="main-content" id="mainContent">
        <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
            <div class="d-flex align-items-center">
                <!-- Tombol Toggle Sidebar -->
                <button class="btn btn-outline-secondary me-3" id="sidebarToggle" title="Sembunyikan/Tampilkan Sidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <div>
                    <h3 class="fw-bold text-dark mb-1">Daftar Akun Peminjam</h3>
                    <p class="text-muted small mb-0">Kelola akun anggota / siswa yang melakukan peminjaman alat.</p>
                </div>
            </div>
            <!-- Sesuaikan nama file tujuan tambah -->
            <a href="tambah_peminjam.php" class="btn btn-success fw-bold shadow-sm rounded-pill px-3">
                <i class="fas fa-user-plus me-2"></i> Tambah Peminjam Baru
            </a>
        </div>

        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Username</th>
                            <th>Nama Lengkap</th>
                            <th>Role</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                        ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td class="fw-semibold"><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                                    <td><span class="badge bg-success text-uppercase px-3 py-2"><?php echo $row['role']; ?></span></td>
                                    <td class="text-center">
                                        <a href="peminjam_edit.php?id=<?php echo $row['id_user']; ?>" class="btn btn-warning btn-sm text-white fw-bold px-2 py-1 me-1">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <!-- Sesuaikan nama file hapus yang benar -->
                                        <a href="peminjam_hapus.php?id=<?php echo $row['id_user']; ?>" class="btn btn-danger btn-sm fw-bold px-2 py-1" onclick="return confirm('Yakin ingin menghapus peminjam ini?')">
                                            <i class="fas fa-trash"></i> Hapus
                                        </a>
                                    </td>
                                </tr>
                        <?php
                            }
                        } else {
                            echo '<tr><td colspan="5" class="text-center text-muted py-4">Belum ada data peminjam terdaftar.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Script Toggle Sidebar -->
    <script>
        const sidebar = document.getElementById('sidebarNav');
        const mainContent = document.getElementById('mainContent');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarClose = document.getElementById('sidebarClose');

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('hidden');
                mainContent.classList.toggle('expanded');
            });
        }
        if (sidebarClose) {
            sidebarClose.addEventListener('click', () => {
                sidebar.classList.add('hidden');
                mainContent.classList.add('expanded');
            });
        }
    </script>
</body>

</html>