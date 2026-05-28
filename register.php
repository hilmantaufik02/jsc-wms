<?php
session_start();
require_once 'config/db.php';

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'] ?? '';

    if (empty($name) || empty($username) || empty($password) || empty($confirm_password) || empty($role)) {
        $error = "Semua kolom form wajib diisi!";
    } elseif ($password !== $confirm_password) {
        $error = "Konfirmasi password tidak cocok!";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } else {
        // Cek apakah username sudah dipakai
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            $error = "Username sudah terdaftar, gunakan yang lain!";
        } else {
            // Hash password menggunakan Bcrypt (Algoritma standar PHP saat ini)
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_stmt = $conn->prepare("INSERT INTO users (username, password, name, role) VALUES (?, ?, ?, ?)");
            $insert_stmt->bind_param("ssss", $username, $hashed_password, $name, $role);
            
            if ($insert_stmt->execute()) {
                $success = "Pendaftaran berhasil! Anda sekarang bisa login.";
            } else {
                $error = "Terjadi kesalahan sistem saat menyimpan data.";
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    }
}

$title = "Daftar Akun";
require_once 'includes/auth_header.php';
?>

<div class="bg-white/10 backdrop-blur-xl border border-white/10 rounded-[2rem] p-8 sm:p-10 shadow-2xl w-full max-w-md mx-auto my-8">
    <div class="text-center mb-8">
        <h2 class="text-3xl font-bold text-white tracking-tight">Buat Akun</h2>
        <p class="text-slate-400 mt-2 text-sm font-medium">Bergabung sebagai Staff Jersic WMS</p>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-500/10 border border-red-500/50 text-red-400 px-4 py-3 rounded-xl mb-6 text-sm flex items-center">
            <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="bg-emerald-500/10 border border-emerald-500/50 text-emerald-400 px-4 py-3 rounded-xl mb-6 text-sm flex items-center">
            <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="" class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-slate-300 mb-1.5" for="name">Nama Lengkap</label>
            <input type="text" id="name" name="name" class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" placeholder="John Doe" required value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-300 mb-1.5" for="username">Username</label>
            <input type="text" id="username" name="username" class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" placeholder="johndoe123" required value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-300 mb-1.5" for="password">Password</label>
            <div class="relative">
                <input type="password" id="password" name="password" class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all pr-12" placeholder="Minimal 6 karakter" required>
                <button type="button" onclick="togglePassword('password', 'eyeIconReg')" class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400 hover:text-white focus:outline-none">
                    <svg id="eyeIconReg" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                </button>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-300 mb-1.5" for="confirm_password">Konfirmasi Password</label>
            <div class="relative">
                <input type="password" id="confirm_password" name="confirm_password" class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all pr-12" placeholder="Ulangi password" required>
                <button type="button" onclick="togglePassword('confirm_password', 'eyeIconConf')" class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400 hover:text-white focus:outline-none">
                    <svg id="eyeIconConf" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                </button>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-300 mb-1.5" for="role">Posisi / Jabatan</label>
            <select id="role" name="role" class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" required>
                <option value="" disabled selected>Pilih posisi Anda</option>
                <option value="staff" <?= (isset($_POST['role']) && $_POST['role'] == 'staff') ? 'selected' : '' ?>>Staff Gudang</option>
                <option value="admin" <?= (isset($_POST['role']) && $_POST['role'] == 'admin') ? 'selected' : '' ?>>Admin Gudang</option>
                <option value="super_admin" <?= (isset($_POST['role']) && $_POST['role'] == 'super_admin') ? 'selected' : '' ?>>IT Support / Developer (Super Admin)</option>
            </select>
        </div>

        <button type="submit" class="w-full bg-gradient-to-r from-purple-500 to-pink-600 hover:from-purple-400 hover:to-pink-500 text-white font-semibold py-3 px-4 rounded-xl shadow-lg shadow-purple-500/30 transform transition-all duration-300 hover:-translate-y-1 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 focus:ring-purple-500 mt-6">
            Daftar Sekarang
        </button>
    </form>

    <div class="mt-8 text-center text-sm text-slate-400">
        Sudah punya akun? <a href="login.php" class="text-purple-400 hover:text-purple-300 font-semibold transition-colors">Masuk di sini</a>
    </div>
</div>

<?php require_once 'includes/auth_footer.php'; ?>
