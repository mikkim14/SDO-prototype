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
            $sql = "SELECT * FROM tbllpg";
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
            $records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            Response::success($records, 'Records retrieved successfully');
            break;
        case 'add_record':
            $date = Helper::getPost('date', '');
            $month = Helper::getPost('month', '');
            $quantity = (float)Helper::getPost('quantity', 0);
            $stmt = $db->prepare("INSERT INTO tbllpg (campus, office, date, month, quantity) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssd", $user['campus'], $user['office'], $date, $month, $quantity);
            if ($stmt->execute()) {
                Helper::logActivity($db, 'Added LPG Record for ' . $user['campus'] . ' - Office: ' . $user['office'], 'LPG Report');
                Response::success(['id' => $db->insert_id], 'Record added successfully');
            } else {
                Response::error('Failed', 500);
            }
            $stmt->close();
            break;
        case 'update_record':
            $id = (int)Helper::getPost('id', 0);
            $date = Helper::getPost('date', '');
            $month = Helper::getPost('month', '');
            $quantity = (float)Helper::getPost('quantity', 0);
            $stmt = $db->prepare("UPDATE tbllpg SET date = ?, month = ?, quantity = ? WHERE id = ? AND campus = ? AND office = ?");    
            $stmt->bind_param("ssdsiss", $date, $month, $quantity, $id, $user['campus'], $user['office']);
            $stmt->execute() ? Response::success([], 'Updated') : Response::error('Failed', 500);
            $stmt->close();
            break;
        case 'delete_record':
            $id = (int)Helper::getPost('id', 0);
            $stmt = $db->prepare("DELETE FROM tbllpg WHERE id = ? AND campus = ? AND office = ?");
            $stmt->bind_param("iss", $id, $user['campus'], $user['office']);
            $stmt->execute() ? Response::success([], 'Deleted') : Response::error('Failed', 500);
            $stmt->close();
            break;
        default:
            Response::error('Unknown action', 400);
    }
} catch (Exception $e) {
    Response::error($e->getMessage(), 500);
}
?>
