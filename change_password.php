<?php
$page_title = 'Change Password';
require_once 'pages/tailwind-header.php';

$message = '';
$error_msg = '';

if (Helper::isPostRequest()) {
    $username = $user['username'];
    $old_password = Helper::getPost('old_password', '');
    $new_password = Helper::getPost('new_password', '');
    $confirm_password = Helper::getPost('confirm_password', '');

    // Validation
    $validator = new Validator();
    $validator->validate('old_password', $old_password, 'required|min:6');
    $validator->validate('new_password', $new_password, 'required|min:6');
    
    if ($validator->fails()) {
        $error_msg = array_values($validator->errors())[0];
    } elseif ($new_password !== $confirm_password) {
        $error_msg = 'New passwords do not match';
    } else {
        // Verify old password
        $stmt = $db->prepare("SELECT password FROM tblsignin WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_data = $result->fetch_assoc();
        $stmt->close();

        if ($user_data && $user_data['password'] === $old_password) {
            // Update password
            $auth = new Auth($database);
            if ($auth->changePassword($username, $new_password)) {
                $message = 'Password changed successfully';
                Helper::logActivity($db, 'Changed password', 'Password Change');
            } else {
                $error_msg = 'Failed to change password. Please try again.';
            }
        } else {
            $error_msg = 'Old password is incorrect';
        }
    }
}
?>

                    <div class="max-w-2xl">
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <div class="mb-6">
                                <h1 class="text-3xl font-bold text-gray-800 flex items-center">
                                    <i class="fas fa-key text-blue-600 mr-3"></i>
                                    Change Password
                                </h1>
                            </div>

                            <?php if (!empty($message)): ?>
                                <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded flex items-center">
                                    <i class="fas fa-check-circle mr-3"></i>
                                    <?php echo htmlspecialchars($message); ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($error_msg)): ?>
                                <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded flex items-center">
                                    <i class="fas fa-exclamation-circle mr-3"></i>
                                    <?php echo htmlspecialchars($error_msg); ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="" class="space-y-4 auto-validate">
                                <div>
                                    <label for="old_password" class="block text-sm font-medium text-gray-700 mb-2">
                                        Current Password <span class="text-red-500">*</span>
                                    </label>
                                    <input 
                                        type="password" 
                                        id="old_password" 
                                        name="old_password" 
                                        required
                                        data-rules="required|min:6"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                        placeholder="Enter your current password"
                                    >
                                </div>

                                <div>
                                    <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">
                                        New Password <span class="text-red-500">*</span>
                                    </label>
                                    <input 
                                        type="password" 
                                        id="new_password" 
                                        name="new_password" 
                                        required
                                        data-rules="required|min:6"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                        placeholder="Enter your new password"
                                    >
                                </div>

                                <div>
                                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                                        Confirm Password <span class="text-red-500">*</span>
                                    </label>
                                    <input 
                                        type="password" 
                                        id="confirm_password" 
                                        name="confirm_password" 
                                        required
                                        data-rules="required|min:6"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                        placeholder="Confirm your new password"
                                    >
                                </div>

                                <div class="flex gap-3 pt-4">
                                    <button 
                                        type="submit"
                                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors flex items-center"
                                    >
                                        <i class="fas fa-save mr-2"></i>Change Password
                                    </button>
                                    <a 
                                        href="emu_dashboard.php"
                                        class="px-6 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded-lg transition-colors flex items-center"
                                    >
                                        <i class="fas fa-times mr-2"></i>Cancel
                                    </a>
                                </div>
                            </form>

                            <div class="mt-6 p-4 bg-blue-50 border-l-4 border-blue-500 text-blue-700 rounded">
                                <p class="text-sm">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    <strong>Password Requirements:</strong> Minimum 6 characters long
                                </p>
                            </div>
                        </div>
                    </div>

<?php require_once 'pages/tailwind-footer.php'; ?>
