<?php
session_start();
include '../config/database.php';
include '../includes/functions.php';

// Check if user is logged in and is doctor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'dokter') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (isset($_GET['id'])) {
    $id_jadwal = (int)$_GET['id'];
    $doctor_id = getDoctorId($conn, $_SESSION['user_id']);
    
    $stmt = $conn->prepare("SELECT * FROM jadwal_praktik WHERE id_jadwal = ? AND id_dokter = ?");
    $stmt->bind_param("ii", $id_jadwal, $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $schedule = $result->fetch_assoc();
        echo json_encode(['success' => true, 'schedule' => $schedule]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Schedule not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
