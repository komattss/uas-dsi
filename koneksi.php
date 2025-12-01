<?php
// Mengatur informasi koneksi database
$servername = "localhost"; // Ganti dengan host database Anda (biasanya localhost)
$username = "root"; // Ganti dengan username database Anda
$password = ""; // Ganti dengan password database Anda (kosong jika tidak ada)
$dbname = "warkop"; // Nama database yang digunakan

// Membuat koneksi ke database yang ditentukan
$conn = new mysqli($servername, $username, $password, $dbname);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
// Jika database tidak ditemukan (error 1049), coba buat database lalu reconnect
if ($conn->connect_errno) {
    if ($conn->connect_errno == 1049) {
        // Connect tanpa database untuk membuatnya
        $tmp = new mysqli($servername, $username, $password);
        if ($tmp->connect_errno) {
            die("Koneksi gagal: " . $tmp->connect_error);
        }

        $createSql = "CREATE DATABASE IF NOT EXISTS `" . $tmp->real_escape_string($dbname) . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        if (!$tmp->query($createSql)) {
            die("Gagal membuat database: " . $tmp->error);
        }
        $tmp->close();

        // Coba reconnect ke database yang baru dibuat
        $conn = new mysqli($servername, $username, $password, $dbname);
        if ($conn->connect_errno) {
            die("Koneksi gagal: " . $conn->connect_error);
        }
    } else {
        die("Koneksi gagal: " . $conn->connect_error);
    }
}
// Koneksi berhasil (jangan echo ketika file di-include)
?>