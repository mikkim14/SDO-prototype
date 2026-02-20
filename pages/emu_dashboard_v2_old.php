<?php
$page_title = 'EMU Dashboard';
require_once 'tailwind-header.php';
require_once '../includes/GHGCalculator.php';

// Get statistics
$stats = [];

// Electricity records count
$stmt = $db->prepare("SELECT COUNT(*) as count FROM electricity_consumption WHERE campus = ?");
$stmt->bind_param("s", $user['campus']);
$stmt->execute();
$result = $stmt->get_result();
$stats['electricity'] = $result->fetch_assoc()['count'];
$stmt->close();

// Water records count
$stmt = $db->prepare("SELECT COUNT(*) as count FROM tblwater WHERE campus = ?");
$stmt->bind_param("s", $user['campus']);
$stmt->execute();
$result = $stmt->get_result();
$stats['water'] = $result->fetch_assoc()['count'];
$stmt->close();

// Waste records count
$stmt = $db->prepare("SELECT COUNT(*) as count FROM tblsolidwastesegregated WHERE campus = ?");
$stmt->bind_param("s", $user['campus']);
$stmt->execute();
$result = $stmt->get_result();
$stats['waste'] = $result->fetch_assoc()['count'];
$stmt->close();

// Recent activity
$activity_records = [];
$stmt = $db->prepare("SELECT * FROM activity_log WHERE campus = ? AND username = ? ORDER BY timestamp DESC LIMIT 5");
$stmt->bind_param("ss", $user['campus'], $user['username']);
$stmt->execute();
$result = $stmt->get_result();
$activity_records = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate GHG totals based on EMU role
$ghg_stats = AccessControl::getRoleBasedGHGTotals($db, $user['office'], $user['campus']);
?>

                    <div class="max-w-6xl">
                        <!-- Page Header -->
                        <div class="mb-8">
                            <h1 class="text-4xl font-bold text-gray-800 flex items-center">
                                <i class="fas fa-leaf text-green-600 mr-3"></i>
                                Environmental Management Unit Dashboard
                            </h1>
                            <p class="text-gray-600 mt-2 flex items-center">
                                <i class="fas fa-map-marker-alt mr-2"></i>
                                <?php echo htmlspecialchars($user['campus']); ?> • EMU GHG Only
                            </p>
                            
                            <?php if (isset($_GET['debug'])): ?>
                            <div class="mt-4 p-4 bg-gray-100 rounded text-xs">
                                <strong>Debug Info:</strong>
                                <pre><?php print_r($ghg_stats['debug'] ?? 'No debug data'); ?></pre>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Quick Stats -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                            <!-- Electricity Card -->
                            <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 border border-yellow-200 rounded-lg p-6 hover:shadow-lg transition-shadow">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <div class="text-yellow-600 text-sm font-semibold uppercase mb-2">Electricity Records</div>
                                        <div class="text-3xl font-bold text-yellow-900"><?php echo $stats['electricity']; ?></div>
                                        <p class="text-yellow-700 text-xs mt-2">Total consumption entries</p>
                                    </div>
                                    <i class="fas fa-bolt text-4xl text-yellow-400 opacity-20"></i>
                                </div>
                                <a href="electricity_consumption_v2.php" class="mt-4 inline-block px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg text-sm font-semibold transition-colors">
                                    View Records <i class="fas fa-arrow-right ml-2"></i>
                                </a>
                            </div>

                            <!-- Water Card -->
                            <div class="bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-lg p-6 hover:shadow-lg transition-shadow">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <div class="text-blue-600 text-sm font-semibold uppercase mb-2">Water Records</div>
                                        <div class="text-3xl font-bold text-blue-900"><?php echo $stats['water']; ?></div>
                                        <p class="text-blue-700 text-xs mt-2">Total consumption entries</p>
                                    </div>
                                    <i class="fas fa-droplet text-4xl text-blue-400 opacity-20"></i>
                                </div>
                                <a href="water_consumption_v2.php" class="mt-4 inline-block px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold transition-colors">
                                    View Records <i class="fas fa-arrow-right ml-2"></i>
                                </a>
                            </div>

                            <!-- Waste Card -->
                            <div class="bg-gradient-to-br from-red-50 to-red-100 border border-red-200 rounded-lg p-6 hover:shadow-lg transition-shadow">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <div class="text-red-600 text-sm font-semibold uppercase mb-2">Waste Records</div>
                                        <div class="text-3xl font-bold text-red-900"><?php echo $stats['waste']; ?></div>
                                        <p class="text-red-700 text-xs mt-2">Total waste entries</p>
                                    </div>
                                    <i class="fas fa-trash text-4xl text-red-400 opacity-20"></i>
                                </div>
                                <a href="waste_segregation_v2.php" class="mt-4 inline-block px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-semibold transition-colors">
                                    View Records <i class="fas fa-arrow-right ml-2"></i>
                                </a>
                            </div>
                        </div>

                        
                        <!-- GHG Metrics -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                            <!-- CO2 Emissions Card -->
                            <div class="bg-gradient-to-br from-red-50 to-red-100 border border-red-200 rounded-lg p-6 hover:shadow-lg transition-shadow">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <div class="text-red-600 text-sm font-semibold uppercase mb-2">Total CO2 Emissions</div>
                                        <div class="text-3xl font-bold text-red-900"><?php echo number_format($ghg_stats['total_kg_co2'] ?? 0, 2); ?></div>
                                        <p class="text-red-700 text-xs mt-2">kg CO2</p>
                                    </div>
                                    <i class="fas fa-cloud text-4xl text-red-400 opacity-20"></i>
                                </div>
                            </div>

                            <!-- Tree Offset Card -->
                            <div class="bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-lg p-6 hover:shadow-lg transition-shadow">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <div class="text-green-600 text-sm font-semibold uppercase mb-2">Trees to Offset</div>
                                        <div class="text-3xl font-bold text-green-900"><?php echo number_format($ghg_stats['tree_offset'] ?? 0); ?></div>
                                        <p class="text-green-700 text-xs mt-2">Needed annually (<?php echo number_format($ghg_stats['t_co2'] ?? 0, 2); ?> tCO2)</p>
                                    </div>
                                    <i class="fas fa-tree text-4xl text-green-400 opacity-20"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Access Menu -->
                        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                            <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-th-large text-blue-600 mr-2"></i>
                                Quick Access
                            </h2>
                            
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                <a href="electricity_consumption_v2.php" class="p-4 bg-yellow-50 hover:bg-yellow-100 rounded-lg transition-colors border border-yellow-200 text-center">
                                    <i class="fas fa-bolt text-yellow-600 text-2xl mb-2"></i>
                                    <p class="font-semibold text-gray-800 text-sm">Electricity</p>
                                </a>
                                
                                <a href="water_consumption_v2.php" class="p-4 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors border border-blue-200 text-center">
                                    <i class="fas fa-droplet text-blue-600 text-2xl mb-2"></i>
                                    <p class="font-semibold text-gray-800 text-sm">Water</p>
                                </a>
                                
                                <a href="treated_water_v2.php" class="p-4 bg-cyan-50 hover:bg-cyan-100 rounded-lg transition-colors border border-cyan-200 text-center">
                                    <i class="fas fa-faucet text-cyan-600 text-2xl mb-2"></i>
                                    <p class="font-semibold text-gray-800 text-sm">Treated Water</p>
                                </a>
                                
                                <a href="waste_segregation_v2.php" class="p-4 bg-red-50 hover:bg-red-100 rounded-lg transition-colors border border-red-200 text-center">
                                    <i class="fas fa-trash text-red-600 text-2xl mb-2"></i>
                                    <p class="font-semibold text-gray-800 text-sm">Waste Seg.</p>
                                </a>
                                
                                <a href="waste_unsegregation_v2.php" class="p-4 bg-green-50 hover:bg-green-100 rounded-lg transition-colors border border-green-200 text-center">
                                    <i class="fas fa-recycle text-green-600 text-2xl mb-2"></i>
                                    <p class="font-semibold text-gray-800 text-sm">Waste Unseg.</p>
                                </a>
                            </div>
                        </div>
                    </div>

<?php require_once 'tailwind-footer.php'; ?>



