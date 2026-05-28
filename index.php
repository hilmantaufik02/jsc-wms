<?php
require_once 'config/db.php';
$title = "Dashboard";
require_once 'includes/header.php';

// Stats Query
$total_products = $conn->query("SELECT SUM(stock) as total, COUNT(id) as count FROM products")->fetch_assoc();
$total_in = $conn->query("SELECT COUNT(id) as count, COALESCE(SUM(quantity),0) as qty FROM transactions WHERE type='in' AND MONTH(created_at)=MONTH(NOW())")->fetch_assoc();
$total_out = $conn->query("SELECT COUNT(id) as count, COALESCE(SUM(quantity),0) as qty FROM transactions WHERE type='out' AND MONTH(created_at)=MONTH(NOW())")->fetch_assoc();
$low_stock = $conn->query("SELECT COUNT(id) as count FROM products WHERE stock <= min_stock")->fetch_assoc();

// Chart Data (7 Hari Terakhir)
$chart_query = $conn->query("
    SELECT 
        DATE(created_at) as date,
        SUM(CASE WHEN type = 'in' THEN quantity ELSE 0 END) as total_in,
        SUM(CASE WHEN type = 'out' THEN quantity ELSE 0 END) as total_out
    FROM transactions 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");

$dates = [];
$data_in = [];
$data_out = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $dates[$d] = date('d M', strtotime($d));
    $data_in[$d] = 0;
    $data_out[$d] = 0;
}

if ($chart_query) {
    while ($row = $chart_query->fetch_assoc()) {
        $d = $row['date'];
        if (isset($dates[$d])) {
            $data_in[$d] = $row['total_in'];
            $data_out[$d] = $row['total_out'];
        }
    }
}

// Recent Activities
$recent = $conn->query("
    SELECT t.*, p.name as pname, u.name as uname 
    FROM transactions t
    LEFT JOIN products p ON t.product_id = p.id
    LEFT JOIN users u ON t.user_id = u.id
    ORDER BY t.created_at DESC LIMIT 5
");

// Best Sellers
$best_sellers = $conn->query("
    SELECT p.name, p.sku, p.image, SUM(t.quantity) as total_sold 
    FROM transactions t 
    JOIN products p ON t.product_id = p.id 
    WHERE t.type = 'out' AND t.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY p.id 
    ORDER BY total_sold DESC 
    LIMIT 5
");

// Dead Stock
$dead_stock_query = $conn->query("
    SELECT p.name, p.sku, p.stock, p.image, p.created_at
    FROM products p 
    LEFT JOIN transactions t ON p.id = t.product_id AND t.type = 'out'
    WHERE t.id IS NULL AND p.stock > 0 AND p.created_at <= DATE_SUB(NOW(), INTERVAL 7 DAY)
    LIMIT 5
");
?>

<!-- Highlight Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-[1.5rem] p-6 border border-slate-100 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-indigo-50 rounded-full group-hover:scale-150 transition-transform duration-500 ease-in-out"></div>
        <div class="relative flex items-center justify-between mb-4">
            <div class="w-14 h-14 bg-indigo-100 text-indigo-600 rounded-2xl flex items-center justify-center text-3xl shadow-inner"><i class="ph ph-package"></i></div>
        </div>
        <h3 class="relative text-slate-500 text-sm font-semibold uppercase tracking-wider">Total Stok Barang</h3>
        <p class="relative text-4xl font-extrabold text-slate-800 mt-2"><?= number_format($total_products['total'] ?? 0) ?> <span class="text-lg font-medium text-slate-400">pcs</span></p>
    </div>

    <div class="bg-white rounded-[1.5rem] p-6 border border-slate-100 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-50 rounded-full group-hover:scale-150 transition-transform duration-500 ease-in-out"></div>
        <div class="relative flex items-center justify-between mb-4">
            <div class="w-14 h-14 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center text-3xl shadow-inner"><i class="ph ph-download-simple"></i></div>
        </div>
        <h3 class="relative text-slate-500 text-sm font-semibold uppercase tracking-wider">Masuk (Bulan Ini)</h3>
        <p class="relative text-4xl font-extrabold text-slate-800 mt-2"><?= $total_in['count'] ?> <span class="text-lg font-medium text-slate-400">trx</span></p>
    </div>

    <div class="bg-white rounded-[1.5rem] p-6 border border-slate-100 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-rose-50 rounded-full group-hover:scale-150 transition-transform duration-500 ease-in-out"></div>
        <div class="relative flex items-center justify-between mb-4">
            <div class="w-14 h-14 bg-rose-100 text-rose-600 rounded-2xl flex items-center justify-center text-3xl shadow-inner"><i class="ph ph-upload-simple"></i></div>
        </div>
        <h3 class="relative text-slate-500 text-sm font-semibold uppercase tracking-wider">Keluar (Bulan Ini)</h3>
        <p class="relative text-4xl font-extrabold text-slate-800 mt-2"><?= $total_out['count'] ?> <span class="text-lg font-medium text-slate-400">trx</span></p>
    </div>

    <div class="bg-white rounded-[1.5rem] p-6 border <?= $low_stock['count'] > 0 ? 'border-amber-200 bg-gradient-to-br from-amber-50 to-white' : 'border-slate-100' ?> shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group">
        <div class="absolute -right-4 -top-4 w-24 h-24 <?= $low_stock['count'] > 0 ? 'bg-amber-100' : 'bg-slate-50' ?> rounded-full group-hover:scale-150 transition-transform duration-500 ease-in-out"></div>
        <div class="relative flex items-center justify-between mb-4">
            <div class="w-14 h-14 <?= $low_stock['count'] > 0 ? 'bg-amber-100 text-amber-600' : 'bg-slate-100 text-slate-400' ?> rounded-2xl flex items-center justify-center text-3xl shadow-inner"><i class="ph ph-warning-circle"></i></div>
            <?php if ($low_stock['count'] > 0): ?>
                <span class="text-xs font-bold text-amber-700 bg-amber-200 px-3 py-1.5 rounded-full animate-pulse">Perhatian</span>
            <?php endif; ?>
        </div>
        <h3 class="relative <?= $low_stock['count'] > 0 ? 'text-amber-700' : 'text-slate-500' ?> text-sm font-semibold uppercase tracking-wider">Stok Menipis</h3>
        <p class="relative text-4xl font-extrabold text-slate-800 mt-2"><?= $low_stock['count'] ?> <span class="text-lg font-medium text-slate-400">item</span></p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <div class="lg:col-span-2 bg-white rounded-[2rem] p-8 border border-slate-100 shadow-sm flex flex-col">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-8">
            <div>
                <h3 class="text-xl font-bold text-slate-800">Statistik Pergerakan Barang</h3>
                <p class="text-sm text-slate-500 mt-1">Data barang masuk dan keluar selama seminggu terakhir.</p>
            </div>
        </div>
        <div class="flex-1 w-full min-h-[300px] relative">
            <canvas id="trxChart"></canvas>
        </div>
    </div>

    <div class="bg-white rounded-[2rem] p-8 border border-slate-100 shadow-sm">
        <div class="flex items-center justify-between mb-8">
            <h3 class="text-xl font-bold text-slate-800">Aktivitas Terakhir</h3>
            <a href="reports.php" class="text-indigo-500 hover:text-indigo-600 text-sm font-semibold hover:underline">Lihat Semua</a>
        </div>
        <div class="relative before:absolute before:inset-0 before:ml-5 before:-translate-x-px md:before:translate-x-0 before:h-full before:w-0.5 before:bg-slate-200 space-y-6">
            <?php if ($recent && $recent->num_rows > 0): while ($row = $recent->fetch_assoc()): ?>
                    <div class="relative flex items-center">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full border-4 border-white <?= $row['type'] == 'in' ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600' ?> shrink-0 z-10 shadow-sm">
                            <i class="ph <?= $row['type'] == 'in' ? 'ph-download-simple' : 'ph-upload-simple' ?> text-lg"></i>
                        </div>
                        <div class="ml-4">
                            <div class="flex flex-col">
                                <span class="text-sm font-bold text-slate-800"><?= $row['type'] == 'in' ? 'Barang Masuk' : 'Barang Keluar' ?></span>
                                <span class="text-sm text-slate-600 mt-0.5"><?= $row['quantity'] ?> pcs - <?= htmlspecialchars($row['pname']) ?></span>
                                <span class="text-xs font-medium text-slate-400 mt-1.5 flex items-center"><i class="ph ph-clock mr-1"></i> <?= date('d M Y, H:i', strtotime($row['created_at'])) ?> (<?= $row['uname'] ?>)</span>
                            </div>
                        </div>
                    </div>
                <?php endwhile;
            else: ?>
                <p class="text-sm text-slate-400 text-center py-4">Belum ada aktivitas.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8 mt-8">
    <!-- Best Seller -->
    <div class="bg-white rounded-[2rem] p-8 border border-slate-100 shadow-sm">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-xl font-bold text-slate-800 flex items-center"><i class="ph ph-fire text-orange-500 mr-2 text-2xl"></i> Top 5 Terlaris</h3>
                <p class="text-xs text-slate-500 mt-1">Barang paling sering keluar (30 Hari Terakhir)</p>
            </div>
        </div>
        <div class="space-y-4">
            <?php if ($best_sellers && $best_sellers->num_rows > 0): while ($row = $best_sellers->fetch_assoc()): ?>
                    <div class="flex items-center justify-between p-4 rounded-2xl bg-slate-50 hover:bg-slate-100 transition-colors border border-slate-100">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-xl bg-white border border-slate-200 overflow-hidden flex items-center justify-center mr-4 flex-shrink-0">
                                <?php if ($row['image']): ?>
                                    <img src="<?= htmlspecialchars($row['image']) ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <i class="ph ph-t-shirt text-slate-400 text-xl"></i>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-slate-800 line-clamp-1"><?= htmlspecialchars($row['name']) ?></h4>
                                <p class="text-xs text-slate-500 font-mono mt-0.5"><?= htmlspecialchars($row['sku']) ?></p>
                            </div>
                        </div>
                        <div class="text-right ml-4">
                            <span class="block text-lg font-black text-indigo-600"><?= $row['total_sold'] ?></span>
                            <span class="text-[10px] uppercase font-bold text-slate-400">Terjual</span>
                        </div>
                    </div>
                <?php endwhile;
            else: ?>
                <div class="text-center py-6 text-slate-400 text-sm">Belum ada data penjualan bulan ini.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Dead Stock -->
    <div class="bg-white rounded-[2rem] p-8 border border-slate-100 shadow-sm">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-xl font-bold text-slate-800 flex items-center"><i class="ph ph-snowflake text-cyan-500 mr-2 text-2xl"></i> Dead Stock / Lambat</h3>
                <p class="text-xs text-slate-500 mt-1">Stok lama yang belum pernah keluar (≥ 7 Hari)</p>
            </div>
        </div>
        <div class="space-y-4">
            <?php if ($dead_stock_query && $dead_stock_query->num_rows > 0): while ($row = $dead_stock_query->fetch_assoc()): ?>
                    <div class="flex items-center justify-between p-4 rounded-2xl bg-slate-50 hover:bg-slate-100 transition-colors border border-slate-100">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-xl bg-white border border-slate-200 overflow-hidden flex items-center justify-center mr-4 flex-shrink-0">
                                <?php if ($row['image']): ?>
                                    <img src="<?= htmlspecialchars($row['image']) ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <i class="ph ph-t-shirt text-slate-400 text-xl"></i>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-slate-800 line-clamp-1"><?= htmlspecialchars($row['name']) ?></h4>
                                <p class="text-xs text-slate-500 mt-0.5">Masuk: <span class="font-medium text-slate-600"><?= date('d M Y', strtotime($row['created_at'])) ?></span></p>
                            </div>
                        </div>
                        <div class="text-right flex flex-col items-end ml-4">
                            <span class="px-2 py-1 bg-amber-100 text-amber-700 text-xs font-bold rounded-lg border border-amber-200 mb-1">Diam</span>
                            <span class="text-xs font-bold text-slate-500">Stok: <?= $row['stock'] ?></span>
                        </div>
                    </div>
                <?php endwhile;
            else: ?>
                <div class="text-center py-6 text-slate-400 text-sm flex flex-col items-center">
                    <i class="ph ph-check-circle text-3xl text-emerald-400 mb-2"></i>
                    Bagus! Semua barang bergerak aktif.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('trxChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_values($dates)) ?>,
            datasets: [{
                    label: 'Barang Masuk',
                    data: <?= json_encode(array_values($data_in)) ?>,
                    backgroundColor: '#34d399',
                    borderRadius: 4
                },
                {
                    label: 'Barang Keluar',
                    data: <?= json_encode(array_values($data_out)) ?>,
                    backgroundColor: '#fb7185',
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        borderDash: [4, 4]
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
</script>

<?php require_once 'includes/footer.php'; ?>