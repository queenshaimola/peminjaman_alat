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
    
    $query = "DELETE FROM kategori WHERE id_kategori = '$id'";
    if (mysqli_query($koneksi, $query)) {
        echo "<script>alert('Kategori berhasil dihapus!'); window.location='kategori.php';</script>";
        exit();
    } else {
        echo "<script>alert('Gagal menghapus kategori! Pastikan tidak ada alat yang sedang menggunakan kategori ini.'); window.location='kategori.php';</script>";
    }
} else {
    header("Location: kategori.php");
    exit();
}
?>