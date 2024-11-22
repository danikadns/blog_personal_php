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

$message = $subject = $success = $error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $message = $_POST['message'];
    $subject = $_POST['subject'];

    $sns = new SnsClient([
        'version' => 'latest',
        'region'  => 'us-east-1',
    ]);

    $topicArn = 'arn:aws:sns:us-east-1:010526258440:notificaciones_blog_personal';

    try {
        $result = $sns->publish([
            'TopicArn' => $topicArn,
            'Message'  => $message,
            'Subject'  => $subject,
        ]);
        $success = "El mensaje fue enviado correctamente.";
        $message = $subject = ''; // Limpia los campos tras enviar
    } catch (Aws\Exception\AwsException $e) {
        $error = "Error al enviar el mensaje: " . $e->getMessage();
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
<body class="bg-gray-100 min-h-screen">
    <?php include 'navbar.php'; ?>

    <div class="container mx-auto py-8">
        <div class="bg-white shadow-md rounded-lg p-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Publicar Anuncio</h1>

            <!-- Mensajes de éxito o error -->
            <?php if ($success): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php elseif ($error): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="admin_publish.php" method="POST" class="space-y-6">
                <div>
                    <label for="subject" class="block text-sm font-bold text-gray-700 mb-2">Asunto:</label>
                    <input type="text" name="subject" id="subject" value="<?= htmlspecialchars($subject) ?>" 
                        required class="w-full border rounded-lg py-2 px-3 focus:outline-none focus:ring focus:ring-blue-300">
                </div>
                <div>
                    <label for="message" class="block text-sm font-bold text-gray-700 mb-2">Mensaje:</label>
                    <textarea name="message" id="message" rows="5" required 
                        class="w-full border rounded-lg py-2 px-3 focus:outline-none focus:ring focus:ring-blue-300"><?= htmlspecialchars($message) ?></textarea>
                </div>
                <button type="submit" 
                    class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-200">
                    Enviar Anuncio
                </button>
            </form>
        </div>
    </div>
</body>
</html>
