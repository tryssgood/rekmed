<?php
session_start();
include '../config/database.php';
include '../includes/functions.php'; // Pastikan ini di-include jika Anda menggunakannya (untuk getPatientId)

// PENTING: Set header ini agar browser tahu kita mengembalikan JSON
header('Content-Type: application/json');

// Check if user is logged in and is patient
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pasien') {
    echo json_encode(['status' => 'error', 'message' => 'Akses tidak sah.']);
    exit();
}

// Pastikan request adalah POST dan ada ID kunjungan
// *** PERHATIKAN: Sekarang kita mengharapkan $_POST['id'] BUKAN $_GET['id'] ***
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $visitId = $_POST['id'];
    $patient_id = getPatientId($conn, $_SESSION['user_id']); // Ambil ID pasien yang sedang login

    if (!$patient_id) {
        echo json_encode(['status' => 'error', 'message' => 'Data pasien tidak ditemukan.']);
        exit();
    }

    // Lakukan operasi UPDATE status kunjungan di database
    // Pastikan Anda hanya membatalkan kunjungan yang dimiliki oleh pasien yang sedang login
    $stmt = $conn->prepare("UPDATE kunjungan SET status = 'Batal' WHERE id_kunjungan = ? AND id_pasien = ? AND status != 'Selesai' AND status != 'Batal'");
    // Menambahkan `AND status != 'Selesai' AND status != 'Batal'` untuk mencegah pembatalan kunjungan yang sudah selesai atau sudah dibatalkan.
    
    // Periksa apakah prepare statement berhasil
    if ($stmt === false) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Gagal menyiapkan statement database: ' . $conn->error
        ]);
        exit();
    }

    $stmt->bind_param("ii", $visitId, $patient_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            // Kunjungan berhasil dibatalkan
            echo json_encode([
                'status' => 'success',
                'message' => 'Kunjungan berhasil dibatalkan.'
            ]);
        } else {
            // Ini bisa terjadi jika id_kunjungan tidak ditemukan, bukan milik pasien ini,
            // atau statusnya sudah Selesai/Batal.
            echo json_encode([
                'status' => 'error',
                'message' => 'Kunjungan tidak ditemukan, sudah dibatalkan, atau sudah selesai.'
            ]);
        }
    } else {
        // Kesalahan saat menjalankan query
        echo json_encode([
            'status' => 'error',
            'message' => 'Gagal memperbarui status di database: ' . $stmt->error // Menggunakan $stmt->error untuk pesan error statement
        ]);
    }
    $stmt->close();
} else {
    // Jika bukan POST request atau tidak ada ID
    echo json_encode([
        'status' => 'error',
        'message' => 'Permintaan tidak valid.'
    ]);
}
$conn->close();
?>