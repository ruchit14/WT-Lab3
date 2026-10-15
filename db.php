<?php
// docker setup for backup
const USE_DOCKER = false;

if (USE_DOCKER) {
    $host = "127.0.0.1";
    $port = 3307;
    $user = "lab3user";
    $pass = "lab3pass";
    $dbname = "lab3";
} else {
    $host = "127.0.0.1";
    $port = 3306;
    $user = "student";
    $pass = "Student@123";
    $dbname = "skillswap";
}

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli($host, $user, $pass, $dbname, $port);

if ($conn->connect_error) {
    die("Database connection failed. Check db.php settings and that MySQL is running.");
}
$conn->set_charset("utf8mb4");
