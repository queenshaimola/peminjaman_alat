<?php
session_start();
include '../koneksi.php';
/** @var mysqli $koneksi */

// Proteksi halaman khusus admin
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Ambil data gambar terlebih dahulu sebelum datanya dihapus
    $result_img = mysqli_query($koneksi, "SELECT gambar FROM alat WHERE id_alat = '$id'");
    if ($result_img && mysqli_num_rows($result_img) > 0) {
        $row_img = mysqli_fetch_assoc($result_img);
        $gambar = $row_img['gambar'];
        
        // Hapus file fisik gambar jika ada di folder uploads
        if (!empty($gambar) && file_exists('../uploads/' . $gambar)) {
            unlink('../uploads/' . $gambar);
        }
    }
    
    // Query hapus data alat berdasarkan id_alat[cite: 2]
    $query = "DELETE FROM alat WHERE id_alat = '$id'";
    if (mysqli_query($koneksi, $query)) {
        echo "<script>alert('Alat berhasil dihapus!'); window.location='alat.php';</script>";
        exit();
    } else {
        echo "<script>alert('Gagal menghapus alat! Alat mungkin sedang digunakan dalam transaksi peminjaman.'); window.location='alat.php';</script>";
    }
} else {
    header("Location: alat.php");
    exit();
}
?>