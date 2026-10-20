<?php
session_start();

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'peminjam') {
    header("Location: ../login.php");
    exit();
}

$id_alat = $_GET['id'] ?? '';

if (!empty($id_alat)) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Jika alat sudah ada di keranjang, tambahkan jumlahnya (+1). Jika belum, set ke 1.
    if (isset($_SESSION['cart'][$id_alat])) {
        $_SESSION['cart'][$id_alat] += 1;
    } else {
        $_SESSION['cart'][$id_alat] = 1;
    }
}

header("Location: keranjang.php");
exit();
?>