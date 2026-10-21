<?php
session_start();
include '../koneksi.php';
/** @var mysqli $koneksi */

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'peminjam') {
    echo "<script>alert('Akses ditolak!'); window.location='../login.php';</script>";
    exit();
}

$id_user = $_SESSION['id_user'];
$id_peminjaman = isset($_GET['id']) ? mysqli_real_escape_string($koneksi, $_GET['id']) : '';

// Ambil data peminjaman & pastikan milik user yang sedang login
$q_pinjam = mysqli_query($koneksi, "SELECT * FROM peminjaman WHERE id_peminjaman = '$id_peminjaman' AND id_user = '$id_user'");
$data_pinjam = mysqli_fetch_assoc($q_pinjam);

if (!$data_pinjam) {
    echo "<script>alert('Data peminjaman tidak ditemukan atau bukan milik Anda!'); window.location='riwayat.php';</script>";
    exit();
}

// Proses Simpan Pengembalian & Update Kondisi Alat
if (isset($_POST['proses_kembali'])) {
    $tgl_hari_ini = date('Y-m-d');
    $kondisi_alat_input = $_POST['kondisi_alat'] ?? []; // Array [id_alat => kondisi]

    // 1. Masukkan ke tabel pengembalian
    $q_cek = mysqli_query($koneksi, "SELECT * FROM pengembalian WHERE id_peminjaman = '$id_peminjaman'");
    if (mysqli_num_rows($q_cek) == 0) {
        mysqli_query($koneksi, "INSERT INTO pengembalian (id_peminjaman, tgl_kembali_real, denda) VALUES ('$id_peminjaman', '$tgl_hari_ini', 0)");
    }

    // 2. Update kondisi masing-masing alat di tabel alat berdasarkan pilihan peminjam
    foreach ($kondisi_alat_input as $id_alat => $kondisi_baru) {
        $id_al_esc = mysqli_real_escape_string($koneksi, $id_alat);
        $kondisi_esc = mysqli_real_escape_string($koneksi, $kondisi_baru);
        mysqli_query($koneksi, "UPDATE alat SET kondisi = '$kondisi_esc' WHERE id_alat = '$id_al_esc'");
    }

    // 3. Update status peminjaman menjadi 'selesai' agar muat di kolom status database
    mysqli_query($koneksi, "UPDATE peminjaman SET status = 'selesai' WHERE id_peminjaman = '$id_peminjaman'");

    echo "<script>alert('Pengembalian berhasil diajukan dan diproses!'); window.location='riwayat.php';</script>";
    exit();
}

// Ambil detail alat yang dipinjam
$q_detail = mysqli_query($koneksi, "SELECT dp.*, a.nama_alat, a.merk, a.kondisi as kondisi_sekarang, a.gambar 
                                    FROM detail_peminjaman dp 
                                    JOIN alat a ON dp.id_alat = a.id_alat 
                                    WHERE dp.id_peminjaman = '$id_peminjaman'");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Pengembalian Alat - Rental & Co.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-warning" href="../index.php"><i class="fas fa-tools me-2"></i> Rental & Co.</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="riwayat.php">Kembali ke Riwayat</a>
            </div>
        </div>
    </nav>

    <div class="container my-5" style="max-width: 800px;">
        <div class="mb-4">
            <h3 class="fw-bold text-dark mb-1"><i class="fas fa-undo text-primary me-2"></i> Form Pengembalian Alat</h3>
            <p class="text-muted small">Nota Transaksi: <b>#<?php echo str_pad($id_peminjaman, 5, '0', STR_PAD_LEFT); ?></b>. Silakan tentukan kondisi alat saat dikembalikan.</p>
        </div>

        <form action="" method="POST">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                <h5 class="fw-bold mb-3">Daftar Alat yang Dikembalikan</h5>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Alat</th>
                                <th class="text-center">Jumlah</th>
                                <th class="text-center" style="width: 200px;">Kondisi Alat Saat Kembali</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($d = mysqli_fetch_assoc($q_detail)) { ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($d['gambar']) && file_exists('../uploads/' . $d['gambar'])) { ?>
                                                <img src="../uploads/<?php echo $d['gambar']; ?>" class="rounded me-2" style="width: 45px; height: 45px; object-fit: cover;">
                                            <?php } ?>
                                            <div>
                                                <span class="fw-semibold text-dark d-block"><?php echo htmlspecialchars($d['nama_alat']); ?></span>
                                                <small class="text-muted"><?php echo htmlspecialchars($d['merk'] ?? '-'); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center fw-semibold"><?php echo $d['jumlah']; ?> Unit</td>
                                    <td class="text-center">
                                        <select name="kondisi_alat[<?php echo $d['id_alat']; ?>]" class="form-select form-select-sm" required>
                                            <option value="Baik" <?php echo ($d['kondisi_sekarang'] == 'Baik') ? 'selected' : ''; ?>>Baik</option>
                                            <option value="Rusak" <?php echo ($d['kondisi_sekarang'] == 'Rusak') ? 'selected' : ''; ?>>Rusak</option>
                                            <option value="Hilang" <?php echo ($d['kondisi_sekarang'] == 'Hilang') ? 'selected' : ''; ?>>Hilang</option>
                                        </select>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 d-flex justify-content-between align-items-center">
                    <a href="riwayat.php" class="btn btn-outline-secondary px-4 rounded-pill">Batal</a>
                    <button type="submit" name="proses_kembali" class="btn btn-success px-5 rounded-pill fw-bold shadow-sm" onclick="return confirm('Yakin ingin menyelesaikan pengembalian alat ini?')">
                        <i class="fas fa-check-circle me-1"></i> Simpan Pengembalian
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>