<?php
/**
 * Waste Unsegregation API
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
            
            $stmt = $db->prepare("SELECT * FROM tblsolidwasteunsegregated WHERE campus = ? ORDER BY date DESC LIMIT ? OFFSET ?");
            $stmt->bind_param("sii", $user['campus'], $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();
            $records = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            Response::success($records, 'Records retrieved successfully');
            break;

        case 'add_record':
            $date = Helper::getPost('date', '');
            $amount = (float)Helper::getPost('amount', 0);

            $validator = new Validator();
            $validator->validate('date', $date, 'required|date');
            $validator->validate('amount', $amount, 'required|numeric');

            if ($validator->fails()) {
                Response::error(array_values($validator->errors())[0], 422);
            }

            $stmt = $db->prepare("INSERT INTO tblsolidwasteunsegregated (campus, date, amount) VALUES (?, ?, ?)");
            $stmt->bind_param("ssd", $user['campus'], $date, $amount);
            
            if ($stmt->execute()) {
                Helper::logActivity($db, 'Added Waste Unsegregation Record for ' . $user['campus'], 'Waste Unsegregation Report');
                Response::success(['id' => $db->insert_id], 'Record added successfully');
            } else {
                Response::error('Failed to add record', 500);
            }
            $stmt->close();
            break;

        case 'update_record':
            $id = (int)Helper::getPost('id', 0);
            $date = Helper::getPost('date', '');
            $amount = (float)Helper::getPost('amount', 0);

            $validator = new Validator();
            $validator->validate('date', $date, 'required|date');
            $validator->validate('amount', $amount, 'required|numeric');

            if ($validator->fails()) {
                Response::error(array_values($validator->errors())[0], 422);
            }

            $stmt = $db->prepare("UPDATE tblsolidwasteunsegregated SET date = ?, amount = ? WHERE id = ? AND campus = ?");
            $stmt->bind_param("sdis", $date, $amount, $id, $user['campus']);
            
            if ($stmt->execute()) {
                Helper::logActivity($db, 'Updated Waste Unsegregation Record (ID: ' . $id . ')', 'Waste Unsegregation Report');
                Response::success([], 'Record updated successfully');
            } else {
                Response::error('Failed to update record', 500);
            }
            $stmt->close();
            break;

        case 'delete_record':
            $id = (int)Helper::getPost('id', 0);

            $stmt = $db->prepare("DELETE FROM tblsolidwasteunsegregated WHERE id = ? AND campus = ?");
            $stmt->bind_param("is", $id, $user['campus']);
            
            if ($stmt->execute()) {
                Helper::logActivity($db, 'Deleted Waste Unsegregation Record (ID: ' . $id . ')', 'Waste Unsegregation Report');
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
                FROM tblsolidwasteunsegregated 
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
