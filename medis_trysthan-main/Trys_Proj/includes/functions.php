<?php
// File: ../includes/functions.php

/**
 * Mendapatkan jumlah total catatan dalam sebuah tabel.
 *
 * @param mysqli $conn Objek koneksi database.
 * @param string $table Nama tabel.
 * @return int Jumlah total catatan.
 */
function getCount($conn, $table) {
    // Lebih aman menggunakan prepared statement meskipun hanya untuk nama tabel
    // Namun untuk nama tabel, ini agak rumit karena nama tabel tidak bisa dibind.
    // Pastikan $table hanya berasal dari sumber yang terpercaya (whitelist)
    // Jika tidak, ada risiko SQL injection pada nama tabel.
    // Untuk tujuan praktis dan umum, asumsikan $table sudah aman.
    $sql = "SELECT COUNT(*) as count FROM " . $conn->real_escape_string($table);
    $result = $conn->query($sql);
    if ($result) {
        $row = $result->fetch_assoc();
        return $row['count'] ?? 0;
    }
    return 0; // Return 0 if query fails
}

/**
 * Mendapatkan ID dokter yang terkait dengan ID pengguna.
 *
 * @param mysqli $conn Objek koneksi database.
 * @param int $user_id ID pengguna.
 * @return int|null ID dokter atau null jika tidak ditemukan.
 */
function getDoctorId($conn, $user_id) {
    $stmt = $conn->prepare("SELECT id_dokter FROM dokter WHERE id_user = ?");
    if (!$stmt) {
        // Handle prepare error if needed
        error_log("Prepare failed in getDoctorId: " . $conn->error);
        return null;
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['id_dokter'];
    }
    
    return null;
}

/**
 * Mendapatkan ID pasien yang terkait dengan ID pengguna.
 * Asumsi: tabel 'pasien' memiliki kolom 'id_user'.
 *
 * @param mysqli $conn Objek koneksi database.
 * @param int $user_id ID pengguna.
 * @return int|null ID pasien atau null jika tidak ditemukan.
 */
function getPatientId($conn, $user_id) {
    $stmt = $conn->prepare("SELECT id_pasien FROM pasien WHERE id_user = ?");
    if (!$stmt) {
        error_log("Prepare failed in getPatientId: " . $conn->error);
        return null;
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['id_pasien'];
    }
    
    return null;
}

/**
 * Mendapatkan jumlah pasien yang terdaftar kunjungan hari ini untuk seorang dokter.
 * Nama fungsi disesuaikan dengan penggunaan di profile.php.
 *
 * @param mysqli $conn Objek koneksi database.
 * @param int $doctor_id ID dokter.
 * @return int Jumlah pasien yang terdaftar hari ini.
 */
function getTodayPatientsCount($conn, $doctor_id) { // PERBAIKAN: Mengubah nama fungsi dari getTodayPatients menjadi getTodayPatientsCount
    $today = date('Y-m-d');
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT k.id_pasien) as count 
        FROM kunjungan k
        JOIN poliklinik p ON k.id_poli = p.id_poli 
        JOIN dokter d ON d.id_poli = p.id_poli 
        WHERE d.id_dokter = ? AND k.tgl_kunjungan = ?
    ");
    if (!$stmt) {
        error_log("Prepare failed in getTodayPatientsCount: " . $conn->error); // PERBAIKAN: Log error juga disesuaikan
        return 0;
    }
    $stmt->bind_param("is", $doctor_id, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'] ?? 0;
}

/**
 * Mendapatkan informasi pasien berdasarkan ID pasien.
 *
 * @param mysqli $conn Objek koneksi database.
 * @param int $patient_id ID pasien.
 * @return array|null Informasi pasien atau null jika tidak ditemukan.
 */
function getPatientInfo($conn, $patient_id) {
    $stmt = $conn->prepare("SELECT * FROM pasien WHERE id_pasien = ?");
    if (!$stmt) {
        error_log("Prepare failed in getPatientInfo: " . $conn->error);
        return null;
    }
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Mendapatkan jumlah rekam medis yang tertunda (belum lengkap) untuk seorang dokter.
 * Dianggap 'pending' jika 'keluhan' atau 'tindakan_resep' masih NULL/kosong untuk kunjungan hari ini.
 *
 * @param mysqli $conn Objek koneksi database.
 * @param int $doctor_id ID dokter.
 * @return int Jumlah rekam medis tertunda.
 */
function getPendingRecords($conn, $doctor_id) {
    $today = date('Y-m-d'); 
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM kunjungan k
        JOIN poliklinik p ON k.id_poli = p.id_poli 
        JOIN dokter d ON d.id_poli = p.id_poli 
        WHERE d.id_dokter = ? 
          AND k.tgl_kunjungan = ? 
          AND (k.keluhan IS NULL OR k.keluhan = '' OR k.tindakan_resep IS NULL OR k.tindakan_resep = '')
    ");
    if (!$stmt) {
        error_log("Prepare failed in getPendingRecords: " . $conn->error);
        return 0;
    }
    $stmt->bind_param("is", $doctor_id, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'] ?? 0;
}

/**
 * Menampilkan daftar pasien yang terdaftar kunjungan hari ini untuk seorang dokter.
 *
 * @param mysqli $conn Objek koneksi database.
 * @param int $doctor_id ID dokter.
 */
function displayTodayPatientsList($conn, $doctor_id) {
    $today = date('Y-m-d');
    $stmt = $conn->prepare("
        SELECT p.id_pasien, p.nm_pasien, p.no_rm, k.jam_kunjungan, pl.nm_poli, k.id_kunjungan, k.keluhan, k.tindakan_resep
        FROM kunjungan k
        JOIN pasien p ON k.id_pasien = p.id_pasien
        JOIN poliklinik pl ON k.id_poli = pl.id_poli 
        JOIN dokter d ON d.id_poli = pl.id_poli 
        WHERE d.id_dokter = ? AND k.tgl_kunjungan = ?
        ORDER BY k.jam_kunjungan
    ");
    if (!$stmt) {
        echo '<p class="alert alert-danger">Error preparing statement for displayTodayPatientsList: ' . $conn->error . '</p>';
        return;
    }
    $stmt->bind_param("is", $doctor_id, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo '<table class="data-table">
                <thead>
                    <tr>
                        <th>No. RM</th>
                        <th>Nama Pasien</th>
                        <th>Jam Kunjungan</th>
                        <th>Poliklinik</th>
                        <th>Status Rekam Medis</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>';
        
        while ($row = $result->fetch_assoc()) {
            // Status rekam medis berdasarkan keluhan dan tindakan/resep
            $rm_status = (!empty($row['keluhan']) && !empty($row['tindakan_resep'])) ? '<span class="status-complete">Sudah Diisi</span>' : '<span class="status-pending">Belum Diisi</span>';
            $action_button = '';
            if (empty($row['keluhan']) || empty($row['tindakan_resep'])) { // Jika salah satu kosong, berarti belum diisi lengkap
                 $action_button = '
                    <a href="rekam_medis_form.php?kunjungan_id=' . htmlspecialchars($row['id_kunjungan']) . '" class="btn btn-sm btn-primary">
                        <i class="fas fa-notes-medical"></i> Input Rekam Medis
                    </a>';
            } else {
                 $action_button = '
                    <a href="rekam_medis_form.php?kunjungan_id=' . htmlspecialchars($row['id_kunjungan']) . '&view=true" class="btn btn-sm btn-info">
                        <i class="fas fa-eye"></i> Lihat RM
                    </a>';
            }

            echo '<tr>
                    <td>' . htmlspecialchars($row['no_rm']) . '</td>
                    <td>' . htmlspecialchars($row['nm_pasien']) . '</td>
                    <td>' . htmlspecialchars($row['jam_kunjungan']) . '</td>
                    <td>' . htmlspecialchars($row['nm_poli'] ?? '-') . '</td>
                    <td>' . $rm_status . '</td>
                    <td>' . $action_button . '</td>
                </tr>';
        }
        
        echo '</tbody></table>';
    } else {
        echo '<p class="no-data">Tidak ada pasien untuk hari ini.</p>';
    }
}

/**
 * Menampilkan riwayat kunjungan pasien.
 * Mengambil data dari tabel 'kunjungan'.
 *
 * @param mysqli $conn Objek koneksi database.
 * @param int $patient_id ID pasien.
 */
function displayPatientVisitHistory($conn, $patient_id) {
    $stmt = $conn->prepare("
        SELECT k.tgl_kunjungan, pl.nm_poli, d.nm_dokter, k.keluhan, k.tindakan_resep
        FROM kunjungan k
        LEFT JOIN poliklinik pl ON k.id_poli = pl.id_poli 
        LEFT JOIN dokter d ON d.id_poli = pl.id_poli 
        WHERE k.id_pasien = ?
        ORDER BY k.tgl_kunjungan DESC
        LIMIT 5
    ");
    if (!$stmt) {
        echo '<p class="alert alert-danger">Error preparing statement for displayPatientVisitHistory: ' . $conn->error . '</p>';
        return;
    }
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo '<table class="data-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Poliklinik</th>
                        <th>Dokter</th>
                        <th>Keluhan (Diagnosa)</th>
                        <th>Tindakan/Resep</th>
                    </tr>
                </thead>
                <tbody>';
        
        while ($row = $result->fetch_assoc()) {
            echo '<tr>
                    <td>' . htmlspecialchars(date('d/m/Y', strtotime($row['tgl_kunjungan']))) . '</td>
                    <td>' . htmlspecialchars($row['nm_poli'] ?? '-') . '</td>
                    <td>' . htmlspecialchars($row['nm_dokter'] ?? '-') . '</td>
                    <td>' . htmlspecialchars($row['keluhan'] ?? '-') . '</td>
                    <td>' . htmlspecialchars($row['tindakan_resep'] ?? '-') . '</td>
                </tr>';
        }
        
        echo '</tbody></table>';
    } else {
        echo '<p class="no-data">Tidak ada riwayat kunjungan.</p>';
    }
}

/**
 * Mendapatkan jumlah kunjungan mendatang untuk seorang pasien.
 *
 * @param mysqli $conn Objek koneksi database.
 * @param int $patient_id ID pasien.
 * @return int Jumlah kunjungan mendatang.
 */
function getUpcomingVisits($conn, $patient_id) {
    $today = date('Y-m-d');
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count
        FROM kunjungan
        WHERE id_pasien = ? AND tgl_kunjungan >= ?
    ");
    if (!$stmt) {
        error_log("Prepare failed in getUpcomingVisits: " . $conn->error);
        return 0;
    }
    $stmt->bind_param("is", $patient_id, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'] ?? 0;
}

/**
 * Menampilkan daftar kunjungan mendatang untuk seorang pasien.
 *
 * @param mysqli $conn Objek koneksi database.
 * @param int $patient_id ID pasien.
 */
function displayUpcomingVisits($conn, $patient_id) {
    $today = date('Y-m-d');
    $stmt = $conn->prepare("
        SELECT k.tgl_kunjungan, k.jam_kunjungan, p.nm_poli
        FROM kunjungan k
        JOIN poliklinik p ON k.id_poli = p.id_poli
        WHERE k.id_pasien = ? AND k.tgl_kunjungan >= ?
        ORDER BY k.tgl_kunjungan, k.jam_kunjungan
    ");
    if (!$stmt) {
        echo '<p class="alert alert-danger">Error preparing statement for displayUpcomingVisits: ' . $conn->error . '</p>';
        return;
    }
    $stmt->bind_param("is", $patient_id, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo '<table class="data-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Jam</th>
                        <th>Poliklinik</th>
                    </tr>
                </thead>
                <tbody>';
        
        while ($row = $result->fetch_assoc()) {
            echo '<tr>
                    <td>' . htmlspecialchars(date('d/m/Y', strtotime($row['tgl_kunjungan']))) . '</td>
                    <td>' . htmlspecialchars($row['jam_kunjungan']) . '</td>
                    <td>' . htmlspecialchars($row['nm_poli']) . '</td>
                </tr>';
        }
        
        echo '</tbody></table>';
    } else {
        echo '<p class="no-data">Tidak ada jadwal kunjungan mendatang.</p>';
    }
}

/**
 * Mengubah format tanggal menjadi format Indonesia (contoh: 17 Juni 2025).
 *
 * @param string $date Tanggal dalam format yang dikenali strtotime().
 * @return string Tanggal dalam format Indonesia.
 */
function formatDate($date) {
    // Menangani kasus di mana $date mungkin null, kosong, atau '0000-00-00'
    if (empty($date) || $date === '0000-00-00') {
        return '-'; // Atau string lain yang menunjukkan tanggal tidak tersedia
    }
    
    $timestamp = strtotime($date);
    if ($timestamp === false) { // Periksa jika strtotime gagal (invalid date)
        return '-';
    }

    $months = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $day = date('d', $timestamp);
    $month = $months[date('n', $timestamp) - 1];
    $year = date('Y', $timestamp);
    
    return $day . ' ' . $month . ' ' . $year;
}

/**
 * Memeriksa apakah sebuah catatan (record) ada di tabel berdasarkan kolom dan nilai.
 *
 * @param mysqli $conn Objek koneksi database.
 * @param string $table Nama tabel.
 * @param string $column Nama kolom.
 * @param mixed $value Nilai yang dicari.
 * @return bool True jika catatan ada, False jika tidak.
 */
function recordExists($conn, $table, $column, $value) {
    // Seperti getCount, nama tabel dan kolom tidak bisa dibind langsung.
    // Pastikan $table dan $column berasal dari sumber terpercaya (whitelist)
    // untuk mencegah SQL Injection pada nama tabel/kolom.
    $table = $conn->real_escape_string($table);
    $column = $conn->real_escape_string($column);

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM $table WHERE $column = ?");
    if (!$stmt) {
        error_log("Prepare failed in recordExists: " . $conn->error);
        return false;
    }
    $stmt->bind_param("s", $value); // Asumsi nilai bisa string, sesuaikan jika selalu int/lainnya
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return ($row['count'] ?? 0) > 0;
}

/**
 * Menghasilkan nomor rekam medis yang unik.
 * Format: RM-TAHUNBULAN-NOMORURUT (contoh: RM-202506-0001).
 *
 * @param mysqli $conn Objek koneksi database.
 * @return string Nomor rekam medis yang dihasilkan.
 */
function generateMedicalRecordNumber($conn) {
    $prefix = "RM-";
    $year = date('Y');
    $month = date('m');
    
    // Query untuk mendapatkan nomor urut terakhir dengan prefix tahun dan bulan saat ini
    $sql = "SELECT MAX(CAST(SUBSTRING_INDEX(no_rm, '-', -1) AS UNSIGNED)) as last_number 
             FROM pasien 
             WHERE no_rm LIKE CONCAT(?, ?, ?, '%')"; // Menggunakan CONCAT untuk LIKE pattern
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed in generateMedicalRecordNumber: " . $conn->error);
        return $prefix . $year . $month . '-0001'; // Fallback if prepare fails
    }
    $pattern_prefix = $prefix;
    $pattern_year = $year;
    $pattern_month = $month;
    $stmt->bind_param("sss", $pattern_prefix, $pattern_year, $pattern_month);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $last_number = $row['last_number'] ?? 0;
    $next_number = str_pad($last_number + 1, 4, '0', STR_PAD_LEFT);
    
    return $prefix . $year . $month . '-' . $next_number;
}

?>