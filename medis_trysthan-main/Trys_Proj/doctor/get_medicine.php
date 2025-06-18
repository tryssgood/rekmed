<?php
include '../config/database.php'; // Path ini relatif dari get_medicine.php

header('Content-Type: application/json'); // Penting untuk memberitahu browser bahwa respons adalah JSON

if (isset($_GET['id'])) {
    $id_obat = (int)$_GET['id'];

    // Pastikan ambil semua kolom yang diperlukan, termasuk 'stok'
    $stmt = $conn->prepare("SELECT id_obat, nm_obat, kemasan, harga, stok, keterangan FROM obat WHERE id_obat = ?");
    $stmt->bind_param("i", $id_obat);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $medicine = $result->fetch_assoc();
        echo json_encode(['success' => true, 'medicine' => $medicine]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Obat tidak ditemukan.']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'ID Obat tidak diberikan.']);
}
$conn->close();
?>