<?php
require_once 'config/db.php';
$title = "Laporan Transaksi";
require_once 'includes/header.php';

$can_manage = in_array($_SESSION['role'], ['admin', 'super_admin']);
if (!$can_manage) {
    echo '<div class="p-6"><div class="bg-rose-50 text-rose-600 p-4 rounded-xl">Akses Ditolak.</div></div>';
    require_once 'includes/footer.php'; exit;
}

$type_filter = $_GET['type'] ?? '';
$start_date = $_GET['start'] ?? date('Y-m-01');
$end_date = $_GET['end'] ?? date('Y-m-d');

$where = "DATE(t.created_at) BETWEEN '$start_date' AND '$end_date'";
if ($type_filter === 'in' || $type_filter === 'out') {
    $where .= " AND t.type = '$type_filter'";
}

$query = "SELECT t.*, p.name as pname, p.sku, p.unit, s.name as sname, u.name as uname 
          FROM transactions t
          LEFT JOIN products p ON t.product_id = p.id
          LEFT JOIN suppliers s ON t.supplier_id = s.id
          LEFT JOIN users u ON t.user_id = u.id
          WHERE $where ORDER BY t.created_at DESC";

if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    $result = $conn->query($query);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Laporan_WMS_' . $start_date . '_sd_' . $end_date . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Tanggal', 'Tipe', 'SKU', 'Nama Barang', 'Qty', 'Satuan', 'Referensi', 'Supplier', 'User', 'Catatan']);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['created_at'],
                $row['type'] == 'in' ? 'Masuk' : 'Keluar',
                $row['sku'],
                $row['pname'],
                $row['quantity'],
                $row['unit'],
                $row['reference_no'],
                $row['sname'],
                $row['uname'],
                $row['notes']
            ]);
        }
    }
    fclose($output);
    exit;
}

$result = $conn->query($query);
$total_in = 0; $total_out = 0;
$count_in = 0; $count_out = 0;

if ($result) {
    $rows = [];
    while ($r = $result->fetch_assoc()) {
        $rows[] = $r;
        if ($r['type'] == 'in') { $total_in += $r['quantity']; $count_in++; }
        else { $total_out += $r['quantity']; $count_out++; }
    }
}
?>

<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Laporan Transaksi</h2>
        <p class="text-sm text-slate-500 mt-1">Pantau pergerakan masuk-keluar barang gudang Anda.</p>
    </div>
</div>

<div class="bg-white rounded-[1.25rem] border border-slate-100 shadow-sm p-5 mb-6">
    <form action="" method="GET" class="flex flex-col sm:flex-row gap-4 items-end">
        <div class="w-full sm:w-auto flex-1">
            <label class="block text-xs font-semibold text-slate-500 uppercase mb-2">Tanggal Mulai</label>
            <input type="date" name="start" value="<?= $start_date ?>" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
        </div>
        <div class="w-full sm:w-auto flex-1">
            <label class="block text-xs font-semibold text-slate-500 uppercase mb-2">Tanggal Selesai</label>
            <input type="date" name="end" value="<?= $end_date ?>" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
        </div>
        <div class="w-full sm:w-auto flex-1">
            <label class="block text-xs font-semibold text-slate-500 uppercase mb-2">Tipe</label>
            <select name="type" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                <option value="">Semua Transaksi</option>
                <option value="in" <?= $type_filter=='in'?'selected':'' ?>>Barang Masuk</option>
                <option value="out" <?= $type_filter=='out'?'selected':'' ?>>Barang Keluar</option>
            </select>
        </div>
        <div class="w-full sm:w-auto flex gap-2">
            <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-xl shadow-md transition-all">Filter</button>
            <a href="?start=<?= $start_date ?>&end=<?= $end_date ?>&type=<?= $type_filter ?>&export=csv" class="px-6 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-medium rounded-xl shadow-md transition-all flex items-center"><i class="ph ph-file-csv mr-2"></i> Export CSV</a>
        </div>
    </form>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm"><p class="text-xs font-semibold text-slate-500 uppercase">Trx Masuk</p><p class="text-2xl font-bold text-emerald-600"><?= $count_in ?></p></div>
    <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm"><p class="text-xs font-semibold text-slate-500 uppercase">Total Qty Masuk</p><p class="text-2xl font-bold text-emerald-600"><?= number_format($total_in) ?></p></div>
    <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm"><p class="text-xs font-semibold text-slate-500 uppercase">Trx Keluar</p><p class="text-2xl font-bold text-rose-600"><?= $count_out ?></p></div>
    <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm"><p class="text-xs font-semibold text-slate-500 uppercase">Total Qty Keluar</p><p class="text-2xl font-bold text-rose-600"><?= number_format($total_out) ?></p></div>
</div>

<div class="bg-white rounded-[1.25rem] border border-slate-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse whitespace-nowrap">
            <thead>
                <tr class="bg-slate-50/80 border-b border-slate-100">
                    <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase">Waktu</th>
                    <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase">Tipe</th>
                    <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase">Barang</th>
                    <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase text-right">Qty</th>
                    <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase">Referensi</th>
                    <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase">User</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (count($rows) > 0): foreach ($rows as $row): ?>
                <tr class="hover:bg-slate-50">
                    <td class="py-3 px-4 text-sm text-slate-600"><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                    <td class="py-3 px-4"><?php if($row['type']=='in') echo '<span class="px-2 py-1 bg-emerald-100 text-emerald-700 text-xs font-bold rounded-lg border border-emerald-200">Masuk</span>'; else echo '<span class="px-2 py-1 bg-rose-100 text-rose-700 text-xs font-bold rounded-lg border border-rose-200">Keluar</span>'; ?></td>
                    <td class="py-3 px-4 text-sm"><p class="font-bold text-slate-800"><?= htmlspecialchars($row['pname']) ?></p><p class="text-xs text-slate-400 font-mono"><?= htmlspecialchars($row['sku']) ?></p></td>
                    <td class="py-3 px-4 text-sm font-bold text-right <?= $row['type']=='in'?'text-emerald-600':'text-rose-600' ?>"><?= $row['type']=='in'?'+':'-' ?><?= number_format($row['quantity']) ?> <?= $row['unit'] ?></td>
                    <td class="py-3 px-4 text-sm text-slate-600"><?= htmlspecialchars($row['reference_no'] ?? '-') ?></td>
                    <td class="py-3 px-4 text-sm text-slate-600"><?= htmlspecialchars($row['uname'] ?? '-') ?></td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="6" class="py-8 text-center text-slate-500">Tidak ada data transaksi.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
