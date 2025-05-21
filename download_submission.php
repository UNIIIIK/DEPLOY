<?php
require '../dbconnection.php';
include('../auth_check.php');

if (!isset($_SESSION['is_student'])) {
    header("Location: ../login.php");
    exit();
}

$submissionId = $_GET['id'] ?? 0;

// Verify submission belongs to student
$stmt = $connection->prepare("
    SELECT file_path FROM task_submissions 
    WHERE submission_id = ? AND student_id = ?
");
$stmt->execute([$submissionId, $_SESSION['student']]);
$submission = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$submission || empty($submission['file_path'])) {
    die("File not found");
}

$filePath = $submission['file_path'];

if (file_exists($filePath)) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.basename($filePath).'"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;
} else {
    die("File not found on server");
}