<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../koneksi.php';
/** @var mysqli $koneksi */

// 1. Proteksi Akses Login & Admin/Petugas
$role = strtolower($_SESSION['role'] ?? $_SESSION['akses'] ?? '');
if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login' || ($role !== 'admin' && $role !== '1' && $role !== 'petugas')) {
    echo "<script>alert('Akses ditolak!'); window.location='../dashboard.php';</script>";
    exit();
}

// Ambil ID user dari session untuk dicatat di log
$id_user_login = $_SESSION['id_user'] ?? $_SESSION['userID'] ?? 0;

if (isset($_GET['id'])) {
    $id_peminjaman = mysqli_real_escape_string($koneksi, $_GET['id']);

    // 2. Catat ke log_aktivitas menggunakan kolom 'timestamp'
    $aktivitas_text = "Menghapus data transaksi peminjaman alat dengan ID #" . $id_peminjaman;
    mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_user, aktivitas, timestamp) VALUES ('$id_user_login', '$aktivitas_text', NOW())");

    // 3. Hapus detail barang yang dipinjam dulu
    mysqli_query($koneksi, "DELETE FROM detail_peminjaman WHERE id_peminjaman = '$id_peminjaman'");

    // 4. Hapus data utama peminjaman
    $hapus = mysqli_query($koneksi, "DELETE FROM peminjaman WHERE id_peminjaman = '$id_peminjaman'");

    if ($hapus) {
        echo "<script>alert('Data peminjaman berhasil dihapus dan tercatat di log!'); window.location='peminjaman.php';</script>";
    } else {
        echo "<script>alert('Gagal menghapus data: " . mysqli_error($koneksi) . "'); window.location='peminjaman.php';</script>";
    }
} else {
    header("Location: peminjaman.php");
}
exit();
?>