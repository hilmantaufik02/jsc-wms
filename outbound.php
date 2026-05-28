<?php
require_once 'config/db.php';
$title = "Barang Keluar";
require_once 'includes/header.php';
$error = ''; $success = '';

$products_res = $conn->query("SELECT id, sku, name, size, stock, unit FROM products ORDER BY name ASC");
$products_arr = [];
if ($products_res) while ($p = $products_res->fetch_assoc()) $products_arr[] = $p;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id   = (int)$_POST['product_id'];
    $quantity     = (int)$_POST['quantity'];
    $reference_no = trim($_POST['reference_no'] ?? '');
    $notes        = trim($_POST['notes'] ?? '');
    $user_id      = $_SESSION['user_id'];

    if ($product_id == 0 || $quantity <= 0) {
        $error = "Produk dan jumlah barang wajib diisi dengan benar.";
    } else {
        // Cek stok
        $stok_row = $conn->query("SELECT stock, name FROM products WHERE id = $product_id")->fetch_assoc();
        if ($stok_row['stock'] < $quantity) {
            $error = "Stok tidak mencukupi! Stok tersedia: <b>{$stok_row['stock']}</b>, diminta: <b>{$quantity}</b>.";
        } else {
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("INSERT INTO transactions (type, reference_no, product_id, quantity, notes, user_id) VALUES ('out', ?, ?, ?, ?, ?)");
                $stmt->bind_param("sissi", $reference_no, $product_id, $quantity, $notes, $user_id);
                $stmt->execute(); $stmt->close();
                $conn->query("UPDATE products SET stock = stock - $quantity WHERE id = $product_id");
                $conn->commit();
                $success = "Barang keluar berhasil dicatat. Stok produk telah dikurangi.";
            } catch (Exception $e) { $conn->rollback(); $error = "Terjadi kesalahan: " . $e->getMessage(); }
        }
    }
}

$transactions = $conn->query("SELECT t.*, p.name as pname, p.sku, p.size, p.unit, u.name as uname
    FROM transactions t
    LEFT JOIN products p ON t.product_id = p.id
    LEFT JOIN users u ON t.user_id = u.id
    WHERE t.type = 'out' ORDER BY t.created_at DESC LIMIT 100");
$stats = $conn->query("SELECT COUNT(*) as total_trx, COALESCE(SUM(quantity),0) as total_qty FROM transactions WHERE type='out' AND MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())")->fetch_assoc();
$low_stock = $conn->query("SELECT COUNT(*) as c FROM products WHERE stock <= min_stock")->fetch_assoc();
?>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Barang Keluar</h2>
    <p class="text-sm text-slate-500 mt-1">Catat pengiriman atau pengeluaran stok dari gudang.</p>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 bg-rose-100 text-rose-600 rounded-xl flex items-center justify-center text-2xl flex-shrink-0"><i class="ph ph-upload-simple"></i></div>
        <div><p class="text-xs font-semibold text-slate-500 uppercase">Transaksi Bulan Ini</p><p class="text-2xl font-extrabold text-slate-800"><?= $stats['total_trx'] ?></p></div>
    </div>
    <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 bg-orange-100 text-orange-600 rounded-xl flex items-center justify-center text-2xl flex-shrink-0"><i class="ph ph-package"></i></div>
        <div><p class="text-xs font-semibold text-slate-500 uppercase">Total Qty Keluar</p><p class="text-2xl font-extrabold text-slate-800"><?= number_format($stats['total_qty']) ?></p></div>
    </div>
    <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 bg-amber-100 text-amber-600 rounded-xl flex items-center justify-center text-2xl flex-shrink-0"><i class="ph ph-warning-circle"></i></div>
        <div><p class="text-xs font-semibold text-slate-500 uppercase">Stok Menipis</p><p class="text-2xl font-extrabold text-slate-800"><?= $low_stock['c'] ?></p></div>
    </div>
    <?php $prod_count = $conn->query("SELECT COUNT(*) as c FROM products")->fetch_assoc(); ?>
    <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center text-2xl flex-shrink-0"><i class="ph ph-archive"></i></div>
        <div><p class="text-xs font-semibold text-slate-500 uppercase">Total Produk</p><p class="text-2xl font-extrabold text-slate-800"><?= $prod_count['c'] ?></p></div>
    </div>
</div>

<?php if ($error): ?><div class="bg-rose-50 border-l-4 border-rose-500 p-4 rounded-xl mb-6 flex items-start"><i class="ph ph-warning-circle text-rose-500 text-xl mr-3 mt-0.5"></i><div><h3 class="text-rose-800 font-bold text-sm">Gagal</h3><p class="text-rose-600 text-xs mt-1"><?= $error ?></p></div></div><?php endif; ?>
<?php if ($success): ?><div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-xl mb-6 flex items-start"><i class="ph ph-check-circle text-emerald-500 text-xl mr-3 mt-0.5"></i><div><h3 class="text-emerald-800 font-bold text-sm">Berhasil</h3><p class="text-emerald-600 text-xs mt-1"><?= $success ?></p></div></div><?php endif; ?>

<div class="flex flex-col lg:flex-row gap-8">
    <div class="w-full lg:w-2/5">
        <div class="bg-white rounded-[1.25rem] border border-slate-100 shadow-sm p-6 lg:sticky lg:top-28">
            <h3 class="text-lg font-bold text-slate-800 mb-5 flex items-center">
                <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center mr-3"><i class="ph ph-upload-simple"></i></div>
                Catat Pengeluaran Baru
            </h3>
            <form action="outbound.php" method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Pilih Produk <span class="text-rose-500">*</span></label>
                    <select name="product_id" required id="productSelect" onchange="updateStockInfo(this)" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500 transition-all cursor-pointer">
                        <option value="" disabled selected>-- Pilih Produk --</option>
                        <?php foreach ($products_arr as $p): ?>
                        <option value="<?= $p['id'] ?>" data-stock="<?= $p['stock'] ?>" data-unit="<?= htmlspecialchars($p['unit']) ?>">
                            [<?= htmlspecialchars($p['sku']) ?>] <?= htmlspecialchars($p['name']) ?><?= $p['size'] ? ' ('.$p['size'].')' : '' ?> — Stok: <?= $p['stock'] ?> <?= $p['unit'] ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="stockInfo" class="hidden mt-2 px-3 py-2 bg-slate-50 rounded-lg border border-slate-200">
                        <p class="text-xs text-slate-500">Stok tersedia: <span id="stockValue" class="font-bold text-slate-800"></span> <span id="stockUnit"></span></p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Jumlah <span class="text-rose-500">*</span></label>
                        <input type="number" name="quantity" id="qtyInput" min="1" required placeholder="0" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500 transition-all text-center font-bold">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">No. Referensi</label>
                        <input type="text" name="reference_no" placeholder="SO-2024-001" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500 transition-all font-mono uppercase">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Catatan</label>
                    <textarea name="notes" rows="3" placeholder="Keterangan tambahan..." class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500 transition-all resize-none"></textarea>
                </div>
                <button type="submit" class="w-full bg-rose-600 hover:bg-rose-700 text-white font-semibold py-3 rounded-xl shadow-md shadow-rose-600/20 transition-all hover:-translate-y-0.5 flex items-center justify-center">
                    <i class="ph ph-check-circle mr-2 text-lg"></i> Simpan Pengeluaran
                </button>
            </form>
        </div>
    </div>

    <div class="w-full lg:w-3/5">
        <div class="bg-white rounded-[1.25rem] border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <h3 class="text-base font-bold text-slate-700"><i class="ph ph-clock-counter-clockwise mr-2 text-slate-400"></i>Histori Barang Keluar</h3>
                <span class="text-xs text-slate-400">100 transaksi terakhir</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse whitespace-nowrap">
                    <thead><tr class="bg-slate-50/80 border-b border-slate-100">
                        <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase">Produk</th>
                        <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase text-right">Qty</th>
                        <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase">Referensi</th>
                        <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase">Waktu</th>
                    </tr></thead>
                    <tbody class="divide-y divide-slate-100">
                    <?php if ($transactions && $transactions->num_rows > 0): while ($row = $transactions->fetch_assoc()): ?>
                        <tr class="hover:bg-rose-50/30 transition-colors">
                            <td class="py-3 px-4"><p class="text-sm font-bold text-slate-800"><?= htmlspecialchars($row['pname'] ?? '-') ?> <?= $row['size'] ? '<span class="text-xs font-normal text-slate-400">('.$row['size'].')</span>' : '' ?></p><p class="text-xs text-slate-400 font-mono"><?= htmlspecialchars($row['sku'] ?? '') ?></p></td>
                            <td class="py-3 px-4 text-right"><span class="text-base font-extrabold text-rose-600">-<?= number_format($row['quantity']) ?></span><span class="text-xs text-slate-400 ml-1"><?= htmlspecialchars($row['unit'] ?? '') ?></span></td>
                            <td class="py-3 px-4"><?php if ($row['reference_no']): ?><span class="font-mono text-xs bg-slate-100 text-slate-700 px-2 py-0.5 rounded font-semibold"><?= htmlspecialchars($row['reference_no']) ?></span><?php else: ?><span class="text-xs text-slate-400">—</span><?php endif; ?></td>
                            <td class="py-3 px-4"><p class="text-xs text-slate-600 font-medium"><?= date('d M Y', strtotime($row['created_at'])) ?></p><p class="text-xs text-slate-400"><?= date('H:i', strtotime($row['created_at'])) ?> · <?= htmlspecialchars($row['uname'] ?? '-') ?></p></td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="4" class="py-16 text-center"><div class="flex flex-col items-center"><div class="w-16 h-16 bg-rose-50 rounded-full flex items-center justify-center mb-3"><i class="ph ph-upload-simple text-3xl text-rose-300"></i></div><h3 class="font-bold text-slate-700">Belum Ada Transaksi Keluar</h3><p class="text-sm text-slate-400 mt-1">Gunakan form di samping untuk mencatat pengeluaran.</p></div></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function updateStockInfo(sel) {
    const opt = sel.options[sel.selectedIndex];
    const stock = opt.dataset.stock;
    const unit  = opt.dataset.unit;
    const info  = document.getElementById('stockInfo');
    const qty   = document.getElementById('qtyInput');
    if (stock !== undefined) {
        info.classList.remove('hidden');
        document.getElementById('stockValue').textContent = stock;
        document.getElementById('stockUnit').textContent  = unit;
        qty.setAttribute('max', stock);
        info.className = parseInt(stock) <= 5
            ? 'mt-2 px-3 py-2 bg-amber-50 rounded-lg border border-amber-200'
            : 'mt-2 px-3 py-2 bg-emerald-50 rounded-lg border border-emerald-200';
    }
}
</script>
<?php require_once 'includes/footer.php'; ?>
