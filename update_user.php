<?php
require 'dbconnection.php';
session_start();

header('Content-Type: application/json');

try {
    if (!isset($_POST['user_id'])) {
        throw new Exception('User ID is required');
    }

    $userId = $_POST['user_id'];
    $firstName = $_POST['first_name'] ?? '';
    $lastName = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phoneNumber = $_POST['phone_number'] ?? '';
    $course = $_POST['course'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $birthdate = $_POST['birthdate'] ?? '';
    $address = $_POST['address'] ?? '';

    // Handle file upload
    $profileImage = null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/profiles/';
        
        // Ensure upload directory exists and is writable
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                throw new Exception('Failed to create upload directory');
            }
        }
        
        if (!is_writable($uploadDir)) {
            chmod($uploadDir, 0777);
        }

        $fileInfo = pathinfo($_FILES['profile_image']['name']);
        $extension = strtolower($fileInfo['extension']);
        
        // Validate file type
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($extension, $allowedTypes)) {
            throw new Exception('Invalid file type. Allowed types: ' . implode(', ', $allowedTypes));
        }

        // Generate unique filename
        $newFileName = uniqid('profile_') . '.' . $extension;
        $uploadPath = $uploadDir . $newFileName;

        // Move uploaded file
        if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadPath)) {
            throw new Exception('Failed to move uploaded file');
        }

        // Set proper permissions for the uploaded file
        chmod($uploadPath, 0644);

        $profileImage = $newFileName;
    }

    // Update user information
    $sql = "UPDATE students SET 
            first_name = ?, 
            last_name = ?, 
            email = ?, 
            phone_number = ?, 
            course = ?, 
            gender = ?, 
            birthdate = ?, 
            user_address = ?";

    $params = [$firstName, $lastName, $email, $phoneNumber, $course, $gender, $birthdate, $address];

    if ($profileImage) {
        $sql .= ", profile_image = ?";
        $params[] = $profileImage;
    }

    $sql .= " WHERE student_id = ?";
    $params[] = $userId;

    $stmt = $connection->prepare($sql);
    $result = $stmt->execute($params);

    if ($result) {
        echo json_encode(['status' => 'success', 'message' => 'Profile updated successfully']);
    } else {
        throw new Exception('Failed to update profile');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>