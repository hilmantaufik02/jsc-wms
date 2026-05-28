<?php
require_once 'config/db.php';
$title = "Kategori Barang";
require_once 'includes/header.php';

$error = '';
$success = '';

// Role Validation
$can_edit_delete = in_array($_SESSION['role'], ['admin', 'super_admin']);

// Handle Get Data untuk Edit
$edit_category = null;
if (isset($_GET['edit']) && $can_edit_delete) {
    $id = (int)$_GET['edit'];
    $edit_category = $conn->query("SELECT * FROM categories WHERE id = $id")->fetch_assoc();
}

// Handle Proses Tambah / Update Kategori
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!empty($_POST['action']) && $_POST['action'] == 'edit' && !$can_edit_delete) {
        $error = "Akses ditolak: Anda tidak memiliki izin untuk mengedit kategori.";
    } else {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);

        if (empty($name)) {
            $error = "Nama kategori tidak boleh kosong.";
        } else {
            if ($_POST['action'] == 'add') {
                $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
                $stmt->bind_param("ss", $name, $description);
                if ($stmt->execute()) {
                    $success = "Kategori berhasil ditambahkan.";
                } else {
                    $error = "Gagal menambahkan kategori.";
                }
                $stmt->close();
            } elseif ($_POST['action'] == 'edit' && isset($_POST['id'])) {
                $id = (int)$_POST['id'];
                $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
                $stmt->bind_param("ssi", $name, $description, $id);
                if ($stmt->execute()) {
                    $success = "Kategori berhasil diperbarui.";
                    $edit_category = null; // reset form
                } else {
                    $error = "Gagal memperbarui kategori.";
                }
                $stmt->close();
            }
        }
    }
}

// Handle Proses Hapus Kategori
if (isset($_GET['delete'])) {
    if (!$can_edit_delete) {
        $error = "Akses ditolak: Anda tidak memiliki izin untuk menghapus kategori.";
    } else {
        $id = (int)$_GET['delete'];
        
        // Validasi: Cek apakah kategori sedang dipakai oleh barang/produk
        $check = $conn->query("SELECT id FROM products WHERE category_id = $id LIMIT 1");
        if ($check->num_rows > 0) {
            $error = "Gagal menghapus: Kategori sedang digunakan oleh barang di dalam sistem.";
        } else {
            $conn->query("DELETE FROM categories WHERE id = $id");
            $success = "Kategori berhasil dihapus.";
        }
    }
}

// Ambil Data Kategori dari Database (termasuk jumlah barang di dalamnya)
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$where_clause = "";
if ($search) {
    $where_clause = " WHERE c.name LIKE '%$search%' OR c.description LIKE '%$search%' ";
}

$query = "SELECT c.*, COUNT(p.id) as total_products 
          FROM categories c 
          LEFT JOIN products p ON c.id = p.category_id 
          $where_clause
          GROUP BY c.id 
          ORDER BY c.id DESC";
$result = $conn->query($query);
?>

<div class="mb-2">
    <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Kategori & Seri Barang</h2>
    <p class="text-sm text-slate-500 mt-1">Kelola hierarki Kategori Utama dan Sub Kategori (Seri) inventaris Anda.</p>
</div>

<!-- Navigation Tabs -->
<div class="flex gap-6 mb-6 border-b border-slate-200">
    <a href="categories.php" class="py-3 border-b-2 border-indigo-500 text-indigo-600 font-bold transition-colors">Kategori Utama</a>
    <a href="subcategories.php" class="py-3 border-b-2 border-transparent text-slate-500 hover:text-slate-800 font-medium transition-colors">Sub Kategori (Seri)</a>
</div>

<!-- Alert Messages -->
<?php if ($error): ?>
    <div class="bg-rose-50 border-l-4 border-rose-500 p-4 rounded-xl mb-6 flex items-start shadow-sm">
        <i class="ph ph-warning-circle text-rose-500 text-xl mr-3 mt-0.5"></i>
        <div>
            <h3 class="text-rose-800 font-bold text-sm">Terjadi Kesalahan</h3>
            <p class="text-rose-600 text-xs mt-1"><?= $error ?></p>
        </div>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-xl mb-6 flex items-start shadow-sm">
        <i class="ph ph-check-circle text-emerald-500 text-xl mr-3 mt-0.5"></i>
        <div>
            <h3 class="text-emerald-800 font-bold text-sm">Berhasil</h3>
            <p class="text-emerald-600 text-xs mt-1"><?= $success ?></p>
        </div>
    </div>
<?php endif; ?>

<div class="flex flex-col lg:flex-row gap-8">
    
    <!-- Kolom Kiri: Form Tambah / Edit Kategori -->
    <div class="w-full lg:w-1/3">
        <div class="bg-white rounded-[1.25rem] border border-slate-100 shadow-sm p-6 lg:sticky lg:top-28">
            <h3 class="text-lg font-bold text-slate-800 mb-6 flex items-center">
                <div class="w-8 h-8 rounded-lg <?= $edit_category ? 'bg-amber-50 text-amber-600' : 'bg-indigo-50 text-indigo-600' ?> flex items-center justify-center mr-3 shadow-inner">
                    <i class="ph <?= $edit_category ? 'ph-pencil-simple' : 'ph-plus' ?> font-bold"></i>
                </div>
                <?= $edit_category ? 'Edit Kategori' : 'Tambah Kategori' ?>
            </h3>
            
            <form action="categories.php" method="POST">
                <input type="hidden" name="action" value="<?= $edit_category ? 'edit' : 'add' ?>">
                <?php if($edit_category): ?>
                    <input type="hidden" name="id" value="<?= $edit_category['id'] ?>">
                <?php endif; ?>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Kategori <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" required placeholder="Contoh: Sepatu Olahraga" value="<?= $edit_category ? htmlspecialchars($edit_category['name']) : '' ?>" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Deskripsi (Opsional)</label>
                    <textarea name="description" rows="3" placeholder="Beri keterangan singkat tentang kategori ini..." class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all resize-none"><?= $edit_category ? htmlspecialchars($edit_category['description']) : '' ?></textarea>
                </div>
                
                <div class="flex gap-2">
                    <?php if($edit_category): ?>
                        <a href="categories.php" class="w-1/3 bg-slate-100 hover:bg-slate-200 text-slate-600 font-medium py-3 px-4 rounded-xl shadow-sm transition-all flex items-center justify-center">
                            Batal
                        </a>
                        <button type="submit" class="w-2/3 bg-amber-500 hover:bg-amber-600 text-white font-medium py-3 px-4 rounded-xl shadow-md shadow-amber-500/20 transition-all hover:-translate-y-0.5 flex items-center justify-center">
                            <i class="ph ph-check-circle mr-2 text-lg"></i> Update
                        </button>
                    <?php else: ?>
                        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-3 px-4 rounded-xl shadow-md shadow-indigo-600/20 transition-all hover:-translate-y-0.5 flex items-center justify-center">
                            <i class="ph ph-floppy-disk mr-2 text-lg"></i> Simpan Kategori
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Kolom Kanan: Tabel List Kategori -->
    <div class="w-full lg:w-2/3">
        <div class="bg-white rounded-[1.25rem] border border-slate-100 shadow-sm overflow-hidden">
            <!-- Search Bar Area -->
            <div class="p-4 border-b border-slate-100 bg-slate-50/50">
                <form action="" method="GET" class="w-full max-w-sm relative">
                    <i class="ph ph-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="search" value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" placeholder="Cari nama atau deskripsi kategori..." class="w-full pl-11 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
                    <?php if(!empty($_GET['search'])): ?>
                        <a href="categories.php" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-rose-500 transition-colors tooltip" title="Reset Pencarian"><i class="ph ph-x-circle text-lg"></i></a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-100">
                            <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider w-16 text-center">No</th>
                            <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider">Info Kategori</th>
                            <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Jumlah Barang</th>
                            <?php if($can_edit_delete): ?>
                            <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider text-center w-28">Aksi</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php $no = 1; while($row = $result->fetch_assoc()): ?>
                            <tr class="hover:bg-indigo-50/30 transition-colors group">
                                <td class="py-4 px-6 text-sm font-medium text-slate-400 text-center"><?= $no++ ?></td>
                                <td class="py-4 px-6">
                                    <p class="text-sm font-bold text-slate-800"><?= htmlspecialchars($row['name']) ?></p>
                                    <?php if($row['description']): ?>
                                        <p class="text-xs text-slate-500 mt-1 truncate max-w-[200px] sm:max-w-xs"><?= htmlspecialchars($row['description']) ?></p>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <span class="inline-flex items-center justify-center px-3 py-1 rounded-full text-xs font-bold <?= $row['total_products'] > 0 ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-500' ?>">
                                        <?= $row['total_products'] ?> Produk
                                    </span>
                                </td>
                                <?php if($can_edit_delete): ?>
                                <td class="py-4 px-6">
                                    <div class="flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                        <a href="categories.php?edit=<?= $row['id'] ?>" class="p-2 text-amber-500 hover:text-white hover:bg-amber-500 rounded-lg transition-colors tooltip mr-1" title="Edit Kategori">
                                            <i class="ph ph-pencil-simple text-lg"></i>
                                        </a>
                                        <button onclick="deleteCategory(<?= $row['id'] ?>)" class="p-2 text-rose-500 hover:text-white hover:bg-rose-500 rounded-lg transition-colors tooltip" title="Hapus Kategori">
                                            <i class="ph ph-trash text-lg"></i>
                                        </button>
                                    </div>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <!-- Empty State -->
                            <tr>
                                <td colspan="4" class="py-16 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                                            <i class="ph ph-tag text-4xl text-slate-400"></i>
                                        </div>
                                        <h3 class="text-lg font-bold text-slate-800">Belum Ada Kategori</h3>
                                        <p class="text-sm text-slate-500 mt-1 max-w-sm">Gunakan form di samping untuk membuat kategori produk pertama Anda.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Footer Tabel -->
            <?php if ($result && $result->num_rows > 0): ?>
            <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between bg-slate-50/50">
                <span class="text-sm text-slate-500 font-medium">Total <span class="font-bold text-slate-700"><?= $result->num_rows ?></span> kategori</span>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function deleteCategory(id) {
    if(confirm('Hapus kategori ini? Kategori tidak akan bisa dihapus jika masih ada produk yang terkait dengannya.')) {
        window.location.href = 'categories.php?delete=' + id;
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
