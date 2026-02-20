<?php
$page_title = 'Activity Logs';
require_once 'tailwind-header.php';

// Pagination settings
$records_per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 25;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $records_per_page;

// Filters
$filter_username = $_GET['username'] ?? '';
$filter_action = $_GET['action'] ?? '';
$filter_report = $_GET['report'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';

// Build query with filters
$where_conditions = [];
$params = [];
$types = '';

// Campus filter - non-CSD users only see their campus
if ($user['office'] !== 'Central Sustainable Office') {
    $where_conditions[] = "campus = ?";
    $params[] = $user['campus'];
    $types .= 's';
}

if (!empty($filter_username)) {
    $where_conditions[] = "username LIKE ?";
    $params[] = "%$filter_username%";
    $types .= 's';
}

if (!empty($filter_action)) {
    $where_conditions[] = "action = ?";
    $params[] = $filter_action;
    $types .= 's';
}

if (!empty($filter_report)) {
    $where_conditions[] = "report_name LIKE ?";
    $params[] = "%$filter_report%";
    $types .= 's';
}

if (!empty($filter_date_from)) {
    $where_conditions[] = "DATE(timestamp) >= ?";
    $params[] = $filter_date_from;
    $types .= 's';
}

if (!empty($filter_date_to)) {
    $where_conditions[] = "DATE(timestamp) <= ?";
    $params[] = $filter_date_to;
    $types .= 's';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total records count
$count_query = "SELECT COUNT(*) as total FROM activity_log $where_clause";
$count_stmt = $db->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$count_stmt->close();

$total_pages = ceil($total_records / $records_per_page);

// Get activity logs with filters
$query = "SELECT * FROM activity_log $where_clause ORDER BY timestamp DESC LIMIT ? OFFSET ?";
$params[] = $records_per_page;
$params[] = $offset;
$types .= 'ii';

$stmt = $db->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$activity_logs = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get unique values for filters (campus-aware)
$campus_filter_clause = ($user['office'] !== 'Central Sustainable Office') ? "WHERE campus = '" . $db->real_escape_string($user['campus']) . "'" : "";

$actions_query = "SELECT DISTINCT action FROM activity_log $campus_filter_clause ORDER BY action";
$actions_result = $db->query($actions_query);
$available_actions = $actions_result->fetch_all(MYSQLI_ASSOC);

$users_query = "SELECT DISTINCT username FROM activity_log $campus_filter_clause ORDER BY username";
$users_result = $db->query($users_query);
$available_users = $users_result->fetch_all(MYSQLI_ASSOC);
?>

<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">
            <i class="fas fa-history text-blue-600 mr-3"></i>Activity Logs
        </h1>
        <p class="text-gray-600 mt-2">
            <?php if ($user['office'] === 'Central Sustainable Office'): ?>
                View system-wide activity and change history
            <?php else: ?>
                View activity and change history for <?php echo htmlspecialchars($user['campus']); ?>
            <?php endif; ?>
        </p>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <form method="GET" action="" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Username Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                    <select name="username" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">All Users</option>
                        <?php foreach ($available_users as $u): ?>
                            <option value="<?php echo htmlspecialchars($u['username']); ?>" 
                                    <?php echo $filter_username === $u['username'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($u['username']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Action Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Action</label>
                    <select name="action" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">All Actions</option>
                        <?php foreach ($available_actions as $a): ?>
                            <option value="<?php echo htmlspecialchars($a['action']); ?>" 
                                    <?php echo $filter_action === $a['action'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($a['action']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Report Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Report/Category</label>
                    <input type="text" name="report" value="<?php echo htmlspecialchars($filter_report); ?>" 
                           placeholder="Enter report name" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Date From -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date From</label>
                    <input type="date" name="date_from" value="<?php echo htmlspecialchars($filter_date_from); ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Date To -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date To</label>
                    <input type="date" name="date_to" value="<?php echo htmlspecialchars($filter_date_to); ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Rows per page -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Rows per page</label>
                    <select name="per_page" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="10" <?php echo $records_per_page == 10 ? 'selected' : ''; ?>>10</option>
                        <option value="25" <?php echo $records_per_page == 25 ? 'selected' : ''; ?>>25</option>
                        <option value="50" <?php echo $records_per_page == 50 ? 'selected' : ''; ?>>50</option>
                        <option value="100" <?php echo $records_per_page == 100 ? 'selected' : ''; ?>>100</option>
                        <option value="200" <?php echo $records_per_page == 200 ? 'selected' : ''; ?>>200</option>
                    </select>
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-filter mr-2"></i>Apply Filters
                </button>
                <a href="activity_logs.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    <i class="fas fa-times mr-2"></i>Clear Filters
                </a>
            </div>
        </form>
    </div>

    <!-- Activity Logs Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-800">
                Activity Records (<?php echo number_format($total_records); ?> total)
            </h2>
        </div>

        <?php if (count($activity_logs) > 0): ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-100 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Timestamp</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Username</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Action</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Report/Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($activity_logs as $log): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    <?php echo date('Y-m-d H:i:s', strtotime($log['timestamp'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    <?php echo htmlspecialchars($log['username']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <?php 
                                    $action = htmlspecialchars($log['action']);
                                    $color = 'gray';
                                    $icon = 'fa-info-circle';
                                    
                                    if (strpos($action, 'Created') !== false) {
                                        $color = 'green';
                                        $icon = 'fa-plus-circle';
                                    } elseif (strpos($action, 'Updated') !== false || strpos($action, 'Modified') !== false) {
                                        $color = 'blue';
                                        $icon = 'fa-edit';
                                    } elseif (strpos($action, 'Deleted') !== false) {
                                        $color = 'red';
                                        $icon = 'fa-trash';
                                    }
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-<?php echo $color; ?>-100 text-<?php echo $color; ?>-800">
                                        <i class="fas <?php echo $icon; ?> mr-1"></i>
                                        <?php echo $action; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    <?php echo htmlspecialchars($log['report_name']); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <?php echo htmlspecialchars($log['details'] ?? 'N/A'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-between items-center">
                <div class="text-sm text-gray-700">
                    Showing <?php echo number_format($offset + 1); ?> to <?php echo number_format(min($offset + $records_per_page, $total_records)); ?> 
                    of <?php echo number_format($total_records); ?> entries
                </div>
                <div class="flex gap-2">
                    <?php if ($current_page > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page - 1])); ?>" 
                           class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">Previous</a>
                    <?php endif; ?>

                    <?php
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $current_page + 2);

                    if ($start_page > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" 
                           class="px-3 py-1 bg-white border border-gray-300 rounded hover:bg-gray-50">1</a>
                        <?php if ($start_page > 2): ?>
                            <span class="px-3 py-1">...</span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                           class="px-3 py-1 <?php echo $i == $current_page ? 'bg-blue-600 text-white' : 'bg-white border border-gray-300 hover:bg-gray-50'; ?> rounded">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <span class="px-3 py-1">...</span>
                        <?php endif; ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>" 
                           class="px-3 py-1 bg-white border border-gray-300 rounded hover:bg-gray-50"><?php echo $total_pages; ?></a>
                    <?php endif; ?>

                    <?php if ($current_page < $total_pages): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page + 1])); ?>" 
                           class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="px-6 py-12 text-center text-gray-500">
                <i class="fas fa-calendar-times text-4xl mb-3"></i>
                <p class="text-lg">No activity logs found</p>
                <p class="text-sm mt-2">Try adjusting your filters</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'tailwind-footer.php'; ?>
