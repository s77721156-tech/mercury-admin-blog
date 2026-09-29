<?php
session_start();

// Admin credentials
$admin_user = 'admin';
$admin_pass = 'mercury2026';

// If already logged in, go to blog
if (isset($_SESSION['blog_admin_logged_in']) && $_SESSION['blog_admin_logged_in'] === true) {
    header("Location: blog.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if ($_POST['username'] === $admin_user && $_POST['password'] === $admin_pass) {
        $_SESSION['blog_admin_logged_in'] = true;
        header("Location: blog.php");
        exit;
    } else {
        $login_error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Mercury Softech</title>
    <link rel="icon" href="https://mercurysoftech.in/icon.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 flex flex-col min-h-screen">


    <main class="flex-grow flex items-center justify-center p-4">
        <div class="bg-white p-8 rounded-2xl shadow-xl border border-gray-100 w-full max-w-md">
            <div class="text-center mb-8 flex flex-col items-center">
                <img src="https://mercurysoftech.in/icon.png" alt="Logo" class="w-16 h-16 mb-4 object-contain" onerror="this.src='https://placehold.co/64x64/003d7a/FFF?text=MS'">
                <h1 class="text-2xl font-bold text-gray-900">Welcome Back</h1>
                <p class="text-gray-500 mt-2">Sign in to manage the blog.</p>
            </div>
            
            <?php if (isset($login_error)): ?>
                <div class="bg-red-50 text-red-600 p-3 rounded-lg mb-6 text-sm font-medium border border-red-100 text-center">
                    <?php echo htmlspecialchars($login_error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" class="flex flex-col gap-5">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Username</label>
                    <input type="text" name="username" required class="w-full border border-gray-300 rounded-lg p-3 outline-none focus:border-[#003d7a] focus:ring-2 focus:ring-blue-100 transition">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Password</label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required class="w-full border border-gray-300 rounded-lg p-3 pr-10 outline-none focus:border-[#003d7a] focus:ring-2 focus:ring-blue-100 transition">
                        <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                            <i data-lucide="eye" id="eye-icon" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" name="login" value="1" class="w-full bg-[#003d7a] text-white py-3 rounded-lg font-bold hover:bg-[#002d5a] transition shadow-lg shadow-blue-100 mt-2">
                    Sign In
                </button>
            </form>
        </div>
    </main>

    <footer class="bg-white border-t py-6 text-center text-gray-500 text-sm mt-auto">
        &copy; <?php echo date('Y'); ?> Mercury Softech. All rights reserved.
    </footer>

    <!-- Lucide Icons and Password Toggle Script -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();

        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.setAttribute('data-lucide', 'eye-off');
            } else {
                passwordInput.type = 'password';
                eyeIcon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }
    </script>
</body>
</html>
