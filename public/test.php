<?php
$host = 'localhost';
$db   = 'binsysid_sbs';
$user = 'binsysid_sbs'; 
$pass = 'sbsbinsysid';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    echo "Koneksi BERHASIL!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>