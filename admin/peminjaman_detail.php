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

$id_peminjaman = isset($_GET['id']) ? mysqli_real_escape_string($koneksi, $_GET['id']) : '';

if (empty($id_peminjaman)) {
    header("Location: peminjaman.php");
    exit();
}

// 2. Query Utama Data Peminjaman
$query_peminjaman = "SELECT peminjaman.*, users.nama_lengkap, users.role 
                    FROM peminjaman 
                    LEFT JOIN users ON peminjaman.id_user = users.id_user 
                    WHERE peminjaman.id_peminjaman = '$id_peminjaman'";
$res_peminjaman = mysqli_query($koneksi, $query_peminjaman);
$peminjaman     = mysqli_fetch_assoc($res_peminjaman);

if (!$peminjaman) {
    echo "<script>alert('Data peminjaman tidak ditemukan!'); window.location='peminjaman.php';</script>";
    exit();
}

// 3. Query Detail Barang/Alat yang Dipinjam
$query_detail = "SELECT detail_peminjaman.*, alat.nama_alat 
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
    <title>Detail Peminjaman #<?= $peminjaman['id_peminjaman']; ?> - Rental & Co.</title>
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
                    <h3 class="fw-bold text-dark mb-1">Detail Peminjaman</h3>
                    <p class="text-muted small mb-0">Rincian transaksi peminjaman alat #<?= $peminjaman['id_peminjaman']; ?></p>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-outline-primary fw-semibold rounded-3">
                    <i class="fas fa-print me-1"></i> Cetak Nota
                </button>
                <a href="peminjaman.php" class="btn btn-secondary fw-semibold rounded-3">
                    <i class="fas fa-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>

        <!-- NOTA / CARD DETAIL -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <div class="row mb-4 border-bottom pb-3">
                <div class="col-md-6">
                    <h5 class="fw-bold text-primary mb-1"><i class="fas fa-tools me-2"></i>Rental & Co.</h5>
                    <p class="text-muted small mb-0">Sistem Informasi Peminjaman & Rental Alat</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h6 class="fw-bold mb-1">ID Transaksi: #<?= $peminjaman['id_peminjaman']; ?></h6>
                    <small class="text-muted">Status Pinjam: </small>
                    <?php 
                        $st = strtolower($peminjaman['status'] ?? '');
                        if ($st === 'selesai' || $st === 'sel_') {
                            echo '<span class="badge bg-success">Selesai</span>';
                        } elseif ($st === 'terlambat') {
                            echo '<span class="badge bg-danger">Terlambat</span>';
                        } else {
                            echo '<span class="badge bg-warning text-dark">Dipinjam</span>';
                        }
                    ?>
                </div>
            </div>

            <!-- RINCIAN INFORMASI -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <table class="table table-borderless table-sm mb-0">
                        <tr>
                            <td class="text-muted" width="40%">Nama Peminjam</td>
                            <td class="fw-bold">: <?= htmlspecialchars($peminjaman['nama_lengkap'] ?? 'Umum'); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Tanggal Pinjam</td>
                            <td>: <?= !empty($peminjaman['tgl_pinjam']) ? date('d-m-Y', strtotime($peminjaman['tgl_pinjam'])) : '-'; ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Tgl Rencana Kembali</td>
                            <td>: <?= !empty($peminjaman['tgl_kembali_rencana']) ? date('d-m-Y', strtotime($peminjaman['tgl_kembali_rencana'])) : '-'; ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless table-sm mb-0">
                        <tr>
                            <td class="text-muted" width="40%">Status Pembayaran</td>
                            <td>: 
                                <?php 
                                    $bayar = strtolower($peminjaman['status_pembayaran'] ?? '');
                                    if ($bayar === 'lunas') {
                                        echo '<span class="badge bg-success">Lunas</span>';
                                    } else {
                                        echo '<span class="badge bg-danger">Belum Bayar</span>';
                                    }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Total Biaya</td>
                            <td class="fw-bold text-success">: Rp <?= number_format($peminjaman['total_biaya'] ?? 0, 0, ',', '.'); ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- TABEL DAFTAR ALAT YANG DIPINJAM -->
            <h6 class="fw-bold text-dark mb-3">Daftar Alat / Barang Dipinjam</h6>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">#</th>
                            <th>Nama Alat</th>
                            <th width="15%" class="text-center">Jumlah</th>
                            <th width="20%" class="text-end">Subtotal Biaya</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        if ($res_detail && mysqli_num_rows($res_detail) > 0) {
                            while ($d = mysqli_fetch_assoc($res_detail)) {
                        ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($d['nama_alat'] ?? 'Alat'); ?></td>
                                <td class="text-center"><?= $d['jumlah'] ?? $d['qty'] ?? 1; ?> pcs</td>
                                <td class="text-end fw-bold">Rp <?= number_format($d['subtotal'] ?? 0, 0, ',', '.'); ?></td>
                            </tr>
                        <?php 
                            }
                        } else {
                            echo '<tr><td colspan="4" class="text-center text-muted py-3">Tidak ada rincian alat pada peminjaman ini.</td></tr>';
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