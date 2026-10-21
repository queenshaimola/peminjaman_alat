<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../koneksi.php';
/** @var mysqli $koneksi */

// 1. Proteksi Halaman Khusus Admin (Cek $_SESSION['role'] atau $_SESSION['akses'])
$role = strtolower($_SESSION['role'] ?? $_SESSION['akses'] ?? '');

if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login' || ($role !== 'admin' && $role !== '1')) {
    echo "<script>alert('Akses ditolak! Halaman ini khusus Administrator.'); window.location='dashboard.php';</script>";
    exit();
}

// 2. Tangkap Input Pencarian & Filter dari GET
$cari        = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, trim($_GET['cari'])) : '';
$status      = isset($_GET['status']) ? mysqli_real_escape_string($koneksi, trim($_GET['status'])) : '';
$tgl_mulai   = isset($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : '';
$tgl_selesai = isset($_GET['tgl_selesai']) ? $_GET['tgl_selesai'] : '';

// 3. Query Utama (Join peminjaman ke users)
$query = "SELECT peminjaman.*, users.nama_lengkap, users.role 
          FROM peminjaman 
          LEFT JOIN users ON peminjaman.id_user = users.id_user 
          WHERE 1=1";

// Filter berdasarkan Pencarian Nama Pengguna atau ID Peminjaman
if (!empty($cari)) {
    $query .= " AND (users.nama_lengkap LIKE '%$cari%' OR peminjaman.id_peminjaman LIKE '%$cari%')";
}

// Filter berdasarkan Status Peminjaman
if (!empty($status)) {
    $query .= " AND peminjaman.status = '$status'";
}

// Filter berdasarkan Rentang Tanggal Pinjam
if (!empty($tgl_mulai) && !empty($tgl_selesai)) {
    $query .= " AND DATE(peminjaman.tgl_pinjam) BETWEEN '$tgl_mulai' AND '$tgl_selesai'";
}

$query .= " ORDER BY peminjaman.id_peminjaman DESC";
$result = mysqli_query($koneksi, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Peminjaman - Rental & Co.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
                    <h3 class="fw-bold text-dark mb-1">Data Peminjaman Alat</h3>
                    <p class="text-muted small mb-0">Memantau dan mengelola seluruh riwayat peminjaman alat.</p>
                </div>
            </div>
            <a href="peminjaman_tambah.php" class="btn btn-primary fw-bold rounded-3 px-3">
                <i class="fas fa-plus me-1"></i> Tambah Peminjaman
            </a>
        </div>

        <!-- CARD PENCARIAN & FILTER -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Cari Peminjam / ID</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" class="form-control" name="cari" value="<?php echo htmlspecialchars($cari); ?>" placeholder="Nama peminjam / ID...">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Status Peminjaman</label>
                    <select name="status" class="form-select">
                        <option value="">-- Semua Status --</option>
                        <option value="dipinjam" <?php echo ($status == 'dipinjam') ? 'selected' : ''; ?>>Dipinjam</option>
                        <option value="selesai" <?php echo ($status == 'selesai') ? 'selected' : ''; ?>>Selesai</option>
                        <option value="terlambat" <?php echo ($status == 'terlambat') ? 'selected' : ''; ?>>Terlambat</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">Dari Tanggal</label>
                    <input type="date" class="form-control" name="tgl_mulai" value="<?php echo htmlspecialchars($tgl_mulai); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">Sampai Tanggal</label>
                    <input type="date" class="form-control" name="tgl_selesai" value="<?php echo htmlspecialchars($tgl_selesai); ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary fw-bold w-100 rounded-pill"><i class="fas fa-filter me-1"></i> Filter</button>
                    <a href="peminjaman.php" class="btn btn-outline-secondary rounded-circle" title="Reset Filter"><i class="fas fa-sync-alt"></i></a>
                </div>
            </form>
        </div>

        <!-- TABEL PEMINJAMAN -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>ID Pinjam</th>
                            <th>Nama Peminjam</th>
                            <th>Tgl Pinjam</th>
                            <th>Tgl Rencana Kembali</th>
                            <th>Total Biaya</th>
                            <th>Status Bayar</th>
                            <th>Status Pinjam</th>
                            <th class="text-center">Aksi</th>
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
                                <td><span class="badge bg-light text-dark border">#<?php echo $row['id_peminjaman']; ?></span></td>
                                <td class="fw-semibold">
                                    <i class="fas fa-user-circle text-secondary me-1"></i> 
                                    <?php echo htmlspecialchars($row['nama_lengkap'] ?? 'Umum'); ?>
                                </td>
                                <td>
                                    <span class="small text-muted">
                                        <?php echo !empty($row['tgl_pinjam']) ? date('d-m-Y', strtotime($row['tgl_pinjam'])) : '-'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="small text-muted">
                                        <?php echo !empty($row['tgl_kembali_rencana']) ? date('d-m-Y', strtotime($row['tgl_kembali_rencana'])) : '-'; ?>
                                    </span>
                                </td>
                                <td class="fw-bold text-success">
                                    Rp <?php echo number_format($row['total_biaya'] ?? 0, 0, ',', '.'); ?>
                                </td>
                                <td>
                                    <?php 
                                        $bayar = strtolower($row['status_pembayaran'] ?? '');
                                        if ($bayar === 'lunas') {
                                            echo '<span class="badge bg-success">Lunas</span>';
                                        } else {
                                            echo '<span class="badge bg-danger">Belum Bayar</span>';
                                        }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                        $st = strtolower($row['status'] ?? '');
                                        if ($st === 'selesai' || $st === 'sel_') {
                                            echo '<span class="badge bg-success">Selesai</span>';
                                        } elseif ($st === 'terlambat') {
                                            echo '<span class="badge bg-danger">Terlambat</span>';
                                        } else {
                                            echo '<span class="badge bg-warning text-dark">Dipinjam</span>';
                                        }
                                    ?>
                                </td>
                                <td class="text-center">
                                    <a href="peminjaman_detail.php?id=<?php echo $row['id_peminjaman']; ?>" class="btn btn-sm btn-outline-info rounded-2 me-1" title="Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="peminjaman_hapus.php?id=<?php echo $row['id_peminjaman']; ?>" class="btn btn-sm btn-outline-danger rounded-2" onclick="return confirm('Yakin ingin menghapus data peminjaman ini?')" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php 
                            }
                        } else {
                            echo '<tr><td colspan="9" class="text-center text-muted py-4">Tidak ada data peminjaman yang sesuai.</td></tr>';
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

        if(sidebarToggle) {
            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('hidden');
                mainContent.classList.toggle('expanded');
            });
        }
    </script>
</body>
</html>