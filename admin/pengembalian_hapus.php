<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../koneksi.php';
/** @var mysqli $koneksi */

// 1. Proteksi Halaman Khusus Admin / Petugas
$role = strtolower($_SESSION['role'] ?? $_SESSION['akses'] ?? '');
if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login' || ($role !== 'admin' && $role !== '1')) {
    echo "<script>alert('Akses ditolak! Hanya Administrator yang dapat menghapus data pengembalian.'); window.location='pengembalian.php';</script>";
    exit();
}

if (isset($_GET['id'])) {
    $id_pengembalian = mysqli_real_escape_string($koneksi, $_GET['id']);

    // 2. Ambil id_peminjaman terlebih dahulu sebelum data pengembalian dihapus
    $q_get = mysqli_query($koneksi, "SELECT id_peminjaman FROM pengembalian WHERE id_pengembalian = '$id_pengembalian'");
    $data  = mysqli_fetch_assoc($q_get);

    if ($data) {
        $id_peminjaman = $data['id_peminjaman'];

        // 3. Hapus data dari tabel pengembalian
        $hapus = mysqli_query($koneksi, "DELETE FROM pengembalian WHERE id_pengembalian = '$id_pengembalian'");

        if ($hapus) {
            // 4. Kembalikan status di tabel peminjaman menjadi 'dipinjam' kembali
            mysqli_query($koneksi, "UPDATE peminjaman SET status = 'dipinjam' WHERE id_peminjaman = '$id_peminjaman'");

            echo "<script>alert('Data pengembalian berhasil dihapus!'); window.location='pengembalian.php';</script>";
        } else {
            echo "<script>alert('Gagal menghapus data pengembalian: " . mysqli_error($koneksi) . "'); window.location='pengembalian.php';</script>";
        }
    } else {
        echo "<script>alert('Data pengembalian tidak ditemukan!'); window.location='pengembalian.php';</script>";
    }
} else {
    header("Location: pengembalian.php");
}
exit();
?>