<?php
include('dbconnection.php');

header('Content-Type: application/json'); 

$response = [];

if (isset($_POST['id'])) {
    $id = $_POST['id'];

    try {
        $stmt = $connection->prepare("SELECT profile_image FROM students WHERE student_id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $query = "DELETE FROM students WHERE student_id = :id";
            $stmt = $connection->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                if (!empty($user['profile_image']) && file_exists($user['profile_image'])) {
                    unlink($user['profile_image']);
                }

                $response['status'] = "success";
                $response['message'] = "User deleted successfully!";
            } else {
                throw new Exception("Failed to delete user from database.");
            }
        } else {
            throw new Exception("User not found.");
        }
    } catch (PDOException $e) {
        $response['status'] = "error";
        $response['message'] = "Database error: " . $e->getMessage();
    }
} else {
    $response['status'] = "error";
    $response['message'] = "ID not provided.";
}

echo json_encode($response);
?>
