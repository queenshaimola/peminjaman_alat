<?php
session_start();
include '../koneksi.php';
/** @var mysqli $koneksi */

// Proteksi halaman khusus petugas
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'petugas') {
    echo "<script>alert('Akses ditolak! Halaman ini khusus Petugas.'); window.location='dashboard.php';</script>";
    exit();
}

$id_petugas = $_SESSION['id_user'];

// PROSES KONFIRMASI / SELESAIKAN PENGEMBALIAN OLEH PETUGAS
if (isset($_POST['konfirmasi_kembali'])) {
    $id_pengembalian = mysqli_real_escape_string($koneksi, $_POST['id_pengembalian']);
    $id_peminjaman = mysqli_real_escape_string($koneksi, $_POST['id_peminjaman']);
    $denda_baru = mysqli_real_escape_string($koneksi, $_POST['denda'] ?? 0);

    // 1. Update data pengembalian (catat petugas yang memproses & denda final)
    $q_up_pengembalian = "UPDATE pengembalian SET id_petugas = '$id_petugas', denda = '$denda_baru' WHERE id_pengembalian = '$id_pengembalian'";
    mysqli_query($koneksi, $q_up_pengembalian);

    // 2. Update status utama peminjaman menjadi 'selesai'
    $q_up_peminjaman = "UPDATE peminjaman SET status = 'selesai' WHERE id_peminjaman = '$id_peminjaman'";
    mysqli_query($koneksi, $q_up_peminjaman);

    // 3. Kembalikan stok alat ke tabel alat secara otomatis
    $q_detail = mysqli_query($koneksi, "SELECT id_alat, jumlah FROM detail_peminjaman WHERE id_peminjaman = '$id_peminjaman'");
    while ($dt = mysqli_fetch_assoc($q_detail)) {
        $id_alat = $dt['id_alat'];
        $jml_pinjam = $dt['jumlah'];
        mysqli_query($koneksi, "UPDATE alat SET stok = stok + $jml_pinjam WHERE id_alat = '$id_alat'");
    }

    echo "<script>alert('Pengembalian alat berhasil dikonfirmasi dan diselesaikan!'); window.location='pengembalian.php';</script>";
    exit();
}

// Tangkap input pencarian & filter dari metode GET
$cari = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, $_GET['cari']) : '';
$tgl_mulai = isset($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : '';
$tgl_selesai = isset($_GET['tgl_selesai']) ? $_GET['tgl_selesai'] : '';

// Query dengan JOIN total_biaya dan GROUP_CONCAT untuk alat
$query = "SELECT pengembalian.*, 
                 peminjaman.total_biaya, peminjaman.status AS status_pinjam,
                 peminjam.nama_lengkap AS nama_peminjam, 
                 petugas.nama_lengkap AS nama_petugas, 
                 GROUP_CONCAT(alat.nama_alat SEPARATOR ', ') AS daftar_alat, 
                 GROUP_CONCAT(detail_peminjaman.jumlah SEPARATOR ', ') AS daftar_jumlah 
          FROM pengembalian 
          JOIN peminjaman ON pengembalian.id_peminjaman = peminjaman.id_peminjaman
          JOIN users AS peminjam ON peminjaman.id_user = peminjam.id_user
          LEFT JOIN users AS petugas ON pengembalian.id_petugas = petugas.id_user
          LEFT JOIN detail_peminjaman ON peminjaman.id_peminjaman = detail_peminjaman.id_peminjaman
          LEFT JOIN alat ON detail_peminjaman.id_alat = alat.id_alat
          WHERE 1=1";

if (!empty($cari)) {
    $query .= " AND (peminjam.nama_lengkap LIKE '%$cari%' OR alat.nama_alat LIKE '%$cari%')";
}

if (!empty($tgl_mulai) && !empty($tgl_selesai)) {
    $query .= " AND pengembalian.tgl_kembali_real BETWEEN '$tgl_mulai' AND '$tgl_selesai'";
}

$query .= " GROUP BY pengembalian.id_pengembalian ORDER BY pengembalian.id_pengembalian DESC";
$result = mysqli_query($koneksi, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengembalian Alat - Rental & Co.</title>
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
                    <h3 class="fw-bold text-dark mb-1">Kelola Pengembalian & Denda Alat</h3>
                    <p class="text-muted small mb-0">Verifikasi pengajuan pengembalian alat dari peminjam dan tetapkan denda jika ada.</p>
                </div>
            </div>
        </div>

        <!-- CARD PENCARIAN & FILTER -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted">Cari Peminjam / Alat</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" class="form-control" name="cari" value="<?php echo htmlspecialchars($cari); ?>" placeholder="Nama peminjam/alat...">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Dari Tgl Kembali</label>
                    <input type="date" class="form-control" name="tgl_mulai" value="<?php echo htmlspecialchars($tgl_mulai); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Sampai Tgl Kembali</label>
                    <input type="date" class="form-control" name="tgl_selesai" value="<?php echo htmlspecialchars($tgl_selesai); ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary fw-bold w-100 rounded-pill"><i class="fas fa-filter me-1"></i> Filter</button>
                    <a href="pengembalian.php" class="btn btn-outline-secondary rounded-circle" title="Reset Filter"><i class="fas fa-sync-alt"></i></a>
                </div>
            </form>
        </div>

        <!-- TABEL DATA PENGEMBALIAN -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Peminjam</th>
                            <th>Alat (Jumlah)</th>
                            <th>Tgl Kembali Real</th>
                            <th>Biaya Sewa</th>
                            <th>Denda</th>
                            <th>Total Tagihan</th>
                            <th>Status Peminjaman</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <? 
                        $no = 1;
                        if ($result && mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                $biaya_sewa = $row['total_biaya'] ?? 0;
                                $denda = $row['denda'] ?? 0;
                                $total_pembayaran = $biaya_sewa + $denda;
                                $status_pinjam = strtolower($row['status_pinjam'] ?? '');
                        ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td class="fw-semibold"><?php echo htmlspecialchars($row['nama_peminjam'] ?? '-'); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($row['daftar_alat'] ?? '-'); ?> 
                                    <span class="badge bg-secondary ms-1">(<?php echo htmlspecialchars($row['daftar_jumlah'] ?? '0'); ?> Unit)</span>
                                </td>
                                <td><?php echo !empty($row['tgl_kembali_real']) ? date('d-m-Y', strtotime($row['tgl_kembali_real'])) : '-'; ?></td>
                                <td>Rp <?php echo number_format($biaya_sewa, 0, ',', '.'); ?></td>
                                <td>
                                    <?php if ($denda > 0) { ?>
                                        <span class="badge bg-danger">Rp <?php echo number_format($denda, 0, ',', '.'); ?></span>
                                    <?php } else { ?>
                                        <span class="badge bg-success">Rp 0</span>
                                    <?php } ?>
                                </td>
                                <td class="fw-bold text-primary">Rp <?php echo number_format($total_pembayaran, 0, ',', '.'); ?></td>
                                <td>
                                    <?php if ($status_pinjam === 'selesai') { ?>
                                        <span class="badge bg-success">Selesai / Dikembalikan</span>
                                    <?php } else { ?>
                                        <span class="badge bg-warning text-dark">Menunggu Konfirmasi</span>
                                    <?php } ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($status_pinjam !== 'selesai') { ?>
                                        <!-- Tombol untuk memicu Modal Proses Konfirmasi -->
                                        <button type="button" class="btn btn-success btn-sm rounded-pill px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#konfirmasiModal<?php echo $row['id_pengembalian']; ?>">
                                            <i class="fas fa-check-circle me-1"></i> Proses Selesai
                                        </button>
                                    <?php } else { ?>
                                        <span class="text-muted small"><i class="fas fa-check text-success me-1"></i> Selesai (Oleh: <?php echo htmlspecialchars($row['nama_petugas'] ?? '-'); ?>)</span>
                                    <?php } ?>
                                </td>
                            </tr>

                            <!-- MODAL KONFIRMASI & INPUT DENDA -->
                            <div class="modal fade" id="konfirmasiModal<?php echo $row['id_pengembalian']; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 rounded-4 shadow">
                                        <form action="" method="POST">
                                            <div class="modal-header border-bottom-0">
                                                <h5 class="fw-bold text-dark mb-0"><i class="fas fa-clipboard-check text-success me-2"></i> Konfirmasi Pengembalian Alat</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body text-start">
                                                <input type="hidden" name="id_pengembalian" value="<?php echo $row['id_pengembalian']; ?>">
                                                <input type="hidden" name="id_peminjaman" value="<?php echo $row['id_peminjaman']; ?>">

                                                <p class="small text-muted mb-3">Peminjam: <strong class="text-dark"><?php echo htmlspecialchars($row['nama_peminjam']); ?></strong></p>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label small fw-semibold">Masukkan Nominal Denda (Jika ada keterlambatan/kerusakan):</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">Rp</span>
                                                        <input type="number" name="denda" class="form-control" value="<?php echo $row['denda']; ?>" min="0" required>
                                                    </div>
                                                </div>
                                                <p class="text-muted small fst-italic">Dengan mengkonfirmasi ini, status peminjaman akan menjadi selesai dan stok alat akan otomatis dikembalikan ke sistem.</p>
                                            </div>
                                            <div class="modal-footer border-top-0">
                                                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" name="konfirmasi_kembali" class="btn btn-success btn-sm rounded-pill px-4 fw-bold">Konfirmasi Selesai</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?
                            }
                        } else {
                            echo '<tr><td colspan="9" class="text-center text-muted py-4">Tidak ada data pengembalian alat yang sesuai.</td></tr>';
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