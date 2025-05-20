<?php
// Loading database connection properties
$properties = parse_ini_file('conexion.properties');

// Database connection parameters
$servername = $properties['servername'];
$username = $properties['username'];
$password = $properties['password'];
$dbname = $properties['dbname'];

try {
    // Creating a PDO connection
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    // Set PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set charset to UTF-8
    $conn->exec("SET NAMES utf8");
} catch(PDOException $e) {
    // Display error message and stop execution
    die("Error de conexión: " . $e->getMessage());
}
?>