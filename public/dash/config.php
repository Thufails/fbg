<?php
// This legacy dashboard is included from an authenticated Laravel route.
// Refuse direct requests so this file cannot expose database-backed reports.
if (!function_exists('config')) {
    http_response_code(403);
    exit('Akses langsung tidak diizinkan.');
}

$host = '76.13.19.70';
$port = 3306;
$dbname = 'fbg_sbs';
$username = 'n8n_user';
$password = 'StrongPassword123!';
$charset = 'utf8';

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    // Avoid exposing connection details or credentials in browser output.
    error_log('Legacy dashboard database connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Koneksi database dashboard gagal. Periksa konfigurasi lokal.');
}
