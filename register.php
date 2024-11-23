<?php
include 'db.php';
require 'vendor/autoload.php';

use Aws\Sns\SnsClient;
use Aws\Sqs\SqsClient;
use Aws\Exception\AwsException;

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    $description = $_POST['description'];

    if (empty($name) || empty($username) || empty($password) || empty($email)) {
        $error = "Todos los campos son obligatorios.";
    } else {
        // Validar que el email y el username sean únicos
        $email_check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $email_check->bind_param('s', $email);
        $email_check->execute();
        $email_result = $email_check->get_result();

        $username_check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $username_check->bind_param('s', $username);
        $username_check->execute();
        $username_result = $username_check->get_result();

        if ($email_result->num_rows > 0) {
            $error = "El correo electrónico ya está registrado.";
        } elseif ($username_result->num_rows > 0) {
            $error = "El nombre de usuario ya está registrado.";
        } else {
            // Encripta la contraseña
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insertar usuario en la base de datos con role_id predeterminado (2)
            $sql = "INSERT INTO users (name, username, password, email, phone_number, description, role_id) 
                    VALUES ('$name', '$username', '$hashed_password', '$email', '$phone_number', '$description', 2)";

            if ($conn->query($sql) === TRUE) {
                // Configurar SQS y SNS
                $sqs = new SqsClient([
                    'version' => 'latest',
                    'region'  => 'us-east-1',
                ]);

                $sns = new SnsClient([
                    'version' => 'latest',
                    'region'  => 'us-east-1',
                ]);

                $queueUrl = 'https://sqs.us-east-1.amazonaws.com/010526258440/UserRegistrationQueue';
                $topicArn = 'arn:aws:sns:us-east-1:010526258440:notificaciones_blog_personal';

                try {
                    $messageBody = json_encode([
                        'email' => $email,
                        'name' => $name,
                        'action' => 'verify_email',
                    ]);

                    // Enviar mensaje a la cola SQS
                    $result = $sqs->sendMessage([
                        'QueueUrl'    => $queueUrl,
                        'MessageBody' => $messageBody,
                    ]);

                    // Suscribir al usuario al tema SNS
                    $sns->subscribe([
                        'TopicArn' => $topicArn,
                        'Protocol' => 'email',
                        'Endpoint' => $email,
                    ]);

                    // Redirigir al login después del registro
                    header('Location: login.php?success=1');
                    exit();
                } catch (AwsException $e) {
                    $error = "Error al procesar el registro: " . $e->getAwsErrorMessage();
                }
            } else {
                $error = "Error creando usuario: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrarse</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-r from-blue-400 via-indigo-500 to-purple-600 min-h-screen flex justify-center items-center">
    <div class="w-full max-w-md bg-white shadow-lg rounded-lg p-6">
        <h2 class="text-3xl font-bold mb-4 text-center text-gray-800">Crear Cuenta</h2>
        <?php if ($error): ?>
            <p class="text-red-500 mb-4"><?= $error ?></p>
        <?php endif; ?>
        <form action="register.php" method="POST" class="space-y-4">
            <div>
                <label for="name" class="block text-gray-700 font-bold mb-2">Nombre Completo:</label>
                <input type="text" name="name" id="name" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
            </div>
            <div>
                <label for="username" class="block text-gray-700 font-bold mb-2">Nombre de Usuario:</label>
                <input type="text" name="username" id="username" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
            </div>
            <div>
                <label for="email" class="block text-gray-700 font-bold mb-2">Correo Electrónico:</label>
                <input type="email" name="email" id="email" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
            </div>
            <div>
                <label for="password" class="block text-gray-700 font-bold mb-2">Contraseña:</label>
                <input type="password" name="password" id="password" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
            </div>
            <div>
                <label for="phone_number" class="block text-gray-700 font-bold mb-2">Teléfono:</label>
                <input type="text" name="phone_number" id="phone_number" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
            </div>
            <div>
                <label for="description" class="block text-gray-700 font-bold mb-2">Descripción:</label>
                <textarea name="description" id="description" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"></textarea>
            </div>
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">Registrarse</button>
        </form>
        <p class="mt-4 text-center text-gray-700">¿Ya tienes una cuenta? <a href="login.php" class="text-indigo-600 hover:underline">Inicia sesión</a></p>
    </div>
</body>
</html>
