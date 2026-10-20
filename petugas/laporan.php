<?php
session_start();
include '../koneksi.php';
/** @var mysqli $koneksi */

// Proteksi: Hanya boleh diakses oleh Admin atau Petugas
if (!isset($_SESSION['role']) || !in_array(strtolower($_SESSION['role']), ['admin', 'petugas'])) {
    echo "<script>alert('Akses ditolak!'); window.location='../login.php';</script>";
    exit();
}

// Ambil nilai filter dari input GET (jika ada)
$keyword = $_GET['keyword'] ?? '';
$tgl_mulai = $_GET['tgl_mulai'] ?? '';
$tgl_selesai = $_GET['tgl_selesai'] ?? '';

// Buat query dasar untuk laporan peminjaman & pengembalian
$query = "SELECT p.*, u.nama_lengkap, peng.tgl_kembali_real, peng.denda 
          FROM peminjaman p 
          JOIN users u ON p.id_user = u.id_user 
          LEFT JOIN pengembalian peng ON p.id_peminjaman = peng.id_peminjaman 
          WHERE 1=1";

// Jika ada filter pencarian (nama peminjam atau status)
if (!empty($keyword)) {
    $keyword_esc = mysqli_real_escape_string($koneksi, $keyword);
    $query .= " AND (u.nama_lengkap LIKE '%$keyword_esc%' OR p.status LIKE '%$keyword_esc%')";
}

// Jika ada filter rentang tanggal pinjam
if (!empty($tgl_mulai) && !empty($tgl_selesai)) {
    $query .= " AND p.tgl_pinjam BETWEEN '$tgl_mulai' AND '$tgl_selesai'";
}

$query .= " ORDER BY p.id_peminjaman DESC";
$result = mysqli_query($koneksi, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Peminjaman & Pengembalian - Rental & Co.</title>
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
        /* CSS khusus saat dicetak (print) */
        @media print {
            .sidebar, .btn, form, .no-print {
                display: none !important;
            }
            .main-content {
                margin-left: 0 !important;
                padding: 0 !important;
            }
            .card {
                border: none !important;
                box-shadow: none !important;
            }
        }
    </style>
</head>
<body class="bg-light">

    <!-- Memanggil Sidebar -->
    <?php include 'sidebar.php'; ?>

    <div class="main-content" id="mainContent">
        <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom no-print">
            <div>
                <h3 class="fw-bold text-dark mb-1"><i class="fas fa-print text-primary me-2"></i> Laporan Peminjaman Alat</h3>
                <p class="text-muted small mb-0">Cetak dan filter laporan riwayat peminjaman serta pengembalian alat.</p>
            </div>
            <button onclick="window.print()" class="btn btn-dark fw-bold shadow-sm rounded-pill px-4">
                <i class="fas fa-print me-2"></i> Cetak Laporan
            </button>
        </div>

        <!-- FORM FILTER & PENCARIAN -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4 no-print">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Cari Peminjam / Status</label>
                    <input type="text" name="keyword" class="form-control form-control-sm" value="<?php echo htmlspecialchars($keyword); ?>" placeholder="Ketik nama atau status...">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Dari Tanggal</label>
                    <input type="date" name="tgl_mulai" class="form-control form-control-sm" value="<?php echo htmlspecialchars($tgl_mulai); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Sampai Tanggal</label>
                    <input type="date" name="tgl_selesai" class="form-control form-control-sm" value="<?php echo htmlspecialchars($tgl_selesai); ?>">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold"><i class="fas fa-filter me-1"></i> Filter</button>
                    <a href="laporan.php" class="btn btn-secondary btn-sm" title="Reset Filter"><i class="fas fa-sync-alt"></i></a>
                </div>
            </form>
        </div>

        <!-- TABEL LAPORAN -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <div class="text-center mb-4 d-none d-print-block">
                <h2 class="fw-bold">RENTAL & CO.</h2>
                <p class="text-muted small">Laporan Rekapitulasi Peminjaman dan Pengembalian Alat</p>
                <hr>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-dark text-center">
                        <tr>
                            <th>#</th>
                            <th>Nama Peminjam</th>
                            <th>Tgl Pinjam</th>
                            <th>Rencana Kembali</th>
                            <th>Tgl Realisasi Kembali</th>
                            <th>Denda</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                        ?>
                            <tr>
                                <td class="text-center"><?php echo $no++; ?></td>
                                <td class="fw-semibold"><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                                <td class="text-center"><?php echo $row['tgl_pinjam']; ?></td>
                                <td class="text-center"><?php echo $row['tgl_kembali_rencana']; ?></td>
                                <td class="text-center"><?php echo $row['tgl_kembali_real'] ?? '-'; ?></td>
                                <td>Rp <?php echo number_format($row['denda'] ?? 0, 0, ',', '.'); ?></td>
                                <td class="text-center">
                                    <?php 
                                        $status = $row['status'] ?? 'dipinjam';
                                        if ($status == 'selesai') {
                                            echo '<span class="badge bg-success">Selesai</span>';
                                        } else {
                                            echo '<span class="badge bg-warning text-dark">Dipinjam</span>';
                                        }
                                    ?>
                                </td>
                            </tr>
                        <?php 
                            }
                        } else {
                            echo '<tr><td colspan="7" class="text-center text-muted py-4">Tidak ada data laporan yang sesuai dengan filter.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- Tanda Tangan Cetak -->
            <div class="row mt-5 d-none d-print-flex justify-content-end">
                <div class="col-4 text-center">
                    <p class="mb-5">Petugas Pengelola,</p>
                    <p class="fw-bold border-bottom pb-1 mb-0"><?php echo htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Petugas'); ?></p>
                </div>
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