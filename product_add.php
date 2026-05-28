<?php
require_once 'config/db.php';
$title = "Tambah Barang";
require_once 'includes/header.php';

$is_admin = in_array($_SESSION['role'], ['admin', 'super_admin']);
$error = '';
$success = '';

// Ambil list kategori untuk dropdown
$categories = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");

// Ambil list subkategori untuk di-passing ke JS
$subcategories = [];
$sub_query = $conn->query("SELECT id, category_id, name FROM subcategories ORDER BY name ASC");
if ($sub_query) {
    while($sub = $sub_query->fetch_assoc()){
        $subcategories[] = $sub;
    }
}

// Handle Form Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sku = trim($_POST['sku']);
    $name = trim($_POST['name']);
    $size = isset($_POST['size']) ? trim($_POST['size']) : '';
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $subcategory_id = !empty($_POST['subcategory_id']) ? (int)$_POST['subcategory_id'] : NULL;
    $stock = (int)$_POST['stock'];
    $min_stock = (int)($_POST['min_stock'] ?? 5);
    $unit = trim($_POST['unit']);
    $purchase_price = (float)($_POST['purchase_price'] ?? 0);
    $selling_price = (float)($_POST['selling_price'] ?? 0);
    if (!$is_admin) {
        $purchase_price = 0;
        $selling_price = 0;
    }
    $location = trim($_POST['location'] ?? '');
    $description = trim($_POST['description']);

    $image_path = NULL;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/products/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $new_filename = uniqid('prod_') . '.' . $file_ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $new_filename)) {
                $image_path = $upload_dir . $new_filename;
            }
        }
    }

    // Validasi basic
    if (empty($sku) || empty($name) || empty($size) || empty($unit) || $category_id == 0) {
        $error = "SKU, Nama Barang, Size, Kategori Utama, dan Satuan wajib diisi.";
    } else {
        // Cek apakah SKU sudah ada (harus unik)
        $check_sku = $conn->query("SELECT id FROM products WHERE sku = '$sku'");
        if ($check_sku->num_rows > 0) {
            $error = "SKU sudah digunakan oleh barang lain. Silakan gunakan SKU yang unik.";
        } else {
            // Insert ke database
            $stmt = $conn->prepare("INSERT INTO products (sku, name, size, category_id, subcategory_id, stock, min_stock, unit, purchase_price, selling_price, location, description, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssiiiisddsss", $sku, $name, $size, $category_id, $subcategory_id, $stock, $min_stock, $unit, $purchase_price, $selling_price, $location, $description, $image_path);
            if ($stmt->execute()) {
                $success = "Barang berhasil ditambahkan ke database!";
            } else {
                $error = "Terjadi kesalahan sistem: " . $conn->error;
            }
            $stmt->close();
        }
    }
}
?>

<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Tambah Barang Baru</h2>
        <p class="text-sm text-slate-500 mt-1">Masukkan detail informasi produk ke dalam sistem inventaris.</p>
    </div>
    <a href="products.php" class="bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-medium py-2.5 px-4 rounded-xl shadow-sm transition-colors flex items-center">
        <i class="ph ph-arrow-left mr-2 text-lg"></i> Kembali ke Daftar
    </a>
</div>

<!-- Alert Messages -->
<?php if ($error): ?>
    <div class="bg-rose-50 border-l-4 border-rose-500 p-4 rounded-xl mb-6 flex items-start shadow-sm">
        <i class="ph ph-warning-circle text-rose-500 text-xl mr-3 mt-0.5"></i>
        <div>
            <h3 class="text-rose-800 font-bold text-sm">Gagal Menyimpan</h3>
            <p class="text-rose-600 text-xs mt-1"><?= $error ?></p>
        </div>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-xl mb-6 flex items-center shadow-sm justify-between">
        <div class="flex items-start">
            <i class="ph ph-check-circle text-emerald-500 text-xl mr-3 mt-0.5"></i>
            <div>
                <h3 class="text-emerald-800 font-bold text-sm">Berhasil</h3>
                <p class="text-emerald-600 text-xs mt-1"><?= $success ?></p>
            </div>
        </div>
        <a href="products.php" class="bg-emerald-100 hover:bg-emerald-200 text-emerald-700 text-xs font-bold py-2 px-4 rounded-lg transition-colors flex items-center">
            Lihat Tabel <i class="ph ph-arrow-right ml-1"></i>
        </a>
    </div>
<?php endif; ?>

<!-- Form Area -->
<div class="bg-white rounded-[1.5rem] border border-slate-100 shadow-sm overflow-hidden">
    <div class="p-6 md:p-8">
        <form action="" method="POST" enctype="multipart/form-data" class="max-w-4xl">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8">
                
                <!-- Kolom Kiri -->
                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Kode SKU (Barcode) <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <i class="ph ph-barcode absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xl"></i>
                            <input type="text" name="sku" required placeholder="Contoh: JRS-MD-001" value="<?= isset($_POST['sku']) && !$success ? htmlspecialchars($_POST['sku']) : '' ?>" 
                                class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-mono uppercase">
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1.5 font-medium">Kode unik/barcode barang. Pastikan tidak sama dengan barang lain.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Nama Barang <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="name" required placeholder="Contoh: Jersey Real Madrid Home 23/24" value="<?= isset($_POST['name']) && !$success ? htmlspecialchars($_POST['name']) : '' ?>" 
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Size (Ukuran) <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <select name="size" required class="w-full pl-4 pr-10 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all appearance-none cursor-pointer">
                                <option value="" disabled <?= empty($_POST['size']) || $success ? 'selected' : '' ?>>-- Pilih Size Baju --</option>
                                <option value="All Size" <?= (isset($_POST['size']) && $_POST['size'] == 'All Size' && !$success) ? 'selected' : '' ?>>All Size / Free Size</option>
                                <optgroup label="Size Dewasa">
                                    <?php 
                                    $dewasa = ['M', 'L', 'XL', 'XXL'];
                                    foreach($dewasa as $s) {
                                        $sel = (isset($_POST['size']) && $_POST['size'] == $s && !$success) ? 'selected' : '';
                                        echo "<option value=\"$s\" $sel>$s</option>";
                                    }
                                    ?>
                                </optgroup>
                                <optgroup label="Size Anak">
                                    <?php 
                                    $anak = ['1', '2', '3', '4', '6', '8', '10'];
                                    foreach($anak as $s) {
                                        $sel = (isset($_POST['size']) && $_POST['size'] == $s && !$success) ? 'selected' : '';
                                        echo "<option value=\"$s\" $sel>$s</option>";
                                    }
                                    ?>
                                </optgroup>
                            </select>
                            <i class="ph ph-caret-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Kategori Utama <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <select name="category_id" id="category_id" required class="w-full pl-4 pr-10 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all appearance-none cursor-pointer">
                                <option value="" disabled <?= empty($_POST['category_id']) || $success ? 'selected' : '' ?>>-- Pilih Kategori Utama --</option>
                                <?php if($categories && $categories->num_rows > 0): ?>
                                    <?php while($cat = $categories->fetch_assoc()): ?>
                                        <option value="<?= $cat['id'] ?>" <?= (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id'] && !$success) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                            <i class="ph ph-caret-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                        </div>
                        <?php if($categories->num_rows == 0): ?>
                            <p class="text-[11px] text-rose-500 mt-1.5 font-bold"><i class="ph ph-warning mr-1"></i>Kategori kosong! Silakan <a href="categories.php" class="underline hover:text-rose-700">buat kategori</a> terlebih dahulu.</p>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Sub Kategori (Seri)
                        </label>
                        <div class="relative">
                            <select name="subcategory_id" id="subcategory_id" class="w-full pl-4 pr-10 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all appearance-none cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                                <option value="">-- Pilih Kategori Utama Dulu --</option>
                            </select>
                            <i class="ph ph-caret-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1.5 font-medium">Opsional. Bergantung pada Kategori Utama yang dipilih.</p>
                    </div>
                </div>

                <!-- Kolom Kanan -->
                <div class="space-y-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                Stok Awal <span class="text-rose-500">*</span>
                            </label>
                            <input type="number" name="stock" min="0" required value="<?= isset($_POST['stock']) && !$success ? htmlspecialchars($_POST['stock']) : '0' ?>" 
                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-center font-bold text-slate-800">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                Satuan <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <select name="unit" required class="w-full pl-4 pr-10 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all appearance-none cursor-pointer">
                                    <?php
                                    $units = ['Pcs', 'Pasang', 'Box', 'Lusinan', 'Karton', 'Pack'];
                                    $selected_unit = isset($_POST['unit']) && !$success ? $_POST['unit'] : 'Pcs';
                                    foreach($units as $u) {
                                        $sel = ($selected_unit == $u) ? 'selected' : '';
                                        echo "<option value=\"$u\" $sel>$u</option>";
                                    }
                                    ?>
                                </select>
                                <i class="ph ph-caret-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Stok Minimum</label>
                            <input type="number" name="min_stock" min="0" value="<?= isset($_POST['min_stock']) && !$success ? htmlspecialchars($_POST['min_stock']) : '5' ?>" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Lokasi Rak</label>
                            <input type="text" name="location" placeholder="Cth: Rak A1" value="<?= isset($_POST['location']) && !$success ? htmlspecialchars($_POST['location']) : '' ?>" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
                        </div>
                    </div>

                    <?php if($is_admin): ?>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Harga Beli (Rp)</label>
                            <input type="number" name="purchase_price" min="0" step="100" value="<?= isset($_POST['purchase_price']) && !$success ? htmlspecialchars($_POST['purchase_price']) : '0' ?>" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Harga Jual (Rp)</label>
                            <input type="number" name="selling_price" min="0" step="100" value="<?= isset($_POST['selling_price']) && !$success ? htmlspecialchars($_POST['selling_price']) : '0' ?>" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
                        </div>
                    </div>
                    <?php endif; ?>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Foto Produk (Opsional)</label>
                        <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Deskripsi Barang (Opsional)
                        </label>
                        <textarea name="description" rows="5" placeholder="Keterangan tambahan (warna, ukuran, bahan, dll)..." 
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all resize-none"><?= isset($_POST['description']) && !$success ? htmlspecialchars($_POST['description']) : '' ?></textarea>
                    </div>
                </div>
                
            </div>
            
            <!-- Aksi Bawah -->
            <div class="mt-10 pt-6 border-t border-slate-100 flex items-center justify-end gap-4">
                <button type="reset" class="px-6 py-2.5 text-sm font-semibold text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-xl transition-colors">
                    Reset Form
                </button>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2.5 px-8 rounded-xl shadow-md shadow-indigo-600/20 transition-all hover:-translate-y-0.5 flex items-center">
                    <i class="ph ph-floppy-disk mr-2 text-lg"></i> Simpan ke Database
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Data subkategori dari PHP ke array JS
const subcategories = <?= json_encode($subcategories) ?>;
const catSelect = document.getElementById('category_id');
const subSelect = document.getElementById('subcategory_id');

// Fungsi render opsi subkategori
function updateSubcategories(selectedCatId, selectedSubId = null) {
    subSelect.innerHTML = '<option value="">-- Tidak Ada / Pilih Seri --</option>';
    
    if (!selectedCatId) {
        subSelect.disabled = true;
        return;
    }

    const filtered = subcategories.filter(sub => sub.category_id == selectedCatId);
    
    if (filtered.length > 0) {
        subSelect.disabled = false;
        filtered.forEach(sub => {
            const option = document.createElement('option');
            option.value = sub.id;
            option.textContent = sub.name;
            if (selectedSubId && selectedSubId == sub.id) {
                option.selected = true;
            }
            subSelect.appendChild(option);
        });
    } else {
        subSelect.disabled = true;
        subSelect.innerHTML = '<option value="">-- Kategori Utama ini tidak memiliki Seri --</option>';
    }
}

// Event listener saat kategori berubah
catSelect.addEventListener('change', function() {
    updateSubcategories(this.value);
});

// Jalankan fungsi saat halaman diload (berguna jika validasi form gagal, jadi data yg terisi tidak hilang)
<?php if(isset($_POST['category_id']) && !$success): ?>
    updateSubcategories(<?= (int)$_POST['category_id'] ?>, <?= !empty($_POST['subcategory_id']) ? (int)$_POST['subcategory_id'] : 'null' ?>);
<?php endif; ?>

// Memastikan input SKU menjadi huruf besar semua secara otomatis
document.querySelector('input[name="sku"]').addEventListener('input', function(e) {
    this.value = this.value.toUpperCase();
});
</script>

<?php require_once 'includes/footer.php'; ?>
