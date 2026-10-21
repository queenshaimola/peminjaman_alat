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

if (isset($_GET['id'])) {
    $id_peminjaman = mysqli_real_escape_string($koneksi, $_GET['id']);

    // 2. Hapus detail barang yang dipinjam dulu
    mysqli_query($koneksi, "DELETE FROM detail_peminjaman WHERE id_peminjaman = '$id_peminjaman'");

    // 3. Hapus data utama peminjaman
    $hapus = mysqli_query($koneksi, "DELETE FROM peminjaman WHERE id_peminjaman = '$id_peminjaman'");

    if ($hapus) {
        echo "<script>alert('Data peminjaman berhasil dihapus!'); window.location='peminjaman.php';</script>";
    } else {
        echo "<script>alert('Gagal menghapus data: " . mysqli_error($koneksi) . "'); window.location='peminjaman.php';</script>";
    }
} else {
    header("Location: peminjaman.php");
}
exit();
?>