<?php
require_once 'config/db.php';
$title = "Manajemen Supplier";
require_once 'includes/header.php';

$error = ''; $success = '';
$can_manage = in_array($_SESSION['role'], ['admin', 'super_admin']);
$edit_supplier = null;

if (isset($_GET['edit']) && $can_manage) {
    $eid = (int)$_GET['edit'];
    $edit_supplier = $conn->query("SELECT * FROM suppliers WHERE id = $eid")->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_manage) {
    $name           = trim($_POST['name']);
    $contact_person = trim($_POST['contact_person'] ?? '');
    $phone          = trim($_POST['phone'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $address        = trim($_POST['address'] ?? '');
    $notes          = trim($_POST['notes'] ?? '');

    if (empty($name)) {
        $error = "Nama supplier tidak boleh kosong.";
    } else {
        if ($_POST['action'] === 'add') {
            $stmt = $conn->prepare("INSERT INTO suppliers (name, contact_person, phone, email, address, notes) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $name, $contact_person, $phone, $email, $address, $notes);
            $success = $stmt->execute() ? "Supplier berhasil ditambahkan." : "Gagal menambahkan supplier.";
            $stmt->close();
        } elseif ($_POST['action'] === 'edit' && isset($_POST['id'])) {
            $sid = (int)$_POST['id'];
            $stmt = $conn->prepare("UPDATE suppliers SET name=?, contact_person=?, phone=?, email=?, address=?, notes=? WHERE id=?");
            $stmt->bind_param("ssssssi", $name, $contact_person, $phone, $email, $address, $notes, $sid);
            $success = $stmt->execute() ? "Supplier berhasil diperbarui." : "Gagal memperbarui supplier.";
            $stmt->close(); $edit_supplier = null;
        }
    }
}

if (isset($_GET['delete']) && $can_manage) {
    $did = (int)$_GET['delete'];
    $check = $conn->query("SELECT id FROM transactions WHERE supplier_id = $did LIMIT 1");
    if ($check->num_rows > 0) {
        $error = "Supplier tidak bisa dihapus karena masih memiliki riwayat transaksi.";
    } else {
        $conn->query("DELETE FROM suppliers WHERE id = $did");
        $success = "Supplier berhasil dihapus.";
    }
}

$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$where  = $search ? "WHERE name LIKE '%$search%' OR contact_person LIKE '%$search%' OR phone LIKE '%$search%'" : '';
$result = $conn->query("SELECT s.*, (SELECT COUNT(*) FROM transactions t WHERE t.supplier_id = s.id) as total_trx FROM suppliers s $where ORDER BY s.id DESC");
?>

<div class="mb-2">
    <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Manajemen Supplier</h2>
    <p class="text-sm text-slate-500 mt-1">Kelola data vendor dan pemasok barang untuk gudang Jersic.</p>
</div>

<?php if ($error): ?><div class="bg-rose-50 border-l-4 border-rose-500 p-4 rounded-xl mb-6 flex items-start shadow-sm mt-4"><i class="ph ph-warning-circle text-rose-500 text-xl mr-3 mt-0.5"></i><div><h3 class="text-rose-800 font-bold text-sm">Terjadi Kesalahan</h3><p class="text-rose-600 text-xs mt-1"><?= $error ?></p></div></div><?php endif; ?>
<?php if ($success): ?><div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-xl mb-6 flex items-start shadow-sm mt-4"><i class="ph ph-check-circle text-emerald-500 text-xl mr-3 mt-0.5"></i><div><h3 class="text-emerald-800 font-bold text-sm">Berhasil</h3><p class="text-emerald-600 text-xs mt-1"><?= $success ?></p></div></div><?php endif; ?>

<div class="flex flex-col lg:flex-row gap-8 mt-4">
    <!-- Form -->
    <div class="w-full lg:w-1/3">
        <div class="bg-white rounded-[1.25rem] border border-slate-100 shadow-sm p-6 lg:sticky lg:top-28">
            <h3 class="text-lg font-bold text-slate-800 mb-5 flex items-center">
                <div class="w-8 h-8 rounded-lg <?= $edit_supplier ? 'bg-amber-50 text-amber-600' : 'bg-indigo-50 text-indigo-600' ?> flex items-center justify-center mr-3">
                    <i class="ph <?= $edit_supplier ? 'ph-pencil-simple' : 'ph-plus' ?>"></i>
                </div>
                <?= $edit_supplier ? 'Edit Supplier' : 'Tambah Supplier' ?>
            </h3>
            <?php if (!$can_manage): ?>
                <div class="bg-amber-50 border border-amber-200 text-amber-700 p-3 rounded-xl text-sm"><i class="ph ph-lock mr-2"></i>Hanya Admin yang bisa mengelola supplier.</div>
            <?php else: ?>
            <form action="suppliers.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="<?= $edit_supplier ? 'edit' : 'add' ?>">
                <?php if ($edit_supplier): ?><input type="hidden" name="id" value="<?= $edit_supplier['id'] ?>"><?php endif; ?>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Supplier <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" required placeholder="PT. Supplier Jaya" value="<?= $edit_supplier ? htmlspecialchars($edit_supplier['name']) : '' ?>" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Kontak (PIC)</label>
                    <input type="text" name="contact_person" placeholder="Budi Santoso" value="<?= $edit_supplier ? htmlspecialchars($edit_supplier['contact_person'] ?? '') : '' ?>" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">No. Telepon</label>
                        <input type="text" name="phone" placeholder="08xxxxxxxxxx" value="<?= $edit_supplier ? htmlspecialchars($edit_supplier['phone'] ?? '') : '' ?>" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Email</label>
                        <input type="email" name="email" placeholder="email@supplier.com" value="<?= $edit_supplier ? htmlspecialchars($edit_supplier['email'] ?? '') : '' ?>" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Alamat</label>
                    <textarea name="address" rows="2" placeholder="Jl. Raya No. 1, Jakarta..." class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all resize-none"><?= $edit_supplier ? htmlspecialchars($edit_supplier['address'] ?? '') : '' ?></textarea>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Catatan</label>
                    <textarea name="notes" rows="2" placeholder="Keterangan tambahan..." class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all resize-none"><?= $edit_supplier ? htmlspecialchars($edit_supplier['notes'] ?? '') : '' ?></textarea>
                </div>
                <div class="flex gap-2 pt-1">
                    <?php if ($edit_supplier): ?>
                    <a href="suppliers.php" class="w-1/3 bg-slate-100 hover:bg-slate-200 text-slate-600 font-medium py-3 rounded-xl transition-all flex items-center justify-center text-sm">Batal</a>
                    <button type="submit" class="w-2/3 bg-amber-500 hover:bg-amber-600 text-white font-medium py-3 rounded-xl shadow-md shadow-amber-500/20 transition-all hover:-translate-y-0.5 flex items-center justify-center text-sm"><i class="ph ph-check-circle mr-2"></i> Update</button>
                    <?php else: ?>
                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-3 rounded-xl shadow-md shadow-indigo-600/20 transition-all hover:-translate-y-0.5 flex items-center justify-center text-sm"><i class="ph ph-floppy-disk mr-2"></i> Simpan Supplier</button>
                    <?php endif; ?>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tabel -->
    <div class="w-full lg:w-2/3">
        <div class="bg-white rounded-[1.25rem] border border-slate-100 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-100 bg-slate-50/50">
                <form action="" method="GET" class="relative max-w-sm">
                    <i class="ph ph-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama atau kontak..." class="w-full pl-11 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
                    <?php if ($search): ?><a href="suppliers.php" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-rose-500"><i class="ph ph-x-circle text-lg"></i></a><?php endif; ?>
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse whitespace-nowrap">
                    <thead><tr class="bg-slate-50/80 border-b border-slate-100">
                        <th class="py-4 px-5 text-xs font-bold text-slate-500 uppercase w-12 text-center">No</th>
                        <th class="py-4 px-5 text-xs font-bold text-slate-500 uppercase">Info Supplier</th>
                        <th class="py-4 px-5 text-xs font-bold text-slate-500 uppercase">Kontak</th>
                        <th class="py-4 px-5 text-xs font-bold text-slate-500 uppercase text-center">Transaksi</th>
                        <?php if ($can_manage): ?><th class="py-4 px-5 text-xs font-bold text-slate-500 uppercase text-center w-24">Aksi</th><?php endif; ?>
                    </tr></thead>
                    <tbody class="divide-y divide-slate-100">
                    <?php if ($result && $result->num_rows > 0): $no = 1; while ($row = $result->fetch_assoc()): ?>
                        <tr class="hover:bg-indigo-50/30 transition-colors group">
                            <td class="py-4 px-5 text-sm text-slate-400 text-center"><?= $no++ ?></td>
                            <td class="py-4 px-5">
                                <p class="text-sm font-bold text-slate-800"><?= htmlspecialchars($row['name']) ?></p>
                                <?php if ($row['address']): ?><p class="text-xs text-slate-400 mt-0.5 truncate max-w-[200px]"><?= htmlspecialchars($row['address']) ?></p><?php endif; ?>
                                <?php if ($row['notes']): ?><p class="text-xs text-slate-400 italic mt-0.5 truncate max-w-[200px]"><?= htmlspecialchars($row['notes']) ?></p><?php endif; ?>
                            </td>
                            <td class="py-4 px-5">
                                <?php if ($row['contact_person']): ?><p class="text-sm font-medium text-slate-700"><?= htmlspecialchars($row['contact_person']) ?></p><?php endif; ?>
                                <?php if ($row['phone']): ?><p class="text-xs text-slate-500"><i class="ph ph-phone mr-1"></i><?= htmlspecialchars($row['phone']) ?></p><?php endif; ?>
                                <?php if ($row['email']): ?><p class="text-xs text-slate-500"><i class="ph ph-envelope mr-1"></i><?= htmlspecialchars($row['email']) ?></p><?php endif; ?>
                            </td>
                            <td class="py-4 px-5 text-center"><span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold <?= $row['total_trx'] > 0 ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-500' ?>"><?= $row['total_trx'] ?> Transaksi</span></td>
                            <?php if ($can_manage): ?>
                            <td class="py-4 px-5"><div class="flex items-center justify-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <a href="suppliers.php?edit=<?= $row['id'] ?>" class="p-2 text-amber-500 hover:text-white hover:bg-amber-500 rounded-lg transition-colors" title="Edit"><i class="ph ph-pencil-simple text-lg"></i></a>
                                <button onclick="if(confirm('Hapus supplier ini?')) window.location.href='suppliers.php?delete=<?= $row['id'] ?>'" class="p-2 text-rose-500 hover:text-white hover:bg-rose-500 rounded-lg transition-colors" title="Hapus"><i class="ph ph-trash text-lg"></i></button>
                            </div></td>
                            <?php endif; ?>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="5" class="py-16 text-center"><div class="flex flex-col items-center"><div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-3"><i class="ph ph-truck text-3xl text-slate-300"></i></div><h3 class="font-bold text-slate-700">Belum Ada Supplier</h3><p class="text-sm text-slate-400 mt-1">Gunakan form di samping untuk menambah supplier.</p></div></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($result && $result->num_rows > 0): ?>
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                <span class="text-sm text-slate-500">Total <span class="font-bold text-slate-700"><?= $result->num_rows ?></span> supplier</span>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
