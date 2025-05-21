<?php
require 'dbconnection.php';

$query = "SELECT * FROM students";
$stmt = $connection->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$output = '';
foreach ($users as $user) {
    // Fix image path - ensure it always points to profiles directory
    $profileImage = !empty($user['profile_image']) ? 
                   (strpos($user['profile_image'], 'profiles/') === 0 ? 
                   $user['profile_image'] : 'profiles/' . $user['profile_image']) : 
                   'profiles/default.jpg';
    
    $output .= '<tr>
        <td>'.$user['student_id'].'</td>
        <td>'.$user['first_name'].'</td>
        <td>'.$user['last_name'].'</td>
        <td>'.$user['email'].'</td>
        <td>'.$user['gender'].'</td>
        <td>'.$user['user_address'].'</td>
        <td>'.calculateAge($user['birthdate']).'</td>
        <td>'.$user['course'].'</td>
        <td><img src="'.$profileImage.'" width="50" height="50" style="object-fit: cover;"></td>
        <td>
            <button class="btn btn-sm btn-primary editBtn" data-student-id="'.$user['student_id'].'">Edit</button>
            <button class="btn btn-sm btn-danger deleteBtn" data-student-id="'.$user['student_id'].'">Delete</button>
        </td>
    </tr>';
}

function calculateAge($birthdate) {
    $today = new DateTime();
    $birthDate = new DateTime($birthdate);
    return $birthDate->diff($today)->y;
}

echo $output;
?>