<?php
session_start();
include '../koneksi.php';
/** @var mysqli $koneksi */

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Hapus data peminjam berdasarkan id_user
    $query = "DELETE FROM users WHERE id_user = '$id' AND role = 'peminjam'";
    if (mysqli_query($koneksi, $query)) {
        echo "<script>alert('Data peminjam berhasil dihapus!'); window.location='peminjam.php';</script>";
        exit();
    } else {
        echo "<script>alert('Gagal menghapus data peminjam!'); window.location='peminjam.php';</script>";
    }
} else {
    header("Location: peminjam.php");
    exit();
}
?>