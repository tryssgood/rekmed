<?php
session_start();
include 'config/database.php';

$error = '';
$success = '';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Process registration form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];
    
    // Patient specific fields
    $nm_pasien = $_POST['nm_pasien'] ?? '';
    $tgl_lhr = $_POST['tgl_lhr'] ?? '';
    $alamat = $_POST['alamat'] ?? '';
    $telepon = $_POST['telepon'] ?? '';
    $no_hp = $_POST['no_hp'] ?? '';
    $jenis_kelamin = $_POST['jenis_kelamin'] ?? '';
    $nm_kk = $_POST['nm_kk'] ?? '';
    $hub_kel = $_POST['hub_kel'] ?? '';
    
    // Doctor specific fields
    $nm_dokter = $_POST['nm_dokter'] ?? '';
    $id_poli = $_POST['id_poli'] ?? '';
    $SIP = $_POST['SIP'] ?? '';
    $tempat_lhr = $_POST['tempat_lhr'] ?? '';
    $alamat_dokter = $_POST['alamat_dokter'] ?? '';
    $no_hp_dokter = $_POST['no_hp_dokter'] ?? '';
    
    // Admin specific fields
    $nm_admin = $_POST['nm_admin'] ?? '';
    $email_admin = $_POST['email_admin'] ?? '';
    
    // Validation
    if (empty($username) || empty($password) || empty($confirm_password) || empty($role)) {
        $error = "Semua field wajib diisi.";
    } elseif ($password !== $confirm_password) {
        $error = "Password dan konfirmasi password tidak cocok.";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter.";
    } else {
        // Role-specific validation
        if ($role == 'pasien') {
            if (empty($nm_pasien) || empty($tgl_lhr) || empty($jenis_kelamin) || empty($alamat) || empty($no_hp)) {
                $error = "Semua field pasien wajib diisi.";
            }
        } elseif ($role == 'dokter') {
            if (empty($nm_dokter) || empty($id_poli) || empty($SIP) || empty($no_hp_dokter)) {
                $error = "Semua field dokter wajib diisi.";
            }
        } elseif ($role == 'admin') {
            if (empty($nm_admin) || empty($email_admin)) {
                $error = "Nama admin dan email wajib diisi.";
            }
        }
        
        if (empty($error)) {
            // Check if username already exists
            $stmt = $conn->prepare("SELECT id_user FROM login WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Username sudah digunakan.";
            } else {
                // Begin transaction
                $conn->begin_transaction();
                
                try {
                    // Hash password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insert user account
                    $stmt = $conn->prepare("INSERT INTO login (username, password, role) VALUES (?, ?, ?)");
                    $stmt->bind_param("sss", $username, $hashed_password, $role);
                    $stmt->execute();
                    $user_id = $conn->insert_id;
                    
                    // Insert role-specific data
                    if ($role == 'pasien') {
                        // Generate medical record number
                        $no_rm = generateMedicalRecordNumber($conn);
                        
                        $stmt = $conn->prepare("INSERT INTO pasien (no_rm, nm_pasien, tgl_lhr, alamat, telepon, no_hp, jenis_kelamin, nm_kk, hub_kel, id_user) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("sssssssssi", $no_rm, $nm_pasien, $tgl_lhr, $alamat, $telepon, $no_hp, $jenis_kelamin, $nm_kk, $hub_kel, $user_id);
                        $stmt->execute();
                        
                        $success = "Registrasi pasien berhasil! No. Rekam Medis: $no_rm. Silakan login dengan akun Anda.";
                    } elseif ($role == 'dokter') {
                        $stmt = $conn->prepare("INSERT INTO dokter (id_poli, id_user, nm_dokter, SIP, tempat_lhr, no_hp, alamat) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("iisssss", $id_poli, $user_id, $nm_dokter, $SIP, $tempat_lhr, $no_hp_dokter, $alamat_dokter);
                        $stmt->execute();
                        
                        $success = "Registrasi dokter berhasil! Silakan login dengan akun Anda.";
                    } elseif ($role == 'admin') {
                        // Create admin profile table if not exists
                        $conn->query("CREATE TABLE IF NOT EXISTS admin_profile (
                            id_admin INT AUTO_INCREMENT PRIMARY KEY,
                            id_user INT,
                            nm_admin VARCHAR(100) NOT NULL,
                            email_admin VARCHAR(100),
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            FOREIGN KEY (id_user) REFERENCES login(id_user) ON DELETE CASCADE
                        )");
                        
                        $stmt = $conn->prepare("INSERT INTO admin_profile (id_user, nm_admin, email_admin) VALUES (?, ?, ?)");
                        $stmt->bind_param("iss", $user_id, $nm_admin, $email_admin);
                        $stmt->execute();
                        
                        $success = "Registrasi admin berhasil! Silakan login dengan akun Anda.";
                    }
                    
                    // Commit transaction
                    $conn->commit();
                    
                    // Clear form data
                    $_POST = array();
                    
                } catch (Exception $e) {
                    // Rollback transaction on error
                    $conn->rollback();
                    $error = "Error: " . $e->getMessage();
                }
            }
        }
    }
}

// Generate medical record number function
function generateMedicalRecordNumber($conn) {
    $prefix = "RM-";
    $year = date('Y');
    $month = date('m');
    
    // Get the last record number
    $sql = "SELECT MAX(CAST(SUBSTRING_INDEX(no_rm, '-', -1) AS UNSIGNED)) as last_number FROM pasien WHERE no_rm LIKE '$prefix$year$month-%'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    
    $last_number = $row['last_number'] ?? 0;
    $next_number = str_pad($last_number + 1, 4, '0', STR_PAD_LEFT);
    
    return $prefix . $year . $month . '-' . $next_number;
}

// Get polyclinics for doctor registration
$polyclinics = $conn->query("SELECT * FROM poliklinik ORDER BY nm_poli");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Sistem Rekam Medis</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="login-page">
    <div class="register-container">
        <div class="register-header">
            <h1>Sistem Rekam Medis</h1>
            <p>Daftar Akun Baru</p>
        </div>
        
        <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <?php echo $success; ?>
        </div>
        <?php endif; ?>
        
        <form class="register-form" method="POST" action="">
            <div class="form-section">
                <h3>Informasi Akun</h3>
                
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Username</label>
                    <input type="text" id="username" name="username" value="<?php echo isset($_POST['username']) ? $_POST['username'] : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="password" name="password" required>
                    <small>Minimal 6 karakter</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password"><i class="fas fa-lock"></i> Konfirmasi Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <div class="form-group">
                    <label for="role"><i class="fas fa-user-tag"></i> Role</label>
                    <select id="role" name="role" onchange="toggleRoleFields()" required>
                        <option value="">-- Pilih Role --</option>
                        <option value="pasien" <?php echo (isset($_POST['role']) && $_POST['role'] == 'pasien') ? 'selected' : ''; ?>>Pasien</option>
                        <option value="dokter" <?php echo (isset($_POST['role']) && $_POST['role'] == 'dokter') ? 'selected' : ''; ?>>Dokter</option>
                        <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
            </div>
            
            <!-- Patient Fields -->
            <div id="patient-fields" class="form-section" style="display: none;">
                <h3>Informasi Pasien</h3>
                
                <div class="form-group">
                    <label for="nm_pasien"><i class="fas fa-user"></i> Nama Lengkap</label>
                    <input type="text" id="nm_pasien" name="nm_pasien" value="<?php echo isset($_POST['nm_pasien']) ? $_POST['nm_pasien'] : ''; ?>">
                </div>
                
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="tgl_lhr"><i class="fas fa-calendar"></i> Tanggal Lahir</label>
                        <input type="date" id="tgl_lhr" name="tgl_lhr" value="<?php echo isset($_POST['tgl_lhr']) ? $_POST['tgl_lhr'] : ''; ?>">
                    </div>
                    
                    <div class="form-group col-md-6">
                        <label for="jenis_kelamin"><i class="fas fa-venus-mars"></i> Jenis Kelamin</label>
                        <select id="jenis_kelamin" name="jenis_kelamin">
                            <option value="">-- Pilih Jenis Kelamin --</option>
                            <option value="L" <?php echo (isset($_POST['jenis_kelamin']) && $_POST['jenis_kelamin'] == 'L') ? 'selected' : ''; ?>>Laki-laki</option>
                            <option value="P" <?php echo (isset($_POST['jenis_kelamin']) && $_POST['jenis_kelamin'] == 'P') ? 'selected' : ''; ?>>Perempuan</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="alamat"><i class="fas fa-map-marker-alt"></i> Alamat</label>
                    <textarea id="alamat" name="alamat" rows="3"><?php echo isset($_POST['alamat']) ? $_POST['alamat'] : ''; ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="telepon"><i class="fas fa-phone"></i> Telepon</label>
                        <input type="text" id="telepon" name="telepon" value="<?php echo isset($_POST['telepon']) ? $_POST['telepon'] : ''; ?>">
                    </div>
                    
                </div>
                
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="nm_kk"><i class="fas fa-users"></i> Nama Kepala Keluarga</label>
                        <input type="text" id="nm_kk" name="nm_kk" value="<?php echo isset($_POST['nm_kk']) ? $_POST['nm_kk'] : ''; ?>">
                    </div>
                    
                </div>
            </div>
            
            <!-- Doctor Fields -->
            <div id="doctor-fields" class="form-section" style="display: none;">
                <h3>Informasi Dokter</h3>
                
                <div class="form-group">
                    <label for="nm_dokter"><i class="fas fa-user-md"></i> Nama Dokter</label>
                    <input type="text" id="nm_dokter" name="nm_dokter" value="<?php echo isset($_POST['nm_dokter']) ? $_POST['nm_dokter'] : ''; ?>" placeholder="Dr. Nama Lengkap, Sp.XX">
                </div>
                
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="SIP"><i class="fas fa-id-card"></i> SIP (Surat Izin Praktik)</label>
                        <input type="text" id="SIP" name="SIP" value="<?php echo isset($_POST['SIP']) ? $_POST['SIP'] : ''; ?>" placeholder="SIP/001/2023">
                    </div>
                    
                    <div class="form-group col-md-6">
                        <label for="id_poli"><i class="fas fa-hospital"></i> Poliklinik</label>
                        <select id="id_poli" name="id_poli">
                            <option value="">-- Pilih Poliklinik --</option>
                            <?php while ($poli = $polyclinics->fetch_assoc()): ?>
                            <option value="<?php echo $poli['id_poli']; ?>" <?php echo (isset($_POST['id_poli']) && $_POST['id_poli'] == $poli['id_poli']) ? 'selected' : ''; ?>>
                                <?php echo $poli['nm_poli']; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="tempat_lhr"><i class="fas fa-map-marker-alt"></i> Tempat Lahir</label>
                        <input type="text" id="tempat_lhr" name="tempat_lhr" value="<?php echo isset($_POST['tempat_lhr']) ? $_POST['tempat_lhr'] : ''; ?>" placeholder="Jakarta">
                    </div>
                    
                    <div class="form-group col-md-6">
                        <label for="no_hp_dokter"><i class="fas fa-mobile-alt"></i> No. HP</label>
                        <input type="text" id="no_hp_dokter" name="no_hp_dokter" value="<?php echo isset($_POST['no_hp_dokter']) ? $_POST['no_hp_dokter'] : ''; ?>" placeholder="081234567890">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="alamat_dokter"><i class="fas fa-home"></i> Alamat</label>
                    <textarea id="alamat_dokter" name="alamat_dokter" rows="3" placeholder="Alamat lengkap dokter"><?php echo isset($_POST['alamat_dokter']) ? $_POST['alamat_dokter'] : ''; ?></textarea>
                </div>
            </div>
            
            <!-- Admin Fields -->
            <div id="admin-fields" class="form-section" style="display: none;">
                <h3>Informasi Admin</h3>
                
                <div class="form-group">
                    <label for="nm_admin"><i class="fas fa-user-shield"></i> Nama Admin</label>
                    <input type="text" id="nm_admin" name="nm_admin" value="<?php echo isset($_POST['nm_admin']) ? $_POST['nm_admin'] : ''; ?>" placeholder="Nama Lengkap Admin">
                </div>
                
                <div class="form-group">
                    <label for="email_admin"><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" id="email_admin" name="email_admin" value="<?php echo isset($_POST['email_admin']) ? $_POST['email_admin'] : ''; ?>" placeholder="admin@example.com">
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Catatan:</strong> Akun admin memiliki akses penuh ke sistem. Pastikan informasi yang dimasukkan benar.
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Daftar</button>
            
            <div class="login-link">
                <p>Sudah punya akun? <a href="login.php">Login di sini</a></p>
            </div>
        </form>
    </div>
    
    <script>
        function toggleRoleFields() {
            const role = document.getElementById('role').value;
            const patientFields = document.getElementById('patient-fields');
            const doctorFields = document.getElementById('doctor-fields');
            const adminFields = document.getElementById('admin-fields');
            
            // Hide all fields first
            patientFields.style.display = 'none';
            doctorFields.style.display = 'none';
            adminFields.style.display = 'none';
            
            // Clear required attributes
            clearRequiredAttributes();
            
            // Show relevant fields and set required attributes
            if (role === 'pasien') {
                patientFields.style.display = 'block';
                setRequiredAttributes(['nm_pasien', 'tgl_lhr', 'jenis_kelamin', 'alamat', 'no_hp']);
            } else if (role === 'dokter') {
                doctorFields.style.display = 'block';
                setRequiredAttributes(['nm_dokter', 'id_poli', 'SIP', 'no_hp_dokter']);
            } else if (role === 'admin') {
                adminFields.style.display = 'block';
                setRequiredAttributes(['nm_admin', 'email_admin']);
            }
        }
        
        function clearRequiredAttributes() {
            const allInputs = document.querySelectorAll('#patient-fields input, #patient-fields select, #patient-fields textarea, #doctor-fields input, #doctor-fields select, #doctor-fields textarea, #admin-fields input, #admin-fields select, #admin-fields textarea');
            allInputs.forEach(input => {
                input.required = false;
            });
        }
        
        function setRequiredAttributes(fieldNames) {
            fieldNames.forEach(fieldName => {
                const field = document.querySelector(`[name="${fieldName}"]`);
                if (field) {
                    field.required = true;
                }
            });
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleRoleFields();
        });
        
        // Form validation
        document.querySelector('.register-form').addEventListener('submit', function(e) {
            const role = document.getElementById('role').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Password dan konfirmasi password tidak cocok!');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password minimal 6 karakter!');
                return false;
            }
            
            // Role-specific validation
            if (role === 'pasien') {
                const requiredFields = ['nm_pasien', 'tgl_lhr', 'jenis_kelamin', 'alamat', 'no_hp'];
                for (let field of requiredFields) {
                    const element = document.querySelector(`[name="${field}"]`);
                    if (!element.value.trim()) {
                        e.preventDefault();
                        alert(`Field ${field.replace('_', ' ')} wajib diisi!`);
                        return false;
                    }
                }
            } else if (role === 'dokter') {
                const requiredFields = ['nm_dokter', 'id_poli', 'SIP', 'no_hp_dokter'];
                for (let field of requiredFields) {
                    const element = document.querySelector(`[name="${field}"]`);
                    if (!element.value.trim()) {
                        e.preventDefault();
                        alert(`Field ${field.replace('_', ' ')} wajib diisi!`);
                        return false;
                    }
                }
            } else if (role === 'admin') {
                const requiredFields = ['nm_admin', 'email_admin'];
                for (let field of requiredFields) {
                    const element = document.querySelector(`[name="${field}"]`);
                    if (!element.value.trim()) {
                        e.preventDefault();
                        alert(`Field ${field.replace('_', ' ')} wajib diisi!`);
                        return false;
                    }
                }
                
                // Validate email format
                const email = document.getElementById('email_admin').value;
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    e.preventDefault();
                    alert('Format email tidak valid!');
                    return false;
                }
            }
            
            return true;
        });
    </script>
 
</body>
</html>
