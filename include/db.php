<?php
$host = "0.0.0.0";
$user = "root";
$password = "root";
$dbname = "college";

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


?>