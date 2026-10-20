<?php
session_start();
include '../koneksi.php';
/** @var mysqli $koneksi */

// Proteksi halaman khusus admin
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    echo "<script>alert('Akses ditolak! Halaman ini khusus Admin.'); window.location='dashboard.php';</script>";
    exit();
}

// Ambil data kategori untuk pilihan filter dropdown
$kategori_filter_result = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY nama_kategori ASC");

// Tangkap nilai pencarian dan filter jika dikirimkan lewat form (GET)
$keyword = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, $_GET['cari']) : '';
$filter_kategori = isset($_GET['id_kategori']) ? $_GET['id_kategori'] : '';

// Susun Query dasar dengan JOIN
$query = "SELECT alat.*, kategori.nama_kategori FROM alat LEFT JOIN kategori ON alat.id_kategori = kategori.id_kategori WHERE 1=1";

// Jika ada kata kunci pencarian
if (!empty($keyword)) {
    $query .= " AND (alat.nama_alat LIKE '%$keyword%' OR alat.merk LIKE '%$keyword%')";
}

// Jika ada filter kategori yang dipilih
if (!empty($filter_kategori)) {
    $query .= " AND alat.id_kategori = '$filter_kategori'";
}

$query .= " ORDER BY alat.id_alat DESC";
$result = mysqli_query($koneksi, $query);

$role_user = strtolower($_SESSION['role']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Alat - Rental & Co.</title>
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

    <?php include 'sidebar.php'; ?>

    <div class="main-content" id="mainContent">
        <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
            <div class="d-flex align-items-center">
                <button class="btn btn-outline-secondary me-3" id="sidebarToggle" title="Sembunyikan/Tampilkan Sidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <div>
                    <h3 class="fw-bold text-dark mb-1">Manajemen Data Alat</h3>
                    <p class="text-muted small mb-0">Kelola inventaris, merk, harga sewa, kondisi, dan ketersediaan alat.</p>
                </div>
            </div>
            <a href="alat_tambah.php" class="btn btn-primary fw-bold shadow-sm rounded-pill px-3">
                <i class="fas fa-plus-circle me-2"></i> Tambah Alat Baru
            </a>
        </div>

        <!-- FORM PENCARIAN DAN FILTER KATEGORI -->
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
            <form method="GET" action="" class="row g-3 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" class="form-control border-start-0" name="cari" value="<?php echo htmlspecialchars($keyword); ?>" placeholder="Cari nama alat atau merk...">
                    </div>
                </div>
                <div class="col-md-4">
                    <select class="form-select" name="id_kategori">
                        <option value="">-- Semua Kategori --</option>
                        <?php while($kat = mysqli_fetch_assoc($kategori_filter_result)) { ?>
                            <option value="<?php echo $kat['id_kategori']; ?>" <?php if($filter_kategori == $kat['id_kategori']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($kat['nama_kategori']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-dark fw-bold w-100"><i class="fas fa-filter me-1"></i> Filter</button>
                    <a href="alat.php" class="btn btn-outline-secondary" title="Reset Filter"><i class="fas fa-sync-alt"></i></a>
                </div>
            </form>
        </div>

        <!-- TABEL DATA ALAT -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Nama Alat</th>
                            <th>Merk</th>
                            <th>Kategori</th>
                            <th>Harga Sewa / Hari</th>
                            <th>Stok</th>
                            <th>Kondisi</th>
                            <th class="text-center text-nowrap">Aksi</th>
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
                                <td class="fw-semibold"><?php echo htmlspecialchars($row['nama_alat']); ?></td>
                                <td><?php echo htmlspecialchars($row['merk'] ?? '-'); ?></td>
                                <td>
                                    <span class="badge bg-secondary text-uppercase px-2 py-1">
                                        <?php echo htmlspecialchars($row['nama_kategori'] ?? 'Tanpa Kategori'); ?>
                                    </span>
                                </td>
                                <td class="fw-bold text-success">Rp <?php echo number_format($row['harga'] ?? 0, 0, ',', '.'); ?></td>
                                <td><span class="badge bg-info text-dark px-2 py-1"><?php echo $row['stok']; ?> Unit</span></td>
                                <td>
                                    <?php 
                                        $kondisi = $row['kondisi'];
                                        $badgeKondisi = ($kondisi === 'Baik') ? 'bg-success' : (($kondisi === 'Rusak') ? 'bg-warning text-dark' : 'bg-danger');
                                    ?>
                                    <span class="badge <?php echo $badgeKondisi; ?> px-2 py-1"><?php echo $kondisi; ?></span>
                                </td>
                                <td class="text-center text-nowrap">
                                    <a href="alat_edit.php?id=<?php echo $row['id_alat']; ?>" class="btn btn-warning btn-sm text-white fw-bold px-2 py-1 me-1">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="alat_hapus.php?id=<?php echo $row['id_alat']; ?>" class="btn btn-danger btn-sm fw-bold px-2 py-1" onclick="return confirm('Yakin ingin menghapus alat ini?')">
                                        <i class="fas fa-trash"></i> Hapus
                                    </a>
                                </td>
                            </tr>
                        <?php 
                            }
                        } else {
                            echo '<tr><td colspan="8" class="text-center text-muted py-4">Tidak ada data alat yang ditemukan.</td></tr>';
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

        if(sidebarToggle) {
            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('hidden');
                mainContent.classList.toggle('expanded');
            });
        }
        if(sidebarClose) {
            sidebarClose.addEventListener('click', () => {
                sidebar.classList.add('hidden');
                mainContent.classList.add('expanded');
            });
        }
    </script>
</body>
</html>