<?php
/**
 * Food Consumption API with Office-based Visibility
 */

require_once '../includes/config.php';
require_once '../includes/AccessControl.php';

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

            // Get visibility filter based on user's office
            $filter = AccessControl::getGHGFilterClause($user['office'], $user['campus']);
            
            $sql = "SELECT * FROM tblfoodwaste";
            if ($filter['where_clause']) {
                $sql .= " " . $filter['where_clause'];
            }
            $sql .= " ORDER BY date DESC LIMIT ? OFFSET ?";
            
            $stmt = $db->prepare($sql);
            $params = array_merge($filter['params'], [$limit, $offset]);
            $types = str_repeat('s', count($filter['params'])) . 'ii';
            
            if (count($filter['params']) > 0) {
                $stmt->bind_param($types, ...$params);
            } else {
                $stmt->bind_param('ii', $limit, $offset);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $records = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            Response::success($records, 'Records retrieved successfully');
            break;

        case 'add_record':
            $date = Helper::getPost('date', '');
            $servings = (int)Helper::getPost('servings', 0);
            $food_type = Helper::getPost('food_type', '');

            $validator = new Validator();
            $validator->validate('date', $date, 'required|date');
            $validator->validate('servings', $servings, 'required|numeric');
            $validator->validate('food_type', $food_type, 'required');

            if ($validator->fails()) {
                Response::error(array_values($validator->errors())[0], 422);
            }

            // Insert with office field
            $stmt = $db->prepare("INSERT INTO tblfoodwaste (campus, office, date, servings, food_type) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssds", $user['campus'], $user['office'], $date, $servings, $food_type);

            if ($stmt->execute()) {
                Helper::logActivity($db, 'Added Food Consumption Record for ' . $user['campus'] . ' - Office: ' . $user['office'], 'Food Report');
                Response::success(['id' => $db->insert_id], 'Record added successfully');
            } else {
                Response::error('Failed to add record', 500);
            }
            $stmt->close();
            break;

        case 'update_record':
            $id = (int)Helper::getPost('id', 0);
            $date = Helper::getPost('date', '');
            $servings = (int)Helper::getPost('servings', 0);
            $food_type = Helper::getPost('food_type', '');

            $validator = new Validator();
            $validator->validate('date', $date, 'required|date');
            $validator->validate('servings', $servings, 'required|numeric');
            $validator->validate('food_type', $food_type, 'required');

            if ($validator->fails()) {
                Response::error(array_values($validator->errors())[0], 422);
            }

            // Update with office enforcement
            $stmt = $db->prepare("UPDATE tblfoodwaste SET date = ?, servings = ?, food_type = ? WHERE id = ? AND campus = ? AND office = ?");
            $stmt->bind_param("sdsiss", $date, $servings, $food_type, $id, $user['campus'], $user['office']);

            if ($stmt->execute()) {
                Helper::logActivity($db, 'Updated Food Consumption Record (ID: ' . $id . ')', 'Food Report');
                Response::success([], 'Record updated successfully');
            } else {
                Response::error('Failed to update record', 500);
            }
            $stmt->close();
            break;

        case 'delete_record':
            $id = (int)Helper::getPost('id', 0);

            // Delete with office enforcement
            $stmt = $db->prepare("DELETE FROM tblfoodwaste WHERE id = ? AND campus = ? AND office = ?");
            $stmt->bind_param("iss", $id, $user['campus'], $user['office']);

            if ($stmt->execute()) {
                Helper::logActivity($db, 'Deleted Food Consumption Record (ID: ' . $id . ')', 'Food Report');
                Response::success([], 'Record deleted successfully');
            } else {
                Response::error('Failed to delete record', 500);
            }
            $stmt->close();
            break;

        case 'get_statistics':
            // Get visibility filter for statistics
            $filter = AccessControl::getGHGFilterClause($user['office'], $user['campus']);
            
            $sql = "SELECT
                    COUNT(*) as total_records,
                    SUM(servings) as total_servings,
                    AVG(servings) as avg_servings
                FROM tblfoodwaste";
            
            if ($filter['where_clause']) {
                $sql .= " " . $filter['where_clause'];
            }
            
            $stmt = $db->prepare($sql);
            $params = $filter['params'];
            
            if (count($params) > 0) {
                $types = str_repeat('s', count($params));
                $stmt->bind_param($types, ...$params);
            }
            
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
