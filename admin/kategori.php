<?php
session_start();
include '../koneksi.php';
/** @var mysqli $koneksi */

// Proteksi halaman khusus admin
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    echo "<script>alert('Akses ditolak! Halaman ini khusus Admin.'); window.location='dashboard.php';</script>";
    exit();
}

// Ambil data kategori dari database
$query = "SELECT * FROM kategori ORDER BY id_kategori DESC";
$result = mysqli_query($koneksi, $query);

$role_user = strtolower($_SESSION['role']);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Kategori - Rental & Co.</title>
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

    <!-- MEMANGGIL SIDEBAR TERPISAH -->
    <?php include 'sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="main-content" id="mainContent">
        <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
            <div class="d-flex align-items-center">
                <button class="btn btn-outline-secondary me-3" id="sidebarToggle" title="Sembunyikan/Tampilkan Sidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <div>
                    <h3 class="fw-bold text-dark mb-1">Manajemen Kategori Alat</h3>
                    <p class="text-muted small mb-0">Kelola kategori pengelompokan alat rental.</p>
                </div>
            </div>
            <a href="kategori_tambah.php" class="btn btn-primary fw-bold shadow-sm rounded-pill px-3">
                <i class="fas fa-plus-circle me-2"></i> Tambah Kategori Baru
            </a>
        </div>

        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th width="10%">#</th>
                            <th>Nama Kategori</th>
                            <th width="20%" class="text-center">Aksi</th>
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
                                    <td class="fw-semibold"><?php echo htmlspecialchars($row['nama_kategori']); ?></td>
                                    <td class="text-center text-nowrap">
                                        <a href="kategori_edit.php?id=<?php echo $row['id_kategori']; ?>" class="btn btn-warning btn-sm text-white fw-bold px-2 py-1 me-1">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="kategori_hapus.php?id=<?php echo $row['id_kategori']; ?>" class="btn btn-danger btn-sm fw-bold px-2 py-1" onclick="return confirm('Yakin ingin menghapus kategori ini?')">
                                            <i class="fas fa-trash"></i> Hapus
                                        </a>
                                    </td>
                                </tr>
                        <?php
                            }
                        } else {
                            echo '<tr><td colspan="3" class="text-center text-muted py-4">Belum ada data kategori alat.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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