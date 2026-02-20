<?php
/**
 * Fuel Emissions API
 */

require_once '../includes/config.php';

// Require login
Auth::requireLogin();

$action = Helper::getPost('action', Helper::getQuery('action', ''));
$user = Auth::getCurrentUser();

header('Content-Type: application/json');

try {
    switch ($action) {
        case 'get_records':
            $limit = (int)(Helper::getPost('limit', 50));
            $offset = (int)(Helper::getPost('offset', 0));
            
            $stmt = $db->prepare("SELECT * FROM fuel_emissions WHERE campus = ? ORDER BY date DESC LIMIT ? OFFSET ?");
            $stmt->bind_param("sii", $user['campus'], $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();
            $records = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            Response::success($records, 'Records retrieved successfully');
            break;

        case 'add_record':
            $date = Helper::getPost('date', '');
            $fuel_type = Helper::getPost('fuel_type', '');
            $amount = (float)Helper::getPost('amount', 0);

            $validator = new Validator();
            $validator->validate('date', $date, 'required|date');
            $validator->validate('fuel_type', $fuel_type, 'required');
            $validator->validate('amount', $amount, 'required|numeric');

            if ($validator->fails()) {
                Response::error(array_values($validator->errors())[0], 422);
            }

            $stmt = $db->prepare("INSERT INTO fuel_emissions (campus, date, fuel_type, amount) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssd", $user['campus'], $date, $fuel_type, $amount);
            
            if ($stmt->execute()) {
                Helper::logActivity($db, 'Added Fuel Emissions Record for ' . $user['campus'], 'Fuel Report');
                Response::success(['id' => $db->insert_id], 'Record added successfully');
            } else {
                Response::error('Failed to add record', 500);
            }
            $stmt->close();
            break;

        case 'update_record':
            $id = (int)Helper::getPost('id', 0);
            $date = Helper::getPost('date', '');
            $fuel_type = Helper::getPost('fuel_type', '');
            $amount = (float)Helper::getPost('amount', 0);

            $validator = new Validator();
            $validator->validate('date', $date, 'required|date');
            $validator->validate('fuel_type', $fuel_type, 'required');
            $validator->validate('amount', $amount, 'required|numeric');

            if ($validator->fails()) {
                Response::error(array_values($validator->errors())[0], 422);
            }

            $stmt = $db->prepare("UPDATE fuel_emissions SET date = ?, fuel_type = ?, amount = ? WHERE id = ? AND campus = ?");
            $stmt->bind_param("sdsds", $date, $fuel_type, $amount, $id, $user['campus']);
            
            if ($stmt->execute()) {
                Helper::logActivity($db, 'Updated Fuel Emissions Record (ID: ' . $id . ')', 'Fuel Report');
                Response::success([], 'Record updated successfully');
            } else {
                Response::error('Failed to update record', 500);
            }
            $stmt->close();
            break;

        case 'delete_record':
            $id = (int)Helper::getPost('id', 0);

            $stmt = $db->prepare("DELETE FROM fuel_emissions WHERE id = ? AND campus = ?");
            $stmt->bind_param("is", $id, $user['campus']);
            
            if ($stmt->execute()) {
                Helper::logActivity($db, 'Deleted Fuel Emissions Record (ID: ' . $id . ')', 'Fuel Report');
                Response::success([], 'Record deleted successfully');
            } else {
                Response::error('Failed to delete record', 500);
            }
            $stmt->close();
            break;

        case 'get_statistics':
            $stmt = $db->prepare("
                SELECT 
                    COUNT(*) as total_records,
                    SUM(amount) as total_amount,
                    AVG(amount) as avg_amount
                FROM fuel_emissions 
                WHERE campus = ?
            ");
            $stmt->bind_param("s", $user['campus']);
            $stmt->execute();
            $result = $stmt->get_result();
            $stats = $result->fetch_assoc();
            $stmt->close();

            Response::success($stats, 'Statistics retrieved successfully');
            break;

        default:
            Response::error('Unknown action', 400);
    }
} catch (Exception $e) {
    Response::error($e->getMessage(), 500);
}
?>
