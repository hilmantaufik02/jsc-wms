<?php
require_once 'config/db.php';
$title = "Data Barang";
require_once 'includes/header.php';

// Filter Variables
$search = $_GET['search'] ?? '';
$cat_id = $_GET['category_id'] ?? '';

$where = [];
if (!empty($search)) {
    $search_esc = $conn->real_escape_string($search);
    $where[] = "(p.name LIKE '%$search_esc%' OR p.sku LIKE '%$search_esc%')";
}
if (!empty($cat_id)) {
    $where[] = "p.category_id = " . (int)$cat_id;
}

$where_clause = count($where) > 0 ? "WHERE " . implode(' AND ', $where) : "";

// Ambil data produk dan join dengan tabel kategori
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          $where_clause
          ORDER BY p.id DESC";
$result = $conn->query($query);

$categories = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
$is_admin = in_array($_SESSION['role'], ['admin', 'super_admin']);
?>

<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Data Barang</h2>
        <p class="text-sm text-slate-500 mt-1">Kelola seluruh inventaris produk di gudang Anda.</p>
    </div>
    <div class="flex items-center gap-3">
        <button class="bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-medium py-2 px-4 rounded-xl shadow-sm transition-colors flex items-center">
            <i class="ph ph-export mr-2 text-lg"></i> Export
        </button>
        <a href="product_add.php" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-xl shadow-sm shadow-indigo-600/20 transition-colors flex items-center">
            <i class="ph ph-plus mr-2 text-lg"></i> Tambah Barang
        </a>
    </div>
</div>

<!-- Filter & Search Bar -->
<form action="" method="GET" class="bg-white p-4 rounded-[1.25rem] border border-slate-100 shadow-sm mb-6 flex flex-col sm:flex-row gap-4">
    <div class="relative flex-1">
        <i class="ph ph-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-lg"></i>
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama barang atau SKU..." class="w-full pl-11 pr-4 py-2.5 bg-slate-50 hover:bg-slate-100 border border-transparent hover:border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
    </div>
    <div class="flex gap-3 sm:gap-4">
        <select name="category_id" onchange="this.form.submit()" class="px-4 py-2.5 bg-slate-50 border border-transparent hover:border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all cursor-pointer min-w-[140px] sm:min-w-[160px] text-slate-600">
            <option value="">Semua Kategori</option>
            <?php if($categories): while($cat = $categories->fetch_assoc()): ?>
                <option value="<?= $cat['id'] ?>" <?= $cat_id == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
            <?php endwhile; endif; ?>
        </select>
        <button type="submit" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-600 p-2.5 rounded-xl transition-colors border border-transparent flex-shrink-0 tooltip" title="Terapkan Filter">
            <i class="ph ph-funnel text-lg"></i>
        </button>
    </div>
</form>

<!-- Table Area -->
<div class="bg-white rounded-[1.25rem] border border-slate-100 shadow-sm overflow-hidden relative min-h-[400px]">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse whitespace-nowrap">
            <thead>
                <tr class="bg-slate-50/80 border-b border-slate-100">
                    <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider w-16 text-center">No</th>
                    <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider">Info Barang & Size</th>
                    <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider">Kategori</th>
                    <?php if($is_admin): ?>
                    <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Harga Jual</th>
                    <?php endif; ?>
                    <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Total Stok</th>
                    <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider text-center w-32">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php $no = 1; while($row = $result->fetch_assoc()): ?>
                    <tr class="hover:bg-indigo-50/30 transition-colors group">
                        <td class="py-4 px-6 text-sm font-medium text-slate-400 text-center"><?= $no++ ?></td>
                        <td class="py-4 px-6">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-400 mr-3 flex-shrink-0 overflow-hidden">
                                    <?php if($row['image']): ?>
                                        <img src="<?= htmlspecialchars($row['image']) ?>" alt="Img" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <i class="ph ph-t-shirt text-xl"></i>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-bold text-slate-800"><?= htmlspecialchars($row['name']) ?></p>
                                        <?php if(!empty($row['size'])): ?>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200"><?= htmlspecialchars($row['size']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-1">SKU: <span class="font-mono text-slate-400 font-semibold"><?= htmlspecialchars($row['sku']) ?></span>
                                    <?php if(!empty($row['location'])): ?>
                                        <span class="mx-1 text-slate-300">•</span> Rak: <span class="font-semibold text-slate-600"><?= htmlspecialchars($row['location']) ?></span>
                                    <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="py-4 px-6">
                            <?php if($row['category_name']): ?>
                                <span class="text-xs font-semibold text-slate-600 bg-slate-100 border border-slate-200 px-3 py-1 rounded-full">
                                    <?= htmlspecialchars($row['category_name']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-xs font-medium text-slate-400 italic">Tanpa Kategori</span>
                            <?php endif; ?>
                        </td>
                        <?php if($is_admin): ?>
                        <td class="py-4 px-6 text-right">
                            <span class="text-sm font-bold text-slate-800">Rp <?= number_format($row['selling_price'], 0, ',', '.') ?></span>
                        </td>
                        <?php endif; ?>
                        <td class="py-4 px-6 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <?php if($row['stock'] <= $row['min_stock']): ?>
                                    <div class="flex items-center justify-center w-5 h-5 rounded-full bg-rose-100">
                                        <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                                    </div>
                                    <span class="text-sm font-extrabold text-rose-600"><?= $row['stock'] ?></span>
                                <?php else: ?>
                                    <span class="w-2 h-2 rounded-full bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.5)] mr-1"></span>
                                    <span class="text-sm font-extrabold text-slate-700"><?= $row['stock'] ?></span>
                                <?php endif; ?>
                                <span class="text-xs font-medium text-slate-500 ml-1"><?= htmlspecialchars($row['unit']) ?></span>
                            </div>
                        </td>
                        <td class="py-4 px-6">
                            <!-- Tombol aksi muncul saat hover row -->
                            <div class="flex items-center justify-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <a href="product_barcode.php?id=<?= $row['id'] ?>" target="_blank" class="p-2 text-emerald-500 hover:text-white hover:bg-emerald-500 rounded-lg transition-colors tooltip" title="Cetak Barcode">
                                    <i class="ph ph-barcode text-lg"></i>
                                </a>
                                <a href="product_edit.php?id=<?= $row['id'] ?>" class="p-2 text-indigo-500 hover:text-white hover:bg-indigo-500 rounded-lg transition-colors tooltip" title="Edit Data">
                                    <i class="ph ph-pencil-simple text-lg"></i>
                                </a>
                                <button onclick="deleteProduct(<?= $row['id'] ?>)" class="p-2 text-rose-500 hover:text-white hover:bg-rose-500 rounded-lg transition-colors tooltip" title="Hapus Barang">
                                    <i class="ph ph-trash text-lg"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <!-- State Data Kosong (Empty State) -->
                    <tr>
                        <td colspan="5" class="py-20 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-24 h-24 bg-indigo-50 rounded-full flex items-center justify-center mb-5 relative">
                                    <i class="ph ph-package text-5xl text-indigo-300"></i>
                                    <div class="absolute -bottom-1 -right-1 w-8 h-8 bg-white rounded-full flex items-center justify-center shadow-sm">
                                        <i class="ph ph-x text-rose-400 font-bold"></i>
                                    </div>
                                </div>
                                <h3 class="text-lg font-bold text-slate-800">Belum Ada Data Barang</h3>
                                <p class="text-sm text-slate-500 mt-1 mb-6 max-w-sm mx-auto">Gudang Anda masih kosong. Mulai kelola inventaris dengan menambahkan barang pertama Anda ke dalam sistem.</p>
                                <a href="product_add.php" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2.5 px-6 rounded-xl shadow-md shadow-indigo-600/20 transition-all hover:-translate-y-0.5 flex items-center">
                                    <i class="ph ph-plus mr-2 text-lg"></i> Tambah Barang Sekarang
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Footer Tabel: Pagination -->
    <?php if ($result && $result->num_rows > 0): ?>
    <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between bg-slate-50/50">
        <span class="text-sm text-slate-500 font-medium">Total <span class="font-bold text-slate-700"><?= $result->num_rows ?></span> barang</span>
        
        <div class="flex items-center gap-1.5">
            <button class="w-9 h-9 flex items-center justify-center rounded-xl border border-slate-200 text-slate-400 bg-white hover:text-indigo-600 transition-colors disabled:opacity-50 cursor-not-allowed">
                <i class="ph ph-caret-left font-bold"></i>
            </button>
            <button class="w-9 h-9 flex items-center justify-center rounded-xl bg-indigo-600 text-white font-bold shadow-sm shadow-indigo-600/30">
                1
            </button>
            <!-- <button class="w-9 h-9 flex items-center justify-center rounded-xl border border-slate-200 text-slate-600 bg-white hover:text-indigo-600 hover:border-indigo-200 transition-colors font-medium">
                2
            </button> -->
            <button class="w-9 h-9 flex items-center justify-center rounded-xl border border-slate-200 text-slate-400 bg-white hover:text-indigo-600 transition-colors disabled:opacity-50 cursor-not-allowed">
                <i class="ph ph-caret-right font-bold"></i>
            </button>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function deleteProduct(id) {
    if(confirm('Apakah Anda yakin ingin menghapus barang ini? Data histori transaksi terkait mungkin akan ikut terpengaruh.')) {
        window.location.href = 'product_delete.php?id=' + id;
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
