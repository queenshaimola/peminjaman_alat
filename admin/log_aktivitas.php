<?php
session_start();
include '../koneksi.php';
/** @var mysqli $koneksi */

// Proteksi halaman khusus admin
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    echo "<script>alert('Akses ditolak! Halaman ini khusus Administrator.'); window.location='dashboard.php';</script>";
    exit();
}

// Tangkap input pencarian & filter dari metode GET
$cari = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, $_GET['cari']) : '';
$tgl_mulai = isset($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : '';
$tgl_selesai = isset($_GET['tgl_selesai']) ? $_GET['tgl_selesai'] : '';

// Base Query
$query = "SELECT log_aktivitas.*, users.nama_lengkap, users.role 
          FROM log_aktivitas 
          LEFT JOIN users ON log_aktivitas.id_user = users.id_user 
          WHERE 1=1";

// Kondisi Pencarian (Nama Pengguna atau Keterangan Aktivitas)
if (!empty($cari)) {
    $query .= " AND (users.nama_lengkap LIKE '%$cari%' OR log_aktivitas.aktivitas LIKE '%$cari%')";
}

// Kondisi Filter Rentang Waktu/Tanggal Log
if (!empty($tgl_mulai) && !empty($tgl_selesai)) {
    $query .= " AND DATE(log_aktivitas.waktu) BETWEEN '$tgl_mulai' AND '$tgl_selesai'";
}

$query .= " ORDER BY log_aktivitas.id_log DESC";
$result = mysqli_query($koneksi, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Aktivitas Sistem - Rental & Co.</title>
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

    <!-- SIDEBAR -->
    <?php include 'sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="main-content" id="mainContent">
        <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
            <div class="d-flex align-items-center">
                <button class="btn btn-outline-secondary me-3" id="sidebarToggle" title="Sembunyikan/Tampilkan Sidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <div>
                    <h3 class="fw-bold text-dark mb-1">Log Aktivitas Sistem</h3>
                    <p class="text-muted small mb-0">Memantau rekam jejak aktivitas pengguna di dalam aplikasi.</p>
                </div>
            </div>
        </div>

        <!-- CARD PENCARIAN & FILTER -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted">Cari Pengguna / Aktivitas</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" class="form-control" name="cari" value="<?php echo htmlspecialchars($cari); ?>" placeholder="Nama user atau isi aktivitas...">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Dari Tanggal</label>
                    <input type="date" class="form-control" name="tgl_mulai" value="<?php echo htmlspecialchars($tgl_mulai); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Sampai Tanggal</label>
                    <input type="date" class="form-control" name="tgl_selesai" value="<?php echo htmlspecialchars($tgl_selesai); ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary fw-bold w-100 rounded-pill"><i class="fas fa-filter me-1"></i> Filter</button>
                    <a href="log_aktivitas.php" class="btn btn-outline-secondary rounded-circle" title="Reset Filter"><i class="fas fa-sync-alt"></i></a>
                </div>
            </form>
        </div>

        <!-- TABEL LOG AKTIVITAS -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Pengguna</th>
                            <th>Role</th>
                            <th>Aktivitas / Keterangan</th>
                            <th>Waktu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        if ($result && mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                        ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td class="fw-semibold">
                                    <i class="fas fa-user-circle text-secondary me-1"></i> 
                                    <?php echo htmlspecialchars($row['nama_lengkap'] ?? 'System / Tamu'); ?>
                                </td>
                                <td>
                                    <?php 
                                        $r = strtolower($row['role'] ?? '');
                                        if ($r === 'admin') {
                                            echo '<span class="badge bg-danger">Admin</span>';
                                        } elseif ($r === 'petugas') {
                                            echo '<span class="badge bg-warning text-dark">Petugas</span>';
                                        } elseif ($r === 'peminjam') {
                                            echo '<span class="badge bg-primary">Peminjam</span>';
                                        } else {
                                            echo '<span class="badge bg-secondary">-</span>';
                                        }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['aktivitas'] ?? '-'); ?></td>
                                <td>
                                    <span class="text-muted small">
                                        <i class="far fa-clock me-1"></i> 
                                        <?php echo !empty($row['waktu']) ? date('d-m-Y H:i:s', strtotime($row['waktu'])) : '-'; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php 
                            }
                        } else {
                            echo '<tr><td colspan="5" class="text-center text-muted py-4">Tidak ada data log aktivitas yang sesuai.</td></tr>';
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