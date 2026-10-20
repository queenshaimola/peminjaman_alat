<?php
session_start();
include '../koneksi.php';
/** @var mysqli $koneksi */

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$alat_result = mysqli_query($koneksi, "SELECT * FROM alat WHERE id_alat = '$id'");
if (mysqli_num_rows($alat_result) === 0) {
    echo "<script>alert('Data alat tidak ditemukan!'); window.location='alat.php';</script>";
    exit();
}
$data = mysqli_fetch_assoc($alat_result);

$kategori_result = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY nama_kategori ASC");

$error = "";
if (isset($_POST['update'])) {
    $nama_alat   = mysqli_real_escape_string($koneksi, $_POST['nama_alat']);
    $merk        = mysqli_real_escape_string($koneksi, $_POST['merk']);
    $id_kategori = $_POST['id_kategori'];
    $harga       = $_POST['harga'];
    $stok        = $_POST['stok'];
    $kondisi     = $_POST['kondisi'];
    $gambar_lama = $_POST['gambar_lama'];

    // PROSES UPLOAD GAMBAR BARU (JIKA ADA)
    $nama_file = $_FILES['gambar']['name'];
    $ukuran_file = $_FILES['gambar']['size'];
    $error_file = $_FILES['gambar']['error'];
    $tmp_file = $_FILES['gambar']['tmp_name'];

    if ($error_file === 4) {
        // Jika tidak upload gambar baru, gunakan gambar lama
        $nama_gambar_baru = $gambar_lama;
    } else {
        $ekstensi_valid = ['jpg', 'jpeg', 'png', 'webp'];
        $ekstensi_file = explode('.', $nama_file);
        $ekstensi_file = strtolower(end($ekstensi_file));

        if (!in_array($ekstensi_file, $ekstensi_valid)) {
            $error = "Format gambar harus JPG, JPEG, PNG, atau WEBP!";
        } else {
            $nama_gambar_baru = uniqid() . '.' . $ekstensi_file;
            move_uploaded_file($tmp_file, '../uploads/' . $nama_gambar_baru);

            // Hapus gambar lama jika ada
            if (!empty($gambar_lama) && file_exists('../uploads/' . $gambar_lama)) {
                unlink('../uploads/' . $gambar_lama);
            }
        }
    }

    if (empty($error)) {
        $query = "UPDATE alat SET 
                    nama_alat = '$nama_alat', 
                    merk = '$merk', 
                    id_kategori = '$id_kategori', 
                    harga = '$harga', 
                    stok = '$stok', 
                    kondisi = '$kondisi', 
                    gambar = '$nama_gambar_baru' 
                  WHERE id_alat = '$id'";

        if (mysqli_query($koneksi, $query)) {
            echo "<script>alert('Data alat berhasil diperbarui!'); window.location='alat.php';</script>";
            exit();
        } else {
            $error = "Gagal memperbarui data ke database: " . mysqli_error($koneksi);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Alat - Rental & Co.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white my-4" style="width: 480px;">
        <h4 class="fw-bold mb-3 text-dark"><i class="fas fa-edit text-warning me-2"></i> Edit Data Alat</h4>
        
        <?php if (!empty($error)) { ?>
            <div class="alert alert-danger py-2 small"><?php echo $error; ?></div>
        <?php } ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="gambar_lama" value="<?php echo htmlspecialchars($data['gambar']); ?>">
            
            <div class="mb-3">
                <label class="form-label small fw-semibold">Nama Alat</label>
                <input type="text" class="form-control" name="nama_alat" value="<?php echo htmlspecialchars($data['nama_alat']); ?>" required autocomplete="off">
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">Merk</label>
                <input type="text" class="form-control" name="merk" value="<?php echo htmlspecialchars($data['merk']); ?>" autocomplete="off">
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">Kategori</label>
                <select class="form-select" name="id_kategori" required>
                    <option value="" disabled>Pilih Kategori...</option>
                    <?php while($kat = mysqli_fetch_assoc($kategori_result)) { ?>
                        <option value="<?php echo $kat['id_kategori']; ?>" <?php if($data['id_kategori'] == $kat['id_kategori']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($kat['nama_kategori']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">Harga Sewa / Hari (Rp)</label>
                <input type="number" class="form-control" name="harga" value="<?php echo $data['harga']; ?>" min="0" step="1000" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">Jumlah Stok</label>
                <input type="number" class="form-control" name="stok" value="<?php echo $data['stok']; ?>" min="0" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">Kondisi</label>
                <select class="form-select" name="kondisi" required>
                    <option value="Baik" <?php if($data['kondisi'] == 'Baik') echo 'selected'; ?>>Baik</option>
                    <option value="Rusak" <?php if($data['kondisi'] == 'Rusak') echo 'selected'; ?>>Rusak</option>
                    <option value="Hilang" <?php if($data['kondisi'] == 'Hilang') echo 'selected'; ?>>Hilang</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-semibold">Foto Alat (Biarkan kosong jika tidak ingin mengubah)</label>
                <?php if (!empty($data['gambar'])) { ?>
                    <div class="mb-2">
                        <img src="../uploads/<?php echo htmlspecialchars($data['gambar']); ?>" alt="Foto Alat" class="rounded border" style="width: 80px; height: 80px; object-fit: cover;">
                    </div>
                <?php } ?>
                <input type="file" class="form-control" name="gambar" accept="image/*">
            </div>
            <div class="d-flex justify-content-between">
                <a href="alat.php" class="btn btn-secondary btn-sm px-4 rounded-pill">Kembali</a>
                <button type="submit" name="update" class="btn btn-warning btn-sm px-4 rounded-pill fw-bold text-white">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</body>
</html>