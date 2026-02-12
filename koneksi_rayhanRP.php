<?php
$databaseRayhanRP = null;

if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

if (!function_exists('mysqli_connect')) {
    error_log("ekstensi mysqli tidak tersedia di PHP.");
    return;
}

$databaseRayhanRP = @mysqli_connect("localhost", "root", "", "rayhanrp_database_ta");
if (!$databaseRayhanRP) {
    error_log("koneksi database gagal: " . mysqli_connect_error());
    return;
}

mysqli_set_charset($databaseRayhanRP, "utf8mb4");
?>
