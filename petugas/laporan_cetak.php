<?php
session_start();
include '../koneksi.php';
/** @var mysqli $koneksi */

// Proteksi: Hanya boleh diakses oleh Admin atau Petugas
if (!isset($_SESSION['role']) || !in_array(strtolower($_SESSION['role']), ['admin', 'petugas'])) {
    echo "<script>alert('Akses ditolak!'); window.location='../login.php';</script>";
    exit();
}

// Ambil parameter filter jika ada dari halaman sebelumnya
$keyword = $_GET['keyword'] ?? '';
$tgl_mulai = $_GET['tgl_mulai'] ?? '';
$tgl_selesai = $_GET['tgl_selesai'] ?? '';

$query = "SELECT p.*, u.nama_lengkap, peng.tgl_kembali_real, peng.denda 
          FROM peminjaman p 
          JOIN users u ON p.id_user = u.id_user 
          LEFT JOIN pengembalian peng ON p.id_peminjaman = peng.id_peminjaman 
          WHERE 1=1";

if (!empty($keyword)) {
    $keyword_esc = mysqli_real_escape_string($koneksi, $keyword);
    $query .= " AND (u.nama_lengkap LIKE '%$keyword_esc%' OR p.status LIKE '%$keyword_esc%')";
}

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
    <title>Cetak Laporan - Rental & Co.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #fff;
            color: #000;
        }
        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="container my-4">
        <!-- Tombol Kembali (Hilang saat diprint) -->
        <div class="mb-3 no-print">
            <a href="laporan.php" class="btn btn-secondary btn-sm">&larr; Kembali ke Laporan</a>
        </div>

        <!-- Header Kop Laporan -->
        <div class="text-center mb-4">
            <h3 class="fw-bold mb-1">RENTAL & CO.</h3>
            <p class="text-muted small mb-0">Laporan Rekapitulasi Peminjaman dan Pengembalian Alat</p>
            <?php if (!empty($tgl_mulai) && !empty($tgl_selesai)) { ?>
                <p class="small text-secondary mt-1">Periode: <?php echo $tgl_mulai; ?> s/d <?php echo $tgl_selesai; ?></p>
            <?php } ?>
            <hr class="mt-2">
        </div>

        <!-- Tabel Data Laporan -->
        <div class="table-responsive">
            <table class="table table-bordered table-sm align-middle">
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
                            <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                            <td class="text-center"><?php echo $row['tgl_pinjam']; ?></td>
                            <td class="text-center"><?php echo $row['tgl_kembali_rencana']; ?></td>
                            <td class="text-center"><?php echo $row['tgl_kembali_real'] ?? '-'; ?></td>
                            <td>Rp <?php echo number_format($row['denda'] ?? 0, 0, ',', '.'); ?></td>
                            <td class="text-center text-uppercase small fw-semibold"><?php echo $row['status'] ?? 'dipinjam'; ?></td>
                        </tr>
                    <?php 
                        }
                    } else {
                        echo '<tr><td colspan="7" class="text-center text-muted py-3">Tidak ada data laporan.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Tanda Tangan -->
        <div class="row mt-5 justify-content-end">
            <div class="col-4 text-center">
                <p class="mb-5">Petugas Pengelola,</p>
                <p class="fw-bold border-bottom pb-1 mb-0"><?php echo htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Petugas'); ?></p>
            </div>
        </div>
    </div>

</body>
</html>