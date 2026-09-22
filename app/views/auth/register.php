<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - MeetSpace MVC</title>
    <!-- Tailwind CSS (Local Compiled Standalone) -->
    <link rel="stylesheet" href="public/css/tailwind.min.css">
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-slate-100 dark:bg-slate-900 text-slate-800 dark:text-slate-100 min-h-[100dvh] flex items-center justify-center p-3 sm:p-4 antialiased transition-colors duration-200">

<div class="w-full max-w-md">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-100 dark:border-slate-700/80 p-5 sm:p-8 relative">
        <button type="button" onclick="toggleTheme()" class="absolute top-4 right-4 p-2 text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700 transition" title="Toggle Theme">
            <i class="fas fa-moon dark:hidden"></i>
            <i class="fas fa-sun hidden dark:inline"></i>
        </button>

        <div class="text-center mb-6">
            <div class="flex justify-center mb-3">
                <img src="public/logo.png" onerror="this.src='public/logo.svg'" alt="Logo" class="h-12 w-auto object-contain" />
            </div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Sistem Pemesanan Ruangan</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Buat akun baru Anda</p>
        </div>

        <?php display_flash(); ?>

        <?php if (!empty($error)): ?>
            <div class="p-4 mb-4 rounded-xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-900 text-rose-800 dark:text-rose-300 text-sm flex items-center gap-3">
                <i class="fas fa-exclamation-circle text-rose-500 text-lg"></i>
                <div class="flex-1"><?php echo htmlspecialchars($error); ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php" class="space-y-4">
            <?php echo csrf_field(); ?>
            <div>
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Nama Lengkap</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fas fa-user"></i>
                    </div>
                    <input type="text" name="name" autocomplete="name" class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-100 text-sm focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition" placeholder="Contoh: Budi Santoso" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Username</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fas fa-at"></i>
                    </div>
                    <input type="text" name="username" autocomplete="username" minlength="3" maxlength="100" pattern="[A-Za-z0-9._\-]+" class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-100 text-sm focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition" placeholder="Contoh: fikus" value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
                </div>
                <p class="mt-1.5 text-[10px] text-slate-500 dark:text-slate-400">Minimal 3 karakter; gunakan huruf, angka, titik, garis bawah, atau tanda hubung.</p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Password</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fas fa-lock"></i>
                    </div>
                    <input type="password" name="password" autocomplete="new-password" minlength="8" class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-100 text-sm focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition" placeholder="Min. 8 karakter, angka & simbol" required>
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Ulangi Password</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <input type="password" name="confirm_password" autocomplete="new-password" class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-100 text-sm focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition" placeholder="Konfirmasi password" required>
                </div>
            </div>

            <button type="submit" class="w-full py-3 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-xl shadow-lg shadow-brand-500/20 transition duration-200 text-sm flex items-center justify-center gap-2 mt-2">
                <i class="fas fa-check-circle"></i> Daftar Akun Sekarang
            </button>
        </form>

        <div class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">
            Sudah memiliki akun?
            <a href="login.php" class="font-bold text-brand-600 dark:text-brand-400 hover:text-brand-700 hover:underline ml-1">Masuk Di Sini</a>
        </div>
    </div>
</div>

<script>
/** Mengganti tema antarmuka antara mode terang dan gelap. */
function toggleTheme() {
    if (document.documentElement.classList.contains('dark')) {
        document.documentElement.classList.remove('dark');
        localStorage.theme = 'light';
    } else {
        document.documentElement.classList.add('dark');
        localStorage.theme = 'dark';
    }
}
</script>

</body>
</html>
