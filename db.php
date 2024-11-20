<?php
$servername = "db-proyecto-blog.cx4442k4w78b.us-east-1.rds.amazonaws.com"; 
$username = "admin"; 
$password = "admin123***"; 
$database = "blog_personal"; 


$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

?>