<?php
require_once __DIR__ . '/../config/db.php';

$check = $conn->query("SHOW COLUMNS FROM products LIKE 'size'");
if ($check->num_rows == 0) {
    $sql = "ALTER TABLE products ADD COLUMN size VARCHAR(20) NULL AFTER name";
    if ($conn->query($sql) === TRUE) {
        echo "Kolom size berhasil ditambahkan.\n";
    } else {
        echo "Error: " . $conn->error . "\n";
    }
} else {
    echo "Kolom size sudah ada.\n";
}
