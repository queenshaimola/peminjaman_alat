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
    
    // Hapus data user berdasarkan id_user
    $query = "DELETE FROM users WHERE id_user = '$id'";
    if (mysqli_query($koneksi, $query)) {
        echo "<script>alert('User berhasil dihapus!'); window.location='user.php';</script>";
        exit();
    } else {
        echo "<script>alert('Gagal menghapus user!'); window.location='user.php';</script>";
    }
} else {
    header("Location: user.php");
    exit();
}
?>