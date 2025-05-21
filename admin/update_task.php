<?php
require '../dbconnection.php';

header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task_id'])) {
    $taskId = $_POST['task_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $due_date = $_POST['due_date'];
    $assigned_to = $_POST['assigned_to'];

    try {
        $stmt = $connection->prepare("UPDATE tasks SET title = ?, description = ?, due_date = ?, assigned_to = ? WHERE task_id = ?");
        if ($stmt->execute([$title, $description, $due_date, $assigned_to, $taskId])) {
            $response['status'] = 'success';
            $response['message'] = 'Task updated successfully';
        } else {
            $response['message'] = 'Failed to update task';
        }
    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request';
}

echo json_encode($response);
?>