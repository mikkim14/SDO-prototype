<?php
/**
 * Electricity Consumption API
 * Handles AJAX requests for electricity data
 */

require_once '../includes/config.php';
require_once '../includes/GHGCalculator.php';

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
            
            $stmt = $db->prepare("SELECT * FROM electricity_consumption WHERE campus = ? ORDER BY date DESC LIMIT ? OFFSET ?");
            $stmt->bind_param("sii", $user['campus'], $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();
            $records = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            Response::success($records, 'Records retrieved successfully');
            break;

        case 'add_record':
            $date = Helper::getPost('date', '');
            $mains = (float)Helper::getPost('mains', 0);
            $solar = (float)Helper::getPost('solar', 0);
            $generator = (float)Helper::getPost('generator', 0);

            $validator = new Validator();
            $validator->validate('date', $date, 'required|date');
            $validator->validate('mains', $mains, 'required|numeric');

            if ($validator->fails()) {
                Response::error(array_values($validator->errors())[0], 422);
            }

            $stmt = $db->prepare("INSERT INTO electricity_consumption (campus, date, mains, solar, generator) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssddd", $user['campus'], $date, $mains, $solar, $generator);
            
            if ($stmt->execute()) {
                Helper::logActivity($db, 'Added Electricity Record for ' . $user['campus'], 'Electricity Report');
                Response::success(['id' => $db->insert_id], 'Record added successfully');
            } else {
                Response::error('Failed to add record', 500);
            }
            $stmt->close();
            break;

        case 'update_record':
            $id = (int)Helper::getPost('id', 0);
            $date = Helper::getPost('date', '');
            $mains = (float)Helper::getPost('mains', 0);
            $solar = (float)Helper::getPost('solar', 0);
            $generator = (float)Helper::getPost('generator', 0);

            $validator = new Validator();
            $validator->validate('date', $date, 'required|date');
            $validator->validate('mains', $mains, 'required|numeric');

            if ($validator->fails()) {
                Response::error(array_values($validator->errors())[0], 422);
            }

            $stmt = $db->prepare("UPDATE electricity_consumption SET date = ?, mains = ?, solar = ?, generator = ? WHERE id = ? AND campus = ?");
            $stmt->bind_param("sdddis", $date, $mains, $solar, $generator, $id, $user['campus']);
            
            if ($stmt->execute()) {
                Helper::logActivity($db, 'Updated Electricity Record (ID: ' . $id . ')', 'Electricity Report');
                Response::success([], 'Record updated successfully');
            } else {
                Response::error('Failed to update record', 500);
            }
            $stmt->close();
            break;

        case 'delete_record':
            $id = (int)Helper::getPost('id', 0);

            $stmt = $db->prepare("DELETE FROM electricity_consumption WHERE id = ? AND campus = ?");
            $stmt->bind_param("is", $id, $user['campus']);
            
            if ($stmt->execute()) {
                Helper::logActivity($db, 'Deleted Electricity Record (ID: ' . $id . ')', 'Electricity Report');
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
                    SUM(mains) as total_mains,
                    SUM(solar) as total_solar,
                    SUM(generator) as total_generator,
                    AVG(mains) as avg_mains
                FROM electricity_consumption 
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


