<?php
session_start();
include '../koneksi.php';
/** @var mysqli $koneksi */

// Proteksi: Hanya boleh diakses oleh Peminjam
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'peminjam') {
    echo "<script>alert('Akses ditolak! Halaman ini khusus peminjam.'); window.location='../login.php';</script>";
    exit();
}

$id_user = $_SESSION['id_user'];

// PROSES AJUKAN PENGEMBALIAN OLEH PEMINJAM
if (isset($_POST['ajukan_kembali'])) {
    $id_peminjaman_kembali = mysqli_real_escape_string($koneksi, $_POST['id_peminjaman']);
    $tgl_hari_ini = date('Y-m-d');

    // Cek apakah data pengembalian untuk peminjaman ini sudah pernah diajukan sebelumnya
    $cek_pengembalian = mysqli_query($koneksi, "SELECT * FROM pengembalian WHERE id_peminjaman = '$id_peminjaman_kembali'");

    if (mysqli_num_rows($cek_pengembalian) == 0) {
        // Masukkan data ke tabel pengembalian
        $q_insert_pengembalian = "INSERT INTO pengembalian (id_peminjaman, tgl_kembali_real, denda) 
                                   VALUES ('$id_peminjaman_kembali', '$tgl_hari_ini', 0)";
        if (mysqli_query($koneksi, $q_insert_pengembalian)) {
            echo "<script>alert('Pengajuan pengembalian berhasil dikirim! Menunggu konfirmasi petugas.'); window.location='riwayat.php';</script>";
            exit();
        } else {
            $error = "Gagal mengajukan pengembalian: " . mysqli_error($koneksi);
        }
    } else {
        echo "<script>alert('Pengajuan pengembalian untuk nota ini sudah pernah dikirim sebelumnya.'); window.location='riwayat.php';</script>";
        exit();
    }
}

// Ambil data peminjaman (header nota) berdasarkan user yang sedang login
$query = "SELECT p.*, peng.tgl_kembali_real, peng.denda 
          FROM peminjaman p 
          LEFT JOIN pengembalian peng ON p.id_peminjaman = peng.id_peminjaman 
          WHERE p.id_user = '$id_user' 
          ORDER BY p.id_peminjaman DESC";
$result = mysqli_query($koneksi, $query);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Peminjaman - Rental & Co.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .card-riwayat {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border: none;
            border-radius: 16px;
        }

        .card-riwayat:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08) !important;
        }
    </style>
</head>

<body class="bg-light d-flex flex-column min-vh-100">

    <!-- Navbar Peminjam -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-warning" href="../index.php"><i class="fas fa-tools me-2"></i> Rental & Co.</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../index.php">Beranda</a>
                <a class="nav-link" href="keranjang.php"><i class="fas fa-shopping-cart me-1"></i> Keranjang</a>
                <a class="nav-link active" href="riwayat.php">Peminjaman Saya</a>
                <a class="nav-link text-danger fw-bold" href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h3 class="fw-bold text-dark mb-1"><i class="fas fa-history text-primary me-2"></i> Riwayat Transaksi Peminjaman</h3>
                <p class="text-muted small mb-0">Daftar nota transaksi, status, dan pengajuan pengembalian alat Anda.</p>
            </div>
            <a href="../index.php#daftar-alat" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm">
                <i class="fas fa-plus me-1"></i> Pinjam Alat Lagi
            </a>
        </div>

        <?php if (!empty($error)) { ?>
            <div class="alert alert-danger py-2 small"><?php echo $error; ?></div>
        <?php } ?>

        <!-- LIST CARD BOX PER NOTA -->
        <div class="row g-4">
            <?php
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $id_peminjaman = $row['id_peminjaman'];
                    $status = strtolower($row['status'] ?? 'menunggu');

                    // Menentukan badge status & warna border nota
                    if ($status === 'selesai') {
                        $badgeClass = 'bg-success';
                        $statusText = 'Selesai / Dikembalikan';
                        $borderClass = 'border-start border-4 border-success';
                    } else if ($status === 'disetujui') {
                        $badgeClass = 'bg-primary';
                        $statusText = 'Disetujui / Sedang Dipinjam';
                        $borderClass = 'border-start border-4 border-primary';
                    } else if ($status === 'menunggu_pengembalian') {
                        $badgeClass = 'bg-info text-dark';
                        $statusText = 'Menunggu Konfirmasi Pengembalian';
                        $borderClass = 'border-start border-4 border-info';
                    } else if ($status === 'ditolak') {
                        $badgeClass = 'bg-danger';
                        $statusText = 'Ditolak';
                        $borderClass = 'border-start border-4 border-danger';
                    } else {
                        $badgeClass = 'bg-warning text-dark';
                        $statusText = 'Menunggu Konfirmasi';
                        $borderClass = 'border-start border-4 border-warning';
                    }

                    // Ambil jumlah alat & beberapa nama alat untuk preview di card
                    $q_preview = mysqli_query($koneksi, "SELECT a.nama_alat FROM detail_peminjaman dp JOIN alat a ON dp.id_alat = a.id_alat WHERE dp.id_peminjaman = '$id_peminjaman'");
                    $total_item = mysqli_num_rows($q_preview);
                    $list_nama_alat = [];
                    while ($p_item = mysqli_fetch_assoc($q_preview)) {
                        $list_nama_alat[] = $p_item['nama_alat'];
                    }
                    $preview_teks = implode(', ', $list_nama_alat);
            ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card card-riwayat shadow-sm bg-white p-4 h-100 <?php echo $borderClass; ?> d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <span class="badge bg-secondary mb-1">Nota #<?php echo str_pad($id_peminjaman, 5, '0', STR_PAD_LEFT); ?></span>
                                    <span class="badge <?php echo $badgeClass; ?> px-2 py-1 rounded-pill small d-block mt-1">
                                        <?php echo $statusText; ?>
                                    </span>
                                </div>
                                <span class="text-success fw-bold">Rp <?php echo number_format($row['total_biaya'] ?? 0, 0, ',', '.'); ?></span>
                            </div>

                            <div class="my-2">
                                <span class="text-muted small d-block">Alat yang Dipinjam (<?php echo $total_item; ?> Item):</span>
                                <p class="fw-semibold text-dark text-truncate mb-0" style="max-width: 100%;" title="<?php echo htmlspecialchars($preview_teks); ?>">
                                    <?php echo htmlspecialchars($preview_teks); ?>
                                </p>
                            </div>

                            <hr class="my-2 text-muted opacity-25">

                            <!-- Detail Tanggal & Denda -->
                            <div class="row g-2 small my-1">
                                <div class="col-6">
                                    <span class="text-muted d-block">Tgl Pinjam:</span>
                                    <span class="fw-bold text-dark"><i class="far fa-calendar-alt me-1 text-primary"></i><?php echo date('d M Y', strtotime($row['tgl_pinjam'])); ?></span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block">Rencana Kembali:</span>
                                    <span class="fw-bold text-dark"><i class="far fa-calendar-check me-1 text-warning"></i><?php echo date('d M Y', strtotime($row['tgl_kembali_rencana'])); ?></span>
                                </div>
                                <?php if (!empty($row['tgl_kembali_real'])) { ?>
                                    <div class="col-6 mt-2">
                                        <span class="text-muted d-block">Dikembalikan:</span>
                                        <span class="fw-bold text-success"><i class="fas fa-check-circle me-1"></i><?php echo date('d M Y', strtotime($row['tgl_kembali_real'])); ?></span>
                                    </div>
                                <?php } ?>
                                <div class="col-6 mt-2">
                                    <span class="text-muted d-block">Denda:</span>
                                    <span class="fw-bold <?php echo ($row['denda'] > 0) ? 'text-danger' : 'text-dark'; ?>">
                                        Rp <?php echo number_format($row['denda'] ?? 0, 0, ',', '.'); ?>
                                    </span>
                                </div>
                            </div>

                            <!-- TOMBOL AKSI KHUSUS PENGEMBALIAN & DETAIL -->
                            <div class="mt-auto pt-3 border-top">

                                <!-- Jika status sudah disetujui, arahkan ke form detail pengisian kondisi alat -->
                                <?php if ($status === 'disetujui') { ?>
                                    <a href="pengembalian_form.php?id=<?php echo $id_peminjaman; ?>" class="btn btn-warning btn-sm rounded-pill w-100 fw-bold text-dark shadow-sm mb-2 text-decoration-none">
                                        <i class="fas fa-undo me-1"></i> Ajukan Pengembalian
                                    </a>
                                <?php } ?>

                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-primary btn-sm rounded-pill w-100 fw-semibold" data-bs-toggle="modal" data-bs-target="#detailModal<?php echo $id_peminjaman; ?>">
                                        <i class="fas fa-info-circle me-1"></i> Detail Alat
                                    </button>
                                    <a href="nota.php?id=<?php echo $id_peminjaman; ?>&metode=Tunai" class="btn btn-dark btn-sm rounded-pill px-3" title="Lihat Nota">
                                        <i class="fas fa-file-invoice"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- MODAL DETAIL ALAT PER NOTA -->
                    <div class="modal fade" id="detailModal<?php echo $id_peminjaman; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 rounded-4 shadow">
                                <div class="modal-header border-bottom-0">
                                    <h5 class="fw-bold text-dark mb-0"><i class="fas fa-box-open text-primary me-2"></i> Detail Alat Nota #<?php echo str_pad($id_peminjaman, 5, '0', STR_PAD_LEFT); ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Nama Alat</th>
                                                    <th>Merk</th>
                                                    <th class="text-center">Kondisi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $q_det_modal = mysqli_query($koneksi, "SELECT a.* FROM detail_peminjaman dp JOIN alat a ON dp.id_alat = a.id_alat WHERE dp.id_peminjaman = '$id_peminjaman'");
                                                while ($det = mysqli_fetch_assoc($q_det_modal)) {
                                                ?>
                                                    <tr>
                                                        <td class="fw-semibold">
                                                            <?php if (!empty($det['gambar']) && file_exists('../uploads/' . $det['gambar'])) { ?>
                                                                <img src="../uploads/<?php echo $det['gambar']; ?>" class="rounded me-1" style="width: 30px; height: 30px; object-fit: cover;">
                                                            <?php } ?>
                                                            <?php echo htmlspecialchars($det['nama_alat']); ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($det['merk'] ?? '-'); ?></td>
                                                        <td class="text-center"><span class="badge bg-secondary"><?php echo htmlspecialchars($det['kondisi']); ?></span></td>
                                                    </tr>
                                                <?php
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="modal-footer border-top-0">
                                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php
                }
            } else {
                ?>
                <div class="col-12 text-center py-5">
                    <div class="bg-white rounded-4 p-5 shadow-sm d-inline-block">
                        <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                        <h5 class="fw-bold text-dark">Belum Ada Transaksi Peminjaman</h5>
                        <p class="text-muted small mb-3">Anda belum pernah melakukan peminjaman alat apapun.</p>
                        <a href="../index.php#daftar-alat" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold">Mulai Pinjam Sekarang</a>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>