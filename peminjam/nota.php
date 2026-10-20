<?php
session_start();
include '../koneksi.php';
/** @var mysqli $koneksi */

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'peminjam') {
    echo "<script>alert('Akses ditolak!'); window.location='../login.php';</script>";
    exit();
}

$id_peminjaman = $_GET['id'] ?? '';
$metode = $_GET['metode'] ?? 'Tunai';
$id_user = $_SESSION['id_user'];

// Ambil data peminjaman
$q_pinjam = mysqli_query($koneksi, "SELECT p.*, u.nama_lengkap FROM peminjaman p JOIN users u ON p.id_user = u.id_user WHERE p.id_peminjaman = '$id_peminjaman' AND p.id_user = '$id_user'");
$data_pinjam = mysqli_fetch_assoc($q_pinjam);

if (!$data_pinjam) {
    echo "<script>alert('Data nota tidak ditemukan!'); window.location='riwayat.php';</script>";
    exit();
}

// Ambil detail alat yang dipinjam
$q_detail = mysqli_query($koneksi, "SELECT dp.*, a.nama_alat, a.merk, a.harga FROM detail_peminjaman dp JOIN alat a ON dp.id_alat = a.id_alat WHERE dp.id_peminjaman = '$id_peminjaman'");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota Pembayaran - Rental & Co.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="bg-light py-5">

    <div class="container" style="max-width: 650px;">
        <div class="mb-3 no-print text-start">
            <a href="riwayat.php" class="btn btn-secondary btn-sm rounded-pill px-3"><i class="fas fa-arrow-left me-1"></i> Ke Riwayat Peminjaman</a>
        </div>

        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <div class="text-center border-bottom pb-4 mb-4">
                <h3 class="fw-bold text-dark mb-1"><i class="fas fa-tools text-warning me-2"></i> RENTAL & CO.</h3>
                <p class="text-muted small mb-0">Nota Tagihan & Bukti Pengajuan Peminjaman</p>
                <span class="badge bg-primary mt-2 px-3 py-1">ID Transaksi: #<?php echo str_pad($data_pinjam['id_peminjaman'], 5, '0', STR_PAD_LEFT); ?></span>
            </div>

            <div class="row small mb-4">
                <div class="col-6">
                    <span class="text-muted d-block">Nama Peminjam:</span>
                    <strong class="text-dark"><?php echo htmlspecialchars($data_pinjam['nama_lengkap']); ?></strong>
                </div>
                <div class="col-6 text-end">
                    <span class="text-muted d-block">Tanggal Pengajuan:</span>
                    <strong class="text-dark"><?php echo date('d M Y', strtotime($data_pinjam['tgl_pinjam'])); ?></strong>
                </div>
                <div class="col-6 mt-2">
                    <span class="text-muted d-block">Rencana Kembali:</span>
                    <strong class="text-dark"><?php echo date('d M Y', strtotime($data_pinjam['tgl_kembali_rencana'])); ?></strong>
                </div>
                <div class="col-6 text-end mt-2">
                    <span class="text-muted d-block">Metode Pembayaran:</span>
                    <strong class="text-uppercase text-primary"><?php echo $metode; ?></strong>
                </div>
            </div>

            <div class="table-responsive mb-4">
                <table class="table table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Alat</th>
                            <th class="text-center">Jumlah</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        while ($item = mysqli_fetch_assoc($q_detail)) {
                            $d1 = new DateTime($data_pinjam['tgl_pinjam']);
                            $d2 = new DateTime($data_pinjam['tgl_kembali_rencana']);
                            $durasi = max(1, $d1->diff($d2)->days);
                            $sub = ($item['harga'] ?? 0) * $durasi;
                        ?>
                            <tr>
                                <td>
                                    <span class="fw-semibold d-block"><?php echo htmlspecialchars($item['nama_alat']); ?></span>
                                    <small class="text-muted">Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?> x <?php echo $durasi; ?> hari</small>
                                </td>
                                <td class="text-center">1 Unit</td>
                                <td class="text-end fw-semibold">Rp <?php echo number_format($sub, 0, ',', '.'); ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2" class="fw-bold text-end">Total Tagihan:</td>
                            <td class="text-end fw-bold text-success fs-5">Rp <?php echo number_format($data_pinjam['total_biaya'], 0, ',', '.'); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Bagian Instruksi Pembayaran Berdasarkan Pilihan -->
            <div class="alert bg-light border p-3 rounded-3 text-center mb-4">
                <?php if (strtoupper($metode) === 'QRIS') { ?>
                    <h6 class="fw-bold text-dark mb-2"><i class="fas fa-qrcode me-1 text-primary"></i> Silakan Scan QRIS di Bawah Ini</h6>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=RENTAL-CO-PAYMENT-<?php echo $data_pinjam['id_peminjaman']; ?>" alt="QRIS Code" class="img-fluid my-2 bg-white p-2 border rounded">
                    <p class="small text-muted mb-0">Tunjukkan bukti layar QRIS ini kepada petugas saat melakukan pengambilan (*pickup*) alat.</p>
                <?php } else { ?>
                    <h6 class="fw-bold text-dark mb-1"><i class="fas fa-money-bill-wave me-1 text-success"></i> Pembayaran Tunai di Kasir</h6>
                    <p class="small text-muted mb-0">Status tagihan Anda saat ini **Belum Bayar**. Silakan lakukan pembayaran secara tunai langsung kepada petugas/kasir saat mengambil alat (*pickup*).</p>
                <?php } ?>
            </div>

            <div class="d-flex justify-content-center gap-2 no-print">
                <button onclick="window.print()" class="btakn btn-dark btn-sm rounded-pill px-4 fw-semibold"><i class="fas fa-print me-1"></i> Cetak Nota</button>
                <a href="riwayat.php" class="btn btn-primary btn-sm rounded-pill px-4 fw-semibold">Selesai & Lihat Riwayat</a>
            </div>
        </div>
    </div>

</body>
</html>