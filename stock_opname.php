<?php
require_once 'config/db.php';
$title = "Stock Opname";
require_once 'includes/header.php';

// Fitur Stock Opname khusus untuk Admin / Super Admin
$is_admin = in_array($_SESSION['role'], ['admin', 'super_admin']);
if (!$is_admin) {
    echo '<div class="p-6"><div class="bg-rose-50 text-rose-600 p-4 rounded-xl font-medium">Akses Ditolak. Fitur Stock Opname hanya untuk Administrator.</div></div>';
    require_once 'includes/footer.php'; exit;
}

$success = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['opname'])) {
    $conn->begin_transaction();
    try {
        $user_id = $_SESSION['user_id'];
        $changes_made = 0;
        
        foreach ($_POST['actual_stock'] as $product_id => $actual_stock) {
            if ($actual_stock === '') continue; // Lewati jika tidak diisi
            
            $product_id = (int)$product_id;
            $actual_stock = (int)$actual_stock;
            
            // Ambil stok sistem saat ini
            $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ? FOR UPDATE");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $system_stock = (int)$row['stock'];
                $diff = $actual_stock - $system_stock;
                
                if ($diff !== 0) {
                    // Update stok produk
                    $upd = $conn->prepare("UPDATE products SET stock = ? WHERE id = ?");
                    $upd->bind_param("ii", $actual_stock, $product_id);
                    $upd->execute();
                    
                    // Insert log transaksi
                    $type = $diff > 0 ? 'in' : 'out';
                    $qty = abs($diff);
                    $notes = "Stock Opname (Sistem: $system_stock, Fisik: $actual_stock)";
                    $trx = $conn->prepare("INSERT INTO transactions (type, product_id, quantity, notes, user_id) VALUES (?, ?, ?, ?, ?)");
                    $trx->bind_param("siisi", $type, $product_id, $qty, $notes, $user_id);
                    $trx->execute();
                    
                    $changes_made++;
                }
            }
        }
        $conn->commit();
        if ($changes_made > 0) {
            $success = "Stock Opname berhasil! Terdapat penyesuaian pada $changes_made barang.";
        } else {
            $success = "Stock Opname selesai. Tidak ada perbedaan antara fisik dan sistem.";
        }
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Terjadi kesalahan sistem: " . $e->getMessage();
    }
}

$search = $_GET['search'] ?? '';
$where_clause = "";
if (!empty($search)) {
    $search_esc = $conn->real_escape_string($search);
    $where_clause = "WHERE p.name LIKE '%$search_esc%' OR p.sku LIKE '%$search_esc%' OR p.location LIKE '%$search_esc%'";
}

$query = "SELECT p.id, p.sku, p.name, p.size, p.stock, p.location, p.image FROM products p $where_clause ORDER BY p.location ASC, p.name ASC";
$result = $conn->query($query);
?>

<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold text-slate-800 tracking-tight flex items-center"><i class="ph ph-clipboard-text text-indigo-500 mr-2 text-3xl"></i> Stock Opname</h2>
        <p class="text-sm text-slate-500 mt-1">Audit dan sesuaikan jumlah stok fisik dengan stok pada sistem.</p>
    </div>
</div>

<?php if ($error): ?><div class="bg-rose-50 border-l-4 border-rose-500 p-4 rounded-xl mb-6 flex items-start shadow-sm"><i class="ph ph-warning-circle text-rose-500 text-xl mr-3 mt-0.5"></i><div><h3 class="text-rose-800 font-bold text-sm">Gagal</h3><p class="text-rose-600 text-xs mt-1"><?= $error ?></p></div></div><?php endif; ?>
<?php if ($success): ?><div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-xl mb-6 flex items-start shadow-sm"><i class="ph ph-check-circle text-emerald-500 text-xl mr-3 mt-0.5"></i><div><h3 class="text-emerald-800 font-bold text-sm">Berhasil</h3><p class="text-emerald-600 text-xs mt-1"><?= $success ?></p></div></div><?php endif; ?>

<!-- Filter Bar -->
<div class="bg-white p-4 rounded-[1.25rem] border border-slate-100 shadow-sm mb-6">
    <form action="" method="GET" class="flex flex-col sm:flex-row gap-4">
        <div class="relative flex-1">
            <i class="ph ph-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-lg"></i>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari barang atau filter rak..." class="w-full pl-11 pr-4 py-2.5 bg-slate-50 border border-transparent focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 rounded-xl text-sm transition-all">
        </div>
        <button type="submit" class="bg-indigo-50 text-indigo-600 px-6 py-2.5 rounded-xl font-bold hover:bg-indigo-100 transition-colors">Filter</button>
    </form>
</div>

<!-- Opname Form Area -->
<form action="" method="POST" onsubmit="return confirm('Proses penyesuaian stok tidak dapat dibatalkan. Pastikan data fisik sudah benar. Lanjutkan?');">
    <input type="hidden" name="opname" value="1">
    <div class="bg-white rounded-[1.25rem] border border-slate-100 shadow-sm overflow-hidden mb-6">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse whitespace-nowrap">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-100">
                        <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase">Barang</th>
                        <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase">Lokasi/Rak</th>
                        <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase text-center">Stok Sistem</th>
                        <th class="py-4 px-6 text-xs font-bold text-indigo-600 uppercase text-center bg-indigo-50/50 w-48">Stok Fisik Aktual</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if ($result && $result->num_rows > 0): while($row = $result->fetch_assoc()): ?>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-6">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-lg bg-slate-50 border border-slate-200 overflow-hidden mr-3 shrink-0">
                                    <?php if($row['image']): ?>
                                        <img src="<?= htmlspecialchars($row['image']) ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <i class="ph ph-t-shirt text-slate-400 text-lg flex items-center justify-center w-full h-full"></i>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-800 line-clamp-1"><?= htmlspecialchars($row['name']) ?></p>
                                    <p class="text-xs text-slate-500 mt-0.5">SKU: <span class="font-mono"><?= htmlspecialchars($row['sku']) ?></span> <?= $row['size'] ? '| Size: <span class="font-bold">'.$row['size'].'</span>' : '' ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="py-3 px-6 text-sm font-semibold text-slate-600">
                            <?= htmlspecialchars($row['location'] ?: '-') ?>
                        </td>
                        <td class="py-3 px-6 text-center text-sm font-extrabold text-slate-400">
                            <?= $row['stock'] ?>
                        </td>
                        <td class="py-3 px-6 bg-indigo-50/30">
                            <input type="number" name="actual_stock[<?= $row['id'] ?>]" min="0" placeholder="<?= $row['stock'] ?>" class="w-full px-3 py-2 bg-white border border-indigo-200 rounded-lg text-center text-sm font-bold text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all placeholder:text-slate-300">
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="4" class="py-8 text-center text-slate-500">Barang tidak ditemukan.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Action Footer -->
    <div class="bg-white p-5 rounded-[1.25rem] border border-slate-100 shadow-sm flex flex-col sm:flex-row justify-between items-center gap-4 sticky bottom-6 z-10">
        <p class="text-sm text-slate-500 font-medium"><i class="ph ph-info mr-1 text-indigo-500"></i> Biarkan kosong jika stok fisik sama dengan sistem.</p>
        <button type="submit" class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded-xl shadow-md shadow-indigo-600/20 transition-all flex items-center justify-center">
            <i class="ph ph-check-circle text-xl mr-2"></i> Proses Penyesuaian
        </button>
    </div>
</form>

<script>
    // Fitur bantuan: Highlight baris jika input aktual berbeda dari placeholder (stok sistem)
    document.querySelectorAll('input[name^="actual_stock"]').forEach(input => {
        input.addEventListener('input', function() {
            const tr = this.closest('tr');
            const systemStock = parseInt(this.getAttribute('placeholder'));
            const actualStock = parseInt(this.value);
            
            if (this.value !== '' && actualStock !== systemStock) {
                tr.classList.add('bg-amber-50');
                this.classList.add('bg-amber-100', 'border-amber-300', 'text-amber-800');
                this.classList.remove('bg-white', 'border-indigo-200', 'text-indigo-700');
            } else {
                tr.classList.remove('bg-amber-50');
                this.classList.remove('bg-amber-100', 'border-amber-300', 'text-amber-800');
                this.classList.add('bg-white', 'border-indigo-200', 'text-indigo-700');
            }
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>
