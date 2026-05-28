<?php
session_start();
require_once 'config/db.php';

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Username dan password wajib diisi!";
    } else {
        $stmt = $conn->prepare("SELECT id, username, password, name, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            // Verifikasi password hash
            if (password_verify($password, $user['password'])) {
                // Set sesi login
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['role'] = $user['role'];
                
                header("Location: index.php");
                exit;
            } else {
                $error = "Password yang Anda masukkan salah!";
            }
        } else {
            $error = "Username tidak ditemukan!";
        }
        $stmt->close();
    }
}

$title = "Masuk";
require_once 'includes/auth_header.php';
?>

<div class="bg-white/10 backdrop-blur-xl border border-white/10 rounded-[2rem] p-8 sm:p-10 shadow-2xl w-full max-w-md mx-auto my-8">
    <div class="text-center mb-8">
        <div class="w-16 h-16 bg-gradient-to-tr from-orange-500 to-blue-500 rounded-2xl mx-auto flex items-center justify-center mb-5 shadow-lg shadow-orange-500/30 transform transition duration-500 hover:rotate-12 hover:scale-110">
            <img src="/jsc-wms/assets/img/jsc.png" alt="Jersic Logo" class="w-10 h-10 object-contain" />
        </div>
        <h2 class="text-3xl font-bold text-white tracking-tight">Jersic WMS</h2>
        <p class="text-slate-400 mt-2 text-sm font-medium">Sistem Manajemen Gudang</p>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-500/10 border border-red-500/50 text-red-400 px-4 py-3 rounded-xl mb-6 text-sm flex items-center animate-pulse">
            <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="" class="space-y-5">
        <div>
            <label class="block text-sm font-medium text-slate-300 mb-1.5" for="username">Username</label>
            <input type="text" id="username" name="username" class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" placeholder="Masukkan username" required value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-300 mb-1.5" for="password">Password</label>
            <div class="relative">
                <input type="password" id="password" name="password" class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all pr-12" placeholder="••••••••" required>
                <button type="button" onclick="togglePassword('password', 'eyeIconLogin')" class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400 hover:text-white focus:outline-none">
                    <svg id="eyeIconLogin" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                </button>
            </div>
        </div>
        
        <div class="flex items-center justify-between mt-2">
            <label class="flex items-center text-sm text-slate-400 cursor-pointer hover:text-slate-300 transition-colors">
                <input type="checkbox" class="rounded border-slate-700 bg-slate-900/50 text-indigo-500 focus:ring-indigo-500/30 w-4 h-4 mr-2">
                Ingat saya
            </label>
        </div>

        <button type="submit" class="w-full bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-400 hover:to-purple-500 text-white font-semibold py-3 px-4 rounded-xl shadow-lg shadow-indigo-500/30 transform transition-all duration-300 hover:-translate-y-1 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 focus:ring-indigo-500 mt-6">
            Masuk Sekarang
        </button>
    </form>

    <div class="mt-8 text-center text-sm text-slate-400">
        Belum punya akun? <a href="register.php" class="text-indigo-400 hover:text-indigo-300 font-semibold transition-colors">Daftar sebagai Staff</a>
    </div>
</div>

<?php require_once 'includes/auth_footer.php'; ?>
