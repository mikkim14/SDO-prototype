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
            $sql = "SELECT * FROM solid_waste_unsegregated";
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
            $quantity = (float)Helper::getPost('quantity', 0);
            $stmt = $db->prepare("INSERT INTO solid_waste_unsegregated (campus, office, date, quantity) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssd", $user['campus'], $user['office'], $date, $quantity);
            if ($stmt->execute()) {
                Helper::logActivity($db, 'Added Waste Record for ' . $user['campus'] . ' - Office: ' . $user['office'], 'Waste Report');
                Response::success(['id' => $db->insert_id], 'Record added successfully');
            } else {
                Response::error('Failed', 500);
            }
            $stmt->close();
            break;
        case 'update_record':
            $id = (int)Helper::getPost('id', 0);
            $date = Helper::getPost('date', '');
            $quantity = (float)Helper::getPost('quantity', 0);
            $stmt = $db->prepare("UPDATE solid_waste_unsegregated SET date = ?, quantity = ? WHERE id = ? AND campus = ? AND office = ?");    
            $stmt->bind_param("sdiss", $date, $quantity, $id, $user['campus'], $user['office']);
            $stmt->execute() ? Response::success([], 'Updated') : Response::error('Failed', 500);
            $stmt->close();
            break;
        case 'delete_record':
            $id = (int)Helper::getPost('id', 0);
            $stmt = $db->prepare("DELETE FROM solid_waste_unsegregated WHERE id = ? AND campus = ? AND office = ?");
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
