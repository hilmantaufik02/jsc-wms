<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Redirect ke login jika belum ada sesi
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? $title . " - " : "" ?>Jersic WMS</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Phosphor Icons (Ikon Premium Open Source) -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        primary: '#6366f1', // Indigo 500
                        secondary: '#a855f7', // Purple 500
                    }
                }
            }
        }
        
        // Membaca preferensi Dark Mode dari localStorage
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        /* Sidebar Scrollbar */
        .sidebar-scroll::-webkit-scrollbar { width: 4px; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }

        /* Dark Mode CSS Filter Hack - Sangat efisien tanpa ubah kelas Tailwind satu-satu */
        html.dark {
            filter: invert(1) hue-rotate(180deg);
        }
        html.dark body { background-color: #f8fafc; } /* Supaya overscroll background gelap */
        html.dark img, html.dark canvas, html.dark svg:not(.ph), html.dark .no-invert {
            filter: invert(1) hue-rotate(180deg);
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 overflow-hidden">
    <div class="flex h-screen w-full">
        <!-- Sidebar disisipkan di sini -->
        <?php require_once 'sidebar.php'; ?>
        
        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col h-screen overflow-hidden">
            <!-- Topbar disisipkan di sini -->
            <?php require_once 'topbar.php'; ?>
            
            <!-- Main Area yang bisa di-scroll -->
            <main class="flex-1 overflow-y-auto custom-scrollbar p-6 lg:p-8 relative">
