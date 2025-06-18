<?php
session_start();
include '../config/database.php';

// Check if user is logged in and is doctor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'dokter') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (isset($_GET['id'])) {
    $id_lab = (int)$_GET['id'];
    
    $stmt = $conn->prepare("
        SELECT l.*, p.nm_pasien, p.no_rm 
        FROM laboratorium l
        JOIN pasien p ON l.id_pasien = p.id_pasien
        WHERE l.id_lab = ?
    ");
    $stmt->bind_param("i", $id_lab);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $lab = $result->fetch_assoc();
        echo json_encode(['success' => true, 'lab' => $lab]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lab result not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
