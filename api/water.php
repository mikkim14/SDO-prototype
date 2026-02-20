<?php
require_once '../includes/config.php';
require_once '../includes/AccessControl.php';

Auth::requireLogin();

$action = Helper::getPost('action', Helper::getQuery('action', ''));
$user = Auth::getCurrentUser();
header('Content-Type: application/json');

try {
    switch ($action) {
        case 'get_records':
            $limit = (int)(Helper::getPost('limit', 50));
            $offset = (int)(Helper::getPost('offset', 0));
            
            $filter = AccessControl::getGHGFilterClause($user['office'], $user['campus']);
            $sql = "SELECT * FROM water_consumption";
            if ($filter['where_clause']) $sql .= " " . $filter['where_clause'];
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
            $consumption = (float)Helper::getPost('consumption', 0);

            $validator = new Validator();
            $validator->validate('date', $date, 'required|date');
            $validator->validate('consumption', $consumption, 'required|numeric');
            if ($validator->fails()) Response::error(array_values($validator->errors())[0], 422);

            $stmt = $db->prepare("INSERT INTO water_consumption (campus, office, date, consumption) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssd", $user['campus'], $user['office'], $date, $consumption);
            if ($stmt->execute()) {
                Helper::logActivity($db, 'Added Water Record for ' . $user['campus'] . ' - Office: ' . $user['office'], 'Water Report');
                Response::success(['id' => $db->insert_id], 'Record added successfully');
            } else {
                Response::error('Failed to add record', 500);
            }
            $stmt->close();
            break;

        case 'update_record':
            $id = (int)Helper::getPost('id', 0);
            $date = Helper::getPost('date', '');
            $consumption = (float)Helper::getPost('consumption', 0);

            $stmt = $db->prepare("UPDATE water_consumption SET date = ?, consumption = ? WHERE id = ? AND campus = ? AND office = ?");    
            $stmt->bind_param("sdiss", $date, $consumption, $id, $user['campus'], $user['office']);
            if ($stmt->execute()) {
                Response::success([], 'Record updated successfully');
            } else {
                Response::error('Failed to update record', 500);
            }
            $stmt->close();
            break;

        case 'delete_record':
            $id = (int)Helper::getPost('id', 0);
            $stmt = $db->prepare("DELETE FROM water_consumption WHERE id = ? AND campus = ? AND office = ?");
            $stmt->bind_param("iss", $id, $user['campus'], $user['office']);
            if ($stmt->execute()) {
                Response::success([], 'Record deleted successfully');
            } else {
                Response::error('Failed to delete record', 500);
            }
            $stmt->close();
            break;

        default:
            Response::error('Unknown action', 400);
    }
} catch (Exception $e) {
    Response::error($e->getMessage(), 500);
}
?>
