<?php
require_once 'includes/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = Helper::getPost('username', '');
    $password = Helper::getPost('password', '');

    if (empty($username) || empty($password)) {
        $error = 'Username and password are required';
    } else {
        $auth = new Auth($database);
        if ($auth->login($username, $password)) {
            $user = Auth::getCurrentUser();
            Helper::logActivity($db, 'User logged in', 'Login');
            Helper::redirect('pages/' . Auth::getOfficeRedirect($user['office']));
        } else {
            $error = 'Invalid username or password';
        }
    }
}

if (isset($_GET['timeout'])) {
    $error = 'Session expired. Please login again.';
}

$success = '';
if (isset($_GET['msg']) && $_GET['msg'] === 'logged_out') {
    $success = 'You have been logged out successfully.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <!-- <title>Login - BatStateU GHG Management System</title>-->
    <title>GHG Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .login-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
    </style>
</head>
<body class="login-bg">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full bg-white rounded-lg shadow-xl p-8">
            <!-- Logo -->
            <div class="text-center mb-8">
                <img src="static/images/logo.png" alt="" width="80" class="mx-auto mb-4">
                <!--<i class="fas fa-leaf text-4xl text-green-600 mb-3"></i>-->
               <h1 class="text-3xl font-bold text-gray-800">BatStateU</h1>
                <!--<h1 class="text-3xl font-bold text-gray-800">CarbonNet</h1>-->
                <p class="text-gray-600 text-sm mt-2">GREENHOUSE GAS MANAGEMENT</p>

            </div>

            <!-- Error Message -->
            <?php if (!empty($error)): ?>
                <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-3"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Success Message -->
            <?php if (!empty($success)): ?>
                <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-3"></i>
                        <span><?php echo htmlspecialchars($success); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" action="" class="space-y-4">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-user mr-2"></i>Username
                    </label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        required 
                        autofocus
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        placeholder="Enter your username"
                    >
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-lock mr-2"></i>Password
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        placeholder="Enter your password"
                    >
                </div>

                <button 
                    type="submit"
                    class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 flex items-center justify-center"
                >
                    <i class="fas fa-sign-in-alt mr-2"></i>Login
                </button>
            </form>

            <!-- Footer -->
            <div class="mt-8 pt-6 border-t border-gray-200 text-center text-sm text-gray-600">
                <p>For access issues, please contact the administrator</p>
            </div>
        </div>
    </div>

    <script src="static/js/app.js"></script>
</body>
</html>
