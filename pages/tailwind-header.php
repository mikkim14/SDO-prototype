<?php
require_once dirname(__DIR__) . '/includes/config.php';

// Require login for all dashboard pages
Auth::requireLogin();

$user = Auth::getCurrentUser();
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <!-- <title><?php //echo isset($page_title) ? $page_title . ' - BatStateU GHG System' : 'BatStateU GHG Management System'; ?></title>-->
    <title><?php echo isset($page_title) ? $page_title . ' - GHG System' : 'GHG Management System'; ?></title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Styles -->
    <style>
        .sidebar-active {
            @apply bg-blue-600 text-white;
        }
        .dark .sidebar-active {
            @apply bg-blue-700 text-white;
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .toast {
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        * {
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }
    </style>
    
    <!-- Dark Mode Script -->
    <script>
        // Load theme before page renders to prevent flash
        (function() {
            const theme = localStorage.getItem('theme') || 'light';
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
</head>
<body class="bg-gray-100 dark:bg-gray-900">
    <!-- Navigation -->
    <nav class="bg-white dark:bg-gray-800 shadow-lg sticky top-0 z-50">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <div class="flex items-center space-x-3">
                        <img src="../static/images/logo.png" alt="BatStateU Logo" class="h-10 w-10">
                        <span class="text-2xl font-bold text-gray-800 dark:text-gray-100">BatStateU <span class="text-green-600 dark:text-green-400">Greenhouse Gas</span> Management System</span>
                        <!--<i class="fas fa-leaf text-4xl text-green-600 mb-3"></i>
                        <span class="text-2xl font-bold text-gray-800 dark:text-gray-100">CarbonNet <span class="text-green-600 dark:text-green-400">GHG</span> Management System</span>-->
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <!-- Dark Mode Toggle -->
                    <button onclick="toggleDarkMode()" class="p-2 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" title="Toggle Dark Mode">
                        <i class="fas fa-moon dark:hidden text-lg"></i>
                        <i class="fas fa-sun hidden dark:inline text-lg"></i>
                    </button>
                    
                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        <span class="font-semibold"><?php echo htmlspecialchars($user['username']); ?></span>
                        <span class="text-gray-500 dark:text-gray-400"> • <?php echo htmlspecialchars($user['campus']); ?></span>
                    </div>
                    <div class="relative group">
                        <button class="flex items-center space-x-2 text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">
                            <i class="fas fa-user-circle text-2xl"></i>
                        </button>
                        <div class="absolute right-0 w-48 mt-2 bg-white dark:bg-gray-800 rounded-lg shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-10 border dark:border-gray-700">
                            <a href="../change_password.php" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 first:rounded-t-lg text-sm text-gray-700 dark:text-gray-300">
                                <i class="fas fa-key mr-2"></i>Change Password
                            </a>
                            <a href="../logout.php" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 last:rounded-b-lg text-sm text-red-600 dark:text-red-400">
                                <i class="fas fa-sign-out-alt mr-2"></i>Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="flex">
        <!-- Sidebar -->
        <div class="hidden md:flex md:flex-shrink-0">
            <div class="flex flex-col w-64 bg-white dark:bg-gray-800 shadow-lg border-r dark:border-gray-700">
                <div class="flex-1 flex flex-col pt-5 pb-4 overflow-y-auto">
                    <nav class="mt-5 flex-1 px-2 space-y-1">
                        <?php
                        // Define navigation based on office
                        $nav_items = [];
                        switch($user['office']) {
                            case 'Environmental Management Unit':
                                $nav_items = [
                                    ['url' => 'emu_dashboard_v2.php', 'label' => 'Dashboard', 'icon' => 'fa-home'],
                                    ['type' => 'reports', 'label' => 'Reports', 'icon' => 'fa-file-alt', 'submenu' => [
                                        ['type' => 'scope', 'label' => 'By Scope', 'icon' => 'fa-layer-group', 'submenu' => [
                                            ['url' => 'emu_report_scope2.php', 'label' => 'Scope 2 (Electricity)', 'icon' => 'fa-circle'],
                                            ['url' => 'emu_report_scope3.php', 'label' => 'Scope 3 (Water, Waste)', 'icon' => 'fa-circle'],
                                        ]],
                                        ['type' => 'category', 'label' => 'By Category', 'icon' => 'fa-tags', 'submenu' => [
                                            ['url' => 'emu_report_category.php?cat=electricity', 'label' => 'Electricity', 'icon' => 'fa-bolt'],
                                            ['url' => 'emu_report_category.php?cat=water', 'label' => 'Water', 'icon' => 'fa-droplet'],
                                            ['url' => 'emu_report_category.php?cat=treated_water', 'label' => 'Treated Water', 'icon' => 'fa-faucet'],
                                            ['url' => 'emu_report_category.php?cat=waste_segregated', 'label' => 'Waste (Segregated)', 'icon' => 'fa-trash'],
                                            ['url' => 'emu_report_category.php?cat=waste_unsegregated', 'label' => 'Waste (Unsegregated)', 'icon' => 'fa-recycle'],
                                        ]],
                                    ]],
                                    ['type' => 'data_entry', 'label' => 'Data Entry', 'icon' => 'fa-keyboard', 'submenu' => [
                                        ['url' => 'electricity_consumption_v2.php', 'label' => 'Electricity', 'icon' => 'fa-bolt'],
                                        ['url' => 'water_consumption_v2.php', 'label' => 'Water', 'icon' => 'fa-droplet'],
                                        ['url' => 'treated_water_v2.php', 'label' => 'Treated Water', 'icon' => 'fa-faucet'],
                                        ['url' => 'waste_segregation_v2.php', 'label' => 'Waste Segregated', 'icon' => 'fa-trash'],
                                        ['url' => 'waste_unsegregation_v2.php', 'label' => 'Waste Unsegregated', 'icon' => 'fa-recycle'],
                                    ]],
                                    ['url' => 'activity_logs.php', 'label' => 'Activity Logs', 'icon' => 'fa-history'],
                                ];
                                break;
                            case 'Resource Generation Office':
                                $nav_items = [
                                    ['url' => 'rgo_dashboard_v2.php', 'label' => 'Dashboard', 'icon' => 'fa-home'],
                                    ['type' => 'reports', 'label' => 'Reports', 'icon' => 'fa-file-alt', 'submenu' => [
                                        ['type' => 'scope', 'label' => 'By Scope', 'icon' => 'fa-layer-group', 'submenu' => [
                                            ['url' => 'rgo_report_scope1.php', 'label' => 'Scope 1 (LPG)', 'icon' => 'fa-circle'],
                                            ['url' => 'rgo_report_scope3.php', 'label' => 'Scope 3 (Food)', 'icon' => 'fa-circle'],
                                        ]],
                                        ['type' => 'category', 'label' => 'By Category', 'icon' => 'fa-tags', 'submenu' => [
                                            ['url' => 'rgo_report_category.php?cat=lpg', 'label' => 'LPG', 'icon' => 'fa-fire'],
                                            ['url' => 'rgo_report_category.php?cat=food', 'label' => 'Food', 'icon' => 'fa-utensils'],
                                        ]],
                                    ]],
                                    ['type' => 'data_entry', 'label' => 'Data Entry', 'icon' => 'fa-keyboard', 'submenu' => [
                                        ['url' => 'lpg_consumption_v2.php', 'label' => 'LPG Consumption', 'icon' => 'fa-fire'],
                                        ['url' => 'food_consumption_v2.php', 'label' => 'Food Consumption', 'icon' => 'fa-utensils'],
                                    ]],
                                    ['url' => 'activity_logs.php', 'label' => 'Activity Logs', 'icon' => 'fa-history'],
                                ];
                                break;
                            case 'General Services Office':
                                $nav_items = [
                                    ['url' => 'gso_dashboard_v2.php', 'label' => 'Dashboard', 'icon' => 'fa-home'],
                                    ['type' => 'reports', 'label' => 'Reports', 'icon' => 'fa-file-alt', 'submenu' => [
                                        ['type' => 'scope', 'label' => 'By Scope', 'icon' => 'fa-layer-group', 'submenu' => [
                                            ['url' => 'gso_report_scope1.php', 'label' => 'Scope 1 (Fuel)', 'icon' => 'fa-circle'],
                                        ]],
                                        ['type' => 'category', 'label' => 'By Category', 'icon' => 'fa-tags', 'submenu' => [
                                            ['url' => 'gso_report_category.php?cat=fuel', 'label' => 'Fuel', 'icon' => 'fa-gas-pump'],
                                        ]],
                                    ]],
                                    ['type' => 'data_entry', 'label' => 'Data Entry', 'icon' => 'fa-keyboard', 'submenu' => [
                                        ['url' => 'fuel_consumption_v2.php', 'label' => 'Fuel Consumption', 'icon' => 'fa-gas-pump'],
                                    ]],
                                    ['url' => 'activity_logs.php', 'label' => 'Activity Logs', 'icon' => 'fa-history'],
                                ];
                                break;
                            case 'Procurement Office':
                                $nav_items = [
                                    ['url' => 'procurement_dashboard_v2.php', 'label' => 'Dashboard', 'icon' => 'fa-home'],
                                    ['url' => 'food_consumption_v2.php', 'label' => 'Food', 'icon' => 'fa-utensils'],
                                    ['url' => 'activity_logs.php', 'label' => 'Activity Logs', 'icon' => 'fa-history'],
                                ];
                                break;
                            case 'Sustainable Development Office':
                                $nav_items = [
                                    ['url' => 'sdo_dashboard_v2.php', 'label' => 'Dashboard', 'icon' => 'fa-home'],
                                    ['type' => 'reports', 'label' => 'Reports', 'icon' => 'fa-file-alt', 'submenu' => [
                                        ['type' => 'scope', 'label' => 'By Scope', 'icon' => 'fa-layer-group', 'submenu' => [
                                            ['url' => 'sdo_report_scope1.php', 'label' => 'Scope 1 (Fuel, LPG)', 'icon' => 'fa-circle'],
                                            ['url' => 'sdo_report_scope2.php', 'label' => 'Scope 2 (Electricity)', 'icon' => 'fa-circle'],
                                            ['url' => 'sdo_report_scope3.php', 'label' => 'Scope 3 (Others)', 'icon' => 'fa-circle'],
                                        ]],
                                        ['type' => 'category', 'label' => 'By Category', 'icon' => 'fa-tags', 'submenu' => [
                                            ['url' => 'sdo_report_category.php?cat=electricity', 'label' => 'Electricity', 'icon' => 'fa-bolt'],
                                            ['url' => 'sdo_report_category.php?cat=water', 'label' => 'Water', 'icon' => 'fa-droplet'],
                                            ['url' => 'sdo_report_category.php?cat=treated_water', 'label' => 'Treated Water', 'icon' => 'fa-faucet'],
                                            ['url' => 'sdo_report_category.php?cat=waste_segregated', 'label' => 'Waste Segregated', 'icon' => 'fa-recycle'],
                                            ['url' => 'sdo_report_category.php?cat=waste_unsegregated', 'label' => 'Waste Unsegregated', 'icon' => 'fa-dumpster'],
                                            ['url' => 'sdo_report_category.php?cat=lpg', 'label' => 'LPG', 'icon' => 'fa-fire'],
                                            ['url' => 'sdo_report_category.php?cat=fuel', 'label' => 'Fuel', 'icon' => 'fa-gas-pump'],
                                            ['url' => 'sdo_report_category.php?cat=food', 'label' => 'Food', 'icon' => 'fa-utensils'],
                                            ['url' => 'sdo_report_category.php?cat=flight', 'label' => 'Flight', 'icon' => 'fa-plane'],
                                            ['url' => 'sdo_report_category.php?cat=accommodation', 'label' => 'Accommodation', 'icon' => 'fa-hotel'],
                                        ]],
                                    ]],
                                    ['type' => 'data_entry', 'label' => 'Data Entry', 'icon' => 'fa-keyboard', 'submenu' => [
                                        ['url' => 'flight_v2.php', 'label' => 'Flight', 'icon' => 'fa-plane'],
                                        ['url' => 'accommodation_v2.php', 'label' => 'Accommodation', 'icon' => 'fa-hotel'],
                                    ]],
                                    ['url' => 'activity_logs.php', 'label' => 'Activity Logs', 'icon' => 'fa-history'],
                                    ['url' => 'sdo_accounts.php', 'label' => 'Account Management', 'icon' => 'fa-users'],
                                ];
                                break;
                            case 'Central Sustainable Office':
                                $nav_items = [
                                    ['url' => 'csd_dashboard_v2.php', 'label' => 'Dashboard', 'icon' => 'fa-home'],
                                    ['type' => 'reports', 'label' => 'Reports', 'icon' => 'fa-file-alt', 'submenu' => [
                                        ['type' => 'scope', 'label' => 'By Scope', 'icon' => 'fa-layer-group', 'submenu' => [
                                            ['url' => 'report_scope1.php', 'label' => 'Scope 1 (Fuel, LPG)', 'icon' => 'fa-circle'],
                                            ['url' => 'report_scope2.php', 'label' => 'Scope 2 (Electricity)', 'icon' => 'fa-circle'],
                                            ['url' => 'report_scope3.php', 'label' => 'Scope 3 (Others)', 'icon' => 'fa-circle'],
                                        ]],
                                        ['type' => 'category', 'label' => 'By Category', 'icon' => 'fa-tags', 'submenu' => [
                                            ['url' => 'report_category.php?cat=electricity', 'label' => 'Electricity', 'icon' => 'fa-bolt'],
                                            ['url' => 'report_category.php?cat=water', 'label' => 'Water', 'icon' => 'fa-droplet'],
                                            ['url' => 'report_category.php?cat=treated_water', 'label' => 'Treated Water', 'icon' => 'fa-faucet'],
                                            ['url' => 'report_category.php?cat=waste_segregated', 'label' => 'Waste Segregated', 'icon' => 'fa-recycle'],
                                            ['url' => 'report_category.php?cat=waste_unsegregated', 'label' => 'Waste Unsegregated', 'icon' => 'fa-dumpster'],
                                            ['url' => 'report_category.php?cat=lpg', 'label' => 'LPG', 'icon' => 'fa-fire'],
                                            ['url' => 'report_category.php?cat=fuel', 'label' => 'Fuel', 'icon' => 'fa-gas-pump'],
                                            ['url' => 'report_category.php?cat=food', 'label' => 'Food', 'icon' => 'fa-utensils'],
                                            ['url' => 'report_category.php?cat=flight', 'label' => 'Flight', 'icon' => 'fa-plane'],
                                            ['url' => 'report_category.php?cat=accommodation', 'label' => 'Accommodation', 'icon' => 'fa-hotel'],
                                        ]],
                                    ]],
                                    ['url' => 'activity_logs.php', 'label' => 'Activity Logs', 'icon' => 'fa-history'],
                                    ['url' => 'csd_accounts.php', 'label' => 'Account Management', 'icon' => 'fa-users-cog'],
                                ];
                                break;
                        }
                        
                        foreach ($nav_items as $item):
                            if (isset($item['url'])):
                                $is_active = $current_page === basename($item['url'], '.php');
                                $active_class = $is_active ? 'sidebar-active' : '';
                        ?>
                            <a href="<?php echo $item['url']; ?>" 
                               class="group flex items-center px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white transition-colors <?php echo $active_class; ?>">
                                <i class="fas <?php echo $item['icon']; ?> mr-3 w-5"></i>
                                <span><?php echo $item['label']; ?></span>
                            </a>
                        <?php 
                            elseif (isset($item['type']) && $item['type'] === 'reports'):
                        ?>
                            <!-- Reports Collapsible Menu -->
                            <div class="space-y-1">
                                <button onclick="toggleMenu('reports-menu')" class="w-full group px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white transition-colors flex items-center justify-between">
                                    <span>
                                        <i class="fas <?php echo $item['icon']; ?> mr-3 w-5"></i>
                                        <span><?php echo $item['label']; ?></span>
                                    </span>
                                    <i class="fas fa-chevron-down transition-transform" id="reports-menu-icon" style="transform: rotate(180deg);"></i>
                                </button>
                                <div id="reports-menu" class="pl-4 space-y-1">
                                    <?php foreach ($item['submenu'] as $submenu): ?>
                                        <!-- Scope/Category Submenu -->
                                        <div class="space-y-1">
                                            <button onclick="toggleMenu('<?php echo $submenu['type']; ?>-submenu')" class="w-full group px-3 py-2 rounded-md text-xs font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white transition-colors flex items-center justify-between">
                                                <span>
                                                    <i class="fas <?php echo $submenu['icon']; ?> mr-2 w-4"></i>
                                                    <span><?php echo $submenu['label']; ?></span>
                                                </span>
                                                <i class="fas fa-chevron-down transition-transform text-xs" id="<?php echo $submenu['type']; ?>-submenu-icon" style="transform: rotate(180deg);"></i>
                                            </button>
                                            <div id="<?php echo $submenu['type']; ?>-submenu" class="pl-4 space-y-1">
                                                <?php foreach ($submenu['submenu'] as $subitem): 
                                                    $is_active = $current_page === basename($subitem['url'], '.php');
                                                    $active_class = $is_active ? 'bg-blue-50 dark:bg-blue-900 text-blue-600 dark:text-blue-300' : '';
                                                ?>
                                                    <a href="<?php echo $subitem['url']; ?>" 
                                                       class="group px-3 py-1.5 rounded-md text-xs font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white transition-colors flex items-center <?php echo $active_class; ?>">
                                                        <i class="fas <?php echo $subitem['icon']; ?> mr-2 w-3 text-xs"></i>
                                                        <span><?php echo $subitem['label']; ?></span>
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php 
                            elseif (isset($item['type']) && $item['type'] === 'data_entry'):
                        ?>
                            <!-- Data Entry Collapsible Menu -->
                            <div class="space-y-1">
                                <button onclick="toggleMenu('data-entry-menu')" class="w-full group px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white transition-colors flex items-center justify-between">
                                    <span>
                                        <i class="fas <?php echo $item['icon']; ?> mr-3 w-5"></i>
                                        <span><?php echo $item['label']; ?></span>
                                    </span>
                                    <i class="fas fa-chevron-down transition-transform" id="data-entry-menu-icon" style="transform: rotate(180deg);"></i>
                                </button>
                                <div id="data-entry-menu" class="pl-4 space-y-1">
                                    <?php foreach ($item['submenu'] as $subitem): 
                                        $is_active = $current_page === basename($subitem['url'], '.php');
                                        $active_class = $is_active ? 'bg-blue-50 dark:bg-blue-900 text-blue-600 dark:text-blue-300' : '';
                                    ?>
                                        <a href="<?php echo $subitem['url']; ?>" 
                                           class="group px-3 py-2 rounded-md text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white transition-colors flex items-center <?php echo $active_class; ?>">
                                            <i class="fas <?php echo $subitem['icon']; ?> mr-2 w-4"></i>
                                            <span><?php echo $subitem['label']; ?></span>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php 
                            endif;
                        endforeach; ?>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-auto">
            <main class="flex-1 overflow-y-auto">
                <div class="py-6 px-4 sm:px-6 md:px-8">
    
    <script>
        function toggleMenu(menuId) {
            const menu = document.getElementById(menuId);
            const icon = document.getElementById(menuId + '-icon');
            
            if (menu.classList.contains('hidden')) {
                menu.classList.remove('hidden');
                icon.style.transform = 'rotate(180deg)';
            } else {
                menu.classList.add('hidden');
                icon.style.transform = 'rotate(0deg)';
            }
        }
        
        function toggleDarkMode() {
            const html = document.documentElement;
            const isDark = html.classList.contains('dark');
            
            if (isDark) {
                html.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                html.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
        }
    </script>
