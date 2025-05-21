<?php
require __DIR__ . '/../dbconnection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $due_date = $_POST['due_date'] ?? '';
    $assigned_to = $_POST['assigned_to'] ?? '';

    try {
        $stmt = $connection->prepare("INSERT INTO tasks (title, description, due_date, assigned_to) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $description, $due_date, $assigned_to]);

        header("Location: task.php");
        exit();
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>