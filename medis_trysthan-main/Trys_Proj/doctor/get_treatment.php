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
    $id_tindakan = (int)$_GET['id'];
    
    $stmt = $conn->prepare("SELECT * FROM tindakan WHERE id_tindakan = ?");
    $stmt->bind_param("i", $id_tindakan);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $treatment = $result->fetch_assoc();
        echo json_encode(['success' => true, 'treatment' => $treatment]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Treatment not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
