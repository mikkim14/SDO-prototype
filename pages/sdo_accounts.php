<?php
$page_title = 'Account Management - Campus Level';
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
        $campus = $user['campus']; // SDO can only create users for their campus
        
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
            $stmt->bind_param("ssss", $username, $password, $office, $campus);
            if ($stmt->execute()) {
                $message = "Account created successfully";
                $message_type = "success";
                
                // Log activity
                Helper::logActivity($db, $user['username'], 'Created', "User: $username", $campus);
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
        
        if (!empty($password)) {
            $stmt = $db->prepare("UPDATE tblsignin SET password = ?, office = ? WHERE userID = ? AND campus = ?");
            $stmt->bind_param("ssis", $password, $office, $user_id, $user['campus']);
        } else {
            $stmt = $db->prepare("UPDATE tblsignin SET office = ? WHERE userID = ? AND campus = ?");
            $stmt->bind_param("sis", $office, $user_id, $user['campus']);
        }
        
        if ($stmt->execute()) {
            $message = "Account updated successfully";
            $message_type = "success";
            
            // Log activity
            Helper::logActivity($db, $user['username'], 'Updated', "User ID: $user_id", $user['campus']);
        } else {
            $message = "Failed to update account";
            $message_type = "error";
        }
        $stmt->close();
    } elseif ($action === 'delete') {
        $user_id = $_POST['user_id'];
        
        $stmt = $db->prepare("DELETE FROM tblsignin WHERE userID = ? AND campus = ?");
        $stmt->bind_param("is", $user_id, $user['campus']);
        if ($stmt->execute()) {
            $message = "Account deleted successfully";
            $message_type = "success";
            
            // Log activity
            Helper::logActivity($db, $user['username'], 'Deleted', "User ID: $user_id", $user['campus']);
        } else {
            $message = "Failed to delete account";
            $message_type = "error";
        }
        $stmt->close();
    }
}

// Get all users in this campus (excluding CSD and SDO)
$stmt = $db->prepare("SELECT userID, username, office FROM tblsignin WHERE campus = ? AND office NOT IN ('Central Sustainable Office', 'Sustainable Development Office') ORDER BY office, username");
$stmt->bind_param("s", $user['campus']);
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Office types (excluding CSD and SDO)
$office_types = [
    'Environmental Management Unit',
    'Resource Generation Office',
    'General Services Office',
    'Procurement Office'
];
?>

                    <div class="max-w-7xl">
                        <!-- Page Header -->
                        <div class="mb-8">
                            <h1 class="text-4xl font-bold text-gray-800 flex items-center">
                                <i class="fas fa-users text-blue-600 mr-3"></i>
                                Account Management
                            </h1>
                            <p class="text-gray-600 mt-2">
                                <i class="fas fa-map-marker-alt mr-2"></i>
                                <?php echo htmlspecialchars($user['campus']); ?> Campus Offices
                            </p>
                        </div>

                        <?php if ($message): ?>
                        <div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200'; ?>">
                            <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                        <?php endif; ?>

                        <!-- Create New Account -->
                        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                            <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-user-plus text-green-600 mr-2"></i>
                                Create New Account
                            </h2>
                            <form method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <input type="hidden" name="action" value="create">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                                    <input type="text" name="username" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                                    <input type="password" name="password" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Office</label>
                                    <select name="office" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Select Office</option>
                                        <?php foreach ($office_types as $office): ?>
                                            <option value="<?php echo htmlspecialchars($office); ?>"><?php echo htmlspecialchars($office); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="flex items-end">
                                    <button type="submit" class="w-full px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                        <i class="fas fa-plus mr-2"></i>Create Account
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Existing Accounts -->
                        <div class="bg-white rounded-lg shadow-md overflow-hidden">
                            <div class="p-6 border-b border-gray-200">
                                <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                                    <i class="fas fa-list text-blue-600 mr-2"></i>
                                    Existing Accounts (<?php echo count($users); ?>)
                                </h2>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username</th>
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
                                                </div>
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
                                                <button onclick="deleteUser(<?php echo $account['userID']; ?>, '<?php echo htmlspecialchars($account['username'], ENT_QUOTES); ?>')" class="text-red-600 hover:text-red-900">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($users)): ?>
                                        <tr>
                                            <td colspan="3" class="px-6 py-8 text-center text-gray-500">
                                                <i class="fas fa-inbox text-4xl mb-2"></i>
                                                <p>No office accounts found in this campus</p>
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
