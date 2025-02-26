<?php
$host = "localhost";
$user = "root";
$password = "";
$dbname = "food_for_family";

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
