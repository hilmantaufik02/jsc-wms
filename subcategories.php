<?php
require_once 'config/db.php';
$title = "Sub Kategori (Seri)";
require_once 'includes/header.php';

$error = '';
$success = '';

// Role Validation
$can_edit_delete = in_array($_SESSION['role'], ['admin', 'super_admin']);

// Handle Get Data untuk Edit
$edit_subcategory = null;
if (isset($_GET['edit']) && $can_edit_delete) {
    $id = (int)$_GET['edit'];
    $edit_subcategory = $conn->query("SELECT * FROM subcategories WHERE id = $id")->fetch_assoc();
}

// Handle Proses Tambah / Update Sub Kategori
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!empty($_POST['action']) && $_POST['action'] == 'edit' && !$can_edit_delete) {
        $error = "Akses ditolak: Anda tidak memiliki izin untuk mengedit sub kategori.";
    } else {
        $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);

        if (empty($name) || $category_id == 0) {
            $error = "Kategori Utama dan Nama Sub Kategori wajib diisi.";
        } else {
            if ($_POST['action'] == 'add') {
                $stmt = $conn->prepare("INSERT INTO subcategories (category_id, name, description) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $category_id, $name, $description);
                if ($stmt->execute()) {
                    $success = "Sub Kategori berhasil ditambahkan.";
                } else {
                    $error = "Gagal menambahkan Sub Kategori.";
                }
                $stmt->close();
            } elseif ($_POST['action'] == 'edit' && isset($_POST['id'])) {
                $id = (int)$_POST['id'];
                $stmt = $conn->prepare("UPDATE subcategories SET category_id = ?, name = ?, description = ? WHERE id = ?");
                $stmt->bind_param("issi", $category_id, $name, $description, $id);
                if ($stmt->execute()) {
                    $success = "Sub Kategori berhasil diperbarui.";
                    $edit_subcategory = null; // reset form
                } else {
                    $error = "Gagal memperbarui Sub Kategori.";
                }
                $stmt->close();
            }
        }
    }
}

// Handle Proses Hapus Sub Kategori
if (isset($_GET['delete'])) {
    if (!$can_edit_delete) {
        $error = "Akses ditolak: Anda tidak memiliki izin untuk menghapus sub kategori.";
    } else {
        $id = (int)$_GET['delete'];
        
        // Validasi pemakaian oleh produk
        $check = $conn->query("SELECT id FROM products WHERE subcategory_id = $id LIMIT 1");
        if ($check->num_rows > 0) {
            $error = "Gagal menghapus: Sub Kategori sedang digunakan oleh barang di dalam sistem.";
        } else {
            $conn->query("DELETE FROM subcategories WHERE id = $id");
            $success = "Sub Kategori berhasil dihapus.";
        }
    }
}

// Ambil list Kategori Induk untuk dropdown form dan filter
$categories = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");

// Handle Search & Filter
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$filter_cat = isset($_GET['filter_cat']) ? (int)$_GET['filter_cat'] : 0;

$where_conditions = [];
if ($search) {
    $where_conditions[] = "(s.name LIKE '%$search%' OR s.description LIKE '%$search%')";
}
if ($filter_cat > 0) {
    $where_conditions[] = "s.category_id = $filter_cat";
}

$where_clause = "";
if (count($where_conditions) > 0) {
    $where_clause = " WHERE " . implode(" AND ", $where_conditions);
}

// Ambil Data Sub Kategori
$query = "SELECT s.*, c.name as parent_name, COUNT(p.id) as total_products 
          FROM subcategories s 
          LEFT JOIN categories c ON s.category_id = c.id 
          LEFT JOIN products p ON s.id = p.subcategory_id 
          $where_clause
          GROUP BY s.id 
          ORDER BY s.id DESC";
$result = $conn->query($query);
?>

<div class="mb-2">
    <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Kategori & Seri Barang</h2>
    <p class="text-sm text-slate-500 mt-1">Kelola hierarki Kategori Utama dan Sub Kategori (Seri) inventaris Anda.</p>
</div>

<!-- Navigation Tabs -->
<div class="flex gap-6 mb-6 border-b border-slate-200">
    <a href="categories.php" class="py-3 border-b-2 border-transparent text-slate-500 hover:text-slate-800 font-medium transition-colors">Kategori Utama</a>
    <a href="subcategories.php" class="py-3 border-b-2 border-indigo-500 text-indigo-600 font-bold transition-colors">Sub Kategori (Seri)</a>
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
    
    <!-- Kolom Kiri: Form Tambah / Edit -->
    <div class="w-full lg:w-1/3">
        <div class="bg-white rounded-[1.25rem] border border-slate-100 shadow-sm p-6 lg:sticky lg:top-28">
            <h3 class="text-lg font-bold text-slate-800 mb-6 flex items-center">
                <div class="w-8 h-8 rounded-lg <?= $edit_subcategory ? 'bg-amber-50 text-amber-600' : 'bg-indigo-50 text-indigo-600' ?> flex items-center justify-center mr-3 shadow-inner">
                    <i class="ph <?= $edit_subcategory ? 'ph-pencil-simple' : 'ph-list-plus' ?> font-bold text-lg"></i>
                </div>
                <?= $edit_subcategory ? 'Edit Sub Kategori' : 'Tambah Sub Kategori' ?>
            </h3>
            
            <form action="subcategories.php" method="POST">
                <input type="hidden" name="action" value="<?= $edit_subcategory ? 'edit' : 'add' ?>">
                <?php if($edit_subcategory): ?>
                    <input type="hidden" name="id" value="<?= $edit_subcategory['id'] ?>">
                <?php endif; ?>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Pilih Kategori Utama <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <select name="category_id" required class="w-full pl-4 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all appearance-none cursor-pointer">
                            <option value="" disabled <?= !$edit_subcategory ? 'selected' : '' ?>>-- Pilih Kategori Utama --</option>
                            <?php 
                            if($categories && $categories->num_rows > 0) {
                                while($cat = $categories->fetch_assoc()) {
                                    $selected = ($edit_subcategory && $edit_subcategory['category_id'] == $cat['id']) ? 'selected' : '';
                                    echo "<option value='".$cat['id']."' $selected>".htmlspecialchars($cat['name'])."</option>";
                                }
                            }
                            ?>
                        </select>
                        <i class="ph ph-caret-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Sub Kategori (Seri) <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" required placeholder="Contoh: Naruto, One Piece, EVO V2" value="<?= $edit_subcategory ? htmlspecialchars($edit_subcategory['name']) : '' ?>" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Deskripsi (Opsional)</label>
                    <textarea name="description" rows="3" placeholder="Keterangan singkat..." class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all resize-none"><?= $edit_subcategory ? htmlspecialchars($edit_subcategory['description']) : '' ?></textarea>
                </div>
                
                <div class="flex gap-2">
                    <?php if($edit_subcategory): ?>
                        <a href="subcategories.php" class="w-1/3 bg-slate-100 hover:bg-slate-200 text-slate-600 font-medium py-3 px-4 rounded-xl shadow-sm transition-all flex items-center justify-center">
                            Batal
                        </a>
                        <button type="submit" class="w-2/3 bg-amber-500 hover:bg-amber-600 text-white font-medium py-3 px-4 rounded-xl shadow-md shadow-amber-500/20 transition-all hover:-translate-y-0.5 flex items-center justify-center">
                            <i class="ph ph-check-circle mr-2 text-lg"></i> Update
                        </button>
                    <?php else: ?>
                        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-3 px-4 rounded-xl shadow-md shadow-indigo-600/20 transition-all hover:-translate-y-0.5 flex items-center justify-center">
                            <i class="ph ph-floppy-disk mr-2 text-lg"></i> Simpan Sub Kategori
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Kolom Kanan: Tabel Sub Kategori -->
    <div class="w-full lg:w-2/3">
        <div class="bg-white rounded-[1.25rem] border border-slate-100 shadow-sm overflow-hidden">
            <!-- Filter & Search Bar Area -->
            <div class="p-4 border-b border-slate-100 bg-slate-50/50">
                <form action="" method="GET" class="flex flex-col sm:flex-row gap-3 w-full">
                    <div class="relative flex-1">
                        <i class="ph ph-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" name="search" value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" placeholder="Cari nama seri..." class="w-full pl-11 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
                        <?php if(!empty($_GET['search'])): ?>
                            <a href="subcategories.php" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-rose-500 transition-colors tooltip" title="Reset Pencarian"><i class="ph ph-x-circle text-lg"></i></a>
                        <?php endif; ?>
                    </div>
                    <div class="relative">
                        <select name="filter_cat" onchange="this.form.submit()" class="w-full sm:w-auto px-4 py-2.5 pr-10 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all cursor-pointer appearance-none">
                            <option value="">Semua Kategori Utama</option>
                            <?php 
                            if($categories && $categories->num_rows > 0) {
                                $categories->data_seek(0); // reset pointer
                                while($cat = $categories->fetch_assoc()) {
                                    $sel = (isset($_GET['filter_cat']) && $_GET['filter_cat'] == $cat['id']) ? 'selected' : '';
                                    echo "<option value='".$cat['id']."' $sel>".htmlspecialchars($cat['name'])."</option>";
                                }
                            }
                            ?>
                        </select>
                        <i class="ph ph-caret-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-100">
                            <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider w-16 text-center">No</th>
                            <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider">Seri (Sub Kategori)</th>
                            <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider">Kategori Induk</th>
                            <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Produk</th>
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
                                        <p class="text-xs text-slate-500 mt-1 truncate max-w-[150px]"><?= htmlspecialchars($row['description']) ?></p>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="text-xs font-semibold text-indigo-700 bg-indigo-50 border border-indigo-100 px-3 py-1 rounded-md">
                                        <?= htmlspecialchars($row['parent_name']) ?>
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <span class="text-xs font-bold text-slate-600">
                                        <?= $row['total_products'] ?>
                                    </span>
                                </td>
                                <?php if($can_edit_delete): ?>
                                <td class="py-4 px-6">
                                    <div class="flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                        <a href="subcategories.php?edit=<?= $row['id'] ?>" class="p-2 text-amber-500 hover:text-white hover:bg-amber-500 rounded-lg transition-colors tooltip mr-1" title="Edit Sub Kategori">
                                            <i class="ph ph-pencil-simple text-lg"></i>
                                        </a>
                                        <button onclick="deleteSubCategory(<?= $row['id'] ?>)" class="p-2 text-rose-500 hover:text-white hover:bg-rose-500 rounded-lg transition-colors tooltip" title="Hapus Sub Kategori">
                                            <i class="ph ph-trash text-lg"></i>
                                        </button>
                                    </div>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="py-16 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                                            <i class="ph ph-tree-structure text-4xl text-slate-400"></i>
                                        </div>
                                        <h3 class="text-lg font-bold text-slate-800">Belum Ada Sub Kategori</h3>
                                        <p class="text-sm text-slate-500 mt-1 max-w-sm">Tambahkan sub kategori (seri) melalui form di samping.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($result && $result->num_rows > 0): ?>
            <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between bg-slate-50/50">
                <span class="text-sm text-slate-500 font-medium">Total <span class="font-bold text-slate-700"><?= $result->num_rows ?></span> sub kategori</span>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function deleteSubCategory(id) {
    if(confirm('Hapus sub kategori (seri) ini? Aksi ini tidak bisa dibatalkan jika sub kategori sedang dipakai oleh produk.')) {
        window.location.href = 'subcategories.php?delete=' + id;
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
