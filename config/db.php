<?php
// Pengaturan Database
$host = "localhost";
$user = "root";       // Default user XAMPP/Laragon
$pass = "";           // Default password XAMPP/Laragon (kosong)
$db   = "jsc_wms";    // Nama database

// Membuat koneksi
$conn = new mysqli($host, $user, $pass, $db);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi Database Gagal: " . $conn->connect_error);
}

// Set charset ke UTF-8
$conn->set_charset("utf8");
?>
