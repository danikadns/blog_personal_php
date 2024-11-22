<?php
require 'session_handler.php';
require 'dynamo_activity.php';

$handler = new MySQLSessionHandler();
session_set_save_handler($handler, true);
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include 'db.php';

$alert_message = '';
$alert_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $author_id = isset($_POST['author_id']) ? (int)$_POST['author_id'] : 0;
    $user_id = $_SESSION['user_id'];

    // Verificar
    $check_subscription = $conn->query("SELECT * FROM subscriptions WHERE user_id = $user_id AND author_id = $author_id");
    if ($check_subscription->num_rows > 0) {
        $alert_message = "Ya estás suscrito a este autor.";
        $alert_type = "error";
    } else {
        
        $conn->query("INSERT INTO subscriptions (user_id, author_id) VALUES ($user_id, $author_id)");

        if ($conn->affected_rows > 0) {
            logUserActivity($user_id, 'subscribe', [
                'author_id' => $author_id
            ]);

            $alert_message = "Suscripción exitosa. Ahora recibirás notificaciones de este autor.";
            $alert_type = "success";

            // Redirigir después de 3 seg
            header("Refresh:3; url=author_blogs.php?author_id=" . $author_id);
        } else {
            $alert_message = "Error al suscribirse. Inténtalo de nuevo.";
            $alert_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suscripción</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto py-8">
        <?php if ($alert_message): ?>
            <div class="max-w-xl mx-auto p-4 mb-4 rounded-lg 
                <?= $alert_type === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700' ?>">
                <p class="text-center font-semibold"><?= htmlspecialchars($alert_message) ?></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
