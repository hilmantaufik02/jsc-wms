<?php
require_once 'config/db.php';
$title = "Kelola Pengguna";
require_once 'includes/header.php';

$error = ''; $success = '';
$can_manage = in_array($_SESSION['role'], ['admin', 'super_admin']);

if (!$can_manage) {
    echo '<div class="p-6"><div class="bg-rose-50 text-rose-600 p-4 rounded-xl">Akses Ditolak. Anda tidak memiliki izin.</div></div>';
    require_once 'includes/footer.php';
    exit;
}

$edit_user = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $edit_user = $conn->query("SELECT * FROM users WHERE id = $eid")->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $name = trim($_POST['name']);
    $role = $_POST['role'];
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($name)) {
        $error = "Username dan Nama wajib diisi.";
    } else {
        if ($_POST['action'] === 'add') {
            if (empty($password)) $error = "Password wajib diisi untuk pengguna baru.";
            else {
                $check = $conn->query("SELECT id FROM users WHERE username = '$username'");
                if ($check->num_rows > 0) $error = "Username sudah digunakan.";
                else {
                    $hashed = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $conn->prepare("INSERT INTO users (username, password, name, role) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("ssss", $username, $hashed, $name, $role);
                    $success = $stmt->execute() ? "Pengguna berhasil ditambahkan." : "Gagal menambahkan pengguna.";
                    $stmt->close();
                }
            }
        } elseif ($_POST['action'] === 'edit' && isset($_POST['id'])) {
            $uid = (int)$_POST['id'];
            $check = $conn->query("SELECT id FROM users WHERE username = '$username' AND id != $uid");
            if ($check->num_rows > 0) $error = "Username sudah digunakan pengguna lain.";
            else {
                if (!empty($password)) {
                    $hashed = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $conn->prepare("UPDATE users SET username=?, password=?, name=?, role=? WHERE id=?");
                    $stmt->bind_param("ssssi", $username, $hashed, $name, $role, $uid);
                } else {
                    $stmt = $conn->prepare("UPDATE users SET username=?, name=?, role=? WHERE id=?");
                    $stmt->bind_param("sssi", $username, $name, $role, $uid);
                }
                $success = $stmt->execute() ? "Data pengguna berhasil diperbarui." : "Gagal memperbarui pengguna.";
                $stmt->close(); $edit_user = null;
            }
        }
    }
}

if (isset($_GET['delete'])) {
    $did = (int)$_GET['delete'];
    if ($did === $_SESSION['user_id']) {
        $error = "Anda tidak bisa menghapus akun Anda sendiri yang sedang login.";
    } else {
        $conn->query("DELETE FROM users WHERE id = $did");
        $success = "Pengguna berhasil dihapus.";
    }
}

$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
?>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Kelola Pengguna</h2>
    <p class="text-sm text-slate-500 mt-1">Manajemen akses admin dan staff pada sistem WMS.</p>
</div>

<?php if ($error): ?><div class="bg-rose-50 border-l-4 border-rose-500 p-4 rounded-xl mb-6 flex items-start shadow-sm"><i class="ph ph-warning-circle text-rose-500 text-xl mr-3 mt-0.5"></i><div><h3 class="text-rose-800 font-bold text-sm">Gagal</h3><p class="text-rose-600 text-xs mt-1"><?= $error ?></p></div></div><?php endif; ?>
<?php if ($success): ?><div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-xl mb-6 flex items-start shadow-sm"><i class="ph ph-check-circle text-emerald-500 text-xl mr-3 mt-0.5"></i><div><h3 class="text-emerald-800 font-bold text-sm">Berhasil</h3><p class="text-emerald-600 text-xs mt-1"><?= $success ?></p></div></div><?php endif; ?>

<div class="flex flex-col lg:flex-row gap-8">
    <div class="w-full lg:w-1/3">
        <div class="bg-white rounded-[1.25rem] border border-slate-100 shadow-sm p-6 lg:sticky lg:top-28">
            <h3 class="text-lg font-bold text-slate-800 mb-5 flex items-center">
                <div class="w-8 h-8 rounded-lg <?= $edit_user ? 'bg-amber-50 text-amber-600' : 'bg-indigo-50 text-indigo-600' ?> flex items-center justify-center mr-3"><i class="ph <?= $edit_user ? 'ph-pencil-simple' : 'ph-plus' ?>"></i></div>
                <?= $edit_user ? 'Edit Pengguna' : 'Tambah Pengguna' ?>
            </h3>
            <form action="users.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="<?= $edit_user ? 'edit' : 'add' ?>">
                <?php if ($edit_user): ?><input type="hidden" name="id" value="<?= $edit_user['id'] ?>"><?php endif; ?>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Lengkap <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" required value="<?= $edit_user ? htmlspecialchars($edit_user['name']) : '' ?>" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Username <span class="text-rose-500">*</span></label>
                    <input type="text" name="username" required value="<?= $edit_user ? htmlspecialchars($edit_user['username']) : '' ?>" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all font-mono">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Password <?= $edit_user ? '<span class="text-slate-400 font-normal">(Kosongkan jika tidak ingin diubah)</span>' : '<span class="text-rose-500">*</span>' ?></label>
                    <input type="password" name="password" <?= $edit_user ? '' : 'required' ?> class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Role / Hak Akses <span class="text-rose-500">*</span></label>
                    <select name="role" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all cursor-pointer">
                        <option value="staff" <?= ($edit_user && $edit_user['role']=='staff') ? 'selected' : '' ?>>Staff Gudang</option>
                        <option value="admin" <?= ($edit_user && $edit_user['role']=='admin') ? 'selected' : '' ?>>Administrator</option>
                        <option value="super_admin" <?= ($edit_user && $edit_user['role']=='super_admin') ? 'selected' : '' ?>>Super Admin</option>
                    </select>
                </div>
                <div class="flex gap-2 pt-1">
                    <?php if ($edit_user): ?>
                    <a href="users.php" class="w-1/3 bg-slate-100 hover:bg-slate-200 text-slate-600 font-medium py-3 rounded-xl transition-all flex items-center justify-center text-sm">Batal</a>
                    <button type="submit" class="w-2/3 bg-amber-500 hover:bg-amber-600 text-white font-medium py-3 rounded-xl shadow-md shadow-amber-500/20 transition-all flex items-center justify-center text-sm"><i class="ph ph-check-circle mr-2"></i> Update</button>
                    <?php else: ?>
                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-3 rounded-xl shadow-md shadow-indigo-600/20 transition-all flex items-center justify-center text-sm"><i class="ph ph-floppy-disk mr-2"></i> Simpan Pengguna</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="w-full lg:w-2/3">
        <div class="bg-white rounded-[1.25rem] border border-slate-100 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between"><h3 class="font-bold text-slate-700">Daftar Pengguna</h3><span class="text-xs text-slate-500"><?= $users->num_rows ?> Total</span></div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse whitespace-nowrap">
                    <thead><tr class="bg-slate-50/80 border-b border-slate-100">
                        <th class="py-4 px-5 text-xs font-bold text-slate-500 uppercase">Pengguna</th>
                        <th class="py-4 px-5 text-xs font-bold text-slate-500 uppercase">Role</th>
                        <th class="py-4 px-5 text-xs font-bold text-slate-500 uppercase text-center w-24">Aksi</th>
                    </tr></thead>
                    <tbody class="divide-y divide-slate-100">
                    <?php if ($users && $users->num_rows > 0): while ($row = $users->fetch_assoc()): ?>
                        <tr class="hover:bg-indigo-50/30 transition-colors group">
                            <td class="py-4 px-5"><div class="flex items-center"><div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold mr-3"><?= strtoupper(substr($row['name'],0,1)) ?></div><div><p class="text-sm font-bold text-slate-800"><?= htmlspecialchars($row['name']) ?></p><p class="text-xs text-slate-500 font-mono mt-0.5">@<?= htmlspecialchars($row['username']) ?></p></div></div></td>
                            <td class="py-4 px-5">
                                <?php if($row['role']=='super_admin') echo '<span class="px-2 py-1 bg-amber-100 text-amber-700 text-xs font-bold rounded-lg border border-amber-200">Super Admin</span>'; ?>
                                <?php if($row['role']=='admin') echo '<span class="px-2 py-1 bg-indigo-100 text-indigo-700 text-xs font-bold rounded-lg border border-indigo-200">Administrator</span>'; ?>
                                <?php if($row['role']=='staff') echo '<span class="px-2 py-1 bg-slate-100 text-slate-600 text-xs font-bold rounded-lg border border-slate-200">Staff Gudang</span>'; ?>
                            </td>
                            <td class="py-4 px-5 text-center"><div class="flex items-center justify-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <a href="users.php?edit=<?= $row['id'] ?>" class="p-2 text-amber-500 hover:text-white hover:bg-amber-500 rounded-lg transition-colors"><i class="ph ph-pencil-simple text-lg"></i></a>
                                <?php if ($row['id'] !== $_SESSION['user_id']): ?>
                                <button onclick="if(confirm('Hapus pengguna ini?')) window.location.href='users.php?delete=<?= $row['id'] ?>'" class="p-2 text-rose-500 hover:text-white hover:bg-rose-500 rounded-lg transition-colors"><i class="ph ph-trash text-lg"></i></button>
                                <?php endif; ?>
                            </div></td>
                        </tr>
                    <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
