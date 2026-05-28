<?php
require_once __DIR__ . '/../config/db.php';

// 1. Buat tabel subcategories
$sql1 = "CREATE TABLE IF NOT EXISTS subcategories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
)";

if ($conn->query($sql1) === TRUE) {
    echo "Tabel subcategories berhasil dibuat/sudah ada.\n";
} else {
    echo "Error membuat tabel subcategories: " . $conn->error . "\n";
}

// 2. Tambahkan kolom subcategory_id di tabel products
$check = $conn->query("SHOW COLUMNS FROM products LIKE 'subcategory_id'");
if ($check->num_rows == 0) {
    $sql2 = "ALTER TABLE products ADD COLUMN subcategory_id INT NULL AFTER category_id";
    if ($conn->query($sql2) === TRUE) {
        echo "Kolom subcategory_id berhasil ditambahkan.\n";
        
        // Tambahkan Foreign Key constraint
        $sql3 = "ALTER TABLE products ADD FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE SET NULL";
        $conn->query($sql3);
        echo "Foreign Key subcategory_id berhasil diset.\n";
    } else {
        echo "Error menambah kolom subcategory_id: " . $conn->error . "\n";
    }
} else {
    echo "Kolom subcategory_id sudah ada di tabel products.\n";
}

$conn->close();
?>
