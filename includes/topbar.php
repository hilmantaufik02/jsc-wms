<!-- Topbar -->
<header class="h-20 bg-white/80 backdrop-blur-md border-b border-slate-200 flex items-center justify-between px-6 lg:px-8 flex-shrink-0 sticky top-0 z-10 shadow-sm">
    <div class="flex items-center">
        <!-- Mobile menu toggle button -->
        <button class="md:hidden text-slate-500 hover:text-indigo-600 mr-4 focus:outline-none transition-colors">
            <i class="ph ph-list text-3xl"></i>
        </button>
        <h2 class="text-2xl font-bold text-slate-800 tracking-tight"><?= isset($title) ? $title : 'Dashboard' ?></h2>
    </div>

    <div class="flex items-center space-x-3 sm:space-x-5">
        <!-- Dark Mode Toggle -->
        <button id="themeToggle" onclick="toggleTheme()" class="w-11 h-11 rounded-full bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-100 hover:text-indigo-600 transition-colors relative shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1 tooltip no-invert" title="Ganti Tema">
            <i id="themeIcon" class="ph ph-moon text-2xl"></i>
        </button>

        <!-- Notification Dropdown -->
        <div class="relative">
            <button id="notifButton" onclick="toggleDropdown('notifDropdown')" class="w-11 h-11 rounded-full bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-100 hover:text-indigo-600 transition-colors relative shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1">
                <i class="ph ph-bell text-2xl"></i>
                <span class="absolute top-2.5 right-2.5 w-2.5 h-2.5 bg-rose-500 rounded-full border-2 border-white animate-pulse"></span>
            </button>
            
            <!-- Notif Menu -->
            <div id="notifDropdown" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden z-50 origin-top-right transform transition-all">
                <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-800">Notifikasi</h3>
                    <span class="text-xs bg-indigo-100 text-indigo-600 px-2 py-0.5 rounded-full font-semibold">2 Baru</span>
                </div>
                <div class="max-h-[22rem] overflow-y-auto custom-scrollbar">
                    <a href="#" class="block px-4 py-3 hover:bg-slate-50 transition-colors border-b border-slate-50">
                        <div class="flex items-start">
                            <div class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center text-amber-600 mr-3 flex-shrink-0 mt-0.5">
                                <i class="ph ph-warning"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-800">Stok Sepatu Futsal menipis</p>
                                <p class="text-xs text-slate-500 mt-0.5">Sisa 3 pcs di gudang utama.</p>
                                <p class="text-[10px] text-slate-400 mt-1">10 menit yang lalu</p>
                            </div>
                        </div>
                    </a>
                    <a href="#" class="block px-4 py-3 hover:bg-slate-50 transition-colors">
                        <div class="flex items-start">
                            <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 mr-3 flex-shrink-0 mt-0.5">
                                <i class="ph ph-check-circle"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-800">Barang masuk berhasil</p>
                                <p class="text-xs text-slate-500 mt-0.5">Sistem mencatat 50 item baru.</p>
                                <p class="text-[10px] text-slate-400 mt-1">1 jam yang lalu</p>
                            </div>
                        </div>
                    </a>
                </div>
                <a href="#" class="block px-4 py-3 text-center text-xs font-semibold text-indigo-600 hover:bg-slate-50 transition-colors border-t border-slate-100">Lihat Semua Notifikasi</a>
            </div>
        </div>
        
        <div class="h-8 w-px bg-slate-200 mx-1 hidden sm:block"></div>
        
        <!-- User Profile Dropdown Toggle -->
        <div class="relative">
            <button id="userButton" onclick="toggleDropdown('userDropdown')" class="w-full flex items-center cursor-pointer hover:bg-slate-50 py-1.5 px-2.5 sm:py-2 sm:px-3 rounded-2xl transition-colors border border-transparent hover:border-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1 text-left">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-tr from-orange-100 to-blue-100 text-blue-700 flex items-center justify-center font-bold text-xl mr-3 shadow-inner shrink-0">
                    <?= strtoupper(substr($_SESSION['name'], 0, 1)) ?>
                </div>
                <div class="hidden sm:block text-left">
                    <p class="text-sm font-bold text-slate-800 leading-tight"><?= htmlspecialchars($_SESSION['name']) ?></p>
                    <p class="text-[11px] font-semibold text-orange-500 uppercase tracking-wider mt-0.5">
                        <?= str_replace('_', ' ', htmlspecialchars($_SESSION['role'])) ?>
                    </p>
                </div>
                <i class="ph ph-caret-down text-slate-400 ml-4 hidden sm:block transition-transform duration-200" id="userCaret"></i>
            </button>

            <!-- User Menu -->
            <div id="userDropdown" class="hidden absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden z-50 origin-top-right transform transition-all">
                <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/50 sm:hidden">
                    <p class="text-sm font-bold text-slate-800"><?= htmlspecialchars($_SESSION['name']) ?></p>
                    <p class="text-xs text-indigo-600 font-semibold uppercase"><?= str_replace('_', ' ', htmlspecialchars($_SESSION['role'])) ?></p>
                </div>
                <div class="py-1">
                    <a href="profile.php" class="flex items-center px-4 py-2.5 text-sm text-slate-700 hover:bg-indigo-50 hover:text-indigo-700 transition-colors">
                        <i class="ph ph-user text-lg mr-3 text-slate-400"></i> Profil Saya
                    </a>
                    <a href="change_password.php" class="flex items-center px-4 py-2.5 text-sm text-slate-700 hover:bg-indigo-50 hover:text-indigo-700 transition-colors">
                        <i class="ph ph-lock-key text-lg mr-3 text-slate-400"></i> Ubah Password
                    </a>
                    <?php if(in_array($_SESSION['role'], ['admin', 'super_admin'])): ?>
                    <a href="settings.php" class="flex items-center px-4 py-2.5 text-sm text-slate-700 hover:bg-indigo-50 hover:text-indigo-700 transition-colors">
                        <i class="ph ph-gear text-lg mr-3 text-slate-400"></i> Pengaturan
                    </a>
                    <?php endif; ?>
                </div>
                <div class="border-t border-slate-100 py-1 bg-slate-50/50">
                    <a href="logout.php" class="flex items-center px-4 py-2.5 text-sm text-rose-600 hover:bg-rose-50 font-medium transition-colors">
                        <i class="ph ph-sign-out text-lg mr-3"></i> Keluar
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
    function toggleTheme() {
        if (document.documentElement.classList.contains('dark')) {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('theme', 'light');
            document.getElementById('themeIcon').classList.replace('ph-sun', 'ph-moon');
        } else {
            document.documentElement.classList.add('dark');
            localStorage.setItem('theme', 'dark');
            document.getElementById('themeIcon').classList.replace('ph-moon', 'ph-sun');
        }
    }
    
    // Set icon correctly on load
    document.addEventListener('DOMContentLoaded', () => {
        if (document.documentElement.classList.contains('dark')) {
            document.getElementById('themeIcon').classList.replace('ph-moon', 'ph-sun');
        }
    });
</script>
