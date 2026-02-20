<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BatStateU Portal - System Access</title>
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
    </style>
</head>
<body>
    <div class="w-full max-w-6xl px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-12">
            <div class="flex justify-center mb-6">
                <div class="relative">
                    <div class="absolute inset-0 bg-white rounded-full blur-lg opacity-30"></div>
                    <img src="static/images/logo.png" alt="BatStateU Logo" class="h-16 w-16 relative">
                </div>
            </div>
            <h1 class="text-5xl font-bold text-white mb-2">BatStateU Portal</h1>
            <p class="text-xl text-purple-100">Access Our Systems</p>
        </div>

        <!-- Systems Grid -->
        <div class="grid md:grid-cols-2 gap-8 mb-12">
            <!-- GHG Management System -->
            <div class="card-hover bg-white rounded-xl overflow-hidden shadow-2xl">
                <div class="bg-gradient-to-r from-green-400 to-green-600 h-32 flex items-center justify-center">
                    <i class="fas fa-leaf text-white text-6xl"></i>
                </div>
                <div class="p-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">GHG Management System</h2>
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
                    <a href="<?php echo isset($ghg_url) ? $ghg_url : '/GHG_PHP/'; ?>" class="block w-full bg-gradient-to-r from-green-400 to-green-600 text-white font-bold py-3 px-6 rounded-lg text-center hover:from-green-500 hover:to-green-700 transition-all">
                        <i class="fas fa-arrow-right mr-2"></i>Access GHG System
                    </a>
                </div>
            </div>

            <!-- Main Website -->
            <div class="card-hover bg-white rounded-xl overflow-hidden shadow-2xl">
                <div class="bg-gradient-to-r from-blue-400 to-blue-600 h-32 flex items-center justify-center">
                    <i class="fas fa-globe text-white text-6xl"></i>
                </div>
                <div class="p-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">Main Website</h2>
                    <p class="text-gray-600 mb-6 leading-relaxed">
                        Visit the main BatStateU website for news, updates, announcements, and general information about our institution.
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
                    <a href="<?php echo isset($main_url) ? $main_url : '/'; ?>" class="block w-full bg-gradient-to-r from-blue-400 to-blue-600 text-white font-bold py-3 px-6 rounded-lg text-center hover:from-blue-500 hover:to-blue-700 transition-all">
                        <i class="fas fa-arrow-right mr-2"></i>Visit Main Website
                    </a>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center text-purple-100">
            <p class="mb-2">© 2026 Batangas State University. All rights reserved.</p>
            <p class="text-sm text-purple-200">Committed to environmental sustainability</p>
        </div>
    </div>
</body>
</html>
