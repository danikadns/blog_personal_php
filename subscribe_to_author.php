<?php
require 'session_handler.php';

$handler = new MySQLSessionHandler();
session_set_save_handler($handler, true);
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $author_id = isset($_POST['author_id']) ? (int)$_POST['author_id'] : 0;
    $user_id = $_SESSION['user_id'];

    // Verificar si ya existe una suscripción
    $check_subscription = $conn->query("SELECT * FROM subscriptions WHERE user_id = $user_id AND author_id = $author_id");
    if ($check_subscription->num_rows > 0) {
        echo "Ya estás suscrito a este autor.";
        exit;
    }

    // Insertar la suscripción en la base de datos
    $conn->query("INSERT INTO subscriptions (user_id, author_id) VALUES ($user_id, $author_id)");

    if ($conn->affected_rows > 0) {
        echo "Suscripción exitosa.";
        header('Location: author_blogs.php?author_id=' . $author_id);
        exit;
    } else {
        echo "Error al suscribirse. Inténtalo de nuevo.";
    }
}
?>
