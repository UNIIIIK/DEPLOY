<?php
require 'dbconnection.php';

header('Content-Type: application/json');

try {
    // Verified users count
    $stmt = $connection->query("SELECT COUNT(*) as verified FROM students WHERE is_verified = 1");
    $verified = $stmt->fetchColumn();
    
    // Active sessions (simplified - in real app you'd track sessions)
    $activeSessions = 1; // At least the current session
    
    // System health (placeholder - would check system status in real app)
    $systemHealth = 100; 

    echo json_encode([
        'verified' => $verified,
        'sessions' => $activeSessions,
        'health' => $systemHealth
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}