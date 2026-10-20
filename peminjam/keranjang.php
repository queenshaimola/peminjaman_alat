<?php
session_start();
include '../koneksi.php';
/** @var mysqli $koneksi */

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'peminjam') {
    echo "<script>alert('Akses ditolak!'); window.location='../login.php';</script>";
    exit();
}

$id_user = $_SESSION['id_user'];

// Proses Ubah Kuantitas atau Hapus Item dari Keranjang
if (isset($_GET['aksi']) && isset($_GET['id'])) {
    $id_target = $_GET['id'];
    $aksi = $_GET['aksi'];

    if ($aksi === 'tambah') {
        $_SESSION['cart'][$id_target] += 1;
    } elseif ($aksi === 'kurang') {
        if (isset($_SESSION['cart'][$id_target]) && $_SESSION['cart'][$id_target] > 1) {
            $_SESSION['cart'][$id_target] -= 1;
        } else {
            unset($_SESSION['cart'][$id_target]);
        }
    } elseif ($aksi === 'hapus') {
        unset($_SESSION['cart'][$id_target]);
    }

    header("Location: keranjang.php");
    exit();
}

// Tanggal default atau dari input
$tgl_pinjam = $_POST['tgl_pinjam'] ?? date('Y-m-d');
$tgl_kembali_rencana = $_POST['tgl_kembali_rencana'] ?? date('Y-m-d', strtotime('+3 days'));

// Hitung durasi hari sewa
$d1 = new DateTime($tgl_pinjam);
$d2 = new DateTime($tgl_kembali_rencana);
$diff = $d1->diff($d2);
$durasi_hari = max(1, $diff->days);

// Proses Checkout & Simpan ke Database
if (isset($_POST['checkout'])) {
    $cart_items = $_SESSION['cart'] ?? [];
    $metode_pembayaran = $_POST['metode_pembayaran'] ?? 'Tunai';

    if (empty($tgl_pinjam) || empty($tgl_kembali_rencana)) {
        $error = "Tanggal peminjaman dan rencana kembali wajib diisi!";
    } else if (empty($cart_items)) {
        $error = "Keranjang Anda masih kosong!";
    } else {
        // 1. HITUNG TOTAL BIAYA KESELURUHAN (Memperhitungkan Kuantitas)
        $subtotal_keseluruhan = 0;
        foreach ($cart_items as $id_alat => $jml) {
            $id_esc = mysqli_real_escape_string($koneksi, $id_alat);
            $q_hrg = mysqli_query($koneksi, "SELECT harga, stok FROM alat WHERE id_alat = '$id_esc'");
            $data_al = mysqli_fetch_assoc($q_hrg);
            if ($data_al) {
                if ($jml > $data_al['stok']) {
                    $error = "Jumlah pinjam untuk salah satu alat melebihi stok yang tersedia!";
                    break;
                }
                $subtotal_keseluruhan += ($data_al['harga'] * $jml) * $durasi_hari;
            } else {
                unset($_SESSION['cart'][$id_alat]);
                $error = "Terdapat item tidak valid di keranjang. Silakan pilih ulang alat.";
                break;
            }
        }

        if (empty($error)) {
            // 2. SIMPAN KE TABEL PEMINJAMAN UTAMA
            $q_insert = "INSERT INTO peminjaman (id_user, tgl_pinjam, tgl_kembali_rencana, total_biaya, status_pembayaran, status) 
                         VALUES ('$id_user', '$tgl_pinjam', '$tgl_kembali_rencana', '$subtotal_keseluruhan', 'Belum Bayar', 'menunggu')";
            
            if (mysqli_query($koneksi, $q_insert)) {
                $id_peminjaman_baru = mysqli_insert_id($koneksi);

                // 3. Simpan detail alat ke detail_peminjaman beserta kuantitasnya
                foreach ($cart_items as $id_alat => $jml) {
                    $id_alat_esc = mysqli_real_escape_string($koneksi, $id_alat);
                    $jml_esc = (int)$jml;
                    mysqli_query($koneksi, "INSERT INTO detail_peminjaman (id_peminjaman, id_alat, jumlah) VALUES ('$id_peminjaman_baru', '$id_alat_esc', '$jml_esc')");
                }

                // Kosongkan keranjang
                unset($_SESSION['cart']);

                header("Location: nota.php?id=$id_peminjaman_baru&metode=$metode_pembayaran");
                exit();
            } else {
                $error = "Gagal memproses peminjaman: " . mysqli_error($koneksi);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang & Pembayaran - Rental & Co.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-warning" href="../index.php"><i class="fas fa-tools me-2"></i> Rental & Co.</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../index.php">Beranda</a>
                <a class="nav-link active" href="keranjang.php"><i class="fas fa-shopping-cart me-1"></i> Keranjang</a>
                <a class="nav-link" href="riwayat.php">Peminjaman Saya</a>
                <a class="nav-link text-danger fw-bold" href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="mb-4">
            <h3 class="fw-bold text-dark"><i class="fas fa-shopping-cart text-primary me-2"></i> Keranjang & Pembayaran</h3>
            <p class="text-muted small">Atur jumlah unit alat yang ingin dipinjam dan pilih metode pembayaran.</p>
        </div>

        <?php if (!empty($error)) { ?>
            <div class="alert alert-danger py-2 small"><?php echo $error; ?></div>
        <?php } ?>

        <form action="" method="POST">
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                        <h5 class="fw-bold mb-3">Daftar Alat Dipilih</h5>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Alat</th>
                                        <th>Harga / Hari</th>
                                        <th class="text-center" style="width: 140px;">Jumlah Unit</th>
                                        <th class="text-end">Subtotal</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $cart = $_SESSION['cart'] ?? [];
                                    $subtotal_tampil = 0;

                                    if (!empty($cart)) {
                                        foreach ($cart as $id_alat => $jumlah_unit) {
                                            $id_esc = mysqli_real_escape_string($koneksi, $id_alat);
                                            $q_item = mysqli_query($koneksi, "SELECT * FROM alat WHERE id_alat = '$id_esc'");
                                            $row = mysqli_fetch_assoc($q_item);

                                            if ($row) {
                                                $harga_alat = $row['harga'] ?? 0;
                                                $subtotal_item = ($harga_alat * $jumlah_unit) * $durasi_hari;
                                                $subtotal_tampil += $subtotal_item;
                                    ?>
                                        <tr>
                                            <td class="fw-semibold">
                                                <?php if (!empty($row['gambar']) && file_exists('../uploads/' . $row['gambar'])) { ?>
                                                    <img src="../uploads/<?php echo $row['gambar']; ?>" class="rounded me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                                <?php } ?>
                                                <?php echo htmlspecialchars($row['nama_alat']); ?>
                                                <small class="text-muted d-block"><?php echo htmlspecialchars($row['merk'] ?? ''); ?></small>
                                            </td>
                                            <td>Rp <?php echo number_format($harga_alat, 0, ',', '.'); ?></td>
                                            <td class="text-center">
                                                <div class="input-group input-group-sm justify-content-center">
                                                    <a href="keranjang.php?aksi=kurang&id=<?php echo $id_alat; ?>" class="btn btn-outline-secondary px-2"><i class="fas fa-minus"></i></a>
                                                    <input type="text" class="form-control text-center bg-white" value="<?php echo $jumlah_unit; ?>" readonly style="max-width: 45px;">
                                                    <a href="keranjang.php?aksi=tambah&id=<?php echo $id_alat; ?>" class="btn btn-outline-secondary px-2"><i class="fas fa-plus"></i></a>
                                                </div>
                                            </td>
                                            <td class="text-end fw-semibold">Rp <?php echo number_format($subtotal_item, 0, ',', '.'); ?></td>
                                            <td class="text-center">
                                                <a href="keranjang.php?aksi=hapus&id=<?php echo $id_alat; ?>" class="btn btn-outline-danger btn-sm rounded-circle" title="Hapus Item">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php 
                                            }
                                        }
                                    } else {
                                        echo '<tr><td colspan="5" class="text-center text-muted py-4">Keranjang Anda masih kosong. <a href="../index.php#daftar-alat">Cari alat sekarang</a></td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                        <h5 class="fw-bold mb-3"><i class="fas fa-file-invoice-dollar text-success me-2"></i> Rincian & Checkout</h5>
                        
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Tanggal Pinjam</label>
                            <input type="date" name="tgl_pinjam" class="form-control form-control-sm" value="<?php echo htmlspecialchars($tgl_pinjam); ?>" onchange="this.form.submit()" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Rencana Tanggal Kembali</label>
                            <input type="date" name="tgl_kembali_rencana" class="form-control form-control-sm" value="<?php echo htmlspecialchars($tgl_kembali_rencana); ?>" onchange="this.form.submit()" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Metode Pembayaran</label>
                            <select name="metode_pembayaran" class="form-select form-select-sm" required>
                                <option value="QRIS">QRIS (Scan & Tunjukkan ke Petugas)</option>
                                <option value="Tunai">Bayar Tunai di Kasir / Petugas</option>
                            </select>
                        </div>

                        <hr class="my-3">

                        <div class="d-flex justify-content-between small mb-2">
                            <span class="text-muted">Durasi Sewa:</span>
                            <span class="fw-bold text-dark"><?php echo $durasi_hari; ?> Hari</span>
                        </div>
                        <div class="d-flex justify-content-between small mb-3">
                            <span class="text-muted">Total Tagihan:</span>
                            <span class="fw-bold text-success fs-5">Rp <?php echo number_format($subtotal_tampil, 0, ',', '.'); ?></span>
                        </div>

                        <button type="submit" name="checkout" class="btn btn-success w-100 fw-bold py-2 rounded-pill shadow-sm" <?php echo empty($cart) ? 'disabled' : ''; ?>>
                            <i class="fas fa-check-circle me-1"></i> Checkout & Buat Nota
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>