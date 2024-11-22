<?php
require 'session_handler.php';
require 'vendor/autoload.php';

use Aws\Sns\SnsClient;

$handler = new MySQLSessionHandler();
session_set_save_handler($handler, true);
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != '1') {
    header('Location: login.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $message = $_POST['message'];

    $sns = new SnsClient([
        'version' => 'latest',
        'region'  => 'us-east-1',
    ]);

    $topicArn = 'arn:aws:sns:us-east-1:010526258440:notificaciones_blog_personal';

    try {
        $result = $sns->publish([
            'TopicArn' => $topicArn,
            'Message'  => $message,
            'Subject'  => 'Anuncio Importante',
        ]);

        echo "Mensaje enviado correctamente.";
    } catch (Aws\Exception\AwsException $e) {
        echo "Error al enviar el mensaje: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publicar Anuncio</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
    <div class="container mx-auto">
        <h1 class="text-3xl font-bold mb-6">Publicar Anuncio</h1>
        <form action="admin_publish.php" method="POST" class="bg-white shadow-md rounded-lg p-6">
            <div class="mb-4">
                <label for="message" class="block text-gray-700 text-sm font-bold mb-2">Mensaje:</label>
                <textarea name="message" id="message" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"></textarea>
            </div>
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">Enviar</button>
        </form>
    </div>
</body>
</html>
