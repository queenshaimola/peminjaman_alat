<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../koneksi.php';
/** @var mysqli $koneksi */

// 1. Proteksi Session Login
$role = strtolower($_SESSION['role'] ?? $_SESSION['akses'] ?? '');
if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login') {
    echo "<script>alert('Silakan login terlebih dahulu!'); window.location='../index.php';</script>";
    exit();
}

// 2. Tangkap Input Filter dan Pencarian
$cari        = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, trim($_GET['cari'])) : '';
$tgl_mulai   = isset($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : '';
$tgl_selesai = isset($_GET['tgl_selesai']) ? $_GET['tgl_selesai'] : '';

// 3. Query Utama (Join pengembalian -> peminjaman -> users)
$query = "SELECT pengembalian.*, peminjaman.tgl_pinjam, peminjaman.tgl_kembali_rencana, users.nama_lengkap 
          FROM pengembalian 
          LEFT JOIN peminjaman ON pengembalian.id_peminjaman = peminjaman.id_peminjaman 
          LEFT JOIN users ON peminjaman.id_user = users.id_user 
          WHERE 1=1";

if (!empty($cari)) {
    $query .= " AND (users.nama_lengkap LIKE '%$cari%' OR pengembalian.id_peminjaman LIKE '%$cari%')";
}

if (!empty($tgl_mulai) && !empty($tgl_selesai)) {
    $query .= " AND DATE(pengembalian.tgl_kembali_real) BETWEEN '$tgl_mulai' AND '$tgl_selesai'";
}

$query .= " ORDER BY pengembalian.id_pengembalian DESC";
$result = mysqli_query($koneksi, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pengembalian - Rental & Co.</title>
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
                <button class="btn btn-outline-secondary me-3" id="sidebarToggle" title="Toggle Sidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <div>
                    <h3 class="fw-bold text-dark mb-1">Data Pengembalian Alat</h3>
                    <p class="text-muted small mb-0">Riwayat pengembalian barang dan pencatatan denda.</p>
                </div>
            </div>
            <a href="pengembalian_tambah.php" class="btn btn-success fw-bold rounded-3 px-3">
                <i class="fas fa-undo me-1"></i> Proses Pengembalian
            </a>
        </div>

        <!-- FILTER & PENCARIAN -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted">Cari Peminjam / ID Pinjam</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" class="form-control" name="cari" value="<?= htmlspecialchars($cari); ?>" placeholder="Nama peminjam / ID Pinjam...">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Dari Tanggal Kembali</label>
                    <input type="date" class="form-control" name="tgl_mulai" value="<?= htmlspecialchars($tgl_mulai); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Sampai Tanggal</label>
                    <input type="date" class="form-control" name="tgl_selesai" value="<?= htmlspecialchars($tgl_selesai); ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary fw-bold w-100 rounded-pill"><i class="fas fa-filter me-1"></i> Filter</button>
                    <a href="pengembalian.php" class="btn btn-outline-secondary rounded-circle" title="Reset"><i class="fas fa-sync-alt"></i></a>
                </div>
            </form>
        </div>

        <!-- TABEL PENGEMBALIAN -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>ID Pinjam</th>
                            <th>Nama Peminjam</th>
                            <th>Tgl Rencana Kembali</th>
                            <th>Tgl Real Kembali</th>
                            <th>Denda</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        if ($result && mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                $denda = (float)($row['denda'] ?? 0);
                        ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><span class="badge bg-light text-dark border">#<?= $row['id_peminjaman']; ?></span></td>
                                <td class="fw-semibold">
                                    <i class="fas fa-user-circle text-secondary me-1"></i> 
                                    <?= htmlspecialchars($row['nama_lengkap'] ?? 'Umum'); ?>
                                </td>
                                <td class="small text-muted">
                                    <?= !empty($row['tgl_kembali_rencana']) ? date('d-m-Y', strtotime($row['tgl_kembali_rencana'])) : '-'; ?>
                                </td>
                                <td class="fw-semibold text-primary">
                                    <?= !empty($row['tgl_kembali_real']) ? date('d-m-Y', strtotime($row['tgl_kembali_real'])) : '-'; ?>
                                </td>
                                <td>
                                    <?php if ($denda > 0): ?>
                                        <span class="fw-bold text-danger">Rp <?= number_format($denda, 0, ',', '.'); ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Tidak Ada</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="peminjaman_detail.php?id=<?= $row['id_peminjaman']; ?>" class="btn btn-sm btn-outline-info rounded-2 me-1" title="Detail Peminjaman">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($role === 'admin' || $role === '1'): ?>
                                        <a href="pengembalian_hapus.php?id=<?= $row['id_pengembalian']; ?>" class="btn btn-sm btn-outline-danger rounded-2" onclick="return confirm('Yakin ingin menghapus data pengembalian ini?')" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php 
                            }
                        } else {
                            echo '<tr><td colspan="7" class="text-center text-muted py-4">Belum ada data pengembalian.</td></tr>';
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