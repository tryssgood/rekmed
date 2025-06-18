<?php
// Database configuration
$host = "localhost";
$username = "root";
$password = "";

// Create connection
$conn = new mysqli($host, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS rekam_medis_db";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
}

// Select the database
$conn->select_db("rekam_medis_db");

// Create tables
$tables = [
    "CREATE TABLE IF NOT EXISTS pasien (
        id_pasien INT AUTO_INCREMENT PRIMARY KEY,
        no_rm VARCHAR(20) UNIQUE NOT NULL,
        nm_pasien VARCHAR(100) NOT NULL,
        tgl_lhr DATE,
        alamat TEXT,
        telepon VARCHAR(15),
        no_hp VARCHAR(15),
        jenis_kelamin ENUM('L', 'P'),
        nm_kk VARCHAR(100),
        hub_kel VARCHAR(50)
    )",
    
    "CREATE TABLE IF NOT EXISTS login (
        id_user INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'dokter', 'pasien') NOT NULL
    )",
    
    "CREATE TABLE IF NOT EXISTS poliklinik (
        id_poli INT AUTO_INCREMENT PRIMARY KEY,
        nm_poli VARCHAR(100) NOT NULL,
        lokasi VARCHAR(100)
    )",
    
    "CREATE TABLE IF NOT EXISTS dokter (
        id_dokter INT AUTO_INCREMENT PRIMARY KEY,
        id_poli INT,
        id_kunjungan INT,
        id_user INT,
        nm_dokter VARCHAR(100) NOT NULL,
        SIP VARCHAR(50),
        tempat_lhr VARCHAR(100),
        no_hp VARCHAR(15),
        alamat TEXT,
        FOREIGN KEY (id_poli) REFERENCES poliklinik(id_poli),
        FOREIGN KEY (id_user) REFERENCES login(id_user)
    )",
    
    "CREATE TABLE IF NOT EXISTS kunjungan (
        id_kunjungan INT AUTO_INCREMENT PRIMARY KEY,
        tgl_kunjungan DATE NOT NULL,
        id_pasien INT,
        id_poli INT,
        jam_kunjungan TIME,
        FOREIGN KEY (id_pasien) REFERENCES pasien(id_pasien),
        FOREIGN KEY (id_poli) REFERENCES poliklinik(id_poli)
    )",
    
    "CREATE TABLE IF NOT EXISTS tindakan (
        id_tindakan INT AUTO_INCREMENT PRIMARY KEY,
        nm_tindakan VARCHAR(100) NOT NULL,
        ket TEXT
    )",
    
    "CREATE TABLE IF NOT EXISTS obat (
        id_obat INT AUTO_INCREMENT PRIMARY KEY,
        nm_obat VARCHAR(100) NOT NULL,
        jml_obat INT,
        ukuran VARCHAR(50),
        harga DECIMAL(10,2)
    )",
    
    "CREATE TABLE IF NOT EXISTS laboratorium (
        id_lab INT AUTO_INCREMENT PRIMARY KEY,
        no_rm VARCHAR(20),
        hasil_lab TEXT,
        ket TEXT,
        FOREIGN KEY (no_rm) REFERENCES pasien(no_rm)
    )",
    
    "CREATE TABLE IF NOT EXISTS rekam_medis (
        no_rm VARCHAR(20),
        id_tindakan INT,
        id_obat INT,
        id_user INT,
        id_pasien INT,
        diagnosa TEXT,
        resep TEXT,
        keluhan TEXT,
        tgl_pemeriksaan DATE,
        ket TEXT,
        PRIMARY KEY (no_rm, id_tindakan, id_obat),
        FOREIGN KEY (no_rm) REFERENCES pasien(no_rm),
        FOREIGN KEY (id_tindakan) REFERENCES tindakan(id_tindakan),
        FOREIGN KEY (id_obat) REFERENCES obat(id_obat),
        FOREIGN KEY (id_user) REFERENCES login(id_user),
        FOREIGN KEY (id_pasien) REFERENCES pasien(id_pasien)
    )"
];

// Execute each table creation query
foreach ($tables as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "Table created successfully<br>";
    } else {
        echo "Error creating table: " . $conn->error . "<br>";
    }
}

// Insert default admin user
$admin_username = "admin";
$admin_password = password_hash("admin123", PASSWORD_DEFAULT);
$admin_role = "admin";

$stmt = $conn->prepare("INSERT INTO login (username, password, role) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $admin_username, $admin_password, $admin_role);

if ($stmt->execute()) {
    echo "Default admin user created successfully<br>";
} else {
    echo "Error creating default admin user: " . $stmt->error . "<br>";
}

// Insert sample data for testing
$sample_data = [
    "INSERT INTO poliklinik (nm_poli, lokasi) VALUES 
        ('Poli Umum', 'Lantai 1'),
        ('Poli Gigi', 'Lantai 1'),
        ('Poli Anak', 'Lantai 2'),
        ('Poli Mata', 'Lantai 2')",
    
    "INSERT INTO tindakan (nm_tindakan, ket) VALUES 
        ('Pemeriksaan Umum', 'Pemeriksaan kesehatan umum'),
        ('Pemeriksaan Gigi', 'Pemeriksaan kesehatan gigi'),
        ('Pemeriksaan Mata', 'Pemeriksaan kesehatan mata'),
        ('Vaksinasi', 'Pemberian vaksin')",
    
    "INSERT INTO obat (nm_obat, jml_obat, ukuran, harga) VALUES 
        ('Paracetamol', 100, '500mg', 5000),
        ('Amoxicillin', 50, '500mg', 15000),
        ('Ibuprofen', 75, '400mg', 8000),
        ('Vitamin C', 200, '500mg', 3000)"
];

// Execute each sample data query
foreach ($sample_data as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "Sample data inserted successfully<br>";
    } else {
        echo "Error inserting sample data: " . $conn->error . "<br>";
    }
}

echo "<br>Setup completed. <a href='../index.php'>Go to login page</a>";

$conn->close();
