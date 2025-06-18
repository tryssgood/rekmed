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
    $id_kunjungan = (int)$_GET['id'];
    
    $stmt = $conn->prepare("SELECT * FROM kunjungan WHERE id_kunjungan = ?");
    $stmt->bind_param("i", $id_kunjungan);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $visit = $result->fetch_assoc();
        echo json_encode(['success' => true, 'visit' => $visit]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Visit not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
