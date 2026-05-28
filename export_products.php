<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/db.php';

// Cek akses
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Set header file untuk unduhan CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="Data_Barang_Jersic_' . date('Y-m-d') . '.csv"');

// Buat pointer file ke output HTTP
$output = fopen('php://output', 'w');

// Tambahkan BOM untuk kompatibilitas Excel pada UTF-8
fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));

$is_admin = in_array($_SESSION['role'], ['admin', 'super_admin']);

// Tentukan baris header berdasarkan hak akses
if ($is_admin) {
    fputcsv($output, array('SKU', 'Nama Barang', 'Ukuran', 'Kategori', 'Stok Total', 'Batas Minimum', 'Satuan', 'Harga Beli (Rp)', 'Harga Jual (Rp)', 'Lokasi Rak', 'Keterangan / Deskripsi'));
} else {
    fputcsv($output, array('SKU', 'Nama Barang', 'Ukuran', 'Kategori', 'Stok Total', 'Batas Minimum', 'Satuan', 'Lokasi Rak', 'Keterangan / Deskripsi'));
}

// Ambil data produk dari database
$query = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.name ASC";
$result = $conn->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        if ($is_admin) {
            fputcsv($output, array(
                $row['sku'],
                $row['name'],
                $row['size'],
                $row['category_name'] ?: 'Tanpa Kategori',
                $row['stock'],
                $row['min_stock'],
                $row['unit'],
                $row['purchase_price'],
                $row['selling_price'],
                $row['location'],
                $row['description']
            ));
        } else {
            fputcsv($output, array(
                $row['sku'],
                $row['name'],
                $row['size'],
                $row['category_name'] ?: 'Tanpa Kategori',
                $row['stock'],
                $row['min_stock'],
                $row['unit'],
                $row['location'],
                $row['description']
            ));
        }
    }
}

fclose($output);
exit;
