<?php
require '../dbconnection.php';

header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task_id'])) {
    $taskId = $_POST['task_id'];

    try {
        $stmt = $connection->prepare("SELECT * FROM tasks WHERE task_id = ?");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($task) {
            $response['status'] = 'success';
            $response['task_id'] = $task['task_id'];
            $response['title'] = $task['title'];
            $response['description'] = $task['description'];
            $response['due_date'] = $task['due_date'];
            $response['assigned_to'] = $task['assigned_to'];
        } else {
            $response['message'] = 'Task not found';
        }
    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request';
}

echo json_encode($response);
?>