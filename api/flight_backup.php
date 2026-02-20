<?php
/**
 * Flight API
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
            
            $stmt = $db->prepare("SELECT * FROM tblflight WHERE campus = ? ORDER BY date DESC LIMIT ? OFFSET ?");
            $stmt->bind_param("sii", $user['campus'], $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();
            $records = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            Response::success($records, 'Records retrieved successfully');
            break;

        case 'add_record':
            $date = Helper::getPost('date', '');
            $destination = Helper::getPost('destination', '');
            $travelers = (int)Helper::getPost('travelers', 0);
            $fuel_consumed = (float)Helper::getPost('fuel_consumed', 0);

            $validator = new Validator();
            $validator->validate('date', $date, 'required|date');
            $validator->validate('destination', $destination, 'required');
            $validator->validate('travelers', $travelers, 'required|numeric');
            $validator->validate('fuel_consumed', $fuel_consumed, 'required|numeric');

            if ($validator->fails()) {
                Response::error(array_values($validator->errors())[0], 422);
            }

            $stmt = $db->prepare("INSERT INTO tblflight (campus, date, destination, travelers, fuel_consumed) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssddd", $user['campus'], $date, $destination, $travelers, $fuel_consumed);
            
            if ($stmt->execute()) {
                Helper::logActivity($db, 'Added Flight Record for ' . $user['campus'], 'Flight Report');
                Response::success(['id' => $db->insert_id], 'Record added successfully');
            } else {
                Response::error('Failed to add record', 500);
            }
            $stmt->close();
            break;

        case 'update_record':
            $id = (int)Helper::getPost('id', 0);
            $date = Helper::getPost('date', '');
            $destination = Helper::getPost('destination', '');
            $travelers = (int)Helper::getPost('travelers', 0);
            $fuel_consumed = (float)Helper::getPost('fuel_consumed', 0);

            $validator = new Validator();
            $validator->validate('date', $date, 'required|date');
            $validator->validate('destination', $destination, 'required');
            $validator->validate('travelers', $travelers, 'required|numeric');
            $validator->validate('fuel_consumed', $fuel_consumed, 'required|numeric');

            if ($validator->fails()) {
                Response::error(array_values($validator->errors())[0], 422);
            }

            $stmt = $db->prepare("UPDATE tblflight SET date = ?, destination = ?, travelers = ?, fuel_consumed = ? WHERE id = ? AND campus = ?");
            $stmt->bind_param("sddis", $date, $destination, $travelers, $fuel_consumed, $id, $user['campus']);
            
            if ($stmt->execute()) {
                Helper::logActivity($db, 'Updated Flight Record (ID: ' . $id . ')', 'Flight Report');
                Response::success([], 'Record updated successfully');
            } else {
                Response::error('Failed to update record', 500);
            }
            $stmt->close();
            break;

        case 'delete_record':
            $id = (int)Helper::getPost('id', 0);

            $stmt = $db->prepare("DELETE FROM tblflight WHERE id = ? AND campus = ?");
            $stmt->bind_param("is", $id, $user['campus']);
            
            if ($stmt->execute()) {
                Helper::logActivity($db, 'Deleted Flight Record (ID: ' . $id . ')', 'Flight Report');
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
                    SUM(travelers) as total_travelers,
                    SUM(fuel_consumed) as total_fuel,
                    AVG(fuel_consumed) as avg_fuel
                FROM tblflight 
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
