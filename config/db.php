<?php
$host = "localhost";
$user = "root";
$pass = "";           // default XAMPP — leave empty
$db   = "ecommerce_db";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("❌ Database connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");



/*
$host = "localhost";
$user = "root";
$pass = "";           // default XAMPP password is empty
$db   = "ecommerce_db";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
*/

?>


