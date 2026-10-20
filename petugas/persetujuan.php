<?php
session_start();
include '../koneksi.php';
/** @var mysqli $koneksi */

// Proteksi halaman khusus Petugas atau Admin
$role = strtolower($_SESSION['role'] ?? '');
if ($role !== 'admin' && $role !== 'petugas') {
    echo "<script>alert('Akses ditolak! Halaman ini khusus Petugas/Admin.'); window.location='../dashboard.php';</script>";
    exit();
}

$id_user_login = $_SESSION['id_user'] ?? 1; // ID petugas yang sedang login

// PROSES AKSI PERSETUJUAN (SETUJU / TOLAK)
if (isset($_GET['aksi']) && isset($_GET['id'])) {
    $id_peminjaman = intval($_GET['id']);
    $aksi = $_GET['aksi'];

    if ($aksi === 'setujui') {
        // 1. Ambil detail barang dan jumlah yang dipinjam untuk pengecekan stok
        $q_detail = mysqli_query($koneksi, "SELECT * FROM detail_peminjaman WHERE id_peminjaman = '$id_peminjaman'");
        $stok_cukup = true;

        while ($item = mysqli_fetch_assoc($q_detail)) {
            $id_alat = $item['id_alat'];
            $jml_pinjam = $item['jumlah'];

            // Cek stok saat ini
            $q_alat = mysqli_query($koneksi, "SELECT stok, nama_alat FROM alat WHERE id_alat = '$id_alat'");
            $data_alat = mysqli_fetch_assoc($q_alat);

            if ($data_alat['stok'] < $jml_pinjam) {
                $stok_cukup = false;
                echo "<script>alert('Gagal disetujui! Stok alat \"{$data_alat['nama_alat']}\" tidak mencukupi.'); window.location='persetujuan.php';</script>";
                exit();
            }
        }

        if ($stok_cukup) {
            // 2. Kurangi stok alat sesuai jumlah peminjaman
            $q_detail2 = mysqli_query($koneksi, "SELECT * FROM detail_peminjaman WHERE id_peminjaman = '$id_peminjaman'");
            while ($item = mysqli_fetch_assoc($q_detail2)) {
                $id_alat = $item['id_alat'];
                $jml_pinjam = $item['jumlah'];
                mysqli_query($koneksi, "UPDATE alat SET stok = stok - $jml_pinjam WHERE id_alat = '$id_alat'");
            }

            // 3. Update status peminjaman menjadi 'disetujui' dan catat id petugas
            $update = "UPDATE peminjaman SET status = 'disetujui', id_id_petugas = '$id_user_login' WHERE id_peminjaman = '$id_peminjaman'";
            mysqli_query($koneksi, $update);

            echo "<script>alert('Peminjaman berhasil disetujui dan stok alat telah diperbarui!'); window.location='persetujuan.php';</script>";
            exit();
        }
    } elseif ($aksi === 'tolak') {
        // Jika ditolak, cukup ubah status tanpa kurangi stok
        $update = "UPDATE peminjaman SET status = 'ditolak', id_id_petugas = '$id_user_login' WHERE id_peminjaman = '$id_peminjaman'";
        mysqli_query($koneksi, $update);

        echo "<script>alert('Peminjaman telah ditolak.'); window.location='persetujuan.php';</script>";
        exit();
    }
}

// Ambil data peminjaman yang statusnya masih 'menunggu'
$query = "SELECT peminjaman.*, users.nama_lengkap, 
                 GROUP_CONCAT(alat.nama_alat SEPARATOR ', ') AS daftar_alat, 
                 GROUP_CONCAT(detail_peminjaman.jumlah SEPARATOR ', ') AS daftar_jumlah 
          FROM peminjaman 
          JOIN users ON peminjaman.id_user = users.id_user 
          LEFT JOIN detail_peminjaman ON peminjaman.id_peminjaman = detail_peminjaman.id_peminjaman 
          LEFT JOIN alat ON detail_peminjaman.id_alat = alat.id_alat 
          WHERE peminjaman.status = 'menunggu'
          GROUP BY peminjaman.id_peminjaman 
          ORDER BY peminjaman.id_peminjaman DESC";
$result = mysqli_query($koneksi, $query);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Persetujuan Peminjaman - Rental & Co.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .main-content {
            margin-left: 260px;
            padding: 30px;
            transition: margin-left 0.3s ease;
            width: calc(100% - 260px);
        }

        .main-content.expanded {
            margin-left: 0;
            width: 100%;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <!-- MEMANGGIL SIDEBAR -->
    <?php include 'sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="main-content" id="mainContent">
        <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
            <div class="d-flex align-items-center">
                <button class="btn btn-outline-secondary me-3" id="sidebarToggle" title="Sembunyikan/Tampilkan Sidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <div>
                    <h3 class="fw-bold text-dark mb-1">Persetujuan Peminjaman Alat</h3>
                    <p class="text-muted small mb-0">Daftar pengajuan peminjaman dari user yang menunggu konfirmasi petugas.</p>
                </div>
            </div>

            <div class="dropdown">
                <button class="btn btn-white border shadow-sm dropdown-toggle rounded-pill px-3" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-user-shield text-primary me-2"></i> <?php echo ucfirst($role); ?> Sistem
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="../index.php" target="_blank"><i class="fas fa-globe me-2"></i> Lihat Website</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item text-danger" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Keluar</a></li>
                </ul>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Peminjam</th>
                            <th>Alat & Jumlah</th>
                            <th>Tgl Pinjam</th>
                            <th>Rencana Kembali</th>
                            <th>Total Biaya</th>
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
                                    <!-- Menggunakan operator ?? '' agar terhindar dari warning null ke htmlspecialchars -->
                                    <td class="fw-semibold"><?php echo htmlspecialchars($row['nama_lengkap'] ?? ''); ?></td>
                                    <!-- KODE BARU YANG DIPERBAIKI -->
                                    <td>
                                        <?php echo htmlspecialchars($row['daftar_alat'] ?? ''); ?>
                                        <span class="badge bg-secondary ms-1">(<?php echo htmlspecialchars(($row['daftar_jumlah'] > 0) ? $row['daftar_jumlah'] : '1'); ?> Unit)</span>
                                    </td>
                                    <td><?php echo date('d-m-Y', strtotime($row['tgl_pinjam'])); ?></td>
                                    <td><?php echo date('d-m-Y', strtotime($row['tgl_kembali_rencana'])); ?></td>
                                    <td class="fw-bold text-success">Rp <?php echo number_format($row['total_biaya'] ?? 0, 0, ',', '.'); ?></td>
                                    <td class="text-center text-nowrap">
                                        <a href="persetujuan.php?aksi=setujui&id=<?php echo $row['id_peminjaman']; ?>" class="btn btn-success btn-sm fw-bold px-3 rounded-pill me-1" onclick="return confirm('Setujui peminjaman ini? Stok alat akan otomatis berkurang.')">
                                            <i class="fas fa-check me-1"></i> Setuju
                                        </a>
                                        <a href="persetujuan.php?aksi=tolak&id=<?php echo $row['id_peminjaman']; ?>" class="btn btn-danger btn-sm fw-bold px-3 rounded-pill" onclick="return confirm('Yakin ingin menolak peminjaman ini?')">
                                            <i class="fas fa-times me-1"></i> Tolak
                                        </a>
                                    </td>
                                </tr>
                        <?php
                            }
                        } else {
                            echo '<tr><td colspan="7" class="text-center text-muted py-4"><i class="fas fa-check-circle fa-2x text-success mb-2 d-block"></i>Tidak ada pengajuan peminjaman yang menunggu persetujuan saat ini.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Script Toggle Sidebar -->
    <script>
        const sidebar = document.getElementById('sidebarNav');
        const mainContent = document.getElementById('mainContent');
        const sidebarToggle = document.getElementById('sidebarToggle');

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('hidden');
                mainContent.classList.toggle('expanded');
            });
        }
    </script>
</body>

</html>