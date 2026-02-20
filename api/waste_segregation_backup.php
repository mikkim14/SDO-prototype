<?php
/**
 * Waste Segregation API
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
            
            $stmt = $db->prepare("SELECT * FROM tblsolidwastesegregated WHERE campus = ? ORDER BY date DESC LIMIT ? OFFSET ?");
            $stmt->bind_param("sii", $user['campus'], $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();
            $records = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            Response::success($records, 'Records retrieved successfully');
            break;

        case 'add_record':
            $date = Helper::getPost('date', '');
            $green = (float)Helper::getPost('green', 0);
            $blue = (float)Helper::getPost('blue', 0);
            $yellow = (float)Helper::getPost('yellow', 0);
            $red = (float)Helper::getPost('red', 0);

            $validator = new Validator();
            $validator->validate('date', $date, 'required|date');

            if ($validator->fails()) {
                Response::error(array_values($validator->errors())[0], 422);
            }

            $stmt = $db->prepare("INSERT INTO tblsolidwastesegregated (campus, date, green, blue, yellow, red) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdddd", $user['campus'], $date, $green, $blue, $yellow, $red);
            
            if ($stmt->execute()) {
                Helper::logActivity($db, 'Added Waste Segregation Record for ' . $user['campus'], 'Waste Segregation Report');
                Response::success(['id' => $db->insert_id], 'Record added successfully');
            } else {
                Response::error('Failed to add record', 500);
            }
            $stmt->close();
            break;

        case 'update_record':
            $id = (int)Helper::getPost('id', 0);
            $date = Helper::getPost('date', '');
            $green = (float)Helper::getPost('green', 0);
            $blue = (float)Helper::getPost('blue', 0);
            $yellow = (float)Helper::getPost('yellow', 0);
            $red = (float)Helper::getPost('red', 0);

            $validator = new Validator();
            $validator->validate('date', $date, 'required|date');

            if ($validator->fails()) {
                Response::error(array_values($validator->errors())[0], 422);
            }

            $stmt = $db->prepare("UPDATE tblsolidwastesegregated SET date = ?, green = ?, blue = ?, yellow = ?, red = ? WHERE id = ? AND campus = ?");
            $stmt->bind_param("sddddis", $date, $green, $blue, $yellow, $red, $id, $user['campus']);
            
            if ($stmt->execute()) {
                Helper::logActivity($db, 'Updated Waste Segregation Record (ID: ' . $id . ')', 'Waste Segregation Report');
                Response::success([], 'Record updated successfully');
            } else {
                Response::error('Failed to update record', 500);
            }
            $stmt->close();
            break;

        case 'delete_record':
            $id = (int)Helper::getPost('id', 0);

            $stmt = $db->prepare("DELETE FROM tblsolidwastesegregated WHERE id = ? AND campus = ?");
            $stmt->bind_param("is", $id, $user['campus']);
            
            if ($stmt->execute()) {
                Helper::logActivity($db, 'Deleted Waste Segregation Record (ID: ' . $id . ')', 'Waste Segregation Report');
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
                    SUM(green) as total_green,
                    SUM(blue) as total_blue,
                    SUM(yellow) as total_yellow,
                    SUM(red) as total_red,
                    AVG(green) as avg_green
                FROM tblsolidwastesegregated 
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
