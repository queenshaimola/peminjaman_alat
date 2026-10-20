<?php
session_start(); 
include 'koneksi.php'; 

/** @var mysqli $koneksi */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($koneksi, trim($_POST['username']));
    $password = md5($_POST['password']); // Menggunakan hash MD5

    // Query menyesuaikan tabel 'users' dan kolom database Anda
    $query = "SELECT * FROM users WHERE username='$username' AND password='$password'";
    $login = mysqli_query($koneksi, $query);

    if ($login && mysqli_num_rows($login) > 0) {
        $data = mysqli_fetch_assoc($login);
        
        // Simpan data ke session
        $_SESSION['id_user']      = $data['id_user'];
        $_SESSION['username']     = $data['username'];
        $_SESSION['nama_lengkap'] = $data['nama_lengkap'];
        $_SESSION['role']         = strtolower($data['role']);;
        $_SESSION['status']       = "login";

        // Redirect otomatis berdasarkan role user di database
        $role = strtolower(trim($data['role']));
        if ($role === 'admin') {
            header("Location: admin/dashboard.php");
            exit();
        } elseif ($role === 'petugas') {
            header("Location: petugas/dashboard.php");
            exit();
        } else {
            header("Location: index.php");
            exit();
        }
    } else {
        // Jika gagal, kembalikan ke halaman login dengan parameter pesan gagal
        header("Location: login.php?pesan=gagal");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}
?>