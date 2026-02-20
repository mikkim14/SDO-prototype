<?php
/**
 * BatStateU Portal - Main Landing/Router
 * Place this at the root of your Hostinger hosting
 * Directs users to either the main website or GHG system
 */

// Configuration - CUSTOMIZE THESE URLS FOR YOUR HOSTINGER SETUP
$config = [
    // URL to your existing main website (adjust based on your Hostinger setup)
    'main_website' => '/main/',  // or 'https://yourdomain.com/' if external
    
    // URL to the GHG system (adjust based on where you place it)
    'ghg_system' => '/GHG_PHP/',  // or 'https://yourdomain.com/GHG_PHP/' if subdirectory
    
    // Site name
    'site_name' => 'BatStateU',
];

// Simple routing - can be expanded for more features
$request = $_SERVER['REQUEST_URI'] ?? '/';

// If someone tries to access /ghg directly, redirect to GHG system
if (strpos($request, '/ghg') === 0 || strpos($request, '/GHG') === 0) {
    header('Location: ' . $config['ghg_system']);
    exit;
}

// If someone tries to access /main directly, redirect to main site
if (strpos($request, '/main') === 0) {
    header('Location: ' . $config['main_website']);
    exit;
}

// Otherwise show the landing page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $config['site_name']; ?> Portal - System Access</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }
        .logo-container {
            animation: float 3s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
    </style>
</head>
<body>
    <div class="w-full max-w-6xl px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-12">
            <div class="flex justify-center mb-6">
                <div class="logo-container relative">
                    <div class="absolute inset-0 bg-white rounded-full blur-lg opacity-30"></div>
                    <div class="relative w-16 h-16 bg-white rounded-full flex items-center justify-center text-3xl font-bold text-purple-600">
                        BS
                    </div>
                </div>
            </div>
            <h1 class="text-5xl font-bold text-white mb-2"><?php echo $config['site_name']; ?> Portal</h1>
            <p class="text-xl text-purple-100">Select a system to access</p>
        </div>

        <!-- Systems Grid -->
        <div class="grid md:grid-cols-2 gap-8 mb-12">
            <!-- GHG Management System Card -->
            <div class="card-hover bg-white rounded-xl overflow-hidden shadow-2xl">
                <div class="bg-gradient-to-r from-green-400 to-green-600 h-32 flex items-center justify-center">
                    <i class="fas fa-leaf text-white text-6xl"></i>
                </div>
                <div class="p-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">GHG Management</h2>
                    <p class="text-gray-600 mb-6 leading-relaxed">
                        Track and manage greenhouse gas emissions across all campus locations. Monitor electricity, water, waste, fuel, flights, and accommodation.
                    </p>
                    <div class="mb-6 space-y-2">
                        <div class="flex items-center text-gray-700">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            <span>Multi-campus support</span>
                        </div>
                        <div class="flex items-center text-gray-700">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            <span>Role-based dashboards</span>
                        </div>
                        <div class="flex items-center text-gray-700">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            <span>Real-time analytics</span>
                        </div>
                    </div>
                    <a href="<?php echo $config['ghg_system']; ?>" class="block w-full bg-gradient-to-r from-green-400 to-green-600 text-white font-bold py-3 px-6 rounded-lg text-center hover:from-green-500 hover:to-green-700 transition-all">
                        <i class="fas fa-arrow-right mr-2"></i>Access GHG System
                    </a>
                </div>
            </div>

            <!-- Main Website Card -->
            <div class="card-hover bg-white rounded-xl overflow-hidden shadow-2xl">
                <div class="bg-gradient-to-r from-blue-400 to-blue-600 h-32 flex items-center justify-center">
                    <i class="fas fa-globe text-white text-6xl"></i>
                </div>
                <div class="p-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">Main Website</h2>
                    <p class="text-gray-600 mb-6 leading-relaxed">
                        Visit the main <?php echo $config['site_name']; ?> website for news, updates, announcements, and general information about our institution.
                    </p>
                    <div class="mb-6 space-y-2">
                        <div class="flex items-center text-gray-700">
                            <i class="fas fa-check-circle text-blue-500 mr-2"></i>
                            <span>Latest news & updates</span>
                        </div>
                        <div class="flex items-center text-gray-700">
                            <i class="fas fa-check-circle text-blue-500 mr-2"></i>
                            <span>Event announcements</span>
                        </div>
                        <div class="flex items-center text-gray-700">
                            <i class="fas fa-check-circle text-blue-500 mr-2"></i>
                            <span>Contact information</span>
                        </div>
                    </div>
                    <a href="<?php echo $config['main_website']; ?>" class="block w-full bg-gradient-to-r from-blue-400 to-blue-600 text-white font-bold py-3 px-6 rounded-lg text-center hover:from-blue-500 hover:to-blue-700 transition-all">
                        <i class="fas fa-arrow-right mr-2"></i>Visit Main Website
                    </a>
                </div>
            </div>
        </div>

        <!-- Additional Info Section -->
        <div class="bg-white bg-opacity-10 backdrop-blur-md rounded-xl p-8 mb-8 text-white">
            <h3 class="text-xl font-bold mb-4 flex items-center">
                <i class="fas fa-info-circle mr-3 text-yellow-300"></i>
                Quick Links
            </h3>
            <div class="grid md:grid-cols-4 gap-4">
                <a href="<?php echo $config['ghg_system'] . 'login.php'; ?>" class="text-purple-100 hover:text-white transition-colors flex items-center">
                    <i class="fas fa-sign-in-alt mr-2"></i> GHG Login
                </a>
                <a href="<?php echo $config['main_website']; ?>" class="text-purple-100 hover:text-white transition-colors flex items-center">
                    <i class="fas fa-home mr-2"></i> Main Home
                </a>
                <a href="#contact" class="text-purple-100 hover:text-white transition-colors flex items-center">
                    <i class="fas fa-envelope mr-2"></i> Support
                </a>
                <a href="#about" class="text-purple-100 hover:text-white transition-colors flex items-center">
                    <i class="fas fa-info-circle mr-2"></i> About
                </a>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center text-purple-100">
            <p class="mb-2">© 2026 <?php echo $config['site_name']; ?>. All rights reserved.</p>
            <p class="text-sm text-purple-200">Committed to environmental sustainability</p>
        </div>
    </div>
</body>
</html>
