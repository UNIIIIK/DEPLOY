<?php
include('dbconnection.php');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $firstName = trim($_POST["first_name"] ?? "");
    $lastName = trim($_POST["last_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $gender = trim($_POST["gender"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $birthdate = trim($_POST["birthdate"] ?? "");
    $course = trim($_POST["course"] ?? ""); // New Course Field
    $profileImagePath = null;

    if (empty($firstName) || empty($lastName) || empty($email) || empty($gender) || empty($address) || empty($birthdate) || empty($course)) {
        echo json_encode(["status" => "error", "message" => "All fields are required."]);
        exit;
    }

    if (!empty($_FILES["profileImage"]["name"])) {
        $uploadDir = "profiles/";
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $imageName = time() . "_" . basename($_FILES["profileImage"]["name"]);
        $uploadFile = $uploadDir . $imageName;

        if (move_uploaded_file($_FILES["profileImage"]["tmp_name"], $uploadFile)) {
            $profileImagePath = $uploadFile;
        } else {
            echo json_encode(["status" => "error", "message" => "Image upload failed."]);
            exit;
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Please upload a profile image."]);
        exit;
    }

    try {
        $query = "INSERT INTO students (first_name, last_name, email, gender, user_address, birthdate, course, profile_image, date_created) 
                  VALUES (:first_name, :last_name, :email, :gender, :user_address, :birthdate, :course, :profile_image, NOW())";
        $stmt = $connection->prepare($query);
        $stmt->bindParam(':first_name', $firstName, PDO::PARAM_STR);
        $stmt->bindParam(':last_name', $lastName, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':gender', $gender, PDO::PARAM_STR);
        $stmt->bindParam(':user_address', $address, PDO::PARAM_STR);
        $stmt->bindParam(':birthdate', $birthdate, PDO::PARAM_STR);
        $stmt->bindParam(':course', $course, PDO::PARAM_STR);
        $stmt->bindParam(':profile_image', $profileImagePath, PDO::PARAM_STR);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "User added successfully!", "image" => $profileImagePath]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to add user."]);
        }
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
    }
}
exit;
?>
