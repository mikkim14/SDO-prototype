<?php
$page_title = 'Account Management - System Wide';
require_once 'tailwind-header.php';

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $office = $_POST['office'];
        $campus = $_POST['campus'];
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Check if username already exists
        $stmt = $db->prepare("SELECT userID FROM tblsignin WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $message = "Username already exists";
            $message_type = "error";
        } else {
            // Create user
            $stmt = $db->prepare("INSERT INTO tblsignin (username, password, office, campus) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $hashed_password, $office, $campus);
            if ($stmt->execute()) {
                $message = "Account created successfully";
                $message_type = "success";
                
                // Log activity
                Helper::logActivity($db, $user['username'], 'Created', "User: $username", 'System-wide');
            } else {
                $message = "Failed to create account";
                $message_type = "error";
            }
        }
        $stmt->close();
    } elseif ($action === 'update') {
        $user_id = $_POST['user_id'];
        $password = $_POST['password'];
        $office = $_POST['office'];
        $campus = $_POST['campus'];
        
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE tblsignin SET password = ?, office = ?, campus = ? WHERE userID = ?");
            $stmt->bind_param("sssi", $hashed_password, $office, $campus, $user_id);
        } else {
            $stmt = $db->prepare("UPDATE tblsignin SET office = ?, campus = ? WHERE userID = ?");
            $stmt->bind_param("ssi", $office, $campus, $user_id);
        }
        
        if ($stmt->execute()) {
            $message = "Account updated successfully";
            $message_type = "success";
            
            // Log activity
            Helper::logActivity($db, $user['username'], 'Updated', "User ID: $user_id", 'System-wide');
        } else {
            $message = "Failed to update account";
            $message_type = "error";
        }
        $stmt->close();
    } elseif ($action === 'delete') {
        $user_id = $_POST['user_id'];
        
        // Prevent deletion of CSD accounts
        $stmt = $db->prepare("SELECT office FROM tblsignin WHERE userID = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $target_user = $result->fetch_assoc();
        
        if ($target_user['office'] === 'Central Sustainable Office') {
            $message = "Cannot delete Central Sustainable Office accounts";
            $message_type = "error";
        } else {
            $stmt = $db->prepare("DELETE FROM tblsignin WHERE userID = ?");
            $stmt->bind_param("i", $user_id);
            if ($stmt->execute()) {
                $message = "Account deleted successfully";
                $message_type = "success";
                
                // Log activity
                Helper::logActivity($db, $user['username'], 'Deleted', "User ID: $user_id", 'System-wide');
            } else {
                $message = "Failed to delete account";
                $message_type = "error";
            }
        }
        $stmt->close();
    }
}

// Get filter parameters
$filter_campus = $_GET['campus'] ?? '';
$filter_office = $_GET['office'] ?? '';

// Build query with filters
$query = "SELECT userID, username, office, campus FROM tblsignin WHERE 1=1";
$params = [];
$types = "";

if ($filter_campus) {
    $query .= " AND campus = ?";
    $params[] = $filter_campus;
    $types .= "s";
}

if ($filter_office) {
    $query .= " AND office = ?";
    $params[] = $filter_office;
    $types .= "s";
}

$query .= " ORDER BY campus, office, username";

$stmt = $db->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get all campuses
$campuses = [
    'Alangilan',
    'ARASOF-Nasugbu',
    'Balayan',
    'Central',
    'JPLPC-Malvar',
    'LIMA',
    'Lipa',
    'Lemery',
    'Lobo',
    'Mabini',
    'Pablo Borbon',
    'Rosario',
    'San Juan'
];

// All office types
$office_types = [
    'Central Sustainable Office',
    'Sustainable Development Office',
    'Environmental Management Unit',
    'Resource Generation Office',
    'General Services Office',
    'Procurement Office'
];

// Get statistics
$total_users = count($users);
$stmt = $db->prepare("SELECT campus, COUNT(*) as count FROM tblsignin GROUP BY campus");
$stmt->execute();
$campus_stats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

                    <div class="max-w-7xl">
                        <!-- Page Header -->
                        <div class="mb-8">
                            <h1 class="text-4xl font-bold text-gray-800 flex items-center">
                                <i class="fas fa-users-cog text-blue-600 mr-3"></i>
                                Account Management
                            </h1>
                            <p class="text-gray-600 mt-2">
                                <i class="fas fa-globe mr-2"></i>
                                System-wide User Management (All Campuses)
                            </p>
                        </div>

                        <?php if ($message): ?>
                        <div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200'; ?>">
                            <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                        <?php endif; ?>

                        <!-- Statistics -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-md p-6 text-white">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-blue-100 text-sm">Total Users</p>
                                        <p class="text-3xl font-bold mt-1"><?php echo $total_users; ?></p>
                                    </div>
                                    <i class="fas fa-users text-5xl opacity-20"></i>
                                </div>
                            </div>
                            <?php foreach ($campus_stats as $stat): ?>
                            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($stat['campus']); ?> Campus</p>
                                        <p class="text-2xl font-bold text-gray-800 mt-1"><?php echo $stat['count']; ?> users</p>
                                    </div>
                                    <i class="fas fa-building text-3xl text-blue-200"></i>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Create New Account -->
                        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                            <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-user-plus text-green-600 mr-2"></i>
                                Create New Account
                            </h2>
                            <form method="POST" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                                <input type="hidden" name="action" value="create">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Username *</label>
                                    <input type="text" name="username" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Password *</label>
                                    <input type="password" name="password" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Campus *</label>
                                    <select name="campus" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Select Campus</option>
                                        <?php foreach ($campuses as $campus_opt): ?>
                                            <option value="<?php echo htmlspecialchars($campus_opt); ?>"><?php echo htmlspecialchars($campus_opt); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Office *</label>
                                    <select name="office" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Select Office</option>
                                        <?php foreach ($office_types as $office): ?>
                                            <option value="<?php echo htmlspecialchars($office); ?>"><?php echo htmlspecialchars($office); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="flex items-end">
                                    <button type="submit" class="w-full px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                        <i class="fas fa-plus mr-2"></i>Create
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Filters -->
                        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                            <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-filter text-blue-600 mr-2"></i>
                                Filter Accounts
                            </h2>
                            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Campus</label>
                                    <select name="campus" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">All Campuses</option>
                                        <?php foreach ($campuses as $campus_opt): ?>
                                            <option value="<?php echo htmlspecialchars($campus_opt); ?>" <?php echo $filter_campus === $campus_opt ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($campus_opt); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Office</label>
                                    <select name="office" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">All Offices</option>
                                        <?php foreach ($office_types as $office): ?>
                                            <option value="<?php echo htmlspecialchars($office); ?>" <?php echo $filter_office === $office ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($office); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="flex items-end gap-2">
                                    <button type="submit" class="flex-1 px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                        <i class="fas fa-search mr-2"></i>Filter
                                    </button>
                                    <a href="csd_accounts.php" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </div>
                            </form>
                        </div>

                        <!-- Existing Accounts -->
                        <div class="bg-white rounded-lg shadow-md overflow-hidden">
                            <div class="p-6 border-b border-gray-200">
                                <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                                    <i class="fas fa-list text-blue-600 mr-2"></i>
                                    User Accounts (<?php echo count($users); ?>)
                                </h2>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Campus</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Office</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($users as $account): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <i class="fas fa-user-circle text-gray-400 mr-2"></i>
                                                    <span class="font-medium text-gray-900"><?php echo htmlspecialchars($account['username']); ?></span>
                                                    <?php if ($account['office'] === 'Central Sustainable Office'): ?>
                                                        <span class="ml-2 px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">
                                                            <i class="fas fa-shield-alt"></i> CSD
                                                        </span>
                                                    <?php elseif ($account['office'] === 'Sustainable Development Office'): ?>
                                                        <span class="ml-2 px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-800">
                                                            <i class="fas fa-crown"></i> SDO
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                    <?php echo htmlspecialchars($account['campus']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                                                    <?php echo htmlspecialchars($account['office']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                                <button onclick="editUser(<?php echo htmlspecialchars(json_encode($account)); ?>)" class="text-blue-600 hover:text-blue-900 mr-3">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <?php if ($account['office'] !== 'Central Sustainable Office'): ?>
                                                <button onclick="deleteUser(<?php echo $account['userID']; ?>, '<?php echo htmlspecialchars($account['username'], ENT_QUOTES); ?>')" class="text-red-600 hover:text-red-900">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                                <?php else: ?>
                                                <span class="text-gray-400" title="Cannot delete CSD accounts">
                                                    <i class="fas fa-lock"></i> Protected
                                                </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($users)): ?>
                                        <tr>
                                            <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                                <i class="fas fa-inbox text-4xl mb-2"></i>
                                                <p>No accounts found matching the filters</p>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

<!-- Edit Modal -->
<div id="editModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Edit Account</h3>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="user_id" id="edit_user_id">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                <input type="text" id="edit_username" disabled class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">New Password (leave blank to keep current)</label>
                <input type="password" name="password" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Campus</label>
                <select name="campus" id="edit_campus" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <?php foreach ($campuses as $campus_opt): ?>
                        <option value="<?php echo htmlspecialchars($campus_opt); ?>"><?php echo htmlspecialchars($campus_opt); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Office</label>
                <select name="office" id="edit_office" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <?php foreach ($office_types as $office): ?>
                        <option value="<?php echo htmlspecialchars($office); ?>"><?php echo htmlspecialchars($office); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-save mr-2"></i>Save Changes
                </button>
                <button type="button" onclick="closeEditModal()" class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Confirm Deletion</h3>
            <p class="text-sm text-gray-600 mt-2">Are you sure you want to delete the account for <span id="delete_username" class="font-bold"></span>?</p>
            <p class="text-xs text-red-600 mt-2"><i class="fas fa-exclamation-triangle"></i> This action cannot be undone.</p>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="user_id" id="delete_user_id">
            <div class="flex gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    <i class="fas fa-trash mr-2"></i>Delete
                </button>
                <button type="button" onclick="closeDeleteModal()" class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function editUser(user) {
    document.getElementById('edit_user_id').value = user.userID;
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_campus').value = user.campus;
    document.getElementById('edit_office').value = user.office;
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

function deleteUser(id, username) {
    document.getElementById('delete_user_id').value = id;
    document.getElementById('delete_username').textContent = username;
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}
</script>

<?php require_once 'tailwind-footer.php'; ?>
