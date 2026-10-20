<?php
session_start();
include '../koneksi.php';
/** @var mysqli $koneksi */

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$kategori_result = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY nama_kategori ASC");

$error = "";
if (isset($_POST['simpan'])) {
    $nama_alat   = mysqli_real_escape_string($koneksi, $_POST['nama_alat']);
    $merk        = mysqli_real_escape_string($koneksi, $_POST['merk']);
    $id_kategori = $_POST['id_kategori'];
    $harga       = $_POST['harga']; // Menangkap input harga sewa
    $stok        = $_POST['stok'];
    $kondisi     = $_POST['kondisi'];

    // PROSES UPLOAD GAMBAR
    $nama_file = $_FILES['gambar']['name'];
    $ukuran_file = $_FILES['gambar']['size'];
    $error_file = $_FILES['gambar']['error'];
    $tmp_file = $_FILES['gambar']['tmp_name'];

    if ($error_file === 4) {
        $nama_gambar_baru = "";
    } else {
        $ekstensi_valid = ['jpg', 'jpeg', 'png', 'webp'];
        $ekstensi_file = explode('.', $nama_file);
        $ekstensi_file = strtolower(end($ekstensi_file));

        if (!in_array($ekstensi_file, $ekstensi_valid)) {
            $error = "Format gambar harus JPG, JPEG, PNG, atau WEBP!";
        } else {
            $nama_gambar_baru = uniqid() . '.' . $ekstensi_file;
            move_uploaded_file($tmp_file, '../uploads/' . $nama_gambar_baru);
        }
    }

    if (empty($error)) {
        // Query disesuaikan dengan memasukkan kolom harga
        $query = "INSERT INTO alat (nama_alat, merk, id_kategori, harga, stok, kondisi, gambar) VALUES ('$nama_alat', '$merk', '$id_kategori', '$harga', '$stok', '$kondisi', '$nama_gambar_baru')";
        if (mysqli_query($koneksi, $query)) {
            echo "<script>alert('Alat berhasil ditambahkan!'); window.location='alat.php';</script>";
            exit();
        } else {
            $error = "Gagal menyimpan data ke database: " . mysqli_error($koneksi);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Alat - Rental & Co.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white my-4" style="width: 480px;">
        <h4 class="fw-bold mb-3 text-dark"><i class="fas fa-toolbox text-primary me-2"></i> Tambah Alat Baru</h4>
        
        <?php if (!empty($error)) { ?>
            <div class="alert alert-danger py-2 small"><?php echo $error; ?></div>
        <?php } ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label small fw-semibold">Nama Alat</label>
                <input type="text" class="form-control" name="nama_alat" required autocomplete="off" placeholder="Contoh: Bor Listrik">
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">Merk</label>
                <input type="text" class="form-control" name="merk" autocomplete="off" placeholder="Contoh: Makita, Bosch...">
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">Kategori</label>
                <select class="form-select" name="id_kategori" required>
                    <option value="" disabled selected>Pilih Kategori...</option>
                    <?php while($kat = mysqli_fetch_assoc($kategori_result)) { ?>
                        <option value="<?php echo $kat['id_kategori']; ?>"><?php echo htmlspecialchars($kat['nama_kategori']); ?></option>
                    <?php } ?>
                </select>
            </div>
            <!-- Input Harga Sewa / Hari -->
            <div class="mb-3">
                <label class="form-label small fw-semibold">Harga Sewa / Hari (Rp)</label>
                <input type="number" class="form-control" name="harga" min="0" step="1000" required placeholder="Contoh: 50000">
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">Jumlah Stok</label>
                <input type="number" class="form-control" name="stok" min="0" required placeholder="Masukkan jumlah stok...">
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">Kondisi</label>
                <select class="form-select" name="kondisi" required>
                    <option value="Baik" selected>Baik</option>
                    <option value="Rusak">Rusak</option>
                    <option value="Hilang">Hilang</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-semibold">Foto Alat</label>
                <input type="file" class="form-control" name="gambar" accept="image/*">
                <div class="form-text small">Format: JPG, JPEG, PNG, WEBP.</div>
            </div>
            <div class="d-flex justify-content-between">
                <a href="alat.php" class="btn btn-secondary btn-sm px-4 rounded-pill">Kembali</a>
                <button type="submit" name="simpan" class="btn btn-primary btn-sm px-4 rounded-pill fw-bold">Simpan Alat</button>
            </div>
        </form>
    </div>
</body>
</html>