<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Sidebar -->
<aside class="w-72 bg-slate-900 text-slate-300 flex-shrink-0 hidden md:flex flex-col transition-all duration-300 relative z-20 shadow-xl shadow-slate-900/20">
    <!-- Logo Area -->
    <div class="h-20 flex items-center px-8 border-b border-white/5">
        <div class="w-9 h-9 bg-gradient-to-tr from-blue-500 to-orange-500 rounded-xl flex items-center justify-center mr-3 shadow-lg shadow-indigo-500/30">
            <img src="/jsc-wms/assets/img/jsc.png" alt="Jersic Logo" class="w-8 h-8 object-contain" />
        </div>
        <h1 class="text-2xl font-bold text-white tracking-wide">Jersic<span class="text-orange-500">WMS</span></h1>
    </div>

    <!-- Navigation Menu -->
    <div class="flex-1 overflow-y-auto sidebar-scroll py-6 px-4 space-y-1">
        <p class="px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Menu Utama</p>
        
        <a href="index.php" class="flex items-center px-4 py-3.5 <?= $current_page == 'index.php' ? 'bg-indigo-500/10 text-orange-500 border-r-4 border-orange-500 font-semibold' : 'hover:bg-white/5 hover:text-white font-medium' ?> rounded-xl transition-all">
            <i class="ph <?= $current_page == 'index.php' ? 'ph-squares-four border-indigo-500' : 'ph-squares-four' ?> text-2xl mr-3"></i>
            <span>Dashboard</span>
        </a>
        
        <a href="products.php" class="flex items-center px-4 py-3.5 <?= $current_page == 'products.php' ? 'bg-indigo-500/10 text-orange-500 border-r-4 border-orange-500 font-semibold' : 'hover:bg-white/5 hover:text-white font-medium' ?> rounded-xl transition-all">
            <i class="ph ph-package text-2xl mr-3"></i>
            <span>Data Barang</span>
        </a>
        
        <a href="categories.php" class="flex items-center px-4 py-3.5 <?= $current_page == 'categories.php' ? 'bg-indigo-500/10 text-orange-500 border-r-4 border-orange-500 font-semibold' : 'hover:bg-white/5 hover:text-white font-medium' ?> rounded-xl transition-all">
            <i class="ph ph-tag text-2xl mr-3"></i>
            <span>Kategori</span>
        </a>

        <p class="px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider mt-8 mb-3">Transaksi</p>
        
        <a href="inbound.php" class="flex items-center px-4 py-3.5 <?= $current_page == 'inbound.php' ? 'bg-emerald-500/10 text-orange-500 border-r-4 border-orange-500 font-semibold' : 'hover:bg-white/5 hover:text-white font-medium' ?> rounded-xl transition-all">
            <i class="ph ph-download-simple text-2xl mr-3"></i>
            <span>Barang Masuk</span>
        </a>
        
        <a href="outbound.php" class="flex items-center px-4 py-3.5 <?= $current_page == 'outbound.php' ? 'bg-rose-500/10 text-orange-500 border-r-4 border-orange-500 font-semibold' : 'hover:bg-white/5 hover:text-white font-medium' ?> rounded-xl transition-all">
            <i class="ph ph-upload-simple text-2xl mr-3"></i>
            <span>Barang Keluar</span>
        </a>

        <!-- Menu khusus untuk Admin & Super Admin -->
        <?php if(in_array($_SESSION['role'], ['admin', 'super_admin'])): ?>
        <p class="px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider mt-8 mb-3">Laporan & Sistem</p>
        
        <a href="reports.php" class="flex items-center px-4 py-3.5 <?= $current_page == 'reports.php' ? 'bg-indigo-500/10 text-orange-500 border-r-4 border-orange-500 font-semibold' : 'hover:bg-white/5 hover:text-white font-medium' ?> rounded-xl transition-all">
            <i class="ph ph-chart-line-up text-2xl mr-3"></i>
            <span>Laporan Transaksi</span>
        </a>
        
        <a href="users.php" class="flex items-center px-4 py-3.5 <?= $current_page == 'users.php' ? 'bg-indigo-500/10 text-orange-500 border-r-4 border-orange-500 font-semibold' : 'hover:bg-white/5 hover:text-white font-medium' ?> rounded-xl transition-all">
            <i class="ph ph-users text-2xl mr-3"></i>
            <span>Kelola Pengguna</span>
        </a>

        <a href="stock_opname.php" class="flex items-center px-4 py-3.5 <?= $current_page == 'stock_opname.php' ? 'bg-indigo-500/10 text-orange-500 border-r-4 border-orange-500 font-semibold' : 'hover:bg-white/5 hover:text-white font-medium' ?> rounded-xl transition-all mt-2 border-t border-white/5 pt-4">
            <i class="ph ph-clipboard-text text-2xl mr-3"></i>
            <span>Stock Opname</span>
        </a>
        <?php endif; ?>
    </div>

    <!-- Bottom Widget: Storage & Help -->
    <div class="p-5 border-t border-white/5 bg-slate-900/50 mt-auto">
        <!-- Storage Capacity Indicator -->
        <div class="mb-5">
            <div class="flex items-center justify-between text-xs font-semibold text-slate-400 mb-2.5">
                <span class="uppercase tracking-wider">Kapasitas Gudang</span>
                <span class="text-orange-500">75%</span>
            </div>
            <div class="w-full bg-slate-800 rounded-full h-1.5 overflow-hidden">
                <div class="bg-gradient-to-r from-orange-500 to-orange-500 h-1.5 rounded-full relative" style="width: 75%">
                    <div class="absolute top-0 right-0 bottom-0 w-4 bg-white/20 blur-sm"></div>
                </div>
            </div>
            <p class="text-[10px] text-slate-500 mt-2 font-medium">Tersisa <span class="text-slate-300">500</span> slot dari <span class="text-slate-300">2000</span></p>
        </div>
        
        <!-- Help Button -->
        <a href="help.php" class="flex items-center justify-center w-full px-4 py-2.5 bg-white/5 hover:bg-indigo-500/10 text-slate-400 hover:text-indigo-400 rounded-xl transition-all group font-medium border border-transparent hover:border-indigo-500/20">
            <i class="ph ph-question text-xl mr-2 group-hover:rotate-12 transition-transform"></i>
            <span class="text-sm">Bantuan Sistem</span>
        </a>
    </div>
</aside>
