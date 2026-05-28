<?php
require_once __DIR__ . '/../config/db.php';

$check = $conn->query("SHOW COLUMNS FROM products LIKE 'description'");
if ($check->num_rows == 0) {
    $sql = "ALTER TABLE products ADD COLUMN description TEXT NULL";
    if ($conn->query($sql) === TRUE) {
        echo "Kolom description berhasil ditambahkan.\n";
    } else {
        echo "Error: " . $conn->error . "\n";
    }
} else {
    echo "Kolom description sudah ada.\n";
}
