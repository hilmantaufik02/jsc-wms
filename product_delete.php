<?php
require_once 'config/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

if (!isset($_GET['id'])) { header("Location: products.php"); exit; }

$id = (int)$_GET['id'];

// Cek apakah produk ada
$check = $conn->query("SELECT id, name FROM products WHERE id = $id");
if ($check->num_rows === 0) {
    header("Location: products.php?msg=notfound");
    exit;
}

// Hapus produk (transaksi terkait akan ikut terhapus karena ON DELETE CASCADE)
if ($conn->query("DELETE FROM products WHERE id = $id")) {
    header("Location: products.php?msg=deleted");
} else {
    header("Location: products.php?msg=error");
}
exit;
