<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../koneksi.php';
/** @var mysqli $koneksi */

// 1. Proteksi Halaman
$role = strtolower($_SESSION['role'] ?? $_SESSION['akses'] ?? '');
if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login') {
    echo "<script>alert('Akses ditolak!'); window.location='../index.php';</script>";
    exit();
}

$id_pengembalian = isset($_GET['id']) ? mysqli_real_escape_string($koneksi, $_GET['id']) : '';

if (empty($id_pengembalian)) {
    header("Location: pengembalian.php");
    exit();
}

// 2. Query Data Pengembalian & Peminjaman
$query_pengembalian = "SELECT pengembalian.*, peminjaman.tgl_pinjam, peminjaman.tgl_kembali_rencana, 
                              users.nama_lengkap, users.role 
                       FROM pengembalian 
                       LEFT JOIN peminjaman ON pengembalian.id_peminjaman = peminjaman.id_peminjaman 
                       LEFT JOIN users ON peminjaman.id_user = users.id_user 
                       WHERE pengembalian.id_pengembalian = '$id_pengembalian'";

$res_pengembalian = mysqli_query($koneksi, $query_pengembalian);
$pengembalian     = mysqli_fetch_assoc($res_pengembalian);

if (!$pengembalian) {
    echo "<script>alert('Data pengembalian tidak ditemukan!'); window.location='pengembalian.php';</script>";
    exit();
}

$id_peminjaman = $pengembalian['id_peminjaman'];

// 3. Query Detail Barang yang Dikembalikan
$query_detail = "SELECT detail_peminjaman.*, alat.nama_alat, alat.kondisi AS kondisi_alat 
                 FROM detail_peminjaman 
                 LEFT JOIN alat ON detail_peminjaman.id_alat = alat.id_alat 
                 WHERE detail_peminjaman.id_peminjaman = '$id_peminjaman'";
$res_detail   = mysqli_query($koneksi, $query_detail);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pengembalian #<?= $pengembalian['id_pengembalian']; ?> - Rental & Co.</title>
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
    @media print {
        .no-print { display: none !important; }
        .main-content { margin-left: 0 !important; padding: 0 !important; }
    }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <div class="no-print">
        <?php include 'sidebar.php'; ?>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content" id="mainContent">
        <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom no-print">
            <div class="d-flex align-items-center">
                <button class="btn btn-outline-secondary me-3" id="sidebarToggle" title="Toggle Sidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <div>
                    <h3 class="fw-bold text-dark mb-1">Detail Pengembalian Alat</h3>
                    <p class="text-muted small mb-0">Rincian kondisi barang saat dikembalikan dan denda (jika ada).</p>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-outline-primary fw-semibold rounded-3">
                    <i class="fas fa-print me-1"></i> Cetak Bukti Kembali
                </button>
                <a href="pengembalian.php" class="btn btn-secondary fw-semibold rounded-3">
                    <i class="fas fa-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>

        <!-- CARD DETAIL / BUKTI PENGEMBALIAN -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <div class="row mb-4 border-bottom pb-3">
                <div class="col-md-6">
                    <h5 class="fw-bold text-primary mb-1"><i class="fas fa-tools me-2"></i>Rental & Co.</h5>
                    <p class="text-muted small mb-0">Bukti Penerimaan Pengembalian Barang</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h6 class="fw-bold mb-1">ID Pengembalian: #<?= $pengembalian['id_pengembalian']; ?></h6>
                    <small class="text-muted">Ref. ID Pinjam: #<?= $pengembalian['id_peminjaman']; ?></small>
                </div>
            </div>

            <!-- RINCIAN INFORMASI -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <table class="table table-borderless table-sm mb-0">
                        <tr>
                            <td class="text-muted" width="40%">Nama Peminjam</td>
                            <td class="fw-bold">: <?= htmlspecialchars($pengembalian['nama_lengkap'] ?? 'Umum'); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Tanggal Pinjam</td>
                            <td>: <?= !empty($pengembalian['tgl_pinjam']) ? date('d-m-Y', strtotime($pengembalian['tgl_pinjam'])) : '-'; ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Tgl Rencana Kembali</td>
                            <td>: <?= !empty($pengembalian['tgl_kembali_rencana']) ? date('d-m-Y', strtotime($pengembalian['tgl_kembali_rencana'])) : '-'; ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless table-sm mb-0">
                        <tr>
                            <td class="text-muted" width="40%">Tgl Real Kembali</td>
                            <td class="fw-bold text-primary">: <?= !empty($pengembalian['tgl_kembali_real']) ? date('d-m-Y', strtotime($pengembalian['tgl_kembali_real'])) : '-'; ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Denda Keterlambatan/Kerusakan</td>
                            <td class="fw-bold text-danger">: 
                                <?php 
                                    $denda = (float)($pengembalian['denda'] ?? 0);
                                    echo ($denda > 0) ? 'Rp ' . number_format($denda, 0, ',', '.') : 'Tidak Ada (Rp 0)';
                                ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- TABEL DAFTAR ALAT & KONDISI PENGEMBALIAN -->
            <h6 class="fw-bold text-dark mb-3">Daftar Alat & Kondisi Saat Dikembalikan</h6>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">#</th>
                            <th>Nama Alat</th>
                            <th width="15%" class="text-center">Jumlah</th>
                            <th width="20%" class="text-center">Kondisi Alat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        if ($res_detail && mysqli_num_rows($res_detail) > 0) {
                            while ($d = mysqli_fetch_assoc($res_detail)) {
                                $kondisi = $d['kondisi_alat'] ?? 'Baik';
                        ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($d['nama_alat'] ?? 'Alat'); ?></td>
                                <td class="text-center"><?= $d['jumlah'] ?? $d['qty'] ?? 1; ?> pcs</td>
                                <td class="text-center">
                                    <?php if (strtolower($kondisi) === 'baik'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1">Baik</span>
                                    <?php elseif (strtolower($kondisi) === 'rusak'): ?>
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-1">Rusak</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1">Hilang / Cacat</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php 
                            }
                        } else {
                            echo '<tr><td colspan="4" class="text-center text-muted py-3">Tidak ada rincian barang.</td></tr>';
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