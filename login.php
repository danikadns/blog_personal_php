<?php
require 'db.php';
require 'session_handler.php';
require 'dynamo_activity.php';
require 'vendor/autoload.php'; // Para el cliente STS

use Aws\Sts\StsClient;

$handler = new MySQLSessionHandler();
session_set_save_handler($handler, true);
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['email']) || empty($_POST['password'])) {
        $error = 'Por favor, ingresa un correo y contraseña.';
    } else {
        $email = $_POST['email'];
        $password = $_POST['password'];

        try {
            $stmt = $conn->prepare('SELECT * FROM users WHERE email = ?');
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['name'];
                $_SESSION['role_id'] = $user['role_id'];

                // Obtener el ARN del rol desde la tabla roles
                $stmt = $conn->prepare('SELECT arn FROM roles WHERE id = ?');
                $stmt->bind_param('i', $user['role_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $role = $result->fetch_assoc();
                $roleArn = $role['arn'];

                if (!$roleArn) {
                    throw new Exception("No se encontró un ARN de rol para este usuario.");
                }

                // Asumir el rol con AWS STS
                $stsClient = new StsClient([
                    'version' => 'latest',
                    'region' => 'us-east-1',
                ]);

                try {
                    $stsResult = $stsClient->assumeRole([
                        'RoleArn' => $roleArn,
                        'RoleSessionName' => 'SessionForUser_' . $user['id'],
                    ]);

                    $credentials = $stsResult->get('Credentials');

                    if (!$credentials) {
                        throw new Exception("Error al obtener las credenciales del rol.");
                    }

                    // Almacenar credenciales en la sesión
                    $_SESSION['aws_access_key'] = $credentials['AccessKeyId'];
                    $_SESSION['aws_secret_key'] = $credentials['SecretAccessKey'];
                    $_SESSION['aws_session_token'] = $credentials['SessionToken'];
                    $_SESSION['aws_expiration'] = strtotime($credentials['Expiration']);
                    $_SESSION['role_arn'] = $roleArn;

                } catch (Exception $e) {
                    error_log("Error al asumir el rol: " . $e->getMessage());
                    $error = 'No se pudieron obtener las credenciales necesarias. Inténtalo más tarde.';
                }

                // Registro de actividad
                try {
                    logUserActivity(
                        $user['id'],
                        'login',
                        ['username' => $user['name'], 'status' => 'success']
                    );
                } catch (Exception $e) {
                    error_log("Error al registrar actividad: " . $e->getMessage());
                }

                header('Location: index.php');
                exit;
            } else {
                logUserActivity(0, 'login_attempt', ['email' => $email, 'status' => 'failed']);
                $error = 'Correo o contraseña incorrectos.';
            }
        } catch (Exception $e) {
            error_log("Error en el inicio de sesión: " . $e->getMessage());
            $error = 'Error interno del servidor. Inténtalo más tarde.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-r from-blue-500 to-indigo-600 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-sm bg-white shadow-lg rounded-lg p-8">
        <h2 class="text-3xl font-bold text-center text-gray-800 mb-6">Iniciar Sesión</h2>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="space-y-6">
            <div>
                <label for="email" class="block text-gray-700 font-medium mb-2">Correo Electrónico</label>
                <input type="email" name="email" id="email" required class="w-full border rounded-lg px-4 py-2">
            </div>
            <div>
                <label for="password" class="block text-gray-700 font-medium mb-2">Contraseña</label>
                <input type="password" name="password" id="password" required class="w-full border rounded-lg px-4 py-2">
            </div>
            <button type="submit" class="w-full bg-blue-500 text-white font-bold py-2 rounded-lg">Iniciar Sesión</button>
        </form>
        <p class="mt-6 text-center text-gray-600">
            ¿No tienes cuenta? <a href="register.php" class="text-blue-500 underline">Regístrate</a>
        </p>
    </div>
</body>
</html>
