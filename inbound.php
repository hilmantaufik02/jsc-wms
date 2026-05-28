<?php
require_once 'config/db.php';
$title = "Barang Masuk";
require_once 'includes/header.php';
$error = ''; $success = '';

$products_res = $conn->query("SELECT id, sku, name, size, stock, unit FROM products ORDER BY name ASC");
$products_arr = [];
if ($products_res) while ($p = $products_res->fetch_assoc()) $products_arr[] = $p;
$suppliers_res = $conn->query("SELECT id, name FROM suppliers ORDER BY name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id   = (int)$_POST['product_id'];
    $quantity     = (int)$_POST['quantity'];
    $reference_no = trim($_POST['reference_no'] ?? '');
    $supplier_id  = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;
    $notes        = trim($_POST['notes'] ?? '');
    $user_id      = $_SESSION['user_id'];
    if ($product_id == 0 || $quantity <= 0) {
        $error = "Produk dan jumlah barang wajib diisi dengan benar.";
    } else {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO transactions (type, reference_no, product_id, quantity, supplier_id, notes, user_id) VALUES ('in', ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("siissi", $reference_no, $product_id, $quantity, $supplier_id, $notes, $user_id);
            $stmt->execute(); $stmt->close();
            $conn->query("UPDATE products SET stock = stock + $quantity WHERE id = $product_id");
            $conn->commit();
            $success = "Barang masuk berhasil dicatat. Stok produk telah diperbarui.";
        } catch (Exception $e) { $conn->rollback(); $error = "Terjadi kesalahan: " . $e->getMessage(); }
    }
}

$transactions = $conn->query("SELECT t.*, p.name as pname, p.sku, p.size, p.unit, s.name as sname, u.name as uname
    FROM transactions t
    LEFT JOIN products p ON t.product_id = p.id
    LEFT JOIN suppliers s ON t.supplier_id = s.id
    LEFT JOIN users u ON t.user_id = u.id
    WHERE t.type = 'in' ORDER BY t.created_at DESC LIMIT 100");
$stats = $conn->query("SELECT COUNT(*) as total_trx, COALESCE(SUM(quantity),0) as total_qty FROM transactions WHERE type='in' AND MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())")->fetch_assoc();
?>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Barang Masuk</h2>
    <p class="text-sm text-slate-500 mt-1">Catat penerimaan stok baru ke dalam gudang.</p>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center text-2xl flex-shrink-0"><i class="ph ph-download-simple"></i></div>
        <div><p class="text-xs font-semibold text-slate-500 uppercase">Transaksi Bulan Ini</p><p class="text-2xl font-extrabold text-slate-800"><?= $stats['total_trx'] ?></p></div>
    </div>
    <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center text-2xl flex-shrink-0"><i class="ph ph-package"></i></div>
        <div><p class="text-xs font-semibold text-slate-500 uppercase">Total Qty Masuk</p><p class="text-2xl font-extrabold text-slate-800"><?= number_format($stats['total_qty']) ?></p></div>
    </div>
    <?php $sup_count = $conn->query("SELECT COUNT(*) as c FROM suppliers")->fetch_assoc(); ?>
    <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center text-2xl flex-shrink-0"><i class="ph ph-truck"></i></div>
        <div><p class="text-xs font-semibold text-slate-500 uppercase">Total Supplier</p><p class="text-2xl font-extrabold text-slate-800"><?= $sup_count['c'] ?></p></div>
    </div>
    <?php $prod_count = $conn->query("SELECT COUNT(*) as c FROM products")->fetch_assoc(); ?>
    <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 bg-amber-100 text-amber-600 rounded-xl flex items-center justify-center text-2xl flex-shrink-0"><i class="ph ph-archive"></i></div>
        <div><p class="text-xs font-semibold text-slate-500 uppercase">Total Produk</p><p class="text-2xl font-extrabold text-slate-800"><?= $prod_count['c'] ?></p></div>
    </div>
</div>

<?php if ($error): ?><div class="bg-rose-50 border-l-4 border-rose-500 p-4 rounded-xl mb-6 flex items-start"><i class="ph ph-warning-circle text-rose-500 text-xl mr-3 mt-0.5"></i><div><h3 class="text-rose-800 font-bold text-sm">Gagal</h3><p class="text-rose-600 text-xs mt-1"><?= $error ?></p></div></div><?php endif; ?>
<?php if ($success): ?><div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-xl mb-6 flex items-start"><i class="ph ph-check-circle text-emerald-500 text-xl mr-3 mt-0.5"></i><div><h3 class="text-emerald-800 font-bold text-sm">Berhasil</h3><p class="text-emerald-600 text-xs mt-1"><?= $success ?></p></div></div><?php endif; ?>

<div class="flex flex-col lg:flex-row gap-8">
    <div class="w-full lg:w-2/5">
        <div class="bg-white rounded-[1.25rem] border border-slate-100 shadow-sm p-6 lg:sticky lg:top-28">
            <h3 class="text-lg font-bold text-slate-800 mb-5 flex items-center">
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center mr-3"><i class="ph ph-download-simple"></i></div>
                Catat Penerimaan Baru
            </h3>
            <form action="inbound.php" method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Pilih Produk <span class="text-rose-500">*</span></label>
                    <select name="product_id" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all cursor-pointer">
                        <option value="" disabled selected>-- Pilih Produk --</option>
                        <?php foreach ($products_arr as $p): ?>
                        <option value="<?= $p['id'] ?>">[<?= htmlspecialchars($p['sku']) ?>] <?= htmlspecialchars($p['name']) ?><?= $p['size'] ? ' ('.$p['size'].')' : '' ?> — Stok: <?= $p['stock'] ?> <?= $p['unit'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Jumlah <span class="text-rose-500">*</span></label>
                        <input type="number" name="quantity" min="1" required placeholder="0" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all text-center font-bold">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">No. Referensi</label>
                        <input type="text" name="reference_no" placeholder="PO-2024-001" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all font-mono uppercase">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Supplier <span class="text-slate-400 font-normal">(Opsional)</span></label>
                    <select name="supplier_id" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all cursor-pointer">
                        <option value="">-- Tanpa Supplier --</option>
                        <?php if ($suppliers_res && $suppliers_res->num_rows > 0): while ($s = $suppliers_res->fetch_assoc()): ?>
                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                        <?php endwhile; endif; ?>
                    </select>
                    <p class="text-[11px] text-slate-400 mt-1.5">Belum ada supplier? <a href="suppliers.php" class="text-indigo-500 underline">Tambah di sini</a></p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Catatan</label>
                    <textarea name="notes" rows="3" placeholder="Keterangan tambahan..." class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all resize-none"></textarea>
                </div>
                <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-3 rounded-xl shadow-md shadow-emerald-600/20 transition-all hover:-translate-y-0.5 flex items-center justify-center">
                    <i class="ph ph-check-circle mr-2 text-lg"></i> Simpan Penerimaan
                </button>
            </form>
        </div>
    </div>

    <div class="w-full lg:w-3/5">
        <div class="bg-white rounded-[1.25rem] border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <h3 class="text-base font-bold text-slate-700"><i class="ph ph-clock-counter-clockwise mr-2 text-slate-400"></i>Histori Barang Masuk</h3>
                <span class="text-xs text-slate-400">100 transaksi terakhir</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse whitespace-nowrap">
                    <thead><tr class="bg-slate-50/80 border-b border-slate-100">
                        <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase">Produk</th>
                        <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase text-right">Qty</th>
                        <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase">Ref / Supplier</th>
                        <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase">Waktu</th>
                    </tr></thead>
                    <tbody class="divide-y divide-slate-100">
                    <?php if ($transactions && $transactions->num_rows > 0): while ($row = $transactions->fetch_assoc()): ?>
                        <tr class="hover:bg-emerald-50/30 transition-colors">
                            <td class="py-3 px-4"><p class="text-sm font-bold text-slate-800"><?= htmlspecialchars($row['pname'] ?? '-') ?> <?= $row['size'] ? '<span class="text-xs font-normal text-slate-400">('.$row['size'].')</span>' : '' ?></p><p class="text-xs text-slate-400 font-mono"><?= htmlspecialchars($row['sku'] ?? '') ?></p></td>
                            <td class="py-3 px-4 text-right"><span class="text-base font-extrabold text-emerald-600">+<?= number_format($row['quantity']) ?></span><span class="text-xs text-slate-400 ml-1"><?= htmlspecialchars($row['unit'] ?? '') ?></span></td>
                            <td class="py-3 px-4"><?php if ($row['reference_no']): ?><span class="font-mono text-xs bg-slate-100 text-slate-700 px-2 py-0.5 rounded font-semibold"><?= htmlspecialchars($row['reference_no']) ?></span><?php endif; ?><p class="text-xs text-slate-500 mt-0.5"><?= htmlspecialchars($row['sname'] ?? 'Tanpa Supplier') ?></p></td>
                            <td class="py-3 px-4"><p class="text-xs text-slate-600 font-medium"><?= date('d M Y', strtotime($row['created_at'])) ?></p><p class="text-xs text-slate-400"><?= date('H:i', strtotime($row['created_at'])) ?> · <?= htmlspecialchars($row['uname'] ?? '-') ?></p></td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="4" class="py-16 text-center"><div class="flex flex-col items-center"><div class="w-16 h-16 bg-emerald-50 rounded-full flex items-center justify-center mb-3"><i class="ph ph-download-simple text-3xl text-emerald-300"></i></div><h3 class="font-bold text-slate-700">Belum Ada Transaksi Masuk</h3><p class="text-sm text-slate-400 mt-1">Gunakan form di samping untuk mencatat penerimaan.</p></div></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
