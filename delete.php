<?php
include 'db.php';

require 'session_handler.php';

$handler = new MySQLSessionHandler();
session_set_save_handler($handler, true);
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != '1') {
    header('Location: login.php');
    exit;
}


$id = $_GET['id'];

$sql = "DELETE FROM users WHERE id=$id";
if ($conn->query($sql) === TRUE) {
    header('Location: users.php');
} else {
    echo "Error: " . $conn->error;
}
?>