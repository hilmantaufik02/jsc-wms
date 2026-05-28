<?php
require_once 'config/db.php';

if (!isset($_GET['id'])) {
    header("Location: products.php");
    exit;
}
$product_id = (int)$_GET['id'];

$title = "Kartu Riwayat Barang";
require_once 'includes/header.php';

// Get Product Info
$product = $conn->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.id = $product_id
")->fetch_assoc();

if (!$product) {
    echo "<script>alert('Barang tidak ditemukan.'); window.location.href='products.php';</script>";
    exit;
}

// Filter Variables for History
$month = $_GET['month'] ?? date('Y-m');
$type = $_GET['type'] ?? '';

$where_cond = "t.product_id = $product_id AND DATE_FORMAT(t.created_at, '%Y-%m') = '$month'";
if ($type === 'in') {
    $where_cond .= " AND t.type = 'in'";
} elseif ($type === 'out') {
    $where_cond .= " AND t.type = 'out'";
}

// Get Transactions
$history = $conn->query("
    SELECT t.*, u.name as user_name 
    FROM transactions t 
    LEFT JOIN users u ON t.user_id = u.id 
    WHERE $where_cond
    ORDER BY t.created_at DESC
");
?>

<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold text-slate-800 tracking-tight flex items-center">
            <i class="ph ph-clock-counter-clockwise text-indigo-500 mr-2 text-3xl"></i> Kartu Riwayat Stok
        </h2>
        <p class="text-sm text-slate-500 mt-1">Lacak seluruh pergerakan masuk dan keluar khusus untuk barang ini.</p>
    </div>
    <div class="flex gap-3">
        <a href="products.php" class="bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-medium py-2.5 px-4 rounded-xl shadow-sm transition-colors flex items-center">
            <i class="ph ph-arrow-left mr-2 text-lg"></i> Kembali
        </a>
    </div>
</div>

<!-- Product Info Card -->
<div class="bg-white rounded-[1.5rem] p-6 md:p-8 border border-slate-100 shadow-sm mb-8 flex flex-col md:flex-row items-start md:items-center gap-6">
    <div class="w-24 h-24 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-center flex-shrink-0 overflow-hidden shadow-inner">
        <?php if($product['image']): ?>
            <img src="<?= htmlspecialchars($product['image']) ?>" alt="Foto Produk" class="w-full h-full object-cover">
        <?php else: ?>
            <i class="ph ph-t-shirt text-4xl text-slate-400"></i>
        <?php endif; ?>
    </div>
    <div class="flex-1">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h3 class="text-xl font-bold text-slate-800"><?= htmlspecialchars($product['name']) ?></h3>
                <div class="flex flex-wrap items-center gap-3 mt-2">
                    <span class="px-2.5 py-1 bg-slate-100 border border-slate-200 text-slate-600 rounded-lg text-xs font-mono font-bold">SKU: <?= htmlspecialchars($product['sku']) ?></span>
                    <?php if($product['size']): ?>
                    <span class="px-2.5 py-1 bg-indigo-50 border border-indigo-100 text-indigo-700 rounded-lg text-xs font-bold">Size: <?= htmlspecialchars($product['size']) ?></span>
                    <?php endif; ?>
                    <span class="text-sm text-slate-500"><i class="ph ph-tag"></i> <?= htmlspecialchars($product['category_name'] ?: 'Tanpa Kategori') ?></span>
                </div>
            </div>
            <div class="text-left md:text-right bg-slate-50 p-4 rounded-xl border border-slate-100 min-w-[160px]">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Stok Saat Ini</p>
                <div class="flex items-center md:justify-end gap-2">
                    <span class="text-3xl font-black <?= $product['stock'] <= $product['min_stock'] ? 'text-rose-600' : 'text-slate-800' ?>"><?= $product['stock'] ?></span>
                    <span class="text-sm font-semibold text-slate-500"><?= htmlspecialchars($product['unit']) ?></span>
                </div>
                <?php if($product['location']): ?>
                <p class="text-xs text-slate-500 mt-1"><i class="ph ph-map-pin-line text-indigo-500"></i> Rak: <span class="font-bold text-slate-700"><?= htmlspecialchars($product['location']) ?></span></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- History Section -->
<div class="bg-white rounded-[1.5rem] border border-slate-100 shadow-sm overflow-hidden min-h-[400px]">
    <!-- Filters -->
    <div class="p-5 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row gap-4 justify-between items-center">
        <h4 class="font-bold text-slate-700 flex items-center"><i class="ph ph-list-bullets mr-2 text-xl"></i> Daftar Riwayat Transaksi</h4>
        <form action="" method="GET" class="flex gap-3 w-full sm:w-auto">
            <input type="hidden" name="id" value="<?= $product_id ?>">
            <input type="month" name="month" value="<?= $month ?>" onchange="this.form.submit()" class="px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
            <select name="type" onchange="this.form.submit()" class="px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500 cursor-pointer">
                <option value="">Semua Tipe</option>
                <option value="in" <?= $type == 'in' ? 'selected' : '' ?>>Barang Masuk</option>
                <option value="out" <?= $type == 'out' ? 'selected' : '' ?>>Barang Keluar</option>
            </select>
        </form>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse whitespace-nowrap">
            <thead>
                <tr class="bg-white border-b border-slate-100">
                    <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase">Tanggal & Waktu</th>
                    <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase text-center">Tipe</th>
                    <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase text-right">Jumlah</th>
                    <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase">Keterangan / Catatan</th>
                    <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase">Oleh (User)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if ($history && $history->num_rows > 0): ?>
                    <?php while($row = $history->fetch_assoc()): ?>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-4 px-6 text-sm text-slate-600">
                            <span class="font-semibold text-slate-800 block"><?= date('d M Y', strtotime($row['created_at'])) ?></span>
                            <span class="text-xs text-slate-400"><?= date('H:i:s', strtotime($row['created_at'])) ?></span>
                        </td>
                        <td class="py-4 px-6 text-center">
                            <?php if($row['type'] == 'in'): ?>
                                <span class="px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold border border-emerald-200"><i class="ph ph-arrow-down-left mr-1"></i> MASUK</span>
                            <?php else: ?>
                                <span class="px-3 py-1 bg-rose-100 text-rose-700 rounded-full text-xs font-bold border border-rose-200"><i class="ph ph-arrow-up-right mr-1"></i> KELUAR</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-4 px-6 text-right">
                            <span class="text-base font-black <?= $row['type'] == 'in' ? 'text-emerald-600' : 'text-rose-600' ?>">
                                <?= $row['type'] == 'in' ? '+' : '-' ?><?= $row['quantity'] ?>
                            </span>
                        </td>
                        <td class="py-4 px-6 text-sm text-slate-600">
                            <?= $row['notes'] ? htmlspecialchars($row['notes']) : '<span class="text-slate-400 italic">Tanpa catatan</span>' ?>
                        </td>
                        <td class="py-4 px-6 text-sm font-medium text-slate-700">
                            <div class="flex items-center">
                                <i class="ph ph-user-circle text-slate-400 text-xl mr-2"></i>
                                <?= htmlspecialchars($row['user_name'] ?: 'Sistem') ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="py-16 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <i class="ph ph-folder-open text-5xl text-slate-300 mb-3"></i>
                                <h3 class="text-base font-bold text-slate-700">Tidak ada riwayat.</h3>
                                <p class="text-sm text-slate-500 mt-1">Belum ada transaksi untuk bulan ini.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
