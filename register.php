<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/dbconnection.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $user_address = trim($_POST['user_address'] ?? '');
    $birthdate = trim($_POST['birthdate'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        throw new Exception('Please fill in all required fields.');
    }

    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    $verification_code = bin2hex(random_bytes(16));

    $profile_image = NULL;
    if (!empty($_FILES['profile_image']['name'])) {
        $target_dir = "profiles/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $imageFileType = strtolower(pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION));
        $profile_image = uniqid() . "." . $imageFileType;

        if (!move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_dir . $profile_image)) {
            throw new Exception('Failed to upload profile image.');
        }
    }

    $stmt = $connection->prepare("INSERT INTO students 
        (first_name, last_name, email, gender, phone_number, user_address, birthdate, course, profile_image, user_password, verification_code, is_verified, date_created) 
        VALUES 
        (:first_name, :last_name, :email, :gender, :phone_number, :user_address, :birthdate, :course, :profile_image, :user_password, :verification_code, NULL, NOW())");
    $stmt->bindParam(':first_name', $first_name);
    $stmt->bindParam(':last_name', $last_name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':gender', $gender);
    $stmt->bindParam(':phone_number', $phone_number);
    $stmt->bindParam(':user_address', $user_address);
    $stmt->bindParam(':birthdate', $birthdate);
    $stmt->bindParam(':course', $course);
    $stmt->bindParam(':profile_image', $profile_image);
    $stmt->bindParam(':user_password', $hashed_password);
    $stmt->bindParam(':verification_code', $verification_code);

    if (!$stmt->execute()) {
        throw new Exception('Database error: Could not insert user.');
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'sanchezjamesss02@gmail.com'; 
        $mail->Password = 'n m e x a t r d y j w c y j l z';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587; 
        $mail->setFrom('sanchezjamesss02@gmail.com', 'Verification');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Verify Your Account';
        $mail->Body = 'Click this link to verify your account: 
            <a href="http://localhost/termid/verify.php?code=' . urlencode($verification_code) . '">Verify Account</a>';                
        $mail->send();
    } catch (Exception $e) {
        throw new Exception('Email error: ' . $mail->ErrorInfo);
    }

    echo json_encode(['status' => 'success', 'message' => 'Registration successful! Check your email to verify your account.']);
    exit();

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit();
}
?>