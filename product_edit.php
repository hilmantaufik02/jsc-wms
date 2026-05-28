<?php
require_once 'config/db.php';
$title = "Edit Barang";
require_once 'includes/header.php';

$error = ''; $success = '';
$is_admin = in_array($_SESSION['role'], ['admin', 'super_admin']);

if (!isset($_GET['id'])) { header("Location: products.php"); exit; }
$id = (int)$_GET['id'];

$product = $conn->query("SELECT * FROM products WHERE id = $id")->fetch_assoc();
if (!$product) { header("Location: products.php"); exit; }

$categories = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
$sub_query  = $conn->query("SELECT id, category_id, name FROM subcategories ORDER BY name ASC");
$subcategories = [];
if ($sub_query) while ($sub = $sub_query->fetch_assoc()) $subcategories[] = $sub;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sku           = trim($_POST['sku']);
    $name          = trim($_POST['name']);
    $size          = trim($_POST['size'] ?? '');
    $category_id   = (int)$_POST['category_id'];
    $subcategory_id = !empty($_POST['subcategory_id']) ? (int)$_POST['subcategory_id'] : null;
    $stock         = (int)$_POST['stock'];
    $min_stock     = (int)($_POST['min_stock'] ?? 5);
    $unit          = trim($_POST['unit']);
    $purchase_price = (float)($_POST['purchase_price'] ?? 0);
    $selling_price  = (float)($_POST['selling_price'] ?? 0);
    if (!$is_admin) {
        $purchase_price = $product['purchase_price'];
        $selling_price = $product['selling_price'];
    }
    $location      = trim($_POST['location'] ?? '');
    $description   = trim($_POST['description'] ?? '');

    if (empty($sku) || empty($name) || $category_id == 0) {
        $error = "SKU, Nama Barang, dan Kategori wajib diisi.";
    } else {
        $check = $conn->query("SELECT id FROM products WHERE sku = '$sku' AND id != $id");
        if ($check->num_rows > 0) {
            $error = "SKU sudah digunakan oleh barang lain.";
        } else {
            $image_path = $product['image'];
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/products/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                    $new_filename = uniqid('prod_') . '.' . $file_ext;
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $new_filename)) {
                        if ($image_path && file_exists($image_path)) unlink($image_path);
                        $image_path = $upload_dir . $new_filename;
                    }
                }
            }

            $stmt = $conn->prepare("UPDATE products SET sku=?, name=?, size=?, category_id=?, subcategory_id=?, stock=?, min_stock=?, unit=?, purchase_price=?, selling_price=?, location=?, description=?, image=? WHERE id=?");
            $stmt->bind_param("sssiiiisddsssi", $sku, $name, $size, $category_id, $subcategory_id, $stock, $min_stock, $unit, $purchase_price, $selling_price, $location, $description, $image_path, $id);
            if ($stmt->execute()) {
                $success = "Data barang berhasil diperbarui.";
                $product = $conn->query("SELECT * FROM products WHERE id = $id")->fetch_assoc();
            } else {
                $error = "Terjadi kesalahan: " . $conn->error;
            }
            $stmt->close();
        }
    }
}
?>
<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Edit Barang</h2>
        <p class="text-sm text-slate-500 mt-1">Perbarui informasi produk: <span class="font-semibold text-slate-700"><?= htmlspecialchars($product['name']) ?></span></p>
    </div>
    <a href="products.php" class="bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-medium py-2.5 px-4 rounded-xl shadow-sm transition-colors flex items-center">
        <i class="ph ph-arrow-left mr-2 text-lg"></i> Kembali ke Daftar
    </a>
</div>

<?php if ($error): ?><div class="bg-rose-50 border-l-4 border-rose-500 p-4 rounded-xl mb-6 flex items-start shadow-sm"><i class="ph ph-warning-circle text-rose-500 text-xl mr-3 mt-0.5"></i><div><h3 class="text-rose-800 font-bold text-sm">Gagal Menyimpan</h3><p class="text-rose-600 text-xs mt-1"><?= $error ?></p></div></div><?php endif; ?>
<?php if ($success): ?><div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-xl mb-6 flex items-center shadow-sm justify-between"><div class="flex items-start"><i class="ph ph-check-circle text-emerald-500 text-xl mr-3 mt-0.5"></i><div><h3 class="text-emerald-800 font-bold text-sm">Berhasil Diperbarui</h3><p class="text-emerald-600 text-xs mt-1"><?= $success ?></p></div></div><a href="products.php" class="bg-emerald-100 hover:bg-emerald-200 text-emerald-700 text-xs font-bold py-2 px-4 rounded-lg transition-colors flex items-center">Lihat Tabel <i class="ph ph-arrow-right ml-1"></i></a></div><?php endif; ?>

<div class="bg-white rounded-[1.5rem] border border-slate-100 shadow-sm overflow-hidden">
    <div class="p-6 md:p-8">
        <form action="" method="POST" enctype="multipart/form-data" class="max-w-4xl">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8">
                <!-- Kolom Kiri -->
                <div class="space-y-5">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Kode SKU <span class="text-rose-500">*</span></label>
                        <div class="relative"><i class="ph ph-barcode absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xl"></i>
                        <input type="text" name="sku" required value="<?= htmlspecialchars($product['sku']) ?>" class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-mono uppercase"></div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Barang <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" required value="<?= htmlspecialchars($product['name']) ?>" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Size</label>
                        <div class="relative">
                            <select name="size" class="w-full pl-4 pr-10 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all appearance-none cursor-pointer">
                                <option value="">-- Pilih Size --</option>
                                <option value="All Size" <?= $product['size']=='All Size'?'selected':'' ?>>All Size / Free Size</option>
                                <?php foreach (['M','L','XL','XXL'] as $s): ?><option value="<?= $s ?>" <?= $product['size']==$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?>
                                <?php foreach (['1','2','3','4','6','8','10'] as $s): ?><option value="<?= $s ?>" <?= $product['size']==$s?'selected':'' ?>><?= $s ?> (Anak)</option><?php endforeach; ?>
                            </select>
                            <i class="ph ph-caret-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Kategori Utama <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <select name="category_id" id="category_id" required class="w-full pl-4 pr-10 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all appearance-none cursor-pointer">
                                <option value="" disabled>-- Pilih Kategori --</option>
                                <?php if ($categories) while ($cat = $categories->fetch_assoc()): ?>
                                <option value="<?= $cat['id'] ?>" <?= $product['category_id']==$cat['id']?'selected':'' ?>><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                            <i class="ph ph-caret-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Sub Kategori</label>
                        <div class="relative">
                            <select name="subcategory_id" id="subcategory_id" class="w-full pl-4 pr-10 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all appearance-none cursor-pointer disabled:opacity-50" disabled>
                                <option value="">-- Pilih Kategori Dulu --</option>
                            </select>
                            <i class="ph ph-caret-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                        </div>
                    </div>
                </div>
                <!-- Kolom Kanan -->
                <div class="space-y-5">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Stok</label>
                            <input type="number" name="stock" min="0" required value="<?= $product['stock'] ?>" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-center font-bold text-slate-800">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Stok Minimum</label>
                            <input type="number" name="min_stock" min="0" value="<?= $product['min_stock'] ?? 5 ?>" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-center font-bold text-amber-600">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Satuan</label>
                        <div class="relative">
                            <select name="unit" class="w-full pl-4 pr-10 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all appearance-none cursor-pointer">
                                <?php foreach (['Pcs','Pasang','Box','Lusinan','Karton','Pack'] as $u): ?>
                                <option value="<?= $u ?>" <?= $product['unit']==$u?'selected':'' ?>><?= $u ?></option>
                                <?php endforeach; ?>
                            </select>
                            <i class="ph ph-caret-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                        </div>
                    </div>
                    <?php if($is_admin): ?>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Harga Beli (Rp)</label>
                            <input type="number" name="purchase_price" min="0" step="100" value="<?= $product['purchase_price'] ?? 0 ?>" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Harga Jual (Rp)</label>
                            <input type="number" name="selling_price" min="0" step="100" value="<?= $product['selling_price'] ?? 0 ?>" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                        </div>
                    </div>
                    <?php endif; ?>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Lokasi Rak</label>
                        <div class="relative"><i class="ph ph-map-pin absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" name="location" placeholder="Cth: Rak-A1, Lemari-3" value="<?= htmlspecialchars($product['location'] ?? '') ?>" class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all"></div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Foto Produk</label>
                        <div class="flex items-center gap-4 mb-3">
                            <div class="w-16 h-16 rounded-xl bg-slate-50 border border-slate-200 overflow-hidden flex items-center justify-center flex-shrink-0">
                                <?php if($product['image']): ?>
                                <img src="<?= htmlspecialchars($product['image']) ?>" alt="Foto" class="w-full h-full object-cover">
                                <?php else: ?>
                                <i class="ph ph-image text-2xl text-slate-400"></i>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1">
                                <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all file:mr-4 file:py-1.5 file:px-3 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                <p class="text-[11px] text-slate-500 mt-1">Abaikan jika tidak ingin mengubah foto.</p>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Deskripsi</label>
                        <textarea name="description" rows="3" placeholder="Keterangan tambahan..." class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all resize-none"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
            <div class="mt-8 pt-6 border-t border-slate-100 flex items-center justify-between">
                <button type="button" onclick="if(confirm('Hapus barang ini?')) window.location.href='product_delete.php?id=<?= $id ?>'" class="px-5 py-2.5 text-sm font-semibold text-rose-500 hover:text-white hover:bg-rose-500 border border-rose-200 hover:border-rose-500 rounded-xl transition-all flex items-center">
                    <i class="ph ph-trash mr-2"></i> Hapus Barang Ini
                </button>
                <div class="flex gap-3">
                    <button type="reset" class="px-6 py-2.5 text-sm font-semibold text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-xl transition-colors">Reset</button>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2.5 px-8 rounded-xl shadow-md shadow-indigo-600/20 transition-all hover:-translate-y-0.5 flex items-center">
                        <i class="ph ph-floppy-disk mr-2 text-lg"></i> Update Data
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
const subcategories = <?= json_encode($subcategories) ?>;
const catSelect = document.getElementById('category_id');
const subSelect = document.getElementById('subcategory_id');
function updateSubcategories(selectedCatId, selectedSubId = null) {
    subSelect.innerHTML = '<option value="">-- Tidak Ada / Pilih Seri --</option>';
    if (!selectedCatId) { subSelect.disabled = true; return; }
    const filtered = subcategories.filter(s => s.category_id == selectedCatId);
    if (filtered.length > 0) {
        subSelect.disabled = false;
        filtered.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id; opt.textContent = s.name;
            if (selectedSubId && selectedSubId == s.id) opt.selected = true;
            subSelect.appendChild(opt);
        });
    } else { subSelect.disabled = true; subSelect.innerHTML = '<option value="">-- Tidak ada seri --</option>'; }
}
catSelect.addEventListener('change', function() { updateSubcategories(this.value); });
updateSubcategories(<?= (int)$product['category_id'] ?>, <?= $product['subcategory_id'] ? (int)$product['subcategory_id'] : 'null' ?>);
document.querySelector('input[name="sku"]').addEventListener('input', function() { this.value = this.value.toUpperCase(); });
</script>
<?php require_once 'includes/footer.php'; ?>
