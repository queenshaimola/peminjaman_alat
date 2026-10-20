<?php
$koneksi = mysqli_connect("127.0.0.01", "root", "", "db_peminjaman_alat");

if (mysqli_connect_errno()) {
    echo "Koneksi database gagal: " . mysqli_connect_error();
    exit();
}

?>